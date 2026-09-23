<?php

namespace App\Http\Controllers;

use App\Services\AdminConfigurationService;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * Contrôleur pour la gestion des configurations admin
 */
class AdminConfigurationController extends Controller
{
    protected AdminConfigurationService $adminConfigService;
    protected ConfigurationService $configService;

    public function __construct(
        AdminConfigurationService $adminConfigService,
        ConfigurationService $configService
    ) {
        $this->adminConfigService = $adminConfigService;
        $this->configService = $configService;
    }

    /**
     * Affiche toutes les configurations
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $configurations = $this->adminConfigService->getAllAdminSettings();
            $stats = $this->adminConfigService->getConfigurationStats();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'configurations' => $configurations,
                    'stats' => $stats,
                    'languages' => $this->configService->getLanguages(),
                    'currencies' => $this->configService->getCurrencies(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des configurations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche les paramètres d'une catégorie
     * 
     * @param string $category
     * @return JsonResponse
     */
    public function show(string $category): JsonResponse
    {
        try {
            $settings = $this->adminConfigService->getCategorySettings($category);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'settings' => $settings
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de la récupération des paramètres de la catégorie {$category}",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un paramètre
     * 
     * @param Request $request
     * @param string $category
     * @param string $key
     * @return JsonResponse
     */
    public function update(Request $request, string $category, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required'
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
                $category,
                $key,
                $request->input('value')
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paramètre mis à jour avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour du paramètre'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du paramètre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour plusieurs paramètres
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $success = $this->adminConfigService->updateMultipleSettings(
                $request->input('settings')
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paramètres mis à jour avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour des paramètres'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des paramètres',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporte les configurations
     * 
     * @return Response
     */
    public function export(): Response
    {
        try {
            $configurations = $this->adminConfigService->exportConfigurations();
            $filename = 'configurations_' . now()->format('Y-m-d_H-i-s') . '.json';
            
            $content = json_encode($configurations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            return response($content)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export des configurations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importe les configurations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json|max:10240', // 10MB max
            'overwrite' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getPathname());
            $configurations = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Fichier JSON invalide');
            }

            $overwrite = $request->boolean('overwrite', false);
            $success = $this->adminConfigService->importConfigurations($configurations, $overwrite);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Configurations importées avec succès'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'import des configurations'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import des configurations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialise les paramètres par défaut
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function reset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = $request->input('category');
            $success = $this->adminConfigService->resetToDefaults($category);

            if ($success) {
                $message = $category 
                    ? "Paramètres de la catégorie {$category} réinitialisés"
                    : "Tous les paramètres réinitialisés";
                    
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la réinitialisation'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtient les statistiques des configurations
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->adminConfigService->getConfigurationStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valide une configuration avant sauvegarde
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateConfiguration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string',
            'key' => 'required|string',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = $request->input('category');
            $key = $request->input('key');
            $value = $request->input('value');

            // Utiliser la méthode de validation du service
            $reflection = new \ReflectionClass($this->adminConfigService);
            $method = $reflection->getMethod('validateSettingValue');
            $method->setAccessible(true);
            
            $isValid = $method->invoke($this->adminConfigService, $category, $key, $value);

            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => $isValid,
                    'category' => $category,
                    'key' => $key,
                    'value' => $value
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
