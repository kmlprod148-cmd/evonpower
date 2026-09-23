<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class TranslationHelper
{
    /**
     * Obtenir la langue actuelle
     */
    public static function getCurrentLocale()
    {
        return App::getLocale();
    }
    
    /**
     * Obtenir la direction du texte
     */
    public static function getDirection()
    {
        return config('app.direction', 'ltr');
    }
    
    /**
     * Vérifier si la langue est RTL
     */
    public static function isRTL()
    {
        return self::getDirection() === 'rtl';
    }
    
    /**
     * Vérifier si la langue est LTR
     */
    public static function isLTR()
    {
        return self::getDirection() === 'ltr';
    }
    
    /**
     * Obtenir les langues disponibles
     */
    public static function getAvailableLocales()
    {
        return config('app.available_locales', ['fr', 'en', 'ar', 'es']);
    }
    
    /**
     * Obtenir les noms des langues
     */
    public static function getLocaleNames()
    {
        return config('app.locale_names', [
            'fr' => 'Français',
            'en' => 'English',
            'ar' => 'العربية',
            'es' => 'Español'
        ]);
    }
    
    /**
     * Obtenir le nom d'une langue
     */
    public static function getLocaleName($locale = null)
    {
        $locale = $locale ?: self::getCurrentLocale();
        $names = self::getLocaleNames();
        return $names[$locale] ?? $locale;
    }
    
    /**
     * Obtenir le drapeau d'une langue
     */
    public static function getLocaleFlag($locale = null)
    {
        $locale = $locale ?: self::getCurrentLocale();
        $flags = [
            'fr' => 'fr',
            'en' => 'gb',
            'ar' => 'ma',
            'es' => 'es'
        ];
        return 'https://flagcdn.com/w20/' . ($flags[$locale] ?? 'fr') . '.png';
    }
    
    /**
     * Changer de langue
     */
    public static function setLocale($locale)
    {
        $availableLocales = self::getAvailableLocales();
        
        if (!in_array($locale, $availableLocales)) {
            return false;
        }
        
        // Définir la langue dans la session
        Session::put('locale', $locale);
        
        // Définir la langue de l'application
        App::setLocale($locale);
        
        // Définir la direction du texte
        $rtlLocales = config('app.rtl_locales', ['ar']);
        if (in_array($locale, $rtlLocales)) {
            config(['app.direction' => 'rtl']);
        } else {
            config(['app.direction' => 'ltr']);
        }
        
        return true;
    }
    
    /**
     * Obtenir la prochaine langue disponible
     */
    public static function getNextLocale()
    {
        $currentLocale = self::getCurrentLocale();
        $availableLocales = self::getAvailableLocales();
        
        $currentIndex = array_search($currentLocale, $availableLocales);
        $nextIndex = ($currentIndex + 1) % count($availableLocales);
        
        return $availableLocales[$nextIndex];
    }
    
    /**
     * Obtenir toutes les informations de langue
     */
    public static function getLanguageInfo()
    {
        return [
            'current' => self::getCurrentLocale(),
            'direction' => self::getDirection(),
            'isRTL' => self::isRTL(),
            'isLTR' => self::isLTR(),
            'available' => self::getAvailableLocales(),
            'names' => self::getLocaleNames(),
            'currentName' => self::getLocaleName(),
            'currentFlag' => self::getLocaleFlag(),
            'next' => self::getNextLocale()
        ];
    }
    
    /**
     * Traduire avec fallback
     */
    public static function trans($key, $replace = [], $locale = null)
    {
        $locale = $locale ?: self::getCurrentLocale();
        
        // Essayer de traduire dans la langue demandée
        $translation = trans($key, $replace, $locale);
        
        // Si la traduction n'existe pas, essayer la langue de fallback
        if ($translation === $key) {
            $fallbackLocale = config('app.fallback_locale', 'en');
            if ($locale !== $fallbackLocale) {
                $translation = trans($key, $replace, $fallbackLocale);
            }
        }
        
        return $translation;
    }
    
    /**
     * Obtenir les attributs HTML pour la direction
     */
    public static function getDirectionAttributes()
    {
        return [
            'dir' => self::getDirection(),
            'lang' => self::getCurrentLocale(),
            'class' => self::isRTL() ? 'rtl' : 'ltr'
        ];
    }
}
