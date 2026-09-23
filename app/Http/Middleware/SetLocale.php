<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
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
        // Obtenir la langue depuis la session, le cookie ou utiliser la langue par défaut
        $locale = Session::get('locale') 
                ?? $request->cookie('locale') 
                ?? config('app.locale', 'fr');
        
        // Vérifier que la langue est supportée
        $availableLocales = config('app.available_locales', ['fr', 'en', 'ar', 'es']);
        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.locale', 'fr');
        }
        
        // Si on a un cookie mais pas de session, synchroniser la session
        if ($request->cookie('locale') && !Session::has('locale')) {
            Session::put('locale', $locale);
        }
        
        // Définir la langue de l'application
        App::setLocale($locale);
        
        // Définir la direction du texte pour les langues RTL
        $rtlLocales = config('app.rtl_locales', ['ar']);
        if (in_array($locale, $rtlLocales)) {
            config(['app.direction' => 'rtl']);
        } else {
            config(['app.direction' => 'ltr']);
        }
        
        // Passer la direction à la vue
        view()->share('direction', config('app.direction', 'ltr'));
        view()->share('currentLocale', $locale);
        
        return $next($request);
    }
}