<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // CRITICAL pour les requêtes depuis mobile (souvent derrière proxies/load balancers)
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        "web" => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // DEBUG : Logger les infos CSRF (désactiver après debug)
            \App\Http\Middleware\DebugCsrfToken::class,
            // CRITICAL FIX : Rafraîchir le token CSRF sur mobile AVANT la vérification
            \App\Http\Middleware\RefreshCsrfForMobile::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\DetectClient::class,
            \App\Http\Middleware\SetLocaleMiddleware::class, // Fixed professional middleware
        ],

        "api" => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        "auth" => \App\Http\Middleware\Authenticate::class,
        "auth.basic" => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        "auth.session" => \Illuminate\Session\Middleware\AuthenticateSession::class,
        "cache.headers" => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        "can" => \Illuminate\Auth\Middleware\Authorize::class,
        "guest" => \App\Http\Middleware\RedirectIfAuthenticated::class,
        "password.confirm" => \Illuminate\Auth\Middleware\RequirePassword::class,
        "signed" => \App\Http\Middleware\ValidateSignature::class,
        "throttle" => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        "verified" => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        // Alias personnalisés - doivent être définis ici ET dans bootstrap/app.php pour Laravel 11 avec withKernels()
        "unified.permission" => \App\Http\Middleware\UnifiedPermissionMiddleware::class,
        "integrator.isolation" => \App\Http\Middleware\IntegratorDataIsolationMiddleware::class,
        "integrator.permission" => \App\Http\Middleware\IntegratorPermissionMiddleware::class,
        "integrator.isolation.test" => \App\Http\Middleware\IntegratorDataIsolationTestMiddleware::class,
        "integrator.redirect" => \App\Http\Middleware\IntegratorRouteRedirectMiddleware::class,
        "simple.integrator.redirect" => \App\Http\Middleware\SimpleIntegratorRedirectMiddleware::class,
        "disable.compression" => \App\Http\Middleware\DisableOutputCompression::class,
        "policy.exceptions" => \App\Http\Middleware\HandlePolicyExceptions::class,
        "hierarchical.validation" => \App\Http\Middleware\ValidateHierarchicalConsistency::class,
        "client.charge.auth"      => \App\Http\Middleware\ClientChargingAuthMiddleware::class,
        "admin.panel"             => \App\Http\Middleware\EnsureAdminPanelAccess::class,
    ];

}