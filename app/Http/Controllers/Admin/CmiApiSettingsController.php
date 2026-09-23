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
 * Contrôleur pour la gestion des paramètres API CMI
 */
class CmiApiSettingsController extends Controller
{
    protected $adminConfigService;

    public function __construct(AdminConfigurationService $adminConfigService)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->email !== 'admin@evon.com') {
                abort(403, 'Accès non autorisé. Seul l\'administrateur principal peut gérer les paramètres API CMI.');
            }
            return $next($request);
        });
        
        $this->adminConfigService = $adminConfigService;
    }

    /**
     * Afficher la page de configuration des API CMI
     */
    public function index()
    {
        $cmiSettings = $this->getCmiSettings();
        
        return view('admin.cmi-api-settings', compact('cmiSettings'));
    }

    /**
     * Mettre à jour les paramètres CMI
     */
    public function update(Request $request)
    {
        $validator = $this->validateCmiSettings($request);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->updateCmiSettings($request->all());
            
            Log::info('Paramètres CMI mis à jour', [
                'user' => Auth::user()->email,
                'updated_at' => now()
            ]);

            return redirect()->route('admin.cmi-api-settings')
                ->with('success', 'Paramètres CMI mis à jour avec succès !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour des paramètres CMI: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la mise à jour des paramètres: ' . $e->getMessage());
        }
    }

    /**
     * Tester la connexion CMI
     */
    public function testConnection(Request $request)
    {
        $validator = $this->validateCmiSettings($request);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
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
     * Réinitialiser les paramètres CMI
     */
    public function reset()
    {
        try {
            $this->resetCmiSettings();
            
            Log::info('Paramètres CMI réinitialisés', [
                'user' => Auth::user()->email,
                'reset_at' => now()
            ]);

            return redirect()->route('admin.cmi-api-settings')
                ->with('success', 'Paramètres CMI réinitialisés aux valeurs par défaut !');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation des paramètres CMI: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la réinitialisation: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les paramètres CMI actuels
     */
    private function getCmiSettings(): array
    {
        $settings = AdminSetting::where('category', 'external_apis')
            ->whereIn('key', [
                'cmi_api_key',
                'cmi_merchant_id',
                'cmi_api_url',
                'cmi_environment',
                'cmi_currency',
                'cmi_language'
            ])
            ->get()
            ->keyBy('key');

        $result = [];
        foreach ($settings as $key => $setting) {
            $result[$key] = [
                'value' => $setting->key === 'cmi_api_key' ? 
                    ($setting->value ? '••••••••••••••••' : '') : 
                    $setting->value,
                'description' => $setting->description,
                'type' => $setting->type,
                'metadata' => json_decode($setting->metadata, true)
            ];
        }

        return $result;
    }

    /**
     * Valider les paramètres CMI
     */
    private function validateCmiSettings(Request $request)
    {
        return Validator::make($request->all(), [
            'cmi_api_key' => 'required|string|min:10',
            'cmi_merchant_id' => 'required|string|min:3',
            'cmi_api_url' => 'required|url',
            'cmi_environment' => 'required|in:test,production',
            'cmi_currency' => 'required|in:EUR,USD',
            'cmi_language' => 'required|in:fr,en,ar'
        ], [
            'cmi_api_key.required' => 'La clé API CMI est requise',
            'cmi_api_key.min' => 'La clé API CMI doit contenir au moins 10 caractères',
            'cmi_merchant_id.required' => 'L\'ID du marchand CMI est requis',
            'cmi_merchant_id.min' => 'L\'ID du marchand doit contenir au moins 3 caractères',
            'cmi_api_url.required' => 'L\'URL de l\'API CMI est requise',
            'cmi_api_url.url' => 'L\'URL de l\'API CMI doit être une URL valide',
            'cmi_environment.required' => 'L\'environnement CMI est requis',
            'cmi_environment.in' => 'L\'environnement doit être "test" ou "production"',
            'cmi_currency.required' => 'La devise est requise',
            'cmi_currency.in' => 'La devise doit être EUR ou USD',
            'cmi_language.required' => 'La langue est requise',
            'cmi_language.in' => 'La langue doit être fr, en ou ar'
        ]);
    }

    /**
     * Mettre à jour les paramètres CMI
     */
    private function updateCmiSettings(array $data)
    {
        $settings = [
            'cmi_api_key' => $data['cmi_api_key'],
            'cmi_merchant_id' => $data['cmi_merchant_id'],
            'cmi_api_url' => $data['cmi_api_url'],
            'cmi_environment' => $data['cmi_environment'],
            'cmi_currency' => $data['cmi_currency'],
            'cmi_language' => $data['cmi_language']
        ];

        foreach ($settings as $key => $value) {
            AdminSetting::updateOrCreate(
                [
                    'category' => 'external_apis',
                    'key' => $key
                ],
                [
                    'value' => $key === 'cmi_api_key' ? Crypt::encryptString($value) : $value,
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Effectuer un test de connexion CMI
     */
    private function performCmiTest(array $data): array
    {
        // Simulation d'un test de connexion CMI
        // Dans un vrai projet, vous feriez un appel API réel
        
        $apiUrl = $data['cmi_api_url'];
        $merchantId = $data['cmi_merchant_id'];
        $apiKey = $data['cmi_api_key'];
        
        // Vérifications de base
        if (empty($apiKey) || strlen($apiKey) < 10) {
            return [
                'success' => false,
                'message' => 'Clé API invalide'
            ];
        }
        
        if (empty($merchantId) || strlen($merchantId) < 3) {
            return [
                'success' => false,
                'message' => 'ID marchand invalide'
            ];
        }
        
        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'URL API invalide'
            ];
        }
        
        // Simulation d'un test de connectivité
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'message' => 'Impossible de se connecter à l\'API CMI',
                'details' => $error
            ];
        }
        
        if ($httpCode >= 200 && $httpCode < 400) {
            return [
                'success' => true,
                'message' => 'Connexion à l\'API CMI réussie',
                'details' => "Code HTTP: {$httpCode}"
            ];
        }
        
        return [
            'success' => false,
            'message' => 'L\'API CMI a retourné une erreur',
            'details' => "Code HTTP: {$httpCode}"
        ];
    }

    /**
     * Réinitialiser les paramètres CMI
     */
    private function resetCmiSettings()
    {
        $defaultSettings = [
            'cmi_api_key' => '',
            'cmi_merchant_id' => '',
            'cmi_api_url' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
            'cmi_environment' => 'test',
            'cmi_currency' => 'EUR',
            'cmi_language' => 'fr'
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
                'production' => 'Production'
            ],
            'currencies' => [
                'EUR' => 'Euro',
                'USD' => 'Dollar américain'
            ],
            'languages' => [
                'fr' => 'Français',
                'en' => 'English',
                'ar' => 'العربية'
            ],
            'api_urls' => [
                'test' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                'production' => 'https://payment.cmi.co.ma/fim/est3Dgate'
            ]
        ];
    }
}