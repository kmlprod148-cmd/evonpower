<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service pour la gestion de la devise de l'application
 */
class AppCurrencyService
{
    const SUPPORTED_CURRENCIES = ['EUR', 'USD'];
    
    const CURRENCY_SYMBOLS = [
        'EUR' => '€',
        'USD' => '$'
    ];
    
    const CURRENCY_NAMES = [
        'EUR' => 'Euro',
        'USD' => 'Dollar américain'
    ];

    /**
     * Obtient la devise par défaut de l'application
     */
    public function getDefaultCurrency(): string
    {
        return Cache::remember('app_default_currency', 3600, function () {
            // Chercher d'abord dans 'currency', puis dans 'system' pour compatibilité
            $setting = AdminSetting::where('category', 'currency')
                ->where('key', 'app_default_currency')
                ->first();
            
            if (!$setting) {
                $setting = AdminSetting::where('category', 'system')
                    ->where('key', 'app_default_currency')
                    ->first();
            }
                
            return $setting?->value ?? 'EUR';
        });
    }

    /**
     * Définit la devise par défaut de l'application
     */
    public function setDefaultCurrency(string $currency): bool
    {
        if (!in_array($currency, self::SUPPORTED_CURRENCIES)) {
            return false;
        }

        try {
            // Mettre à jour la devise (utiliser 'currency' comme catégorie pour cohérence)
            AdminSetting::updateOrCreate(
                [
                    'category' => 'currency',
                    'key' => 'app_default_currency'
                ],
                [
                    'value' => $currency,
                    'description' => 'Devise par défaut de l\'application',
                    'updated_at' => now()
                ]
            );

            // Mettre à jour le symbole
            AdminSetting::updateOrCreate(
                [
                    'category' => 'currency',
                    'key' => 'app_currency_symbol'
                ],
                [
                    'value' => self::CURRENCY_SYMBOLS[$currency],
                    'description' => 'Symbole de la devise de l\'application',
                    'updated_at' => now()
                ]
            );

            Cache::forget('app_default_currency');
            Cache::forget('app_currency_symbol');
            
            Log::info('Devise de l\'application mise à jour', [
                'currency' => $currency,
                'symbol' => self::CURRENCY_SYMBOLS[$currency]
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la devise: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtient le symbole de la devise
     */
    public function getCurrencySymbol(?string $currency = null): string
    {
        $currency = $currency ?? $this->getDefaultCurrency();
        return self::CURRENCY_SYMBOLS[$currency] ?? $currency;
    }

    /**
     * Obtient le nom de la devise
     */
    public function getCurrencyName(string $currency): string
    {
        return self::CURRENCY_NAMES[$currency] ?? $currency;
    }

    /**
     * Obtient toutes les devises supportées
     */
    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Obtient les devises avec leurs informations complètes
     */
    public function getCurrenciesWithInfo(): array
    {
        $currencies = [];
        
        foreach (self::SUPPORTED_CURRENCIES as $currency) {
            $currencies[$currency] = [
                'code' => $currency,
                'name' => self::CURRENCY_NAMES[$currency],
                'symbol' => self::CURRENCY_SYMBOLS[$currency],
                'is_default' => $currency === $this->getDefaultCurrency()
            ];
        }
        
        return $currencies;
    }

    /**
     * Formate un montant avec la devise
     */
    public function formatAmount(float $amount, ?string $currency = null, int $decimals = 2): string
    {
        $currency = $currency ?? $this->getDefaultCurrency();
        $symbol = $this->getCurrencySymbol($currency);
        
        $formattedAmount = number_format($amount, $decimals, ',', ' ');
        
        return $formattedAmount . ' ' . $symbol;
    }

    /**
     * Vérifie si une devise est supportée
     */
    public function isCurrencySupported(string $currency): bool
    {
        return in_array($currency, self::SUPPORTED_CURRENCIES);
    }

    /**
     * Obtient la configuration de formatage
     */
    public function getFormattingConfig(): array
    {
        // Chercher d'abord dans 'currency', puis dans 'system' pour compatibilité
        $setting = AdminSetting::where('category', 'currency')
            ->where('key', 'app_currency_format')
            ->first();
        
        if (!$setting) {
            $setting = AdminSetting::where('category', 'system')
                ->where('key', 'app_currency_format')
                ->first();
        }
            
        return [
            'decimals' => (int) ($setting?->value ?? 2),
            'decimal_separator' => ',',
            'thousands_separator' => ' '
        ];
    }

    /**
     * Met à jour la configuration de formatage
     */
    public function updateFormattingConfig(int $decimals): bool
    {
        try {
            AdminSetting::updateOrCreate(
                [
                    'category' => 'currency',
                    'key' => 'app_currency_format'
                ],
                [
                    'value' => (string) $decimals,
                    'description' => 'Nombre de décimales pour la devise',
                    'updated_at' => now()
                ]
            );
            
            Cache::forget('app_currency_format');
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du formatage: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtient les statistiques d'utilisation des devises
     */
    public function getCurrencyStats(): array
    {
        $stats = [];
        
        foreach (self::SUPPORTED_CURRENCIES as $currency) {
            $stats[$currency] = [
                'code' => $currency,
                'name' => self::CURRENCY_NAMES[$currency],
                'symbol' => self::CURRENCY_SYMBOLS[$currency],
                'usage_count' => 0, // À implémenter selon les besoins
                'is_default' => $currency === $this->getDefaultCurrency()
            ];
        }
        
        return $stats;
    }

    /**
     * Valide une devise
     */
    public function validateCurrency(string $currency): array
    {
        $errors = [];
        
        if (empty($currency)) {
            $errors[] = 'La devise est requise';
        } elseif (!in_array($currency, self::SUPPORTED_CURRENCIES)) {
            $errors[] = 'Devise non supportée. Devises disponibles: ' . implode(', ', self::SUPPORTED_CURRENCIES);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Obtient la configuration complète des devises
     */
    public function getCurrencyConfiguration(): array
    {
        return [
            'default_currency' => $this->getDefaultCurrency(),
            'currency_symbol' => $this->getCurrencySymbol(),
            'supported_currencies' => $this->getSupportedCurrencies(),
            'currencies_with_info' => $this->getCurrenciesWithInfo(),
            'formatting_config' => $this->getFormattingConfig(),
            'stats' => $this->getCurrencyStats()
        ];
    }
}
