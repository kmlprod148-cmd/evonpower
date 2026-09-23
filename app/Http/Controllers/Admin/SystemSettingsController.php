<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Services\AdminConfigurationService;
use App\Services\AppCurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

/**
 * Contrôleur pour la gestion des paramètres système
 * API Keys, WebSocket URLs, Devise
 */
class SystemSettingsController extends Controller
{
    protected $adminConfigService;
    protected $currencyService;

    public function __construct(
        AdminConfigurationService $adminConfigService,
        AppCurrencyService $currencyService
    ) {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            // Autoriser les admins, super-admins et intégrateurs
            if (!$user->hasRole(['admin', 'super_admin', 'integrator'])) {
                abort(403, 'Accès non autorisé. Seuls les administrateurs peuvent gérer les paramètres système.');
            }
            return $next($request);
        });
        
        $this->adminConfigService = $adminConfigService;
        $this->currencyService = $currencyService;
    }

    /**
     * Afficher la page de configuration des paramètres système
     */
    public function index()
    {
        $apiKeys = $this->getApiKeysSettings();
        $websocketUrls = $this->getWebSocketUrlsSettings();
        $currencySettings = $this->getCurrencySettings();
        
        return view('admin.system-settings', compact('apiKeys', 'websocketUrls', 'currencySettings'));
    }

    /**
     * Mettre à jour les API keys
     */
    public function updateApiKeys(Request $request)
    {
        $validator = $this->validateApiKeys($request);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->updateApiKeysSettings($request->all());
            
            Log::info('API Keys mises à jour', [
                'user' => Auth::user()->email,
                'updated_at' => now()
            ]);

            return redirect()->route('admin.system-settings.index')
                ->with('success', 'API Keys mises à jour avec succès !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des API Keys: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour les URLs WebSocket
     */
    public function updateWebSocketUrls(Request $request)
    {
        $validator = $this->validateWebSocketUrls($request);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->updateWebSocketUrlsSettings($request->all());
            
            Log::info('WebSocket URLs mises à jour', [
                'user' => Auth::user()->email,
                'updated_at' => now()
            ]);

            return redirect()->route('admin.system-settings.index')
                ->with('success', 'WebSocket URLs mises à jour avec succès !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des WebSocket URLs: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour les paramètres de devise
     */
    public function updateCurrency(Request $request)
    {
        $validator = $this->validateCurrency($request);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->updateCurrencySettings($request->all());
            
            Log::info('Paramètres de devise mis à jour', [
                'user' => Auth::user()->email,
                'updated_at' => now()
            ]);

            return redirect()->route('admin.system-settings.index')
                ->with('success', 'Paramètres de devise mis à jour avec succès !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la devise: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les paramètres des API keys
     */
    private function getApiKeysSettings(): array
    {
        // Récupérer les paramètres de la catégorie 'api'
        $dbSettings = AdminSetting::where('category', 'api')
            ->get()
            ->keyBy('key');

        // Récupérer les paramètres de la catégorie 'external_apis' pour Stripe et CMI
        $externalApiSettings = AdminSetting::where('category', 'external_apis')
            ->whereIn('key', [
                // Stripe keys
                'stripe_test_publishable_key',
                'stripe_test_secret_key',
                'stripe_test_webhook_secret',
                'stripe_prod_publishable_key',
                'stripe_prod_secret_key',
                'stripe_prod_webhook_secret',
                'stripe_environment',
                // CMI keys
                'cmi_test_api_key',
                'cmi_test_merchant_id',
                'cmi_prod_api_key',
                'cmi_prod_merchant_id',
                'cmi_environment',
            ])
            ->get()
            ->keyBy('key');

        $sensitiveKeys = [
            'steve_api_key', 'steve_api_user', 'steve_api_pass',
            'currency_api_key', 'external_api_key',
            // Stripe sensitive keys
            'stripe_test_secret_key', 'stripe_test_webhook_secret',
            'stripe_prod_secret_key', 'stripe_prod_webhook_secret',
            // CMI sensitive keys
            'cmi_test_api_key', 'cmi_prod_api_key'
        ];

        $defaultSettings = [
            'steve_api_key' => config('services.steve.key', ''),
            'steve_api_user' => config('services.steve.user', 'admin'),
            'steve_api_pass' => config('services.steve.pass', ''),
            'currency_api_key' => config('admin_settings.external_apis.currency_api_key.value', ''),
            'external_api_key' => '',
            // Stripe defaults
            'stripe_test_publishable_key' => '',
            'stripe_test_secret_key' => '',
            'stripe_test_webhook_secret' => '',
            'stripe_prod_publishable_key' => '',
            'stripe_prod_secret_key' => '',
            'stripe_prod_webhook_secret' => '',
            'stripe_environment' => 'test',
            // CMI defaults
            'cmi_test_api_key' => '',
            'cmi_test_merchant_id' => '',
            'cmi_prod_api_key' => '',
            'cmi_prod_merchant_id' => '',
            'cmi_environment' => 'test',
        ];

        $result = [];
        foreach ($defaultSettings as $key => $defaultValue) {
            // Chercher d'abord dans external_apis, puis dans api
            $setting = $externalApiSettings->get($key) ?? $dbSettings->get($key);
            
            if ($setting) {
                $value = $setting->value;
                if (in_array($key, $sensitiveKeys) && $value) {
                    try {
                        $value = Crypt::decryptString($value);
                    } catch (\Exception $e) {
                        $value = $setting->value;
                    }
                }
                
                $result[$key] = [
                    'value' => in_array($key, $sensitiveKeys) && $value ? '••••••••••••••••' : $value,
                    'raw_value' => $value,
                ];
            } else {
                $result[$key] = [
                    'value' => in_array($key, $sensitiveKeys) && $defaultValue ? '••••••••••••••••' : $defaultValue,
                    'raw_value' => $defaultValue,
                ];
            }
        }

        return $result;
    }

    /**
     * Obtenir les paramètres des URLs WebSocket
     */
    private function getWebSocketUrlsSettings(): array
    {
        $dbSettings = AdminSetting::where('category', 'websocket')
            ->get()
            ->keyBy('key');

        $defaultSettings = [
            'steve_websocket_base_url' => config('steve.websocket_url', 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/'),
            'steve_api_url' => config('steve.api_url', 'http://158.69.27.239:8080'),
            'websocket_timeout' => config('steve.websocket_timeout', 30),
        ];

        $result = [];
        foreach ($defaultSettings as $key => $defaultValue) {
            $setting = $dbSettings->get($key);
            
            $result[$key] = [
                'value' => $setting ? $setting->value : $defaultValue,
            ];
        }

        return $result;
    }

    /**
     * Obtenir les paramètres de devise
     */
    private function getCurrencySettings(): array
    {
        $settings = AdminSetting::where('category', 'currency')
            ->get()
            ->keyBy('key');

        $result = [];
        $defaultCurrency = $this->currencyService->getDefaultCurrency();
        
        $result['app_default_currency'] = [
            'value' => $settings->get('app_default_currency')?->value ?? $defaultCurrency,
        ];
        
        $result['app_currency_symbol'] = [
            'value' => $settings->get('app_currency_symbol')?->value ?? ($defaultCurrency === 'EUR' ? '€' : '$'),
        ];
        
        $result['app_currency_format'] = [
            'value' => $settings->get('app_currency_format')?->value ?? '2',
        ];

        return $result;
    }

    /**
     * Valider les API keys
     */
    private function validateApiKeys(Request $request)
    {
        return Validator::make($request->all(), [
            'steve_api_key' => 'nullable|string|min:1',
            'steve_api_user' => 'nullable|string|min:1',
            'steve_api_pass' => 'nullable|string|min:1',
            'currency_api_key' => 'nullable|string|min:1',
            'external_api_key' => 'nullable|string|min:1',
            // Stripe validation
            'stripe_test_publishable_key' => 'nullable|string|min:20',
            'stripe_test_secret_key' => 'nullable|string|min:20',
            'stripe_test_webhook_secret' => 'nullable|string|min:20',
            'stripe_prod_publishable_key' => 'nullable|string|min:20',
            'stripe_prod_secret_key' => 'nullable|string|min:20',
            'stripe_prod_webhook_secret' => 'nullable|string|min:20',
            'stripe_environment' => 'nullable|in:test,prod',
            // CMI validation
            'cmi_test_api_key' => 'nullable|string|min:10',
            'cmi_test_merchant_id' => 'nullable|string|min:3',
            'cmi_prod_api_key' => 'nullable|string|min:10',
            'cmi_prod_merchant_id' => 'nullable|string|min:3',
            'cmi_environment' => 'nullable|in:test,prod',
        ]);
    }

    /**
     * Valider les URLs WebSocket
     */
    private function validateWebSocketUrls(Request $request)
    {
        return Validator::make($request->all(), [
            'steve_websocket_base_url' => 'required|string|min:1',
            'steve_api_url' => 'required|url',
            'websocket_timeout' => 'nullable|integer|min:1|max:300',
        ]);
    }

    /**
     * Valider les paramètres de devise
     */
    private function validateCurrency(Request $request)
    {
        return Validator::make($request->all(), [
            'app_default_currency' => 'required|in:EUR,USD',
            'app_currency_symbol' => 'required|string|max:5',
            'app_currency_format' => 'required|in:0,1,2',
        ]);
    }

    /**
     * Mettre à jour les API keys
     */
    private function updateApiKeysSettings(array $data)
    {
        $encryptedKeys = [
            'steve_api_key', 'steve_api_user', 'steve_api_pass',
            'currency_api_key', 'external_api_key',
            // Stripe encrypted keys
            'stripe_test_secret_key', 'stripe_test_webhook_secret',
            'stripe_prod_secret_key', 'stripe_prod_webhook_secret',
            // CMI encrypted keys
            'cmi_test_api_key', 'cmi_prod_api_key'
        ];
        
        // Clés de la catégorie 'api'
        $apiCategoryKeys = [
            'steve_api_key', 'steve_api_user', 'steve_api_pass',
            'currency_api_key', 'external_api_key'
        ];
        
        // Clés de la catégorie 'external_apis'
        $externalApiCategoryKeys = [
            // Stripe keys
            'stripe_test_publishable_key', 'stripe_test_secret_key',
            'stripe_test_webhook_secret', 'stripe_prod_publishable_key',
            'stripe_prod_secret_key', 'stripe_prod_webhook_secret',
            'stripe_environment',
            // CMI keys
            'cmi_test_api_key', 'cmi_test_merchant_id',
            'cmi_prod_api_key', 'cmi_prod_merchant_id',
            'cmi_environment',
        ];
        
        $allowedKeys = array_merge($apiCategoryKeys, $externalApiCategoryKeys);
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                // Ignorer les valeurs masquées
                if ($value === '••••••••••••••••' || (is_string($value) && strpos($value, '•••') === 0)) {
                    continue;
                }
                
                // Ne pas mettre à jour si la valeur est vide et que c'est une clé sensible
                if (in_array($key, $encryptedKeys) && empty($value)) {
                    continue;
                }
                
                // Déterminer la catégorie
                $category = in_array($key, $externalApiCategoryKeys) ? 'external_apis' : 'api';
                
                $setting = AdminSetting::updateOrCreate(
                    [
                        'category' => $category,
                        'key' => $key
                    ],
                    [
                        'type' => in_array($key, $encryptedKeys) ? 'password' : 'text',
                        'is_active' => true,
                        'description' => $this->getFieldDescription($key)
                    ]
                );
                
                // Crypter les clés sensibles
                if (in_array($key, $encryptedKeys) && !empty($value)) {
                    $setting->value = Crypt::encryptString($value);
                } else {
                    $setting->value = (string) $value;
                }
                
                $setting->save();
            }
        }

        // Synchroniser les clés actives avec les variables d'environnement selon l'environnement sélectionné
        $this->syncActiveKeysToEnvironment($data);

        // Clear cache
        Cache::forget('admin_settings_api');
        Cache::forget('admin_settings_external_apis');
        Cache::forget('admin_settings_all');
        
        // Vider le cache des clés de paiement actives
        if (class_exists(\App\Services\PaymentKeysService::class)) {
            app(\App\Services\PaymentKeysService::class)->clearCache();
        }
    }

    /**
     * Synchronise les clés actives avec les variables d'environnement selon l'environnement sélectionné
     */
    private function syncActiveKeysToEnvironment(array $data)
    {
        try {
            $envPath = base_path('.env');
            
            if (!\Illuminate\Support\Facades\File::exists($envPath)) {
                Log::warning('Fichier .env non trouvé, impossible de synchroniser les clés');
                return;
            }

            $envContent = \Illuminate\Support\Facades\File::get($envPath);
            $updated = false;

            // Récupérer les paramètres depuis AdminSetting (après sauvegarde)
            $settings = AdminSetting::where('category', 'external_apis')
                ->whereIn('key', [
                    'stripe_test_publishable_key', 'stripe_test_secret_key', 'stripe_test_webhook_secret',
                    'stripe_prod_publishable_key', 'stripe_prod_secret_key', 'stripe_prod_webhook_secret',
                    'stripe_environment',
                    'cmi_test_api_key', 'cmi_test_merchant_id',
                    'cmi_prod_api_key', 'cmi_prod_merchant_id',
                    'cmi_environment',
                ])
                ->get()
                ->keyBy('key');

            // Récupérer l'environnement actif pour Stripe
            $stripeEnv = $data['stripe_environment'] ?? $settings->get('stripe_environment')?->value ?? 'test';
            
            // Synchroniser les clés Stripe selon l'environnement
            if ($stripeEnv === 'prod') {
                $stripePublishableKey = $this->getSettingValue($settings->get('stripe_prod_publishable_key'));
                $stripeSecretKey = $this->getSettingValue($settings->get('stripe_prod_secret_key'), true);
                $stripeWebhookSecret = $this->getSettingValue($settings->get('stripe_prod_webhook_secret'), true);
            } else {
                $stripePublishableKey = $this->getSettingValue($settings->get('stripe_test_publishable_key'));
                $stripeSecretKey = $this->getSettingValue($settings->get('stripe_test_secret_key'), true);
                $stripeWebhookSecret = $this->getSettingValue($settings->get('stripe_test_webhook_secret'), true);
            }

            // Mettre à jour STRIPE_PUBLISHABLE_KEY
            if (!empty($stripePublishableKey)) {
                $envContent = $this->updateEnvVariable($envContent, 'STRIPE_PUBLISHABLE_KEY', $stripePublishableKey);
                $updated = true;
            }

            // Mettre à jour STRIPE_SECRET_KEY
            if (!empty($stripeSecretKey)) {
                $envContent = $this->updateEnvVariable($envContent, 'STRIPE_SECRET_KEY', $stripeSecretKey);
                $updated = true;
            }

            // Mettre à jour STRIPE_WEBHOOK_SECRET
            if (!empty($stripeWebhookSecret)) {
                $envContent = $this->updateEnvVariable($envContent, 'STRIPE_WEBHOOK_SECRET', $stripeWebhookSecret);
                $updated = true;
            }

            // Récupérer l'environnement actif pour CMI
            $cmiEnv = $data['cmi_environment'] ?? $settings->get('cmi_environment')?->value ?? 'test';
            
            // Synchroniser les clés CMI selon l'environnement
            if ($cmiEnv === 'prod') {
                $cmiApiKey = $this->getSettingValue($settings->get('cmi_prod_api_key'), true);
                $cmiMerchantId = $this->getSettingValue($settings->get('cmi_prod_merchant_id'));
            } else {
                $cmiApiKey = $this->getSettingValue($settings->get('cmi_test_api_key'), true);
                $cmiMerchantId = $this->getSettingValue($settings->get('cmi_test_merchant_id'));
            }

            // Mettre à jour CMI_STOREKEY (utilisé comme clé API)
            if (!empty($cmiApiKey)) {
                $envContent = $this->updateEnvVariable($envContent, 'CMI_STOREKEY', $cmiApiKey);
                $updated = true;
            }

            // Mettre à jour CMI_CLIENTID (utilisé comme merchant ID)
            if (!empty($cmiMerchantId)) {
                $envContent = $this->updateEnvVariable($envContent, 'CMI_CLIENTID', $cmiMerchantId);
                $updated = true;
            }

            if ($updated) {
                \Illuminate\Support\Facades\File::put($envPath, $envContent);
                Log::info('Variables d\'environnement mises à jour avec les clés API actives', [
                    'stripe_env' => $stripeEnv,
                    'cmi_env' => $cmiEnv
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation des clés vers l\'environnement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Récupère la valeur d'un AdminSetting, décryptée si nécessaire
     */
    private function getSettingValue($setting, bool $isEncrypted = false): string
    {
        if (!$setting || empty($setting->value)) {
            return '';
        }

        if ($isEncrypted) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Exception $e) {
                // Si le décryptage échoue, retourner la valeur telle quelle
                return $setting->value;
            }
        }

        return $setting->value;
    }

    /**
     * Met à jour une variable d'environnement dans le contenu .env
     */
    private function updateEnvVariable(string $envContent, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*$/m";
        $replacement = "{$key}={$value}";
        
        if (preg_match($pattern, $envContent)) {
            // Mettre à jour la variable existante
            return preg_replace($pattern, $replacement, $envContent);
        } else {
            // Ajouter la nouvelle variable
            return $envContent . "\n{$replacement}";
        }
    }


    /**
     * Mettre à jour les URLs WebSocket
     */
    private function updateWebSocketUrlsSettings(array $data)
    {
        $allowedKeys = [
            'steve_websocket_base_url', 'steve_api_url', 'websocket_timeout'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $setting = AdminSetting::updateOrCreate(
                    [
                        'category' => 'websocket',
                        'key' => $key
                    ],
                    [
                        'type' => 'url',
                        'is_active' => true,
                        'description' => $this->getFieldDescription($key)
                    ]
                );
                
                $setting->value = (string) $value;
                $setting->save();
            }
        }

        // Clear cache
        Cache::forget('admin_settings_websocket');
        Cache::forget('admin_settings_all');
    }

    /**
     * Mettre à jour les paramètres de devise
     */
    private function updateCurrencySettings(array $data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, ['app_default_currency', 'app_currency_symbol', 'app_currency_format'])) {
                $setting = AdminSetting::updateOrCreate(
                    [
                        'category' => 'currency',
                        'key' => $key
                    ],
                    [
                        'type' => 'text',
                        'is_active' => true,
                        'description' => $this->getFieldDescription($key)
                    ]
                );
                
                $setting->value = (string) $value;
                $setting->save();
            }
        }

        // Mettre à jour le service de devise
        if (isset($data['app_default_currency'])) {
            $this->currencyService->setDefaultCurrency($data['app_default_currency']);
        }

        // Clear cache
        Cache::forget('admin_settings_currency');
        Cache::forget('admin_settings_all');
        Cache::forget('app_default_currency');
    }
    
    /**
     * Obtenir la description d'un champ
     */
    private function getFieldDescription(string $key): string
    {
        $descriptions = [
            'steve_api_key' => 'Clé API SteVe',
            'steve_api_user' => 'Nom d\'utilisateur API SteVe',
            'steve_api_pass' => 'Mot de passe API SteVe',
            'currency_api_key' => 'Clé API pour les taux de change',
            'external_api_key' => 'Clé API externe',
            // Stripe descriptions
            'stripe_test_publishable_key' => 'Clé publique Stripe pour l\'environnement de test',
            'stripe_test_secret_key' => 'Clé secrète Stripe pour l\'environnement de test',
            'stripe_test_webhook_secret' => 'Secret webhook Stripe pour l\'environnement de test',
            'stripe_prod_publishable_key' => 'Clé publique Stripe pour l\'environnement de production',
            'stripe_prod_secret_key' => 'Clé secrète Stripe pour l\'environnement de production',
            'stripe_prod_webhook_secret' => 'Secret webhook Stripe pour l\'environnement de production',
            'stripe_environment' => 'Environnement actif Stripe (test ou prod)',
            // CMI descriptions
            'cmi_test_api_key' => 'Clé API CMI pour l\'environnement de test',
            'cmi_test_merchant_id' => 'ID marchand CMI pour l\'environnement de test',
            'cmi_prod_api_key' => 'Clé API CMI pour l\'environnement de production',
            'cmi_prod_merchant_id' => 'ID marchand CMI pour l\'environnement de production',
            'cmi_environment' => 'Environnement actif CMI (test ou prod)',
            // WebSocket descriptions
            'steve_websocket_base_url' => 'URL de base WebSocket SteVe',
            'steve_api_url' => 'URL de l\'API SteVe',
            'websocket_timeout' => 'Timeout WebSocket (secondes)',
            // Currency descriptions
            'app_default_currency' => 'Devise par défaut de l\'application',
            'app_currency_symbol' => 'Symbole de devise',
            'app_currency_format' => 'Format de devise',
        ];
        
        return $descriptions[$key] ?? '';
    }
}

