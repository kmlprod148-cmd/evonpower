<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class LocalizationHelper
{
    /**
     * Obtenir la direction du texte pour la locale actuelle
     */
    public static function getTextDirection(): string
    {
        $locale = App::getLocale();
        $rtlLocales = config('app.rtl_locales', []);
        
        return in_array($locale, $rtlLocales) ? 'rtl' : 'ltr';
    }

    /**
     * Vérifier si la locale actuelle est RTL
     */
    public static function isRtl(): bool
    {
        return self::getTextDirection() === 'rtl';
    }

    /**
     * Obtenir les attributs HTML pour la direction
     */
    public static function getDirectionAttributes(): string
    {
        $direction = self::getTextDirection();
        return $direction === 'rtl' ? 'dir="rtl"' : 'dir="ltr"';
    }

    /**
     * Obtenir les classes CSS pour la direction
     */
    public static function getDirectionClasses(): string
    {
        return self::isRtl() ? 'rtl' : 'ltr';
    }

    /**
     * Formater un nombre selon la locale
     */
    public static function formatNumber($number, int $decimals = 2): string
    {
        $locale = App::getLocale();
        
        switch ($locale) {
            case 'fr':
            case 'ar':
                return number_format($number, $decimals, ',', ' ');
            case 'en':
            case 'es':
            default:
                return number_format($number, $decimals, '.', ',');
        }
    }

    /**
     * Formater une devise selon la locale
     */
    public static function formatCurrency($amount, string $currency = 'EUR'): string
    {
        $formattedAmount = self::formatNumber($amount);
        $locale = App::getLocale();
        
        switch ($locale) {
            case 'fr':
            case 'ar':
                return $formattedAmount . ' ' . $currency;
            case 'en':
            case 'es':
            default:
                return $currency . ' ' . $formattedAmount;
        }
    }

    /**
     * Obtenir la locale avec fallback
     */
    public static function getLocaleWithFallback(): string
    {
        $locale = App::getLocale();
        $availableLocales = config('app.available_locales', ['fr', 'en']);
        
        return in_array($locale, $availableLocales) ? $locale : config('app.fallback_locale', 'en');
    }

    /**
     * Obtenir le nom de la locale
     */
    public static function getLocaleName(string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $localeNames = config('app.locale_names', []);
        
        return $localeNames[$locale] ?? strtoupper($locale);
    }

    /**
     * Obtenir l'emoji de la locale
     */
    public static function getLocaleEmoji(string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        
        switch ($locale) {
            case 'fr': return '🇫🇷';
            case 'en': return '🇺🇸';
            case 'ar': return '🇸🇦';
            case 'es': return '🇪🇸';
            default: return '🌐';
        }
    }

    /**
     * Obtenir toutes les locales disponibles avec leurs informations
     */
    public static function getAvailableLocales(): array
    {
        $locales = config('app.available_locales', ['fr', 'en']);
        $localeNames = config('app.locale_names', []);
        $rtlLocales = config('app.rtl_locales', []);
        
        return array_map(function ($locale) use ($localeNames, $rtlLocales) {
            return [
                'code' => $locale,
                'name' => $localeNames[$locale] ?? strtoupper($locale),
                'emoji' => self::getLocaleEmoji($locale),
                'rtl' => in_array($locale, $rtlLocales),
                'direction' => in_array($locale, $rtlLocales) ? 'rtl' : 'ltr'
            ];
        }, $locales);
    }

    /**
     * Obtenir les informations de la locale actuelle
     */
    public static function getCurrentLocaleInfo(): array
    {
        $locale = App::getLocale();
        
        return [
            'code' => $locale,
            'name' => self::getLocaleName($locale),
            'emoji' => self::getLocaleEmoji($locale),
            'rtl' => self::isRtl(),
            'direction' => self::getTextDirection()
        ];
    }

    /**
     * Vérifier si une locale est supportée
     */
    public static function isLocaleSupported(string $locale): bool
    {
        $availableLocales = config('app.available_locales', ['fr', 'en']);
        return in_array($locale, $availableLocales);
    }

    /**
     * Obtenir la locale par défaut
     */
    public static function getDefaultLocale(): string
    {
        return config('app.locale', 'fr');
    }

    /**
     * Obtenir la locale de fallback
     */
    public static function getFallbackLocale(): string
    {
        return config('app.fallback_locale', 'en');
    }

    /**
     * Mettre en cache les traductions pour une locale
     */
    public static function cacheTranslations(string $locale): void
    {
        if (!self::isLocaleSupported($locale)) {
            return;
        }

        $cacheKey = "translations_{$locale}";
        
        if (!Cache::has($cacheKey)) {
            $translations = [
                'messages' => trans('messages', [], $locale),
                'dashboard' => trans('dashboard', [], $locale),
                'auth' => trans('auth', [], $locale),
                'validation' => trans('validation', [], $locale),
                'switch' => trans('switch', [], $locale),
            ];
            
            Cache::put($cacheKey, $translations, 3600); // 1 heure
        }
    }

    /**
     * Obtenir les traductions mises en cache
     */
    public static function getCachedTranslations(string $locale = null): array
    {
        $locale = $locale ?? App::getLocale();
        $cacheKey = "translations_{$locale}";
        
        return Cache::get($cacheKey, []);
    }

    /**
     * Nettoyer le cache des traductions
     */
    public static function clearTranslationCache(): void
    {
        $locales = config('app.available_locales', ['fr', 'en']);
        
        foreach ($locales as $locale) {
            Cache::forget("translations_{$locale}");
        }
    }
}
