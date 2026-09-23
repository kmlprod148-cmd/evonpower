<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Services\AdminConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Contrôleur pour la gestion des paramètres des processeurs de paiement et de devise
 */
class PaymentProcessorsController extends Controller
{
    protected $adminConfigService;

    public function __construct(AdminConfigurationService $adminConfigService)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            // Autoriser les admins, super-admins et intégrateurs
            if (!$user->hasRole(['admin', 'super_admin', 'integrator'])) {
                abort(403, 'Accès non autorisé. Seuls les administrateurs peuvent gérer les paramètres des processeurs de paiement.');
            }
            return $next($request);
        });
        
        $this->adminConfigService = $adminConfigService;
    }

    /**
     * Afficher la page de configuration des processeurs de paiement et de devise
     */
    public function index()
    {
        $paymentProcessors = $this->getPaymentProcessorsSettings();
        $currencySettings = $this->getCurrencySettings();
        
        return view('admin.payment-processors', compact('paymentProcessors', 'currencySettings'));
    }

    /**
     * Mettre à jour les paramètres des processeurs de paiement
     */
    public function updatePaymentProcessors(Request $request)
    {
        $validator = $this->validatePaymentProcessors($request);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->updatePaymentProcessorsSettings($request->all());
            
            Log::info('Paramètres des processeurs de paiement mis à jour', [
                'user' => Auth::user()->email,
                'updated_at' => now()
            ]);

            return redirect()->route('admin.payment-processors')
                ->with('success', 'Paramètres des processeurs de paiement mis à jour avec succès !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des paramètres des processeurs de paiement: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage());
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

            return redirect()->route('admin.payment-processors')
                ->with('success', 'Paramètres de devise mis à jour avec succès !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des paramètres de devise: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage());
        }
    }

    /**
     * Tester la connexion CMI
     */
    public function testCmiConnection(Request $request)
    {
        $validator = $this->validateCmiSettings($request);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres CMI invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $testResult = $this->performCmiTest($request->all());
            
            return response()->json([
                'success' => $testResult['success'],
                'message' => $testResult['message'],
                'details' => $testResult['details'] ?? null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du test de connexion CMI: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tester la connexion Stripe
     */
    public function testStripeConnection(Request $request)
    {
        $validator = $this->validateStripeSettings($request);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres Stripe invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $testResult = $this->performStripeTest($request->all());
            
            return response()->json([
                'success' => $testResult['success'],
                'message' => $testResult['message'],
                'details' => $testResult['details'] ?? null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du test de connexion Stripe: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialiser tous les paramètres
     */
    public function reset()
    {
        try {
            $this->resetAllSettings();
            
            Log::info('Paramètres des processeurs de paiement et de devise réinitialisés', [
                'user' => Auth::user()->email,
                'reset_at' => now()
            ]);

            return redirect()->route('admin.payment-processors')
                ->with('success', 'Paramètres réinitialisés aux valeurs par défaut !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la réinitialisation: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les paramètres des processeurs de paiement
     */
    private function getPaymentProcessorsSettings(): array
    {
        // Récupérer depuis la base de données
        $dbSettings = AdminSetting::where('category', 'payment_processors')
            ->get()
            ->keyBy('key');

        // Récupérer depuis config/payments.php comme valeurs par défaut
        $configPayments = config('payments', []);
        
        // Clés sensibles à masquer
        $sensitiveKeys = [
            'cmi_store_key', 'cmi_store_password', 'cmi_client_id', 'cmi_username', 'cmi_password',
            'stripe_secret_key', 'stripe_webhook_secret', 'stripe_public_key'
        ];

        // Structure complète des paramètres
        $defaultSettings = [
            // CMI Settings
            'cmi_base_url' => $configPayments['cmi']['base_url'] ?? 'https://testpayment.cmi.co.ma',
            'cmi_store_key' => '',
            'cmi_store_password' => '',
            'cmi_client_id' => '',
            'cmi_username' => '',
            'cmi_password' => '',
            'cmi_currency' => $configPayments['cmi']['currency'] ?? 'EUR',
            'cmi_test_mode' => $configPayments['cmi']['test_mode'] ?? true,
            'cmi_timeout' => $configPayments['cmi']['timeout'] ?? 30,
            
            // Stripe Settings
            'stripe_public_key' => '',
            'stripe_secret_key' => '',
            'stripe_webhook_secret' => '',
            'stripe_currency' => $configPayments['stripe']['currency'] ?? 'eur',
            'stripe_test_mode' => $configPayments['stripe']['test_mode'] ?? true,
            'stripe_timeout' => $configPayments['stripe']['timeout'] ?? 30,
            
            // Webhook URLs
            'cmi_webhook_success_url' => $configPayments['webhook_urls']['cmi_success'] ?? route('payment.cmi.success'),
            'cmi_webhook_failure_url' => $configPayments['webhook_urls']['cmi_failure'] ?? route('payment.cmi.failure'),
            'stripe_webhook_url' => $configPayments['webhook_urls']['stripe_webhook'] ?? route('payment.stripe.webhook'),
            
            // Security Settings
            'verify_signatures' => $configPayments['security']['verify_signatures'] ?? true,
            'allowed_ips' => $configPayments['security']['allowed_ips'] ?? '',
            'payment_timeout' => $configPayments['security']['timeout'] ?? 30,
        ];

        $result = [];
        foreach ($defaultSettings as $key => $defaultValue) {
            $setting = $dbSettings->get($key);
            
            if ($setting) {
                // Décrypter si nécessaire
                $value = $setting->value;
                if (in_array($key, $sensitiveKeys) && $value) {
                    try {
                        $value = \Illuminate\Support\Facades\Crypt::decryptString($value);
                    } catch (\Exception $e) {
                        // Si le décryptage échoue, c'est peut-être déjà en clair
                        $value = $setting->value;
                    }
                }
                
                $result[$key] = [
                    'value' => in_array($key, $sensitiveKeys) && $value ? '••••••••••••••••' : $value,
                    'raw_value' => $value, // Pour les tests
                    'description' => $setting->description,
                    'type' => $setting->type,
                    'metadata' => is_string($setting->metadata) ? json_decode($setting->metadata, true) : $setting->metadata
                ];
            } else {
                $result[$key] = [
                    'value' => in_array($key, $sensitiveKeys) && $defaultValue ? '••••••••••••••••' : $defaultValue,
                    'raw_value' => $defaultValue,
                    'description' => '',
                    'type' => in_array($key, $sensitiveKeys) ? 'password' : (is_bool($defaultValue) ? 'boolean' : 'text'),
                    'metadata' => null
                ];
            }
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
        foreach ($settings as $key => $setting) {
            $result[$key] = [
                'value' => $setting->value,
                'description' => $setting->description,
                'type' => $setting->type,
                'metadata' => is_string($setting->metadata) ? json_decode($setting->metadata, true) : $setting->metadata
            ];
        }

        return $result;
    }

    /**
     * Valider les paramètres des processeurs de paiement
     */
    private function validatePaymentProcessors(Request $request)
    {
        return Validator::make($request->all(), [
            // CMI Settings
            'cmi_base_url' => 'required|url',
            'cmi_store_key' => 'nullable|string|min:1',
            'cmi_store_password' => 'nullable|string|min:1',
            'cmi_client_id' => 'nullable|string|min:1',
            'cmi_username' => 'nullable|string|min:1',
            'cmi_password' => 'nullable|string|min:1',
            'cmi_currency' => 'required|in:EUR,USD,MAD',
            'cmi_test_mode' => 'nullable|boolean',
            'cmi_timeout' => 'nullable|integer|min:1|max:300',
            
            // Stripe Settings
            'stripe_public_key' => 'nullable|string|min:20',
            'stripe_secret_key' => 'nullable|string|min:20',
            'stripe_webhook_secret' => 'nullable|string|min:10',
            'stripe_currency' => 'required|in:EUR,USD,MAD,eur,usd,mad',
            'stripe_test_mode' => 'nullable|boolean',
            'stripe_timeout' => 'nullable|integer|min:1|max:300',
            
            // Webhook URLs
            'cmi_webhook_success_url' => 'nullable|url',
            'cmi_webhook_failure_url' => 'nullable|url',
            'stripe_webhook_url' => 'nullable|url',
            
            // Security Settings
            'verify_signatures' => 'nullable|boolean',
            'allowed_ips' => 'nullable|string',
            'payment_timeout' => 'nullable|integer|min:1|max:300',
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
     * Valider les paramètres CMI pour les tests
     */
    private function validateCmiSettings(Request $request)
    {
        return Validator::make($request->all(), [
            'cmi_base_url' => 'required|url',
            'cmi_store_key' => 'nullable|string|min:1',
            'cmi_store_password' => 'nullable|string|min:1',
        ]);
    }

    /**
     * Valider les paramètres Stripe pour les tests
     */
    private function validateStripeSettings(Request $request)
    {
        return Validator::make($request->all(), [
            'stripe_secret_key' => 'required|string|min:20',
        ]);
    }

    /**
     * Mettre à jour les paramètres des processeurs de paiement
     */
    private function updatePaymentProcessorsSettings(array $data)
    {
        // Clés sensibles à crypter
        $encryptedKeys = [
            'cmi_store_key', 'cmi_store_password', 'cmi_client_id', 'cmi_username', 'cmi_password',
            'stripe_secret_key', 'stripe_webhook_secret', 'stripe_public_key'
        ];
        
        // Tous les champs autorisés
        $allowedKeys = [
            // CMI
            'cmi_base_url', 'cmi_store_key', 'cmi_store_password', 'cmi_client_id', 
            'cmi_username', 'cmi_password', 'cmi_currency', 'cmi_test_mode', 'cmi_timeout',
            // Stripe
            'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret', 
            'stripe_currency', 'stripe_test_mode', 'stripe_timeout',
            // Webhooks
            'cmi_webhook_success_url', 'cmi_webhook_failure_url', 'stripe_webhook_url',
            // Security
            'verify_signatures', 'allowed_ips', 'payment_timeout'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                // Ignorer les valeurs masquées (••••••••••••••••)
                if ($value === '••••••••••••••••' || (is_string($value) && strpos($value, '•••') === 0)) {
                    continue;
                }
                
                // Ne pas mettre à jour si la valeur est vide et que c'est une clé sensible
                if (in_array($key, $encryptedKeys) && empty($value)) {
                    continue;
                }
                
                $setting = AdminSetting::updateOrCreate(
                    [
                        'category' => 'payment_processors',
                        'key' => $key
                    ],
                    [
                        'type' => in_array($key, $encryptedKeys) ? 'password' : (is_bool($value) ? 'boolean' : 'text'),
                        'is_active' => true,
                        'description' => $this->getFieldDescription($key)
                    ]
                );
                
                // Crypter les clés sensibles
                if (in_array($key, $encryptedKeys) && !empty($value)) {
                    $setting->value = Crypt::encryptString($value);
                } else {
                    // Convertir les booléens en string
                    if (is_bool($value)) {
                        $setting->value = $value ? '1' : '0';
                    } else {
                        $setting->value = (string) $value;
                    }
                }
                
                $setting->save();
            }
        }

        // Clear cache des configurations de paiement
        $paymentConfigService = app(\App\Services\PaymentConfigService::class);
        $paymentConfigService->clearCache();

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('admin_settings_all');
        \Illuminate\Support\Facades\Cache::forget('admin_settings_payment_processors');
    }
    
    /**
     * Obtenir la description d'un champ
     */
    private function getFieldDescription(string $key): string
    {
        $descriptions = [
            'cmi_base_url' => 'URL de base de l\'API CMI',
            'cmi_store_key' => 'Clé de magasin CMI',
            'cmi_store_password' => 'Mot de passe de magasin CMI',
            'cmi_client_id' => 'ID client CMI',
            'cmi_username' => 'Nom d\'utilisateur CMI',
            'cmi_password' => 'Mot de passe CMI',
            'cmi_currency' => 'Devise CMI',
            'cmi_test_mode' => 'Mode test CMI',
            'cmi_timeout' => 'Timeout CMI (secondes)',
            'stripe_public_key' => 'Clé publique Stripe',
            'stripe_secret_key' => 'Clé secrète Stripe',
            'stripe_webhook_secret' => 'Secret webhook Stripe',
            'stripe_currency' => 'Devise Stripe',
            'stripe_test_mode' => 'Mode test Stripe',
            'stripe_timeout' => 'Timeout Stripe (secondes)',
            'cmi_webhook_success_url' => 'URL de callback succès CMI',
            'cmi_webhook_failure_url' => 'URL de callback échec CMI',
            'stripe_webhook_url' => 'URL de webhook Stripe',
            'verify_signatures' => 'Vérifier les signatures des webhooks',
            'allowed_ips' => 'IPs autorisées pour les webhooks',
            'payment_timeout' => 'Timeout général des paiements (secondes)',
        ];
        
        return $descriptions[$key] ?? '';
    }
    
    /**
     * Synchroniser les configurations avec config/payments.php
     */
    private function syncPaymentConfig()
    {
        // Cette méthode peut être utilisée pour mettre à jour dynamiquement
        // le fichier config/payments.php si nécessaire
        // Pour l'instant, on utilise AdminSetting comme source de vérité
    }

    /**
     * Mettre à jour les paramètres de devise
     */
    private function updateCurrencySettings(array $data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, ['app_default_currency', 'app_currency_symbol', 'app_currency_format'])) {
                $setting = AdminSetting::where('category', 'currency')->where('key', $key)->first();
                if ($setting) {
                    $setting->value = $value;
                    $setting->save();
                }
            }
        }

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('admin_settings_all');
        \Illuminate\Support\Facades\Cache::forget('admin_settings_currency');
    }

    /**
     * Effectuer un test de connexion CMI
     */
    private function performCmiTest(array $data)
    {
        try {
            // Test basique de connexion CMI
            // Vérifier que les paramètres essentiels sont présents
            $requiredFields = ['cmi_base_url'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Le champ {$field} est requis"
                    ];
                }
            }
            
            // Tester la connexion à l'URL de base
            $ch = curl_init($data['cmi_base_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 500) {
                return [
                    'success' => true,
                    'message' => 'Test de connexion CMI réussi !',
                    'details' => [
                        'base_url' => $data['cmi_base_url'],
                        'http_code' => $httpCode,
                        'store_key_configured' => !empty($data['cmi_store_key']),
                        'store_password_configured' => !empty($data['cmi_store_password'])
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Impossible de se connecter à l'URL CMI (Code HTTP: {$httpCode})"
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du test: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Effectuer un test de connexion Stripe
     */
    private function performStripeTest(array $data)
    {
        try {
            if (empty($data['stripe_secret_key'])) {
                return [
                    'success' => false,
                    'message' => 'La clé secrète Stripe est requise'
                ];
            }
            
            // Ignorer si c'est une valeur masquée
            if (strpos($data['stripe_secret_key'], '•••') === 0) {
                return [
                    'success' => false,
                    'message' => 'Veuillez saisir une nouvelle clé secrète (les valeurs masquées ne peuvent pas être testées)'
                ];
            }
            
            $stripe = new \Stripe\StripeClient($data['stripe_secret_key']);
            $account = $stripe->accounts->retrieve();
            
            return [
                'success' => true,
                'message' => 'Test de connexion Stripe réussi !',
                'details' => [
                    'environment' => strpos($data['stripe_secret_key'], 'sk_live_') === 0 ? 'production' : 'test',
                    'account_id' => $account->id ?? 'N/A',
                    'country' => $account->country ?? 'N/A'
                ]
            ];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            return [
                'success' => false,
                'message' => 'Erreur d\'authentification Stripe: Clé secrète invalide'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Échec du test de connexion Stripe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Réinitialiser tous les paramètres
     */
    private function resetAllSettings()
    {
        // Supprimer les paramètres existants
        AdminSetting::where('category', 'payment_processors')->delete();
        AdminSetting::where('category', 'currency')->delete();

        // Ré-exécuter les migrations pour insérer les valeurs par défaut
        \Artisan::call('migrate', [
            '--path' => 'database/migrations/2025_10_10_012108_add_cmi_api_keys_to_admin_settings_table.php',
            '--force' => true
        ]);
        
        \Artisan::call('migrate', [
            '--path' => 'database/migrations/2025_10_10_012600_add_stripe_api_keys_to_admin_settings_table.php',
            '--force' => true
        ]);

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('admin_settings_all');
        \Illuminate\Support\Facades\Cache::forget('admin_settings_payment_processors');
        \Illuminate\Support\Facades\Cache::forget('admin_settings_currency');
    }
}
