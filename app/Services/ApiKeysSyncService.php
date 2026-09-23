<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * Service de synchronisation des clés API avec les variables d'environnement
 */
class ApiKeysSyncService
{
    protected array $apiKeyMappings = [
        'currency_api_key' => 'EXCHANGE_RATE_API_KEY',
        'stripe_public_key' => 'STRIPE_PUBLISHABLE_KEY',
        'stripe_secret_key' => 'STRIPE_SECRET_KEY',
        'stripe_webhook_secret' => 'STRIPE_WEBHOOK_SECRET',
        'cmi_store_key' => 'CMI_STOREKEY',
        'cmi_client_id' => 'CMI_CLIENTID',
        'cmi_username' => 'CMI_USERNAME',
        'cmi_password' => 'CMI_PASSWORD',
        'paypal_client_id' => 'PAYPAL_CLIENT_ID',
        'paypal_secret' => 'PAYPAL_SECRET',
        'aws_access_key_id' => 'AWS_ACCESS_KEY_ID',
        'aws_secret_access_key' => 'AWS_SECRET_ACCESS_KEY',
        'aws_region' => 'AWS_DEFAULT_REGION',
        'sentry_dsn' => 'SENTRY_LARAVEL_DSN',
        'google_maps_api_key' => 'GOOGLE_MAPS_API_KEY',
        'twilio_account_sid' => 'TWILIO_ACCOUNT_SID',
        'twilio_auth_token' => 'TWILIO_AUTH_TOKEN',
    ];

    /**
     * Synchronise les clés API depuis la base de données vers les variables d'environnement
     * 
     * @return bool
     */
    public function syncToEnvironment(): bool
    {
        try {
            $apiKeys = $this->getApiKeysFromDatabase();
            $envPath = base_path('.env');
            
            if (!File::exists($envPath)) {
                Log::error('Fichier .env non trouvé');
                return false;
            }

            $envContent = File::get($envPath);
            $updated = false;

            foreach ($apiKeys as $key => $value) {
                $envKey = $this->apiKeyMappings[$key] ?? null;
                
                if ($envKey && !empty($value)) {
                    $pattern = "/^{$envKey}=.*$/m";
                    $replacement = "{$envKey}={$value}";
                    
                    if (preg_match($pattern, $envContent)) {
                        // Mettre à jour la variable existante
                        $envContent = preg_replace($pattern, $replacement, $envContent);
                        $updated = true;
                    } else {
                        // Ajouter la nouvelle variable
                        $envContent .= "\n{$replacement}";
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                File::put($envPath, $envContent);
                Log::info('Variables d\'environnement mises à jour avec les clés API');
                
                // Nettoyer le cache de configuration
                Cache::forget('admin_settings_external_apis');
                
                return true;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation des clés API vers l\'environnement', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Synchronise les variables d'environnement vers la base de données
     * 
     * @return bool
     */
    public function syncFromEnvironment(): bool
    {
        try {
            $updated = false;

            foreach ($this->apiKeyMappings as $dbKey => $envKey) {
                $envValue = env($envKey);
                
                if ($envValue !== null) {
                    $success = $this->updateApiKeyInDatabase($dbKey, $envValue);
                    if ($success) {
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                Log::info('Clés API mises à jour depuis les variables d\'environnement');
                Cache::forget('admin_settings_external_apis');
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation depuis l\'environnement', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtient les clés API depuis la base de données
     * 
     * @return array
     */
    public function getApiKeysFromDatabase(): array
    {
        $apiKeys = [];
        
        $settings = Setting::where('category', 'external_apis')
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        foreach ($this->apiKeyMappings as $dbKey => $envKey) {
            if (isset($settings[$dbKey])) {
                $apiKeys[$dbKey] = $settings[$dbKey]->value;
            }
        }

        return $apiKeys;
    }

    /**
     * Met à jour une clé API dans la base de données
     * 
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function updateApiKeyInDatabase(string $key, string $value): bool
    {
        try {
            Setting::updateOrCreate(
                ['key' => $key, 'category' => 'external_apis'],
                [
                    'value' => $value,
                    'is_active' => true,
                    'updated_at' => now()
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la clé API en base', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Valide une clé API selon son type
     * 
     * @param string $key
     * @param string $value
     * @return array
     */
    public function validateApiKey(string $key, string $value): array
    {
        $validation = [
            'valid' => true,
            'message' => 'Clé API valide',
            'errors' => []
        ];

        switch ($key) {
            case 'stripe_public_key':
                if (!preg_match('/^pk_(test_|live_)[a-zA-Z0-9]{24,}$/', $value)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Format de clé publique Stripe invalide';
                }
                break;

            case 'stripe_secret_key':
                if (!preg_match('/^sk_(test_|live_)[a-zA-Z0-9]{24,}$/', $value)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Format de clé secrète Stripe invalide';
                }
                break;

            case 'stripe_webhook_secret':
                if (!preg_match('/^whsec_[a-zA-Z0-9]{32,}$/', $value)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Format de secret webhook Stripe invalide';
                }
                break;

            case 'aws_access_key_id':
                if (!preg_match('/^AKIA[0-9A-Z]{16}$/', $value)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Format de clé d\'accès AWS invalide';
                }
                break;

            case 'google_maps_api_key':
                if (!preg_match('/^AIza[0-9A-Za-z_-]{35}$/', $value)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Format de clé API Google Maps invalide';
                }
                break;

            case 'twilio_account_sid':
                if (!preg_match('/^AC[a-f0-9]{32}$/', $value)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Format de SID Twilio invalide';
                }
                break;

            case 'sentry_dsn':
                if (!preg_match('/^https:\/\/[a-f0-9]{32}@[0-9]+\.ingest\.sentry\.io\/[0-9]+$/', $value)) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Format de DSN Sentry invalide';
                }
                break;
        }

        if (!$validation['valid']) {
            $validation['message'] = implode(', ', $validation['errors']);
        }

        return $validation;
    }

    /**
     * Obtient le statut de synchronisation
     * 
     * @return array
     */
    public function getSyncStatus(): array
    {
        $dbKeys = $this->getApiKeysFromDatabase();
        $envKeys = [];
        
        foreach ($this->apiKeyMappings as $dbKey => $envKey) {
            $envValue = env($envKey);
            if ($envValue !== null) {
                $envKeys[$dbKey] = $envValue;
            }
        }

        $synchronized = [];
        $outOfSync = [];

        foreach ($this->apiKeyMappings as $dbKey => $envKey) {
            $dbValue = $dbKeys[$dbKey] ?? null;
            $envValue = $envKeys[$dbKey] ?? null;

            if ($dbValue === $envValue) {
                $synchronized[] = $dbKey;
            } else {
                $outOfSync[] = [
                    'key' => $dbKey,
                    'db_value' => $dbValue,
                    'env_value' => $envValue
                ];
            }
        }

        return [
            'synchronized' => $synchronized,
            'out_of_sync' => $outOfSync,
            'total_keys' => count($this->apiKeyMappings),
            'sync_percentage' => count($synchronized) / count($this->apiKeyMappings) * 100
        ];
    }

    /**
     * Force la synchronisation bidirectionnelle
     * 
     * @return bool
     */
    public function forceSync(): bool
    {
        $syncToEnv = $this->syncToEnvironment();
        $syncFromEnv = $this->syncFromEnvironment();
        
        return $syncToEnv && $syncFromEnv;
    }
}
