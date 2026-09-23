<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        Log::info('Authenticate Middleware: Redirecting to login', ['path' => $request->path()]);
        // Ne pas rediriger pour les requêtes API, retourner simplement une réponse 401
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // Quand l'utilisateur tente d'accéder à une URL du back-office (préfixe
        // configurable via ADMIN_PATH), on l'envoie sur le login back-office
        // (vue distincte avec son propre thème) plutôt que vers le login client.
        $adminPath = config('admin.path') ?: 'admin';
        if ($request->is($adminPath) || $request->is($adminPath.'/*')) {
            return route('admin.login');
        }

        // CRITIQUE : Ne pas stocker l'URL CMI ou vraiment externe dans url.intended
        // Mais permettre les routes internes même si le domaine diffère
        $requestedUrl = $request->fullUrl();
        
        // Vérifier les URLs CMI - toujours bloquer
        $isCmiUrl = strpos($requestedUrl, 'testpayment.cmi.co.ma') !== false || 
                    strpos($requestedUrl, 'payment.cmi.co.ma') !== false ||
                    strpos($requestedUrl, 'est3Dgate') !== false ||
                    strpos($requestedUrl, 'cmi.co.ma') !== false;
        
        if ($isCmiUrl) {
            Log::warning('Authenticate Middleware: Blocked storing CMI URL in intended', [
                'requested_url' => $requestedUrl,
                'path' => $request->path(),
            ]);
            // Ne pas stocker l'URL CMI, rediriger simplement vers login
            $currentIntended = $request->session()->get('url.intended');
            if ($currentIntended === $requestedUrl) {
                $request->session()->forget('url.intended');
            }
            return route('login');
        }
        
        // Pour les autres URLs, vérifier si c'est vraiment externe ou interne
        $appUrl = config('app.url');
        if ($appUrl && !empty($requestedUrl)) {
            $requestedUrlParsed = parse_url($requestedUrl);
            $appUrlParsed = parse_url($appUrl);
            
            if (isset($requestedUrlParsed['host']) && isset($appUrlParsed['host'])) {
                $requestedHost = preg_replace('/^www\./', '', strtolower($requestedUrlParsed['host']));
                $appHost = preg_replace('/^www\./', '', strtolower($appUrlParsed['host']));
                
                if ($requestedHost !== $appHost) {
                    // Vérifier si c'est une URL interne autorisée
                    $path = $requestedUrlParsed['path'] ?? '';
                    $allowedInternalRoutes = [
                        '/credits',
                        '/credit-recharge',
                        '/dashboard',
                        '/profile',
                        '/balances',
                        '/charging-points',
                        '/transactions',
                        '/admin',
                        '/integrator',
                        '/operator',
                        '/partner'
                    ];
                    
                    $isInternalRoute = false;
                    foreach ($allowedInternalRoutes as $route) {
                        if (strpos($path, $route) === 0) {
                            $isInternalRoute = true;
                            Log::info('Authenticate Middleware: Allowing internal route', [
                                'requested_url' => $requestedUrl,
                                'path' => $path,
                                'route' => $route,
                            ]);
                            break;
                        }
                    }
                    
                    if (!$isInternalRoute) {
                        Log::warning('Authenticate Middleware: Blocked storing external URL in intended', [
                            'requested_url' => $requestedUrl,
                            'path' => $request->path(),
                            'is_external' => true,
                        ]);
                        // Ne pas stocker l'URL externe, rediriger simplement vers login
                        $currentIntended = $request->session()->get('url.intended');
                        if ($currentIntended === $requestedUrl) {
                            $request->session()->forget('url.intended');
                        }
                        return route('login');
                    }
                }
            }
        }
        
        return route('login');
    }
}