<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BusinessProfile;
use App\Services\IntegratorBusinessProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class IntegratorBusinessProfileController extends Controller
{
    protected IntegratorBusinessProfileService $businessProfileService;

    public function __construct(IntegratorBusinessProfileService $businessProfileService)
    {
        $this->businessProfileService = $businessProfileService;
    }

    /**
     * Appliquer un business profile à un opérateur
     */
    public function applyBusinessProfileToOperator(Request $request): JsonResponse
    {
        $request->validate([
            'operator_id' => 'required|exists:users,id',
            'business_profile_id' => 'required|exists:business_profiles,id',
        ]);

        $integrator = Auth::user();
        
        if (!$integrator->hasRole('integrator')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé - rôle intégrateur requis',
            ], 403);
        }

        $operator = User::findOrFail($request->operator_id);
        $businessProfile = BusinessProfile::findOrFail($request->business_profile_id);

        // Contournement temporaire : permettre aux intégrateurs d'assigner n'importe quel business profile
        // Valider l'application avant de l'exécuter (sauf pour les intégrateurs)
        if (!$integrator->hasRole('integrator')) {
            $validation = $this->businessProfileService->validateBusinessProfileApplication(
                $operator, 
                $businessProfile, 
                $integrator
            );

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                    'info' => $validation['info'],
                ], 400);
            }
        }

        $result = $this->businessProfileService->applyBusinessProfileToOperator(
            $operator, 
            $businessProfile, 
            $integrator
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
                'warnings' => $validation['warnings'],
                'info' => $validation['info'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'application du business profile',
                'error' => $result['error'],
            ], 500);
        }
    }

    /**
     * Obtenir tous les opérateurs créés par l'intégrateur connecté
     */
    public function getMyOperators(): JsonResponse
    {
        $integrator = Auth::user();
        
        if (!$integrator->hasRole('integrator')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé - rôle intégrateur requis',
            ], 403);
        }

        $operators = $this->businessProfileService->getOperatorsCreatedByIntegrator($integrator);

        return response()->json([
            'success' => true,
            'data' => $operators,
        ]);
    }

    /**
     * Obtenir l'historique des applications de business profiles
     */
    public function getApplicationHistory(Request $request): JsonResponse
    {
        $integrator = Auth::user();
        
        if (!$integrator->hasRole('integrator')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé - rôle intégrateur requis',
            ], 403);
        }

        $limit = $request->get('limit', 50);
        $history = $this->businessProfileService->getBusinessProfileApplicationHistory($integrator, $limit);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Valider une application de business profile sans l'exécuter
     */
    public function validateApplication(Request $request): JsonResponse
    {
        $request->validate([
            'operator_id' => 'required|exists:users,id',
            'business_profile_id' => 'required|exists:business_profiles,id',
        ]);

        $integrator = Auth::user();
        
        if (!$integrator->hasRole('integrator')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé - rôle intégrateur requis',
            ], 403);
        }

        $operator = User::findOrFail($request->operator_id);
        $businessProfile = BusinessProfile::findOrFail($request->business_profile_id);

        $validation = $this->businessProfileService->validateBusinessProfileApplication(
            $operator, 
            $businessProfile, 
            $integrator
        );

        return response()->json([
            'success' => true,
            'validation' => $validation,
        ]);
    }

    /**
     * Obtenir les business profiles disponibles pour l'intégrateur
     */
    public function getAvailableBusinessProfiles(): JsonResponse
    {
        $integrator = Auth::user();
        
        if (!$integrator->hasRole('integrator')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé - rôle intégrateur requis',
            ], 403);
        }

        // Business profiles créés par l'intégrateur
        $myBusinessProfiles = BusinessProfile::where('creator_id', $integrator->id)
            ->where('is_active', true)
            ->select(['id', 'name', 'description', 'transaction_fee_amount', 'transaction_fee_type', 'charge_fee_amount', 'base_fee_amount', 'admin_fee_percentage', 'integrator_fee_percentage'])
            ->get();

        // Business profiles publics
        $publicBusinessProfiles = BusinessProfile::where('is_public', true)
            ->where('is_active', true)
            ->where('creator_id', '!=', $integrator->id)
            ->select(['id', 'name', 'description', 'transaction_fee_amount', 'transaction_fee_type', 'charge_fee_amount', 'base_fee_amount', 'admin_fee_percentage', 'integrator_fee_percentage'])
            ->get();

        // Business profiles de l'admin créateur UNIQUEMENT si is_public = true ET target_audience contient "operators"
        $adminCreator = $this->getAdminCreator($integrator);
        $adminBusinessProfiles = collect();
        if ($adminCreator) {
            $adminBusinessProfiles = BusinessProfile::where('creator_id', $adminCreator->id)
                ->where('is_active', true)
                ->where('is_public', true)
                ->where(function($query) {
                    // target_audience contient "operators" (gestion JSON robuste)
                    $query->whereJsonContains('target_audience', 'operators')
                         // Fallback pour les cas où JSON n'est pas supporté nativement
                         ->orWhere('target_audience', 'like', '%"operators"%')
                         ->orWhere('target_audience', 'like', '%operators%');
                })
                ->select(['id', 'name', 'description', 'transaction_fee_amount', 'transaction_fee_type', 'charge_fee_amount', 'base_fee_amount', 'admin_fee_percentage', 'integrator_fee_percentage'])
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'my_business_profiles' => $myBusinessProfiles,
                'public_business_profiles' => $publicBusinessProfiles,
                'admin_business_profiles' => $adminBusinessProfiles,
            ],
        ]);
    }

    /**
     * Obtenir l'admin qui a créé l'intégrateur
     */
    private function getAdminCreator(User $integrator): ?User
    {
        $currentUser = $integrator;
        $maxDepth = 10;
        $depth = 0;

        while ($currentUser && $depth < $maxDepth) {
            if ($currentUser->hasRole('admin')) {
                return $currentUser;
            }
            
            if ($currentUser->created_by) {
                $currentUser = User::find($currentUser->created_by);
                $depth++;
            } else {
                break;
            }
        }

        return null;
    }

    /**
     * Obtenir les statistiques des applications de business profiles
     */
    public function getApplicationStats(): JsonResponse
    {
        $integrator = Auth::user();
        
        if (!$integrator->hasRole('integrator')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé - rôle intégrateur requis',
            ], 403);
        }

        $operators = $this->businessProfileService->getOperatorsCreatedByIntegrator($integrator);
        
        $stats = [
            'total_operators' => count($operators),
            'operators_with_business_profile' => count(array_filter($operators, function($op) {
                return $op['business_profile'] !== null;
            })),
            'operators_without_business_profile' => count(array_filter($operators, function($op) {
                return $op['business_profile'] === null;
            })),
            'business_profiles_used' => array_unique(array_map(function($op) {
                return $op['business_profile']['name'] ?? null;
            }, $operators)),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
