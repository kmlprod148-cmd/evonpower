<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/**
 * Locale Controller - Gestion professionnelle de la langue
 * 
 * Approche Senior Laravel Developer :
 * - Session + Cookie (redondance)
 * - User preference sauvegardée
 * - Validation stricte
 * - Error handling complet
 * - Cache management
 */
class LocaleController extends Controller
{
    /**
     * Available locales configuration
     */
    private function getAvailableLocales(): array
    {
        return config('app.available_locales', ['fr', 'en', 'ar', 'es']);
    }

    /**
     * RTL locales
     */
    private function getRtlLocales(): array
    {
        return config('app.rtl_locales', ['ar']);
    }

    /**
     * Validate locale
     */
    private function validateLocale(string $locale): bool
    {
        return in_array($locale, $this->getAvailableLocales());
    }

    /**
     * Get direction for locale
     */
    private function getDirection(string $locale): string
    {
        return in_array($locale, $this->getRtlLocales()) ? 'rtl' : 'ltr';
    }

    /**
     * Switch language - Main method
     * 
     * Uses triple storage for maximum reliability:
     * 1. Session (encrypted, server-side)
     * 2. Cookie (24h, client-side fallback)
     * 3. User DB (for authenticated users)
     */
    public function switch(string $locale, Request $request)
    {
        // Step 0: Handle invalid locale formats (numeric IDs from browser extensions)
        if (is_numeric($locale)) {
            Log::info('[CONTROLLER] Invalid numeric locale detected', [
                'invalid_locale' => $locale,
                'query_lang' => $request->query('lang'),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer')
            ]);
            
            // Try to get the intended locale from query parameter
            $intendedLocale = $request->query('lang', app()->getLocale());
            
            // Validate and use it, or fallback to current locale
            if ($this->validateLocale($intendedLocale)) {
                $locale = $intendedLocale;
            } else {
                $locale = app()->getLocale();
            }
            
            // Redirect to the correct URL format
            return redirect()->route('language.switch', ['locale' => $locale]);
        }
        
        // Step 1: Validation
        if (!$this->validateLocale($locale)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Locale non supportée : ' . $locale
                ], 400);
            }
            return redirect()->back()->with('error', 'Langue non supportée');
        }

        // Step 2: Set application locale immediately
        App::setLocale($locale);
        
        // Step 3: Triple storage strategy
        
        // 3a. SESSION (Primary - encrypted, server-side)
        $request->session()->put('locale', $locale);
        $request->session()->put('locale_timestamp', now()->timestamp);
        $request->session()->save(); // Force immediate save
        
        // Double check session save with Laravel facade
        Session::put('locale', $locale);
        Session::put('locale_timestamp', now()->timestamp);
        Session::save();
        
        // 3b. COOKIE (Secondary - 24h fallback)
        $cookie = cookie('locale', $locale, 60 * 24); // 24 hours
        
        // 3c. USER PREFERENCE (Tertiary - for authenticated users)
        if (auth()->check()) {
            try {
                $user = auth()->user();
                // Check if locale column exists
                if (method_exists($user, 'update') && Schema::hasColumn('users', 'locale')) {
                    $user->update(['locale' => $locale]);
                }
            } catch (\Exception $e) {
                Log::warning('Could not update user locale in DB: ' . $e->getMessage());
            }
        }
        
        // Step 4: Set config for this request
        config(['app.locale' => $locale]);
        
        // Step 5: Set direction for RTL languages
        $direction = $this->getDirection($locale);
        config(['app.direction' => $direction]);
        
        // Step 6: Clear translation cache
        try {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        } catch (\Exception $e) {
            Log::debug('Could not clear cache: ' . $e->getMessage());
        }
        
        // Step 7: Response
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'locale' => $locale,
                'direction' => $direction,
                'app_locale' => App::getLocale(),
                'session_locale' => Session::get('locale'),
                'message' => __('messages.language_changed_successfully')
            ])->cookie($cookie);
        }

        // Redirect with ?lang= in URL so next page load picks locale even if session/cookie fail (mobile)
        $backUrl = url()->previous() ?: url('/');
        $parsed = parse_url($backUrl);
        $path = $parsed['path'] ?? '/';
        // Collapse duplicate /public prefixes without stripping a single valid prefix
        $path = preg_replace('#^/public/+#', '/public/', $path);
        parse_str($parsed['query'] ?? '', $query);
        $query['lang'] = $locale;
        $redirectUrl = $path . '?' . http_build_query($query);

        return redirect($redirectUrl)
            ->with('success', __('messages.language_changed_successfully'))
            ->cookie($cookie);
    }

    /**
     * AJAX locale switch - For modern components
     */
    public function setAjax(Request $request)
    {
        $locale = $request->input('locale');

        // Validation
        if (!$this->validateLocale($locale)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Locale non supportée'
            ], 400);
        }
        
        // CRITICAL FIX: Also update user's locale in database immediately
        // This ensures persistence even if session is lost
        if (auth()->check()) {
            try {
                $user = auth()->user();
                $user->locale = $locale;
                $user->save();
                Log::info('[AJAX] User locale updated in DB', ['user_id'=>$user->id,'locale'=>$locale]);
            } catch (\Exception $e) {
                Log::warning('[AJAX] Could not update user locale in DB', ['error'=>$e->getMessage()]);
            }
        }

        try {
            // Set application locale
            App::setLocale($locale);
            
            // Triple storage
            $request->session()->put('locale', $locale);
            $request->session()->put('locale_timestamp', now()->timestamp);
            $request->session()->save();
            
            Session::put('locale', $locale);
            Session::put('locale_timestamp', now()->timestamp);
            Session::save();
            
            // Cookie fallback
            $cookie = cookie('locale', $locale, 60 * 24);
            
            // User DB
            if (auth()->check()) {
                try {
                    auth()->user()->update(['locale' => $locale]);
                } catch (\Exception $e) {
                    // Silent fail
                }
            }
            
            // Config
            config(['app.locale' => $locale]);
            config(['app.direction' => $this->getDirection($locale)]);
            
            // Verify session was saved
            $savedLocale = $request->session()->get('locale');
            
            return response()->json([
                'status' => 'success',
                'locale' => $locale,
                'session_locale' => $savedLocale,
                'app_locale' => App::getLocale(),
                'direction' => $this->getDirection($locale),
                'message' => __('messages.language_changed_successfully'),
                'timestamp' => now()->toIso8601String()
            ])->cookie($cookie);
            
        } catch (\Exception $e) {
            Log::error('Locale switch error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du changement de langue : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current locale info
     */
    public function current(Request $request)
    {
        return response()->json([
            'locale' => App::getLocale(),
            'session_locale' => Session::get('locale'),
            'cookie_locale' => $request->cookie('locale'),
            'user_locale' => auth()->check() ? auth()->user()->locale ?? null : null,
            'config_locale' => config('app.locale'),
            'direction' => config('app.direction', 'ltr'),
            'available_locales' => $this->getAvailableLocales()
        ]);
    }
}

