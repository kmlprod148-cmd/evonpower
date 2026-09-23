<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetUserLocale
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
        // Si l'utilisateur est connecté et a une préférence de langue
        if (Auth::check() && Auth::user()->locale) {
            $userLocale = Auth::user()->locale;
            
            // Vérifier que la langue de l'utilisateur est supportée
            if (in_array($userLocale, config('app.available_locales'))) {
                App::setLocale($userLocale);
                session(['locale' => $userLocale]);
                
                // Définir la direction du texte
                if (in_array($userLocale, config('app.rtl_locales', []))) {
                    config(['app.direction' => 'rtl']);
                } else {
                    config(['app.direction' => 'ltr']);
                }
                
                // Debug: Log the user locale setting
                if (config('app.debug')) {
                    \Log::info('SetUserLocale middleware: Setting user locale to ' . $userLocale);
                }
            }
        }

        return $next($request);
    }
}
