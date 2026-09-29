<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockedIpController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSecurity(), 403);

        $perPage = min(max((int) $request->input('per_page', 20), 5), 100);
        $query = BlockedIp::query()
            ->with('blockedBy:id,name,username,first_name,last_name,email')
            ->orderByDesc('id');

        if ($request->filled('active')) {
            $query->where('is_active', filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $query->paginate($perPage);

        $data = collect($paginator->items())->map(function (BlockedIp $entry): array {
            $u = $entry->blockedBy;
            $displayName = $u
                ? trim(implode(' ', array_filter([$u->first_name, $u->last_name])) ?: $u->name ?: '')
                : '';

            return [
                'id' => $entry->id,
                'ip_address' => $entry->ip_address,
                'reason' => $entry->reason,
                'is_active' => (bool) $entry->is_active,
                'hit_count' => (int) $entry->hit_count,
                'expires_at' => $entry->expires_at?->toIso8601String(),
                'last_hit_at' => $entry->last_hit_at?->toIso8601String(),
                'created_at' => $entry->created_at?->toIso8601String(),
                'blocked_by' => $u ? [
                    'id' => $u->id,
                    'email' => $u->email,
                    'username' => $u->username,
                    'name' => $displayName !== '' ? $displayName : ($u->name ?? $u->email),
                ] : null,
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->canManageSecurity(), 403);

        $validated = $request->validate([
            'ip_address' => ['required', 'ip'],
            'reason' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $entry = BlockedIp::query()->updateOrCreate(
            ['ip_address' => $validated['ip_address']],
            [
                'reason' => $validated['reason'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
                'is_active' => true,
                'blocked_by_user_id' => $user->id,
            ]
        );

        AuditLogService::record(
            $request,
            'security.ip_blocked',
            $user->id,
            BlockedIp::class,
            $entry->id,
            'IP address blocked by admin',
            [
                'ip_address' => $entry->ip_address,
                'reason' => $entry->reason,
                'expires_at' => $entry->expires_at?->toIso8601String(),
            ],
            'success'
        );

        return response()->json([
            'message' => 'IP address has been blocked.',
            'data' => [
                'id' => $entry->id,
                'ip_address' => $entry->ip_address,
                'reason' => $entry->reason,
                'is_active' => (bool) $entry->is_active,
                'expires_at' => $entry->expires_at?->toIso8601String(),
                'hit_count' => (int) $entry->hit_count,
                'last_hit_at' => $entry->last_hit_at?->toIso8601String(),
                'created_at' => $entry->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Request $request, BlockedIp $blockedIp): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->canManageSecurity(), 403);

        $blockedIp->forceFill([
            'is_active' => false,
        ])->save();

        AuditLogService::record(
            $request,
            'security.ip_unblocked',
            $user->id,
            BlockedIp::class,
            $blockedIp->id,
            'IP address unblocked by admin',
            [
                'ip_address' => $blockedIp->ip_address,
            ],
            'success'
        );

        return response()->json([
            'message' => 'IP address has been unblocked.',
        ]);
    }
}
