<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SimpleIntegratorRedirectMiddleware
{
    /**
     * Middleware simple pour rediriger les intégrateurs vers les bonnes routes
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si l'utilisateur est authentifié et est un intégrateur
        if (Auth::check() && Auth::user()->hasRole('integrator')) {
            $path = $request->path();
            
            Log::info('SimpleIntegratorRedirectMiddleware: Checking path', [
                'user_id' => Auth::id(),
                'path' => $path,
                'is_integrator' => Auth::user()->hasRole('integrator'),
                'user_roles' => Auth::user()->getRoleNames()->toArray()
            ]);
            
            // Rediriger /partners vers /integrator/partners
            if (preg_match("/^partners\/?/", $path)) {
                $newPath = str_replace("partners", "integrator/partners", $path);
                
                Log::info('SimpleIntegratorRedirectMiddleware: Redirecting integrator', [
                    'user_id' => Auth::id(),
                    'from' => $path,
                    'to' => $newPath,
                    'redirect_url' => "/" . $newPath
                ]);
                
                return redirect("/" . $newPath);
            }
        }
        
        return $next($request);
    }
}