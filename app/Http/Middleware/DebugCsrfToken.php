<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de debug pour tracer les problèmes CSRF
 */
class DebugCsrfToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Logger les informations CSRF avant la requête
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            $sessionToken = $request->session()->token();
            $requestToken = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
            
            Log::info('[CSRF DEBUG] Requête POST/PUT/DELETE détectée', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'session_token' => substr($sessionToken ?? 'NULL', 0, 10) . '...',
                'request_token' => substr($requestToken ?? 'NULL', 0, 10) . '...',
                'tokens_match' => $sessionToken === $requestToken,
                'session_id' => $request->session()->getId(),
                'has_session' => $request->hasSession(),
                'referer' => $request->header('referer'),
            ]);
        }

        try {
            $response = $next($request);
            
            // Logger si c'est une erreur 419
            if ($response->getStatusCode() === 419) {
                Log::error('[CSRF DEBUG] Erreur 419 détectée !', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                    'session_id' => $request->session()->getId(),
                    'referer' => $request->header('referer'),
                ]);
            }
            
            return $response;
        } catch (\Illuminate\Session\TokenMismatchException $e) {
            Log::error('[CSRF DEBUG] TokenMismatchException attrapée !', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'exception' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}

