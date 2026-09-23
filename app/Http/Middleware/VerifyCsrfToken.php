<?php
namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;
use Closure;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Ajoutez ici les routes API qui ne nécessitent pas de CSRF
        'api/*',
        'webhook/*',
        'webhooks/*',
        // Callbacks CMI pour les recharges de crédit (public - appelés par CMI)
        'credit-recharge/cmi/*/callback',
        'credit-recharge/cmi/*/return',
        // Callbacks CMI pour les réservations (public - appelés par CMI)
        'payment/cmi/reservation/*/callback',
        'payment/cmi/reservation/*/return',
        'payment/cmi/reservation/*/success',
        'payment/cmi/reservation/*/failure',
    ];

    /**
     * Handle an incoming request.
     * 
     * CRITICAL FIX pour mobile et AJAX: Meilleure gestion des erreurs CSRF
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // Log l'erreur pour debugging
            \Log::warning('[CSRF] Token mismatch détecté', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'session_id' => $request->session()->getId(),
                'is_mobile' => $this->isMobileDevice($request),
                'referer' => $request->header('referer'),
                'is_ajax' => $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->expectsJson(),
            ]);

            // Si c'est une requête AJAX ou qui attend du JSON, retourner une réponse JSON
            if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'message' => 'Session expirée. Veuillez rafraîchir la page.',
                    'error' => 'csrf_token_mismatch',
                    'mobile_device' => $this->isMobileDevice($request),
                ], 419);
            }

            throw $e;
        }
    }

    /**
     * Détermine si la requête provient d'un appareil mobile
     */
    protected function isMobileDevice($request): bool
    {
        $userAgent = $request->userAgent();
        
        if (empty($userAgent)) {
            return false;
        }

        $mobilePatterns = ['Android', 'webOS', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'IEMobile', 'Opera Mini', 'Mobile'];
        
        foreach ($mobilePatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}