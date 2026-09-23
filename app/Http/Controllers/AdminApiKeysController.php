<?php

namespace App\Http\Controllers;

use App\Services\AdminConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

/**
 * Contrôleur pour la gestion des clés API externes
 */
class AdminApiKeysController extends Controller
{
    protected AdminConfigurationService $adminConfigService;

    public function __construct(AdminConfigurationService $adminConfigService)
    {
        $this->adminConfigService = $adminConfigService;
    }

    /**
     * Affiche toutes les clés API
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $apiKeys = $this->adminConfigService->getCategorySettings('external_apis');
            
            // Masquer les valeurs sensibles pour l'affichage
            $maskedKeys = [];
            foreach ($apiKeys as $key => $config) {
                $maskedKeys[$key] = $config;
                if (isset($config['value']) && !empty($config['value'])) {
                    $maskedKeys[$key]['masked_value'] = $this->maskApiKey($config['value']);
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $maskedKeys
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des clés API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une clé API
     * 
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function updateApiKey(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $this->adminConfigService->updateSetting(
                'external_apis',
                $key,
                $request->input('value')
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Clé API mise à jour avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour de la clé API'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la clé API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour plusieurs clés API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMultipleApiKeys(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keys' => 'required|array',
            'keys.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = ['external_apis' => $request->input('keys')];
            $success = $this->adminConfigService->updateMultipleSettings($settings);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Clés API mises à jour avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour des clés API'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des clés API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Teste une clé API
     * 
     * @param string $key
     * @return JsonResponse
     */
    public function testApiKey(string $key): JsonResponse
    {
        try {
            $apiKey = $this->adminConfigService->getCategorySettings('external_apis')[$key]['value'] ?? null;
            
            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clé API non trouvée'
                ], 404);
            }

            $testResult = $this->performApiTest($key, $apiKey);
            
            return response()->json([
                'success' => true,
                'data' => $testResult
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de la clé API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Masque une clé API pour l'affichage
     * 
     * @param string $key
     * @return JsonResponse
     */
    public function getMaskedApiKey(string $key): JsonResponse
    {
        try {
            $apiKey = $this->adminConfigService->getCategorySettings('external_apis')[$key]['value'] ?? null;
            
            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clé API non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'masked_value' => $this->maskApiKey($apiKey)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du masquage de la clé API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialise une clé API
     * 
     * @param string $key
     * @return JsonResponse
     */
    public function resetApiKey(string $key): JsonResponse
    {
        try {
            $success = $this->adminConfigService->updateSetting('external_apis', $key, '');
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Clé API réinitialisée avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la réinitialisation de la clé API'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation de la clé API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Masque une clé API pour l'affichage
     * 
     * @param string $apiKey
     * @return string
     */
    private function maskApiKey(string $apiKey): string
    {
        if (empty($apiKey)) {
            return '';
        }

        $length = strlen($apiKey);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        $visibleStart = 4;
        $visibleEnd = 4;
        $masked = substr($apiKey, 0, $visibleStart) . str_repeat('*', $length - $visibleStart - $visibleEnd) . substr($apiKey, -$visibleEnd);
        
        return $masked;
    }

    /**
     * Effectue un test d'API selon le type de clé
     * 
     * @param string $key
     * @param string $apiKey
     * @return array
     */
    private function performApiTest(string $key, string $apiKey): array
    {
        switch ($key) {
            case 'stripe_public_key':
            case 'stripe_secret_key':
                return $this->testStripeApi($apiKey);
            
            case 'currency_api_key':
                return $this->testCurrencyApi($apiKey);
            
            case 'google_maps_api_key':
                return $this->testGoogleMapsApi($apiKey);
            
            case 'twilio_account_sid':
            case 'twilio_auth_token':
                return $this->testTwilioApi($apiKey);
            
            default:
                return [
                    'status' => 'unknown',
                    'message' => 'Type de clé API non supporté pour les tests automatiques',
                    'tested_at' => now()->toISOString()
                ];
        }
    }

    /**
     * Teste l'API Stripe
     */
    private function testStripeApi(string $apiKey): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])->get('https://api.stripe.com/v1/account');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'Clé Stripe valide',
                    'tested_at' => now()->toISOString()
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Clé Stripe invalide',
                    'error_code' => $response->status(),
                    'tested_at' => now()->toISOString()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors du test Stripe: ' . $e->getMessage(),
                'tested_at' => now()->toISOString()
            ];
        }
    }

    /**
     * Teste l'API des taux de change
     */
    private function testCurrencyApi(string $apiKey): array
    {
        try {
            $response = Http::get('https://api.fixer.io/latest', [
                'access_key' => $apiKey,
                'base' => 'USD',
                'symbols' => 'EUR,MAD'
            ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'Clé API des devises valide',
                    'tested_at' => now()->toISOString()
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Clé API des devises invalide',
                    'error_code' => $response->status(),
                    'tested_at' => now()->toISOString()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors du test API devises: ' . $e->getMessage(),
                'tested_at' => now()->toISOString()
            ];
        }
    }

    /**
     * Teste l'API Google Maps
     */
    private function testGoogleMapsApi(string $apiKey): array
    {
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => 'Paris, France',
                'key' => $apiKey
            ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'Clé Google Maps valide',
                    'tested_at' => now()->toISOString()
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Clé Google Maps invalide',
                    'error_code' => $response->status(),
                    'tested_at' => now()->toISOString()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors du test Google Maps: ' . $e->getMessage(),
                'tested_at' => now()->toISOString()
            ];
        }
    }

    /**
     * Teste l'API Twilio
     */
    private function testTwilioApi(string $apiKey): array
    {
        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->get('https://api.twilio.com/2010-04-01/Accounts.json');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'Clé Twilio valide',
                    'tested_at' => now()->toISOString()
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Clé Twilio invalide',
                    'error_code' => $response->status(),
                    'tested_at' => now()->toISOString()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors du test Twilio: ' . $e->getMessage(),
                'tested_at' => now()->toISOString()
            ];
        }
    }
}