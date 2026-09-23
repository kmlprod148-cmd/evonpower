<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Final gate for the /{admin.path}/* area.
 *
 * Order of checks:
 *   1. The request must be authenticated on the `web` guard (the `client`
 *      guard is hard-blocked — clients can never reach the back-office).
 *   2. The authenticated user must hold the permission configured at
 *      config('admin.permission') — either directly granted or inherited
 *      from a role (admin / super_admin get it via the install migration).
 *
 * Failure modes:
 *   - Guest (or only the client guard is active): redirect to admin.login.
 *   - Authenticated but unauthorized: 403, with structured audit log line.
 */
class EnsureAdminPanelAccess
{
    public function handle(Request $request, Closure $next)
    {
        $permission = config('admin.permission', 'access-admin-panel');

        if (! Auth::guard('web')->check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('admin.login');
        }

        $user = Auth::guard('web')->user();

        if (! $user->can($permission)) {
            Log::warning('Admin panel access denied', [
                'user_id' => $user->getKey(),
                'email'   => $user->email ?? null,
                'path'    => $request->path(),
                'ip'      => $request->ip(),
            ]);

            abort(403, 'Accès refusé. Vous ne disposez pas du droit d\'accès au back-office.');
        }

        return $next($request);
    }
}
