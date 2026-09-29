<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('user:id,name,username,first_name,last_name,email');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        $perPage = min(max((int) $request->get('per_page', 20), 5), 100);
        $paginator = $query->orderByDesc('id')->paginate($perPage);

        $data = collect($paginator->items())->map(function (AuditLog $log) {
            $u = $log->user;
            $displayName = $u
                ? trim(implode(' ', array_filter([$u->first_name, $u->last_name])) ?: $u->name ?: '')
                : '';

            return [
                'id' => $log->id,
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'description' => $log->description,
                'metadata' => $log->metadata,
                'status' => $log->status,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at?->toIso8601String(),
                'user' => $u ? [
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
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
