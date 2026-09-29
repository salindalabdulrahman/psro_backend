<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSecurityController extends Controller
{
    private const SETTING_KEY = 'admin_view_security';

    /**
     * @return array<string, mixed>
     */
    private function defaultPolicy(): array
    {
        return [
            'auto_lock_enabled' => true,
            'timeout_minutes' => 5,
            'max_attempts' => 3,
            'block_duration_minutes' => 5,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, mixed>
     */
    private function mergePolicy(?array $stored): array
    {
        $base = $this->defaultPolicy();
        if (! is_array($stored)) {
            return $base;
        }

        return array_merge($base, array_intersect_key($stored, array_flip([
            'auto_lock_enabled',
            'timeout_minutes',
            'max_attempts',
            'block_duration_minutes',
            'updated_at',
            'updated_by_user_id',
        ])));
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canManageSecurity()) {
            abort(403);
        }

        $raw = SiteSetting::getValue(self::SETTING_KEY, null);
        $policy = $this->mergePolicy(is_array($raw) ? $raw : null);

        $updatedBy = null;
        $uid = $policy['updated_by_user_id'] ?? null;
        if ($uid) {
            $u = User::query()->find((int) $uid);
            if ($u) {
                $display = trim(implode(' ', array_filter([$u->first_name, $u->last_name])));
                if ($display === '') {
                    $display = $u->name ?? $u->username ?? $u->email ?? 'User #'.$u->id;
                }
                $updatedBy = [
                    'id' => $u->id,
                    'name' => $display,
                ];
            }
        }

        return response()->json([
            'auto_lock_enabled' => (bool) $policy['auto_lock_enabled'],
            'timeout_minutes' => (int) $policy['timeout_minutes'],
            'max_attempts' => (int) $policy['max_attempts'],
            'block_duration_minutes' => (int) $policy['block_duration_minutes'],
            'updated_at' => $policy['updated_at'] ?? null,
            'updated_by' => $updatedBy,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canManageSecurity()) {
            abort(403);
        }

        $validated = $request->validate([
            'auto_lock_enabled' => ['required', 'boolean'],
            'timeout_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'block_duration_minutes' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $raw = SiteSetting::getValue(self::SETTING_KEY, null);
        $before = $this->mergePolicy(is_array($raw) ? $raw : null);

        $after = [
            'auto_lock_enabled' => $validated['auto_lock_enabled'],
            'timeout_minutes' => (int) $validated['timeout_minutes'],
            'max_attempts' => (int) $validated['max_attempts'],
            'block_duration_minutes' => (int) $validated['block_duration_minutes'],
            'updated_at' => now()->toIso8601String(),
            'updated_by_user_id' => $user->id,
        ];

        SiteSetting::updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'value' => json_encode($after),
                'type' => 'json',
                'group' => 'security',
                'description' => 'Admin View security policy (auto-lock, attempts, block duration)',
            ]
        );

        $beforeLock = (bool) $before['auto_lock_enabled'];
        $afterLock = (bool) $after['auto_lock_enabled'];
        if ($beforeLock !== $afterLock) {
            $auditAction = $afterLock
                ? 'admin_view_security.auto_lock_enabled'
                : 'admin_view_security.auto_lock_disabled';
            $auditDescription = $afterLock
                ? 'Auto-lock enabled'
                : 'Auto-lock disabled';

            AuditLogService::record(
                $request,
                $auditAction,
                $user->id,
                'SiteSetting',
                null,
                $auditDescription,
                [
                    'before' => [
                        'auto_lock_enabled' => (bool) $before['auto_lock_enabled'],
                        'timeout_minutes' => (int) $before['timeout_minutes'],
                        'max_attempts' => (int) $before['max_attempts'],
                        'block_duration_minutes' => (int) $before['block_duration_minutes'],
                    ],
                    'after' => [
                        'auto_lock_enabled' => $after['auto_lock_enabled'],
                        'timeout_minutes' => $after['timeout_minutes'],
                        'max_attempts' => $after['max_attempts'],
                        'block_duration_minutes' => $after['block_duration_minutes'],
                    ],
                ],
                'success'
            );
        }

        $policy = $this->mergePolicy($after);

        $updatedBy = [
            'id' => $user->id,
            'name' => trim(implode(' ', array_filter([$user->first_name, $user->last_name])))
                ?: ($user->name ?? $user->username ?? $user->email ?? 'User #'.$user->id),
        ];

        return response()->json([
            'auto_lock_enabled' => (bool) $policy['auto_lock_enabled'],
            'timeout_minutes' => (int) $policy['timeout_minutes'],
            'max_attempts' => (int) $policy['max_attempts'],
            'block_duration_minutes' => (int) $policy['block_duration_minutes'],
            'updated_at' => $policy['updated_at'] ?? null,
            'updated_by' => $updatedBy,
            'message' => 'Security policy saved.',
        ]);
    }

}
