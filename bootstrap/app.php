<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware pour nettoyer les URLs avec préfixe /public/ incorrect
        $middleware->web(prepend: [
            \App\Http\Middleware\RemovePublicPrefixMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\DetectClient::class,
            \App\Http\Middleware\SetCurrencyMiddleware::class,
        ]);

        // P6.3: every API request gets a correlation id (X-Request-Id) injected
        // into log context and echoed back on the response. Prepended so the id
        // is in scope for every later middleware's log lines too.
        $middleware->api(prepend: [
            \App\Http\Middleware\InjectRequestId::class,
        ]);
        
        // Configuration robuste pour TrustProxies
        // En production, utiliser une configuration plus simple
        $middleware->trustProxies(at: '*');
        
        // Enregistrer les alias de middleware
        $middleware->alias([
            'unified.permission' => \App\Http\Middleware\UnifiedPermissionMiddleware::class,
            'integrator.isolation' => \App\Http\Middleware\IntegratorDataIsolationMiddleware::class,
            'integrator.permission' => \App\Http\Middleware\IntegratorPermissionMiddleware::class,
            'integrator.isolation.test' => \App\Http\Middleware\IntegratorDataIsolationTestMiddleware::class,
            'integrator.redirect' => \App\Http\Middleware\IntegratorRouteRedirectMiddleware::class,
            'simple.integrator.redirect' => \App\Http\Middleware\SimpleIntegratorRedirectMiddleware::class,
            'disable.compression' => \App\Http\Middleware\DisableOutputCompression::class,
            'policy.exceptions' => \App\Http\Middleware\HandlePolicyExceptions::class,
            'hierarchical.validation' => \App\Http\Middleware\ValidateHierarchicalConsistency::class,
            'client.charge.auth'      => \App\Http\Middleware\ClientChargingAuthMiddleware::class,
            // Back-office final gate: web-guard auth + access-admin-panel permission.
            'admin.panel'             => \App\Http\Middleware\EnsureAdminPanelAccess::class,
            // Spatie Permission middleware
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    // Enregistrer le binding pour Illuminate\Contracts\Http\Kernel vers App\Http\Kernel
    // Nécessaire pour la compatibilité avec Sanctum et d'autres packages
    // Ce binding doit être fait tôt dans le cycle de vie de l'application
    ->booting(function ($app) {
        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );
    })
    ->create();
