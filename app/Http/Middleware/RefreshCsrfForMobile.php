<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour rafraîchir automatiquement le token CSRF sur mobile
 * 
 * Ce middleware résout l'erreur 419 "Page Expired" sur les appareils mobiles
 * en régénérant le token CSRF si nécessaire
 */
class RefreshCsrfForMobile
{
    /**
     * Liste des User-Agents considérés comme mobiles
     */
    private const MOBILE_PATTERNS = [
        'Android',
        'webOS',
        'iPhone',
        'iPad',
        'iPod',
        'BlackBerry',
        'IEMobile',
        'Opera Mini',
        'Mobile',
        'mobile',
    ];

    /**
     * Durée de vie du token en secondes (45 minutes)
     * Après ce délai, le token sera régénéré
     */
    private const TOKEN_LIFETIME = 2700;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // CRITICAL FIX: Ne JAMAIS régénérer le token sur les requêtes POST/PUT/DELETE
        // car cela invalide le token soumis AVANT que VerifyCsrfToken ne puisse le vérifier
        $isMutatingRequest = in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH']);
        
        // Vérifier si c'est un appareil mobile
        if ($this->isMobileDevice($request) && !$isMutatingRequest) {
            // Rafraîchir uniquement sur GET requests (safe)
            $this->refreshTokenIfNeeded($request);
        }

        $response = $next($request);

        // Ajouter des headers pour améliorer la compatibilité mobile
        if ($this->isMobileDevice($request)) {
            $response->headers->set('X-Mobile-Optimized', 'true');
            
            // Désactiver le cache pour les requêtes POST (évite les tokens expirés)
            if ($isMutatingRequest) {
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
            }
        }

        return $response;
    }

    /**
     * Vérifie si l'appareil est mobile
     */
    private function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->userAgent();
        
        if (empty($userAgent)) {
            return false;
        }

        foreach (self::MOBILE_PATTERNS as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rafraîchit le token CSRF si nécessaire
     */
    private function refreshTokenIfNeeded(Request $request): void
    {
        try {
            // Obtenir le timestamp de la dernière régénération du token
            $lastTokenRefresh = Session::get('csrf_token_refreshed_at', 0);
            $currentTime = time();

            // Vérifier si le token doit être régénéré
            if ($currentTime - $lastTokenRefresh > self::TOKEN_LIFETIME) {
                // Régénérer le token CSRF
                Session::regenerateToken();
                
                // Enregistrer le timestamp de la régénération
                Session::put('csrf_token_refreshed_at', $currentTime);
                
                // Log pour le debugging (optionnel)
                if (config('app.debug')) {
                    \Log::info('[Mobile CSRF] Token CSRF rafraîchi automatiquement', [
                        'user_agent' => $request->userAgent(),
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // En cas d'erreur, logger mais ne pas interrompre la requête
            \Log::error('[Mobile CSRF] Erreur lors du rafraîchissement du token', [
                'error' => $e->getMessage(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }
}

