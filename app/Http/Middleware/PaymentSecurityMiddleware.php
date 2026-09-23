<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PaymentSecurityMiddleware
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
        // Rate limiting pour les paiements
        $key = 'payment_attempts:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 10)) {
            Log::warning('Payment rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->url()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives de paiement. Veuillez réessayer plus tard.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        // Vérification de l'origine de la requête
        if (!$this->isValidOrigin($request)) {
            Log::warning('Invalid payment request origin', [
                'ip' => $request->ip(),
                'origin' => $request->header('Origin'),
                'referer' => $request->header('Referer'),
                'url' => $request->url()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Origine de la requête non autorisée.'
            ], 403);
        }

        // Vérification des headers de sécurité
        if (!$this->hasValidHeaders($request)) {
            Log::warning('Invalid payment request headers', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Headers de sécurité manquants.'
            ], 400);
        }

        // Enregistrer la tentative
        RateLimiter::hit($key, 300); // 5 minutes

        return $next($request);
    }

    /**
     * Vérifier si l'origine de la requête est valide
     */
    private function isValidOrigin(Request $request): bool
    {
        $allowedOrigins = [
            config('app.url'),
            'https://checkout.stripe.com',
            'https://testpayment.cmi.co.ma',
            'https://payment.cmi.co.ma'
        ];

        $origin = $request->header('Origin');
        $referer = $request->header('Referer');

        // Vérifier l'origine
        if ($origin && !in_array($origin, $allowedOrigins)) {
            return false;
        }

        // Vérifier le referer pour les requêtes non-AJAX
        if (!$request->ajax() && $referer && !str_contains($referer, config('app.url'))) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier les headers de sécurité requis
     */
    private function hasValidHeaders(Request $request): bool
    {
        // Vérifier la présence du token CSRF
        if (!$request->hasHeader('X-CSRF-TOKEN') && !$request->hasHeader('X-XSRF-TOKEN')) {
            return false;
        }

        // Vérifier le content-type pour les requêtes POST
        if ($request->isMethod('POST') && !$request->isJson()) {
            $contentType = $request->header('Content-Type');
            if (!$contentType || !str_contains($contentType, 'application/json')) {
                return false;
            }
        }

        return true;
    }
}
