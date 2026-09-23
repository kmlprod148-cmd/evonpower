<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleMiddleware
{
    /**
     * Get supported locales from config
     */
    protected function getSupportedLocales()
    {
        return config('app.available_locales', ['fr', 'en', 'ar', 'es']);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // SIMPLIFIED APPROACH: Use ONLY session (encrypted and secure)
        // Priority: 1. URL parameter (for testing), 2. Session, 3. Default
        $locale = null;

        // Get supported locales
        $supportedLocales = $this->getSupportedLocales();

        // Check URL parameter first (for testing purposes)
        if ($request->has('lang')) {
            $locale = $request->get('lang');
            // If valid, save to session immediately
            if ($locale && in_array($locale, $supportedLocales)) {
                Session::put('locale', $locale);
            }
        }
        
        // Check session (primary source)
        if (!$locale && Session::has('locale')) {
            $locale = Session::get('locale');
        }

        // Validate locale
        if ($locale && in_array($locale, $supportedLocales)) {
            // Valid locale found - use it
            App::setLocale($locale);
            config(['app.locale' => $locale]);
            
            // Ensure session has the locale
            Session::put('locale', $locale);
        } else {
            // No valid locale found - use default
            $defaultLocale = config('app.locale', 'fr');
            App::setLocale($defaultLocale);
            config(['app.locale' => $defaultLocale]);
            
            // Set default in session
            Session::put('locale', $defaultLocale);
        }

        // Set RTL for Arabic
        $currentLocale = App::getLocale();
        $rtlLocales = config('app.rtl_locales', ['ar']);
        if (in_array($currentLocale, $rtlLocales)) {
            config(['app.direction' => 'rtl']);
            $request->merge(['direction' => 'rtl']);
        } else {
            config(['app.direction' => 'ltr']);
            $request->merge(['direction' => 'ltr']);
        }

        // Share locale with views
        view()->share('currentLocale', $currentLocale);
        view()->share('direction', config('app.direction', 'ltr'));

        return $next($request);
    }
}
