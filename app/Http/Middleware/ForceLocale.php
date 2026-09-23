<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

class ForceLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Récupérer la langue depuis l'URL, le cookie, la session ou le profil utilisateur
        $locale = $request->get('lang') ?: 
                  $request->cookie('locale') ?:
                  session('locale') ?: 
                  auth()->user()?->locale ?: 
                  config('app.locale');

        // Vérifier que la langue est disponible
        if (in_array($locale, config('app.available_locales'))) {
            App::setLocale($locale);
            session(['locale' => $locale]);
            config(['app.locale' => $locale]);
            
            // Forcer la sauvegarde de la session
            session()->save();
            
            // Debug: Log the locale setting
            if (config('app.debug')) {
                \Log::info('ForceLocale middleware: Setting locale to ' . $locale . ' (source: ' . ($request->get('lang') ? 'URL' : ($request->cookie('locale') ? 'Cookie' : (session('locale') ? 'Session' : 'Default'))) . ')');
            }
        } else {
            // Si la langue n'est pas valide, utiliser la langue par défaut
            $defaultLocale = config('app.locale');
            App::setLocale($defaultLocale);
            session(['locale' => $defaultLocale]);
            session()->save();
            
            // Debug: Log the fallback
            if (config('app.debug')) {
                \Log::info('ForceLocale middleware: Invalid locale ' . $locale . ', falling back to ' . $defaultLocale);
            }
        }

        $response = $next($request);

        // Si la locale a été définie via cookie, s'assurer qu'elle est persistante
        if ($request->cookie('locale') && in_array($request->cookie('locale'), config('app.available_locales'))) {
            $response->withCookie(cookie('locale', $request->cookie('locale'), 60 * 24 * 30)); // 30 jours
        }

        return $response;
    }
}
