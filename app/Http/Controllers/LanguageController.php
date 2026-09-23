<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Cookie;

class LanguageController extends Controller
{
    /**
     * Switch the application language
     *
     * @param string $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switchLanguage($locale, Request $request = null)
    {
        // Validate locale
        $availableLocales = config('app.available_locales', ['fr', 'en', 'ar', 'es']);
        
        if (!in_array($locale, $availableLocales)) {
            if ($request && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Language not supported: ' . $locale
                ], 400);
            }
            return redirect()->back()->with('error', 'Language not supported: ' . $locale);
        }

        // Set the locale
        App::setLocale($locale);
        
        // Définir la direction du texte pour les langues RTL
        $direction = 'ltr';
        if (in_array($locale, config('app.rtl_locales', ['ar']))) {
            $direction = 'rtl';
            config(['app.direction' => 'rtl']);
        } else {
            config(['app.direction' => 'ltr']);
        }
        
        // SIMPLIFIED: Store ONLY in session (encrypted and secure)
        Session::put('locale', $locale);
        config(['app.locale' => $locale]);
        
        // Force session save
        Session::save();
        
        // Mettre à jour la langue de l'utilisateur connecté si possible
        if (auth()->check()) {
            $user = auth()->user();
            if (method_exists($user, 'update') && isset($user->locale)) {
                try {
                    $user->update(['locale' => $locale]);
                } catch (\Exception $e) {
                    // Silently fail if locale column doesn't exist
                    \Log::debug('Could not update user locale: ' . $e->getMessage());
                }
            }
        }

        // If AJAX request, return JSON response
        if ($request && $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'locale' => $locale,
                'session_locale' => Session::get('locale'),
                'app_locale' => App::getLocale(),
                'direction' => $direction,
                'message' => __('messages.language_changed_successfully') ?? 'Language switched to ' . $locale
            ]);
        }

        // Normal request, redirect back
        return redirect()->back()
            ->with('success', __('messages.language_changed_successfully') ?? 'Language switched to ' . $locale);
    }

    /**
     * Change language (alternative method)
     *
     * @param string $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function change($locale)
    {
        return $this->switchLanguage($locale);
    }

    /**
     * Get current language
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrent()
    {
        return response()->json([
            'locale' => App::getLocale(),
            'session_locale' => Session::get('locale'),
            'config_locale' => config('app.locale')
        ]);
    }

    /**
     * Get translations for a specific locale
     *
     * @param string $locale
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTranslations($locale)
    {
        // Validate locale
        $availableLocales = config('app.available_locales', ['en', 'fr']);
        
        if (!in_array($locale, $availableLocales)) {
            return response()->json(['error' => 'Language not supported'], 400);
        }

        // Set locale temporarily to get translations
        $originalLocale = App::getLocale();
        App::setLocale($locale);

        // Get common translations
        $translations = [
            'welcome' => __('messages.welcome'),
            'dashboard' => __('messages.dashboard'),
            'settings' => __('messages.settings'),
            'profile' => __('messages.profile'),
            'logout' => __('messages.logout'),
            'login' => __('messages.login'),
            'register' => __('messages.register'),
        ];

        // Restore original locale
        App::setLocale($originalLocale);

        return response()->json([
            'locale' => $locale,
            'translations' => $translations
        ]);
    }

    /**
     * Test language functionality
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testLanguage()
    {
        return response()->json([
            'current_locale' => App::getLocale(),
            'session_locale' => Session::get('locale'),
            'config_locale' => config('app.locale'),
            'available_locales' => config('app.available_locales', ['en', 'fr']),
            'welcome_message' => __('messages.welcome'),
            'session_id' => Session::getId(),
            'timestamp' => now()->toDateTimeString()
        ]);
    }
    /**
     * Set the application locale via AJAX.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setLocaleAjax(Request $request)
    {
        $locale = $request->input('locale');
        $availableLocales = config('app.available_locales', ['fr', 'en', 'ar', 'es']);

        if (!in_array($locale, $availableLocales)) {
            return response()->json([
                'status' => 'error', 
                'message' => __('messages.language_not_supported') ?? 'Language not supported'
            ], 400);
        }

        try {
            // Définir la langue de l'application immédiatement
            App::setLocale($locale);
            
            // Définir la direction du texte pour les langues RTL
            $direction = 'ltr';
            if (in_array($locale, config('app.rtl_locales', ['ar']))) {
                $direction = 'rtl';
                config(['app.direction' => 'rtl']);
            } else {
                config(['app.direction' => 'ltr']);
            }

            // IMPORTANT: Set locale in App FIRST before session/cookies
            // This ensures the locale is available immediately
            App::setLocale($locale);
            
            // Définir la langue dans la session (utiliser la session de la requête)
            // IMPORTANT: Session is saved FIRST because it's available immediately on next request
            $request->session()->put('locale', $locale);
            
            // Forcer la sauvegarde de la session immédiatement
            $request->session()->save();
            
            // Aussi utiliser Session facade pour compatibilité
            Session::put('locale', $locale);
            Session::save();
            
            // Mettre à jour la config pour que le middleware le lise
            config(['app.locale' => $locale]);

            // Mettre à jour la langue de l'utilisateur connecté si possible
            if (auth()->check()) {
                $user = auth()->user();
                if (method_exists($user, 'update') && isset($user->locale)) {
                    try {
                        $user->update(['locale' => $locale]);
                    } catch (\Exception $e) {
                        // Silently fail if locale column doesn't exist
                        \Log::debug('Could not update user locale: ' . $e->getMessage());
                    }
                }
            }

            // Vérifier que tout est bien sauvegardé avant de répondre
            // IMPORTANT: Re-read session locale to verify it was saved correctly
            $sessionLocale = $request->session()->get('locale');
            if (empty($sessionLocale)) {
                $sessionLocale = Session::get('locale');
            }
            $appLocale = App::getLocale();
            
            // Si la session n'a pas été sauvegardée, réessayer plusieurs fois
            if ($sessionLocale !== $locale) {
                // Try saving again with both methods
                $request->session()->put('locale', $locale);
                $request->session()->save();
                Session::put('locale', $locale);
                Session::save();
                
                // Re-read to verify
                $sessionLocale = $request->session()->get('locale') ?: Session::get('locale');
            }
            
            // SIMPLIFIED: No cookies, only session
            return response()->json([
                'status' => 'success', 
                'locale' => $locale,
                'session_locale' => $sessionLocale,
                'app_locale' => $appLocale,
                'config_locale' => config('app.locale'),
                'message' => __('messages.language_changed_successfully') ?? 'Language changed successfully',
                'direction' => $direction
            ]);
        } catch (\Exception $e) {
            \Log::error('Error setting locale: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to change language: ' . $e->getMessage()
            ], 500);
        }
    }
}