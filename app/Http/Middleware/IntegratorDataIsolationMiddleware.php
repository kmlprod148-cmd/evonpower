<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class IntegratorDataIsolationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Block partners and operators from accessing integrator routes
        if ($user) {
            $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
            
            if ($isPartnerOrOperator) {
                // Partners and operators cannot access integrator routes
                Log::warning('Partner/Operator tried to access integrator route', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_roles' => $userRolesLower,
                    'route' => $request->route()->getName()
                ]);
                
                return redirect()->route('dashboard')
                    ->with('error', 'Vous n\'avez pas accès à cette section.');
            }
        }
        
        // Only allow integrators
        if (!$user || !$user->hasRole('integrator')) {
            return $next($request);
        }

        // Verify integrator has a valid integrator_id
        if (!$user->integrator_id) {
            Log::warning('Integrator user without integrator_id', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'route' => $request->route()->getName()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Votre compte intégrateur n\'est pas correctement configuré.');
        }

        // Add integrator_id to request parameters for filtering
        $request->merge(['current_integrator_id' => $user->integrator_id]);

        return $next($request);
    }
}
