<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LocalizationService
{
    /**
     * Configuration des régions supportées
     */
    private const REGIONS = [
        'MA' => [
            'name' => 'Maroc',
            'currency' => 'EUR',
            'timezone' => 'Africa/Casablanca',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'language' => 'fr',
            'phone_code' => '+212',
            'vat_rate' => 20.0
        ],
        'FR' => [
            'name' => 'France',
            'currency' => 'EUR',
            'timezone' => 'Europe/Paris',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'language' => 'fr',
            'phone_code' => '+33',
            'vat_rate' => 20.0
        ],
        'EN' => [
            'name' => 'International',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'language' => 'en',
            'phone_code' => '+1',
            'vat_rate' => 0.0
        ]
    ];

    /**
     * Obtenir la configuration d'une région
     */
    public function getRegionConfig(string $regionCode): array
    {
        return self::REGIONS[$regionCode] ?? self::REGIONS['EN'];
    }

    /**
     * Obtenir la configuration pour un utilisateur
     */
    public function getUserConfig(User $user): array
    {
        $regionCode = $user->country ?? 'MA';
        $config = $this->getRegionConfig($regionCode);

        // Surcharger avec les préférences utilisateur
        if ($user->language) {
            $config['language'] = $user->language;
        }

        if ($user->timezone) {
            $config['timezone'] = $user->timezone;
        }

        return $config;
    }

    /**
     * Obtenir la configuration pour un profil business
     */
    public function getBusinessProfileConfig(BusinessProfile $profile): array
    {
        $regionCode = $profile->country ?? 'MA';
        $config = $this->getRegionConfig($regionCode);

        // Surcharger avec les paramètres du profil
        if ($profile->currency) {
            $config['currency'] = $profile->currency;
        }

        if ($profile->timezone) {
            $config['timezone'] = $profile->timezone;
        }

        if ($profile->language) {
            $config['language'] = $profile->language;
        }

        return $config;
    }

    /**
     * Formater un montant selon la configuration régionale
     */
    public function formatAmount(float $amount, array $config = null): string
    {
        if (!$config) {
            $config = $this->getRegionConfig('MA');
        }

        $formatted = number_format(
            $amount,
            2,
            $config['decimal_separator'],
            $config['thousands_separator']
        );

        return $formatted . ' ' . $config['currency'];
    }

    /**
     * Formater une date selon la configuration régionale
     */
    public function formatDate(Carbon $date, array $config = null): string
    {
        if (!$config) {
            $config = $this->getRegionConfig('MA');
        }

        return $date->format($config['date_format']);
    }

    /**
     * Formater une heure selon la configuration régionale
     */
    public function formatTime(Carbon $time, array $config = null): string
    {
        if (!$config) {
            $config = $this->getRegionConfig('MA');
        }

        return $time->format($config['time_format']);
    }

    /**
     * Formater une date et heure selon la configuration régionale
     */
    public function formatDateTime(Carbon $datetime, array $config = null): string
    {
        if (!$config) {
            $config = $this->getRegionConfig('MA');
        }

        return $datetime->format($config['date_format'] . ' ' . $config['time_format']);
    }

    /**
     * Appliquer la configuration régionale à l'application
     */
    public function applyConfig(array $config): void
    {
        // Définir la langue
        App::setLocale($config['language']);

        // Définir le fuseau horaire
        date_default_timezone_set($config['timezone']);

        // Mettre en cache la configuration
        Cache::put('localization_config', $config, 3600);
    }

    /**
     * Obtenir la configuration actuelle
     */
    public function getCurrentConfig(): array
    {
        return Cache::get('localization_config', $this->getRegionConfig('MA'));
    }

    /**
     * Obtenir la liste des régions disponibles
     */
    public function getAvailableRegions(): array
    {
        return self::REGIONS;
    }

    /**
     * Obtenir la liste des langues disponibles
     */
    public function getAvailableLanguages(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'English',
            'ar' => 'العربية'
        ];
    }

    /**
     * Obtenir la liste des devises disponibles
     */
    public function getAvailableCurrencies(): array
    {
        return [
            'EUR' => 'Euro',
            'USD' => 'Dollar américain'
        ];
    }

    /**
     * Vérifier si une région est supportée
     */
    public function isRegionSupported(string $regionCode): bool
    {
        return isset(self::REGIONS[$regionCode]);
    }

    /**
     * Obtenir le taux de TVA pour une région
     */
    public function getVatRate(string $regionCode = null): float
    {
        if (!$regionCode) {
            $regionCode = 'MA';
        }

        $config = $this->getRegionConfig($regionCode);
        return $config['vat_rate'];
    }

    /**
     * Calculer le montant avec TVA
     */
    public function calculateWithVat(float $amount, string $regionCode = null): float
    {
        $vatRate = $this->getVatRate($regionCode);
        return $amount * (1 + $vatRate / 100);
    }

    /**
     * Calculer le montant sans TVA
     */
    public function calculateWithoutVat(float $amount, string $regionCode = null): float
    {
        $vatRate = $this->getVatRate($regionCode);
        return $amount / (1 + $vatRate / 100);
    }

    /**
     * Obtenir le code téléphonique pour une région
     */
    public function getPhoneCode(string $regionCode = null): string
    {
        if (!$regionCode) {
            $regionCode = 'MA';
        }

        $config = $this->getRegionConfig($regionCode);
        return $config['phone_code'];
    }

    /**
     * Formater un numéro de téléphone
     */
    public function formatPhoneNumber(string $phone, string $regionCode = null): string
    {
        $phoneCode = $this->getPhoneCode($regionCode);
        
        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Ajouter le code pays si nécessaire
        if (!str_starts_with($phone, $phoneCode)) {
            $phone = $phoneCode . $phone;
        }
        
        return $phone;
    }
}
