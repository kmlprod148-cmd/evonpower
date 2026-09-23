<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageHelper
{
    /**
     * Force la locale pour la requête actuelle
     */
    public static function forceLocale($locale)
    {
        // Valider la locale
        $availableLocales = ['en', 'fr'];
        if (!in_array($locale, $availableLocales)) {
            $locale = 'fr';
        }
        
        // Forcer la locale de plusieurs façons
        Session::put('locale', $locale);
        App::setLocale($locale);
        config(['app.locale' => $locale]);
        
        return $locale;
    }
    
    /**
     * Obtenir la locale actuelle
     */
    public static function getCurrentLocale()
    {
        return App::getLocale();
    }
    
    /**
     * Obtenir la locale de la session
     */
    public static function getSessionLocale()
    {
        return Session::get('locale');
    }
    
    /**
     * Vérifier si la locale est valide
     */
    public static function isValidLocale($locale)
    {
        return in_array($locale, ['en', 'fr']);
    }
    
    /**
     * Obtenir les langues disponibles
     */
    public static function getAvailableLocales()
    {
        return [
            'fr' => [
                'name' => 'Français',
                'flag' => 'https://flagcdn.com/w20/fr.png',
                'native_name' => 'Français'
            ],
            'en' => [
                'name' => 'English',
                'flag' => 'https://flagcdn.com/w20/gb.png',
                'native_name' => 'English'
            ]
        ];
    }
    
    /**
     * Forcer l'anglais
     */
    public static function forceEnglish()
    {
        return self::forceLocale('en');
    }
    
    /**
     * Forcer le français
     */
    public static function forceFrench()
    {
        return self::forceLocale('fr');
    }
} 