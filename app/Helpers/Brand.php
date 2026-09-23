<?php

namespace App\Helpers;

use App\Http\Middleware\DetectClient;
use App\Models\Client;

class Brand
{
    /**
     * Obtenir le client actuel.
     */
    public static function client(): ?Client
    {
        return DetectClient::getCurrentClient();
    }

    /**
     * Obtenir l'ID du client actuel.
     */
    public static function clientId(): ?int
    {
        return DetectClient::getCurrentClientId();
    }

    /**
     * Vérifier si un client est actuellement détecté.
     */
    public static function hasClient(): bool
    {
        return DetectClient::hasCurrentClient();
    }

    /**
     * Obtenir la configuration de marque complète.
     */
    public static function config(): array
    {
        return DetectClient::getCurrentClientBrandConfig();
    }

    /**
     * Obtenir une valeur spécifique de la configuration de marque.
     */
    public static function get(string $key, $default = null)
    {
        return DetectClient::getCurrentClientBrandValue($key, $default);
    }

    /**
     * Obtenir le nom de la marque.
     */
    public static function name(): string
    {
        return DetectClient::getCurrentClientName();
    }

    /**
     * Obtenir le nom d'affichage de la marque.
     */
    public static function displayName(): string
    {
        return DetectClient::getCurrentClientDisplayName();
    }

    /**
     * Obtenir le logo de la marque.
     */
    public static function logo(): string
    {
        return DetectClient::getCurrentClientLogo();
    }

    /**
     * Obtenir le favicon de la marque.
     */
    public static function favicon(): string
    {
        $client = self::client();
        if ($client) {
            $faviconUrl = $client->favicon_url;
            // Vérifier si le fichier existe et n'est pas vide
            $faviconPath = public_path(str_replace(asset(''), '', $faviconUrl));
            if (file_exists($faviconPath) && filesize($faviconPath) > 0) {
                return $faviconUrl;
            }
        }
        
        // Fallback vers SVG si ICO n'existe pas ou est vide
        $icoPath = public_path('images/default-favicon.ico');
        if (file_exists($icoPath) && filesize($icoPath) > 0) {
            return asset('images/default-favicon.ico');
        }
        
        return asset('images/default-favicon.svg');
    }

    /**
     * Obtenir la couleur primaire de la marque.
     */
    public static function primaryColor(): string
    {
        return DetectClient::getCurrentClientPrimaryColor();
    }

    /**
     * Obtenir la couleur secondaire de la marque.
     */
    public static function secondaryColor(): string
    {
        return DetectClient::getCurrentClientSecondaryColor();
    }

    /**
     * Obtenir la langue de la marque.
     */
    public static function language(): string
    {
        return DetectClient::hasCurrentClient() ? DetectClient::getCurrentClientLanguage() : 'fr';
    }

    /**
     * Obtenir la devise de la marque.
     */
    public static function currency(): string
    {
        return DetectClient::getCurrentClientCurrency();
    }

    /**
     * Obtenir le symbole de la devise de la marque.
     */
    public static function currencySymbol(): string
    {
        return DetectClient::getCurrentClientCurrencySymbol();
    }

    /**
     * Obtenir la direction du texte basée sur la langue.
     */
    public static function textDirection(): string
    {
        $language = self::language();
        return in_array($language, ['ar', 'he', 'fa']) ? 'rtl' : 'ltr';
    }

    /**
     * Obtenir la classe CSS pour la direction du texte basée sur la langue.
     */
    public static function textDirectionClass(): string
    {
        $direction = self::textDirection();
        return $direction === 'rtl' ? 'rtl' : 'ltr';
    }

    /**
     * Obtenir les variables CSS pour les couleurs de la marque.
     */
    public static function cssVariables(): string
    {
        return "
            :root {
                --primary-color: " . self::primaryColor() . ";
                --secondary-color: " . self::secondaryColor() . ";
                --accent-color: " . self::get('accent_color', '#F59E0B') . ";
                --success-color: " . self::get('success_color', '#10B981') . ";
                --warning-color: " . self::get('warning_color', '#F59E0B') . ";
                --error-color: " . self::get('error_color', '#EF4444') . ";
                --info-color: " . self::get('info_color', '#3B82F6') . ";
                --background-primary: " . self::get('background_primary', '#FFFFFF') . ";
                --background-secondary: " . self::get('background_secondary', '#F9FAFB') . ";
                --background-dark: " . self::get('background_dark', '#111827') . ";
                --text-primary: " . self::get('text_primary', '#111827') . ";
                --text-secondary: " . self::get('text_secondary', '#6B7280') . ";
                --text-light: " . self::get('text_light', '#9CA3AF') . ";
            }
        ";
    }
}
