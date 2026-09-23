<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ClientChargingAuthMiddleware
 *
 * Guards the client charging payment routes. If the visitor is not authenticated
 * via the 'client' guard, it preserves the charging-point ID in the session and
 * redirects them to the public registration page (rather than the admin login).
 */
class ClientChargingAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('client')->check()) {
            // Preserve the charging point ID so registration can redirect back
            if ($id = $request->route('id')) {
                session(['pending_charging_point_id' => $id]);
            }

            return redirect()->route('register')
                ->with('info', 'Veuillez créer votre compte pour accéder à l\'offre de recharge.');
        }

        // Set the default guard to 'client' for this entire request so that
        // Auth::user(), Auth::check(), and view composers all resolve the
        // ClientUser automatically — without this, pages render as "guest".
        Auth::shouldUse('client');

        return $next($request);
    }
}
