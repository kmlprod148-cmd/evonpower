<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperatorAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur a le rôle 'operator'
        if (!$user->hasRole('operator')) {
            // Log pour débogage
            \Log::info('Accès refusé au profil opérateur', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'user_type' => $user->user_type ?? 'Non défini',
                'route' => $request->route()->getName()
            ]);

            abort(403, 'Accès non autorisé. Rôle opérateur requis.');
        }

        return $next($request);
    }
}
