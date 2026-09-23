<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class IntegratorRouteRedirectMiddleware
{
    /**
     * Middleware pour rediriger automatiquement les intégrateurs vers les bonnes routes
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié et est un intégrateur
        if (Auth::check() && Auth::user()->hasRole('integrator')) {
            $currentPath = $request->path();
            
            // Rediriger les routes générales vers les routes intégrateur
            $redirects = [
                'partners' => 'integrator.partners.index',
                'partners/create' => 'integrator.partners.create',
                'partners/{id}' => 'integrator.partners.show',
                'partners/{id}/edit' => 'integrator.partners.edit',
                'users' => 'integrator.operators.index',
                'users/create' => 'integrator.operators.create',
                'users/{id}' => 'integrator.operators.show',
                'users/{id}/edit' => 'integrator.operators.edit',
            ];
            
            // Vérifier si la route actuelle correspond à un pattern de redirection
            foreach ($redirects as $pattern => $redirectRoute) {
                if ($this->matchesPattern($currentPath, $pattern)) {
                    $redirectUrl = $this->buildRedirectUrl($request, $pattern, $redirectRoute);
                    
                    Log::info('IntegratorRouteRedirectMiddleware: Redirecting integrator', [
                        'user_id' => Auth::id(),
                        'from' => $currentPath,
                        'to' => $redirectUrl
                    ]);
                    
                    return redirect($redirectUrl);
                }
            }
        }

        return $next($request);
    }
    
    /**
     * Vérifier si le chemin correspond au pattern
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Convertir le pattern en regex
        $regex = str_replace(['{id}', '/'], ['(\d+)', '\/'], $pattern);
        $regex = '/^' . $regex . '$/';
        
        return preg_match($regex, $path);
    }
    
    /**
     * Construire l'URL de redirection
     */
    private function buildRedirectUrl(Request $request, string $pattern, string $redirectRoute): string
    {
        $path = $request->path();
        
        // Extraire l'ID si présent
        if (strpos($pattern, '{id}') !== false) {
            preg_match('/\/(\d+)(?:\/|$)/', $path, $matches);
            $id = $matches[1] ?? null;
            
            if ($id) {
                return route($redirectRoute, $id);
            }
        }
        
        return route($redirectRoute);
    }
}
