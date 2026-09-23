<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ReservationAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder aux réservations.');
        }

        $user = Auth::user();

        // Admin and super-admin have full access
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $next($request);
        }

        // Check if user has any reservation permission
        if (!$user->canAny(['view_reservations', 'manage_reservations', 'create_reservations'])) {
            abort(403, 'Vous n\'avez pas les permissions nécessaires pour accéder aux réservations.');
        }

        return $next($request);
    }
}
