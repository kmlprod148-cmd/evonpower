<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RemovePublicPrefixMiddleware
{
    /**
     * Liste des routes publiques légitimes qui doivent commencer par /public/
     * Ces routes ne seront pas redirigées
     */
    protected $publicRoutes = [
        'public/charging-points',
        'public/charging-point',
        'public/notifications',
        'public/api',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        
        // Ignorer les requêtes AJAX et API pour éviter les problèmes
        if ($request->expectsJson() || $request->is('api/*')) {
            return $next($request);
        }
        
        // Collapse duplicate leading public prefixes only
        if (strpos($path, 'public/') === 0) {
            $cleanPath = preg_replace('#^public/+#', 'public/', $path);

            if ($cleanPath !== $path) {
                Log::info('RemovePublicPrefixMiddleware: Redirecting duplicate public prefix', [
                    'original_path' => $path,
                    'clean_path' => $cleanPath,
                    'url' => $request->url(),
                ]);

                // Préserver les paramètres de requête
                $queryString = $request->getQueryString();
                $redirectUrl = '/' . $cleanPath . ($queryString ? '?' . $queryString : '');

                // Vérifier qu'on ne crée pas une boucle de redirection
                if ($redirectUrl !== $request->getRequestUri()) {
                    return redirect($redirectUrl, 301); // 301 Permanent Redirect
                }
            }
        }

        return $next($request);
    }
}

