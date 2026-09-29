<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIpNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        if (! $ip) {
            return $next($request);
        }

        $blocked = BlockedIp::query()
            ->active()
            ->where('ip_address', $ip)
            ->first();

        if (! $blocked) {
            return $next($request);
        }

        $blocked->forceFill([
            'hit_count' => (int) $blocked->hit_count + 1,
            'last_hit_at' => now(),
        ])->save();

        AuditLogService::record(
            $request,
            'security.ip_blocked_request',
            null,
            BlockedIp::class,
            $blocked->id,
            'Blocked request from denylisted IP address',
            [
                'ip_address' => $ip,
                'blocked_ip_id' => $blocked->id,
                'path' => $request->path(),
                'method' => $request->method(),
            ],
            'failure'
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Access denied from this IP address.',
            ], 403);
        }

        abort(403, 'Access denied from this IP address.');
    }
}
