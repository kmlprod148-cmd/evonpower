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
 * Contrôleur pour la gestion des paramètres API Stripe
 */
class StripeApiSettingsController extends Controller
{
    protected $adminConfigService;

    public function __construct(AdminConfigurationService $adminConfigService)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            // Autoriser les admins, super-admins
            if (!$user->hasRole(['admin', 'super_admin'])) {
                abort(403, 'Accès non autorisé. Seuls les administrateurs peuvent gérer les paramètres API Stripe.');
            }
            return $next($request);
        });
        
        $this->adminConfigService = $adminConfigService;
    }

    /**
     * Afficher la page de configuration des API Stripe
     */
    public function index()
    {
        $stripeSettings = $this->getStripeSettings();
        $currencySettings = $this->getCurrencySettings();
        
        return view('admin.stripe-api-settings', compact('stripeSettings', 'currencySettings'));
    }

    /**
     * Mettre à jour les paramètres Stripe
     */
    public function update(Request $request)
    {
        $validator = $this->validateStripeSettings($request);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->updateStripeSettings($request->all());
            
            Log::info('Paramètres Stripe mis à jour', [
                'user' => Auth::user()->email,
                'updated_at' => now()
            ]);

            return redirect()->route('admin.stripe-api-settings')
                ->with('success', 'Paramètres Stripe mis à jour avec succès !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des paramètres Stripe: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour la devise de l'application
     */
    public function updateCurrency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_default_currency' => 'required|in:EUR,USD',
        ], [
            'app_default_currency.required' => 'La devise est requise',
            'app_default_currency.in' => 'La devise doit être EUR ou USD'
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $currency = $request->app_default_currency;
            $this->updateApplicationCurrency($currency);
            
            Log::info('Devise de l\'application mise à jour', [
                'user' => Auth::user()->email,
                'currency' => $currency,
                'updated_at' => now()
            ]);

            return redirect()->route('admin.stripe-api-settings')
                ->with('success', 'Devise de l\'application mise à jour avec succès !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la devise: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la mise à jour de la devise: ' . $e->getMessage());
        }
    }

    /**
     * Tester la connexion Stripe
     */
    public function testConnection(Request $request)
    {
        $testEnvironment = $request->input('test_environment', 'test');
        
        // Validation spécifique selon l'environnement testé
        $rules = [];
        if ($testEnvironment === 'test') {
            $rules = [
                'stripe_test_publishable_key' => 'required|string|min:20',
                'stripe_test_secret_key' => 'required|string|min:20'
            ];
        } else {
            $rules = [
                'stripe_prod_publishable_key' => 'required|string|min:20',
                'stripe_prod_secret_key' => 'required|string|min:20'
            ];
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['test_environment'] = $testEnvironment;
            $testResult = $this->performStripeTest($data);
            
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
     * Réinitialiser les paramètres Stripe
     */
    public function reset()
    {
        try {
            $this->resetStripeSettings();
            
            Log::info('Paramètres Stripe réinitialisés', [
                'user' => Auth::user()->email,
                'reset_at' => now()
            ]);

            return redirect()->route('admin.stripe-api-settings')
                ->with('success', 'Paramètres Stripe réinitialisés aux valeurs par défaut !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation des paramètres Stripe: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la réinitialisation: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les paramètres Stripe actuels
     */
    private function getStripeSettings(): array
    {
        $settings = AdminSetting::where('category', 'external_apis')
            ->whereIn('key', [
                // Test keys
                'stripe_test_publishable_key',
                'stripe_test_secret_key',
                'stripe_test_webhook_secret',
                'stripe_test_webhook_url',
                // Prod keys
                'stripe_prod_publishable_key',
                'stripe_prod_secret_key',
                'stripe_prod_webhook_secret',
                'stripe_prod_webhook_url',
                // Common settings
                'stripe_environment',
                'stripe_currency'
            ])
            ->get()
            ->keyBy('key');

        $encryptedKeys = [
            'stripe_test_secret_key',
            'stripe_test_webhook_secret',
            'stripe_prod_secret_key',
            'stripe_prod_webhook_secret'
        ];

        $result = [];
        foreach ($settings as $key => $setting) {
            $value = $setting->value;
            
            // Décrypter les clés sensibles pour l'affichage
            if (in_array($key, $encryptedKeys) && $value) {
                try {
                    $decrypted = Crypt::decryptString($value);
                    $value = '••••••••••••••••';
                } catch (\Exception $e) {
                    $value = $setting->value ? '••••••••••••••••' : '';
                }
            }
            
            $result[$key] = [
                'value' => $value,
                'description' => $setting->description,
                'type' => $setting->type,
                'metadata' => is_string($setting->metadata) ? json_decode($setting->metadata, true) : $setting->metadata
            ];
        }

        // Valeurs par défaut si non définies
        $defaultKeys = [
            'stripe_test_publishable_key' => '',
            'stripe_test_secret_key' => '',
            'stripe_test_webhook_secret' => '',
            'stripe_test_webhook_url' => '',
            'stripe_prod_publishable_key' => '',
            'stripe_prod_secret_key' => '',
            'stripe_prod_webhook_secret' => '',
            'stripe_prod_webhook_url' => '',
            'stripe_environment' => 'test',
            'stripe_currency' => 'EUR'
        ];

        foreach ($defaultKeys as $key => $defaultValue) {
            if (!isset($result[$key])) {
                $result[$key] = [
                    'value' => $defaultValue,
                    'description' => '',
                    'type' => 'text',
                    'metadata' => null
                ];
            }
        }

        return $result;
    }

    /**
     * Obtenir les paramètres de devise actuels
     */
    private function getCurrencySettings(): array
    {
        $settings = AdminSetting::where('category', 'system')
            ->whereIn('key', [
                'app_default_currency',
                'app_currency_symbol',
                'app_currency_format'
            ])
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
     * Valider les paramètres Stripe
     */
    private function validateStripeSettings(Request $request)
    {
        return Validator::make($request->all(), [
            // Test keys
            'stripe_test_publishable_key' => 'nullable|string|min:20',
            'stripe_test_secret_key' => 'nullable|string|min:20',
            'stripe_test_webhook_secret' => 'nullable|string|min:20',
            'stripe_test_webhook_url' => 'nullable|url',
            // Prod keys
            'stripe_prod_publishable_key' => 'nullable|string|min:20',
            'stripe_prod_secret_key' => 'nullable|string|min:20',
            'stripe_prod_webhook_secret' => 'nullable|string|min:20',
            'stripe_prod_webhook_url' => 'nullable|url',
            // Common settings
            'stripe_environment' => 'required|in:test,prod',
            'stripe_currency' => 'required|in:EUR,USD'
        ], [
            'stripe_test_publishable_key.min' => 'La clé publique de test doit contenir au moins 20 caractères',
            'stripe_test_secret_key.min' => 'La clé secrète de test doit contenir au moins 20 caractères',
            'stripe_test_webhook_secret.min' => 'Le secret webhook de test doit contenir au moins 20 caractères',
            'stripe_test_webhook_url.url' => 'L\'URL du webhook de test doit être une URL valide',
            'stripe_prod_publishable_key.min' => 'La clé publique de production doit contenir au moins 20 caractères',
            'stripe_prod_secret_key.min' => 'La clé secrète de production doit contenir au moins 20 caractères',
            'stripe_prod_webhook_secret.min' => 'Le secret webhook de production doit contenir au moins 20 caractères',
            'stripe_prod_webhook_url.url' => 'L\'URL du webhook de production doit être une URL valide',
            'stripe_environment.required' => 'L\'environnement Stripe est requis',
            'stripe_environment.in' => 'L\'environnement doit être "test" ou "prod"',
            'stripe_currency.required' => 'La devise Stripe est requise',
            'stripe_currency.in' => 'La devise doit être EUR ou USD'
        ]);
    }

    /**
     * Mettre à jour les paramètres Stripe
     */
    private function updateStripeSettings(array $data)
    {
        $encryptedKeys = [
            'stripe_test_secret_key',
            'stripe_test_webhook_secret',
            'stripe_prod_secret_key',
            'stripe_prod_webhook_secret'
        ];

        $settings = [
            // Test keys
            'stripe_test_publishable_key' => $data['stripe_test_publishable_key'] ?? '',
            'stripe_test_secret_key' => $data['stripe_test_secret_key'] ?? '',
            'stripe_test_webhook_secret' => $data['stripe_test_webhook_secret'] ?? '',
            'stripe_test_webhook_url' => $data['stripe_test_webhook_url'] ?? '',
            // Prod keys
            'stripe_prod_publishable_key' => $data['stripe_prod_publishable_key'] ?? '',
            'stripe_prod_secret_key' => $data['stripe_prod_secret_key'] ?? '',
            'stripe_prod_webhook_secret' => $data['stripe_prod_webhook_secret'] ?? '',
            'stripe_prod_webhook_url' => $data['stripe_prod_webhook_url'] ?? '',
            // Common settings
            'stripe_environment' => $data['stripe_environment'] ?? 'test',
            'stripe_currency' => $data['stripe_currency'] ?? 'EUR'
        ];

        foreach ($settings as $key => $value) {
            // Ignorer les valeurs masquées (••••••••••••••••)
            if ($value === '••••••••••••••••' || (is_string($value) && strpos($value, '•••') === 0)) {
                continue;
            }

            // Ne pas mettre à jour si la valeur est vide et que c'est une clé sensible
            if (in_array($key, $encryptedKeys) && empty($value)) {
                continue;
            }

            $finalValue = in_array($key, $encryptedKeys) && !empty($value) ? 
                Crypt::encryptString($value) : $value;

            AdminSetting::updateOrCreate(
                [
                    'category' => 'external_apis',
                    'key' => $key
                ],
                [
                    'value' => $finalValue,
                    'type' => in_array($key, $encryptedKeys) ? 'password' : 'text',
                    'description' => $this->getFieldDescription($key),
                    'is_active' => true,
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Obtenir la description d'un champ
     */
    private function getFieldDescription(string $key): string
    {
        $descriptions = [
            'stripe_test_publishable_key' => 'Clé publique Stripe pour l\'environnement de test',
            'stripe_test_secret_key' => 'Clé secrète Stripe pour l\'environnement de test',
            'stripe_test_webhook_secret' => 'Secret webhook Stripe pour l\'environnement de test',
            'stripe_test_webhook_url' => 'URL webhook Stripe pour l\'environnement de test',
            'stripe_prod_publishable_key' => 'Clé publique Stripe pour l\'environnement de production',
            'stripe_prod_secret_key' => 'Clé secrète Stripe pour l\'environnement de production',
            'stripe_prod_webhook_secret' => 'Secret webhook Stripe pour l\'environnement de production',
            'stripe_prod_webhook_url' => 'URL webhook Stripe pour l\'environnement de production',
            'stripe_environment' => 'Environnement actif (test ou prod)',
            'stripe_currency' => 'Devise utilisée pour les paiements Stripe'
        ];
        
        return $descriptions[$key] ?? '';
    }

    /**
     * Mettre à jour la devise de l'application
     */
    private function updateApplicationCurrency(string $currency)
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$'
        ];

        AdminSetting::updateOrCreate(
            [
                'category' => 'system',
                'key' => 'app_default_currency'
            ],
            [
                'value' => $currency,
                'updated_at' => now()
            ]
        );

        AdminSetting::updateOrCreate(
            [
                'category' => 'system',
                'key' => 'app_currency_symbol'
            ],
            [
                'value' => $symbols[$currency],
                'updated_at' => now()
            ]
        );
    }

    /**
     * Effectuer un test de connexion Stripe
     */
    private function performStripeTest(array $data): array
    {
        $testEnvironment = $data['test_environment'] ?? 'test';
        
        if ($testEnvironment === 'test') {
            $publishableKey = $data['stripe_test_publishable_key'] ?? '';
            $secretKey = $data['stripe_test_secret_key'] ?? '';
        } else {
            $publishableKey = $data['stripe_prod_publishable_key'] ?? '';
            $secretKey = $data['stripe_prod_secret_key'] ?? '';
        }
        
        // Vérifications de base
        if (empty($publishableKey) || strlen($publishableKey) < 20) {
            return [
                'success' => false,
                'message' => 'Clé publique Stripe invalide pour l\'environnement ' . $testEnvironment
            ];
        }
        
        if (empty($secretKey) || strlen($secretKey) < 20) {
            return [
                'success' => false,
                'message' => 'Clé secrète Stripe invalide pour l\'environnement ' . $testEnvironment
            ];
        }

        // Vérifier le format des clés
        if ($testEnvironment === 'test') {
            if (!str_starts_with($publishableKey, 'pk_test_') || !str_starts_with($secretKey, 'sk_test_')) {
                return [
                    'success' => false,
                    'message' => 'Les clés ne correspondent pas à l\'environnement test'
                ];
            }
        } else {
            if (!str_starts_with($publishableKey, 'pk_live_') || !str_starts_with($secretKey, 'sk_live_')) {
                return [
                    'success' => false,
                    'message' => 'Les clés ne correspondent pas à l\'environnement production'
                ];
            }
        }

        // Test de connexion avec l'API Stripe
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/account');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'message' => 'Impossible de se connecter à l\'API Stripe',
                    'details' => $error
                ];
            }
            
            if ($httpCode === 200) {
                $accountData = json_decode($result, true);
                return [
                    'success' => true,
                    'message' => 'Connexion à l\'API Stripe réussie',
                    'details' => "Compte: " . ($accountData['display_name'] ?? 'N/A') . " (ID: " . ($accountData['id'] ?? 'N/A') . ")"
                ];
            } elseif ($httpCode === 401) {
                return [
                    'success' => false,
                    'message' => 'Clés Stripe invalides',
                    'details' => 'Vérifiez vos clés API'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'L\'API Stripe a retourné une erreur',
                    'details' => "Code HTTP: {$httpCode}"
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du test de connexion',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Réinitialiser les paramètres Stripe
     */
    private function resetStripeSettings()
    {
        $defaultSettings = [
            'stripe_test_publishable_key' => '',
            'stripe_test_secret_key' => '',
            'stripe_test_webhook_secret' => '',
            'stripe_test_webhook_url' => '',
            'stripe_prod_publishable_key' => '',
            'stripe_prod_secret_key' => '',
            'stripe_prod_webhook_secret' => '',
            'stripe_prod_webhook_url' => '',
            'stripe_environment' => 'test',
            'stripe_currency' => 'EUR'
        ];

        foreach ($defaultSettings as $key => $value) {
            AdminSetting::updateOrCreate(
                [
                    'category' => 'external_apis',
                    'key' => $key
                ],
                [
                    'value' => $value,
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Obtenir les options pour les champs select
     */
    public function getFieldOptions()
    {
        return [
            'environments' => [
                'test' => 'Test',
                'live' => 'Production'
            ],
            'currencies' => [
                'EUR' => 'Euro',
                'USD' => 'Dollar américain'
            ],
            'app_currencies' => [
                'EUR' => 'Euro (EUR)',
                'USD' => 'Dollar américain (USD)'
            ]
        ];
    }
}