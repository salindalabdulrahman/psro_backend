<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BackupImportService
{
    /**
     * Tables cleared and repopulated from a v2.0 JSON backup (same set as export).
     *
     * @var list<string>
     */
    public const TABLES = [
        'users',
        'posts',
        'post_categories',
        'site_settings',
        'slider_settings',
        'activities',
        'headlines',
        'mandates',
        'header_settings',
        'footer_links',
        'contact_submissions',
        'audit_logs',
    ];

    /**
     * Insert order respects foreign keys (posts.author_id → users.id; audit_logs.user_id → users.id).
     *
     * @var list<string>
     */
    private const INSERT_ORDER = [
        'users',
        'post_categories',
        'site_settings',
        'slider_settings',
        'activities',
        'headlines',
        'mandates',
        'header_settings',
        'footer_links',
        'posts',
        'contact_submissions',
        'audit_logs',
    ];

    /**
     * Session / token tables cleared so users must sign in again after restore.
     *
     * @var list<string>
     */
    private const SESSION_TOKEN_TABLES = [
        'personal_access_tokens',
        'sessions',
        'password_reset_tokens',
    ];

    /**
     * @param  array<string, mixed>  $payload  Decoded JSON root object
     * @return array{counts: array<string, int>}
     */
    public function importFromPayload(array $payload): array
    {
        $tables = $this->extractTables($payload);
        $this->validateTablesForImport($tables);

        $counts = [];

        DB::transaction(function () use ($tables, &$counts) {
            // Use DELETE (not TRUNCATE) so the whole restore stays inside one DB transaction on MySQL.
            Schema::disableForeignKeyConstraints();

            try {
                foreach (self::TABLES as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                foreach (self::SESSION_TOKEN_TABLES as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }

            foreach (self::INSERT_ORDER as $table) {
                $rows = $tables[$table] ?? [];
                if (! is_array($rows) || $rows === []) {
                    $counts[$table] = 0;

                    continue;
                }
                if (! Schema::hasTable($table)) {
                    $counts[$table] = 0;

                    continue;
                }
                $counts[$table] = $this->insertRows($table, $rows);
            }
        });

        return ['counts' => $counts];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function extractTables(array $payload): array
    {
        if (isset($payload['database']['tables']) && is_array($payload['database']['tables'])) {
            $version = $payload['version'] ?? null;
            if ($version !== null && (string) $version !== '2.0') {
                throw ValidationException::withMessages([
                    'file' => ['Only backup format version 2.0 can be imported. Please export a new backup from this application.'],
                ]);
            }

            return $this->normalizeTablesArray($payload['database']['tables']);
        }

        throw ValidationException::withMessages([
            'file' => ['Unrecognized backup file. Expected version 2.0 with database.tables.'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $tables
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function normalizeTablesArray(array $tables): array
    {
        $out = [];
        foreach (self::TABLES as $name) {
            $raw = $tables[$name] ?? [];
            if (! is_array($raw)) {
                $out[$name] = [];

                continue;
            }
            $out[$name] = array_values(array_filter($raw, fn ($row) => is_array($row)));
        }

        return $out;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $tables
     */
    private function validateTablesForImport(array $tables): void
    {
        $users = $tables['users'] ?? [];
        if ($users === []) {
            throw ValidationException::withMessages([
                'file' => ['Backup must include at least one user row.'],
            ]);
        }

        $hasAdmin = false;
        foreach ($users as $row) {
            if (($row['role'] ?? null) === 'admin') {
                $hasAdmin = true;
                break;
            }
        }

        if (! $hasAdmin) {
            throw ValidationException::withMessages([
                'file' => ['Backup must include at least one user with role "admin".'],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function insertRows(string $table, array $rows): int
    {
        $columns = Schema::getColumnListing($table);
        $columnSet = array_flip($columns);
        $n = 0;

        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                if (! is_string($key) || ! isset($columnSet[$key])) {
                    continue;
                }
                $data[$key] = $this->castColumnValue($table, $key, $value);
            }

            if ($data === []) {
                continue;
            }

            DB::table($table)->insert($data);
            $n++;
        }

        return $n;
    }

    private function castColumnValue(string $table, string $column, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        try {
            $type = Schema::getColumnType($table, $column);
        } catch (\Throwable) {
            return $value;
        }

        if (in_array($type, ['json', 'jsonb'], true)) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }
            if (is_string($value) && $value !== '') {
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $value;
                }

                return json_encode($value);
            }

            return json_encode($value);
        }

        if ($type === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ? 1 : 0;
        }

        return $value;
    }
}
