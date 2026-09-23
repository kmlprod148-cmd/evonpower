<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

/**
 * Set Locale Middleware - Professional Implementation
 * 
 * Priority Order (Senior Laravel approach):
 * 1. URL parameter (?lang=en) - Testing & development
 * 2. Session storage - Primary source (encrypted)
 * 3. Cookie storage - Fallback if session fails
 * 4. User database preference - For authenticated users
 * 5. Accept-Language header - Browser preference
 * 6. Default from config - Last resort
 * 
 * This multi-layer approach ensures language persistence across:
 * - Browser restarts
 * - Session timeouts
 * - Mobile app backgrounds
 * - Different devices
 */
class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->determineLocale($request);
        
        // Validate locale
        if (!$this->isValidLocale($locale)) {
            $locale = config('app.locale', 'fr');
        }
        
        // Apply locale
        $this->applyLocale($locale, $request);
        
        $response = $next($request);
        
        // When locale was set from URL param: attach cookie for mobile persistence
        if ($request->has('lang') && $this->isValidLocale($request->get('lang'))) {
            $response->cookie('locale', $locale, 60 * 24 * 30); // 30 days
            // Update user preference for authenticated users
            if (auth()->check() && Schema::hasColumn('users', 'locale')) {
                try {
                    auth()->user()->update(['locale' => $locale]);
                } catch (\Exception $e) {
                    // Silent fail
                }
            }
        }
        
        return $response;
    }

    /**
     * Determine the locale from various sources
     */
    private function determineLocale(Request $request): string
    {
        $availableLocales = $this->getAvailableLocales();
        
        // Priority 1: URL parameter (for testing)
        if ($request->has('lang')) {
            $locale = trim(strtolower((string) $request->get('lang')));
            if ($locale && $this->isValidLocale($locale)) {
                // If valid, save to session immediately for future requests
                $this->saveLocaleToSession($locale, $request);
                return $locale;
            }
        }
        
        // Priority 2: Session (Primary source) - CRITICAL FIX: Check both sources
        $sessionLocale = $request->session()->get('locale');
        if (!$sessionLocale) {
            $sessionLocale = Session::get('locale');
        }
        
        if ($sessionLocale && $this->isValidLocale($sessionLocale)) {
            return $sessionLocale;
        }
        
        // Priority 3: Cookie (Fallback)
        $cookieLocale = $request->cookie('locale');
        if ($cookieLocale && $this->isValidLocale($cookieLocale)) {
            // Restore to session
            $this->saveLocaleToSession($cookieLocale, $request);
            return $cookieLocale;
        }
        
        // Priority 4: User preference (for authenticated users)
        if (auth()->check()) {
            try {
                $user = auth()->user();
                if ($user && isset($user->locale) && $user->locale) {
                    $userLocale = $user->locale;
                    if ($this->isValidLocale($userLocale)) {
                        // Restore to session
                        $this->saveLocaleToSession($userLocale, $request);
                        return $userLocale;
                    }
                }
            } catch (\Exception $e) {
                // Silent fail if locale column doesn't exist
            }
        }
        
        // Priority 5: REMOVED - Don't use browser Accept-Language as it always returns 'fr'
        // This was causing the bug - browser preference was overriding session
        
        // Priority 6: Default from config
        return config('app.locale', 'fr');
    }

    /**
     * Apply the determined locale to the application
     */
    private function applyLocale(string $locale, Request $request): void
    {
        // Set application locale
        App::setLocale($locale);
        
        // Set in config for this request
        config(['app.locale' => $locale]);
        
        // Set direction for RTL languages
        $direction = $this->getDirection($locale);
        config(['app.direction' => $direction]);
        
        // Share with views
        view()->share('currentLocale', $locale);
        view()->share('direction', $direction);
        
        // Ensure session has locale (important for persistence)
        if (!$request->session()->has('locale') || $request->session()->get('locale') !== $locale) {
            $this->saveLocaleToSession($locale, $request);
        }
        
        // Log for debugging (only in non-production)
        if (!app()->isProduction()) {
            Log::debug('Locale set', [
                'locale' => $locale,
                'session_locale' => $request->session()->get('locale'),
                'app_locale' => App::getLocale(),
                'direction' => $direction
            ]);
        }
    }

    /**
     * Save locale to session with both methods for reliability
     */
    private function saveLocaleToSession(string $locale, Request $request): void
    {
        // Method 1: Request session
        $request->session()->put('locale', $locale);
        $request->session()->put('locale_timestamp', now()->timestamp);
        
        // Method 2: Session facade (for compatibility)
        Session::put('locale', $locale);
        Session::put('locale_timestamp', now()->timestamp);
    }

    /**
     * Get available locales
     */
    private function getAvailableLocales(): array
    {
        return config('app.available_locales', ['fr', 'en', 'ar', 'es']);
    }

    /**
     * Check if locale is valid
     */
    private function isValidLocale(?string $locale): bool
    {
        if (!$locale || !is_string($locale)) {
            return false;
        }
        $locale = trim(strtolower($locale));
        return in_array($locale, $this->getAvailableLocales());
    }

    /**
     * Get text direction for locale
     */
    private function getDirection(string $locale): string
    {
        $rtlLocales = config('app.rtl_locales', ['ar']);
        return in_array($locale, $rtlLocales) ? 'rtl' : 'ltr';
    }
}

