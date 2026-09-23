<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\IntegratorPermissionService;

class IntegratorDataIsolationTestMiddleware
{
    protected IntegratorPermissionService $permissionService;

    public function __construct(IntegratorPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request to ensure integrators only see their own data
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si l'utilisateur n'est pas un intégrateur, laisser passer
        if (!Auth::check() || !Auth::user()->hasRole('integrator')) {
            return $next($request);
        }

        $user = Auth::user();
        $integrator = $this->permissionService->getCurrentIntegrator();

        if (!$integrator) {
            Log::warning('IntegratorDataIsolationTestMiddleware: User has integrator role but no integrator record', [
                'user_id' => $user->id,
                'route' => $request->route()->getName()
            ]);
            return redirect()->route('dashboard')->with('error', 'Configuration intégrateur manquante.');
        }

        // Vérifier l'accès aux ressources spécifiques
        $routeName = $request->route()->getName();
        
        // Routes d'opérateurs
        if (str_contains($routeName, 'operators')) {
            $this->validateOperatorAccess($request, $integrator);
        }
        
        // Routes de partenaires
        if (str_contains($routeName, 'partners')) {
            $this->validatePartnerAccess($request, $integrator);
        }

        return $next($request);
    }

    /**
     * Valider l'accès aux opérateurs
     */
    protected function validateOperatorAccess(Request $request, $integrator): void
    {
        $operatorId = $request->route('operator');
        
        if ($operatorId) {
            $operator = \App\Models\User::find($operatorId);
            
            if ($operator && !$this->permissionService->canAccessOperator($operator)) {
                $this->permissionService->logUnauthorizedAccess('access_operator', $operator);
                abort(403, 'Accès refusé. Vous ne pouvez accéder qu\'à vos propres opérateurs.');
            }
        }
    }

    /**
     * Valider l'accès aux partenaires
     */
    protected function validatePartnerAccess(Request $request, $integrator): void
    {
        $partnerId = $request->route('partner');
        
        if ($partnerId) {
            $partner = \App\Models\Partner::find($partnerId);
            
            if ($partner && !$this->permissionService->canAccessPartner($partner)) {
                $this->permissionService->logUnauthorizedAccess('access_partner', $partner);
                abort(403, 'Accès refusé. Vous ne pouvez accéder qu\'à vos propres partenaires.');
            }
        }
    }
}
