<?php

namespace App\Providers;

use App\Helpers\LocalizationHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ne pas déclarer la fonction ici pour éviter les conflits

        // Partager les données de localisation avec toutes les vues
        View::composer('*', function ($view) {
            $view->with([
                'currentLocale' => app()->getLocale(),
                'isRtl' => LocalizationHelper::isRtl(),
                'textDirection' => LocalizationHelper::getTextDirection(),
                'availableLocales' => LocalizationHelper::getAvailableLocales(),
                'currentLocaleInfo' => LocalizationHelper::getCurrentLocaleInfo(),
            ]);
        });

        // Ajouter des macros aux collections pour la localisation
        \Illuminate\Support\Collection::macro('localize', function ($key, $locale = null) {
            $locale = $locale ?? app()->getLocale();
            return $this->map(function ($item) use ($key, $locale) {
                if (is_array($item) && isset($item[$key])) {
                    $item[$key] = $item[$key][$locale] ?? $item[$key]['en'] ?? $item[$key];
                }
                return $item;
            });
        });

        // Ajouter des macros aux strings pour la localisation
        \Illuminate\Support\Str::macro('localize', function ($string, $locale = null) {
            $locale = $locale ?? app()->getLocale();
            
            // Si c'est un JSON, essayer de le décoder et retourner la valeur localisée
            if (is_string($string) && str_starts_with($string, '{')) {
                $decoded = json_decode($string, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded[$locale] ?? $decoded['en'] ?? $string;
                }
            }
            
            return $string;
        });
    }
}
