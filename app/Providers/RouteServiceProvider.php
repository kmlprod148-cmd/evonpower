<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {

        // RateLimiter::for('api', function (Request $request) {
        //     return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        // });

        $this->routes(function () {
            // Load web.php routes explicitly to ensure they are registered
            // before Blade views are compiled. This fixes intermittent
            // RouteNotFoundException issues when using withRouting().
            Route::middleware('web')
                ->group(dirname(__DIR__, 2).'/routes/web.php');

            Route::middleware('web')
                ->group(dirname(__DIR__, 2).'/routes/cmi.php');

            Route::middleware('web')
                ->group(dirname(__DIR__, 2).'/routes/admin.php');

            // Language routes
            // Note: kept in a dedicated file (`routes/language.php`) to avoid bloating `routes/web.php`.
            Route::middleware('web')
                ->group(dirname(__DIR__, 2).'/routes/language.php');
        });
    }
}
