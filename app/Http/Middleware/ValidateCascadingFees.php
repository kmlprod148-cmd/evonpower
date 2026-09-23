<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CascadingFeesValidationService;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;

/**
 * Middleware de validation des frais en cascade
 * 
 * Ce middleware valide que les frais appliqués à un opérateur
 * sont cohérents avec ceux appliqués à son intégrateur.
 */
class ValidateCascadingFees
{
    protected $cascadingFeesService;

    public function __construct(CascadingFeesValidationService $cascadingFeesService)
    {
        $this->cascadingFeesService = $cascadingFeesService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si c'est une création de charging point
        if ($this->isChargingPointCreation($request)) {
            $validation = $this->validateChargingPointFees($request);
            
            if (!$validation['is_valid']) {
                Log::warning('Validation des frais en cascade échouée', $validation);
                
                // Retourner une erreur si la validation échoue
                return response()->json([
                    'success' => false,
                    'message' => 'Validation des frais en cascade échouée',
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'] ?? [],
                    'validation_details' => $validation
                ], 422);
            }

            // Ajouter les avertissements à la réponse si nécessaire
            if (!empty($validation['warnings'])) {
                Log::info('Avertissements de validation des frais', $validation['warnings']);
            }
        }

        return $next($request);
    }

    /**
     * Vérifier si c'est une création de charging point
     */
    private function isChargingPointCreation(Request $request): bool
    {
        return $request->isMethod('POST') && 
               (str_contains($request->path(), 'charging-points') || 
                str_contains($request->path(), 'charging-point'));
    }

    /**
     * Valider les frais du charging point
     */
    private function validateChargingPointFees(Request $request): array
    {
        try {
            // Récupérer les données de la requête
            $data = $request->all();
            
            // Créer un objet ChargingPoint temporaire pour la validation
            $chargingPoint = new ChargingPoint($data);
            
            // Valider les frais en cascade
            return $this->cascadingFeesService->validateChargingPointFees($chargingPoint);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans ValidateCascadingFees middleware', [
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
                'request_method' => $request->method()
            ]);
            
            return [
                'is_valid' => false,
                'errors' => ['Erreur lors de la validation: ' . $e->getMessage()],
                'warnings' => []
            ];
        }
    }
}
