<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BackupImportService;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BackupController extends Controller
{
    /**
     * Get last backup date
     */
    public function show(): JsonResponse
    {
        $raw = SiteSetting::getValue('last_backup_at', null);
        $lastBackupAt = $raw ? Carbon::parse($raw) : null;

        return response()->json([
            'last_backup_at' => $lastBackupAt?->toIso8601String(),
        ]);
    }

    /**
     * Export all application database tables as JSON and update last backup date.
     *
     * Includes every row from each table (raw columns). Does not include Laravel
     * system tables (cache, jobs, sessions, personal_access_tokens, etc.).
     */
    public function export(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $tables = [];
        foreach (BackupImportService::TABLES as $table) {
            $tables[$table] = $this->exportTableRows($table);
        }

        $export = [
            'exported_at' => now()->toIso8601String(),
            'version' => '2.0',
            'database' => [
                'driver' => config('database.default'),
                'tables' => $tables,
            ],
            'meta' => [
                'tables_included' => BackupImportService::TABLES,
                'tables_excluded' => [
                    'migrations',
                    'cache',
                    'cache_locks',
                    'jobs',
                    'job_batches',
                    'failed_jobs',
                    'sessions',
                    'password_reset_tokens',
                    'personal_access_tokens',
                ],
                'note' => 'Full app data snapshot. Uploaded files under storage/app/public are not included.',
            ],
        ];

        $filename = 'psro-backup-' . now()->format('Y-m-d-His') . '.json';

        SiteSetting::setValue('last_backup_at', now()->toIso8601String(), 'text');

        AuditLogService::record(
            $request,
            'backup.exported',
            $request->user()?->id,
            null,
            null,
            'Content backup downloaded',
            ['filename' => $filename],
            'success'
        );

        return response(
            json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportTableRows(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table);
        if (Schema::hasColumn($table, 'id')) {
            $query->orderBy('id');
        }

        return $query->get()
            ->map(fn ($row) => json_decode(json_encode($row), true))
            ->values()
            ->all();
    }

    /**
     * Replace all application data from a v2.0 JSON backup file.
     * Clears Sanctum tokens and sessions — clients must sign in again.
     */
    public function import(Request $request, BackupImportService $importService): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $request->validate([
            'file' => ['required', 'file', 'max:102400'],
            'confirmation' => ['required', 'string', 'in:RESTORE'],
        ]);

        $upload = $request->file('file');
        $ext = strtolower((string) $upload->getClientOriginalExtension());
        if (! in_array($ext, ['json', 'txt'], true)) {
            throw ValidationException::withMessages([
                'file' => ['Upload a JSON backup file (e.g. psro-backup-*.json).'],
            ]);
        }

        $path = $upload->getRealPath();
        if ($path === false) {
            throw ValidationException::withMessages(['file' => ['Could not read the uploaded file.']]);
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw ValidationException::withMessages(['file' => ['Could not read the uploaded file.']]);
        }

        $payload = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages(['file' => ['Invalid JSON file.']]);
        }
        if (! is_array($payload)) {
            throw ValidationException::withMessages(['file' => ['Invalid JSON file.']]);
        }

        $importerEmail = $request->user()?->email;

        try {
            $result = $importService->importFromPayload($payload);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'file' => ['Import failed: '.$e->getMessage()],
            ]);
        }

        $importerId = $importerEmail
            ? User::query()->where('email', $importerEmail)->value('id')
            : null;

        AuditLogService::record(
            $request,
            'backup.imported',
            $importerId,
            null,
            null,
            'Database restored from backup file',
            ['counts' => $result['counts']],
            'success'
        );

        return response()->json([
            'message' => 'Backup imported successfully. Please sign in again; your previous session is no longer valid.',
            'counts' => $result['counts'],
        ]);
    }
}
