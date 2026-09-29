<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Persists audit entries. Failures are swallowed and logged so the main request never breaks.
 */
class AuditLogService
{
    public static function record(
        Request $request,
        string $action,
        ?int $userId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $description = null,
        array $metadata = [],
        string $status = 'success',
    ): void {
        try {
            $uid = $userId ?? $request->user()?->id;

            $safeMeta = self::sanitizeMetadata($metadata);

            AuditLog::create([
                'user_id' => $uid,
                'action' => Str::limit($action, 80, ''),
                'subject_type' => $subjectType ? Str::limit($subjectType, 160, '') : null,
                'subject_id' => $subjectId,
                'description' => $description ? Str::limit($description, 512, '') : null,
                'metadata' => $safeMeta !== [] ? $safeMeta : null,
                'status' => in_array($status, ['success', 'failure'], true) ? $status : 'success',
                'ip_address' => $request->ip(),
                'user_agent' => self::truncateUserAgent($request->userAgent()),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private static function sanitizeMetadata(array $metadata): array
    {
        $blocked = ['password', 'password_confirmation', 'token', 'secret', 'captcha_code', 'two_factor_secret'];

        $out = [];
        foreach ($metadata as $key => $value) {
            $k = is_string($key) ? Str::lower($key) : (string) $key;
            foreach ($blocked as $b) {
                if (str_contains($k, $b)) {
                    continue 2;
                }
            }
            if (is_array($value)) {
                $nested = self::sanitizeMetadata($value);
                if ($nested !== []) {
                    $out[$key] = $nested;
                }

                continue;
            }
            if (is_string($value)) {
                $out[$key] = Str::limit($value, 2000, '…');

                continue;
            }
            if (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private static function truncateUserAgent(?string $ua): ?string
    {
        if ($ua === null || $ua === '') {
            return null;
        }

        return mb_substr($ua, 0, 2000);
    }
}
