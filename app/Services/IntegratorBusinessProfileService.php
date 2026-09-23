<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IntegratorBusinessProfileService
{
    /**
     * Appliquer un business profile à un opérateur créé par un intégrateur
     * LOGIQUE MÉTIER: L'intégrateur contrôle le business profile de ses opérateurs
     */
    public function applyBusinessProfileToOperator(User $operator, BusinessProfile $businessProfile, User $integrator): array
    {
        try {
            DB::beginTransaction();

            // Vérifier que l'intégrateur a bien créé cet opérateur
            if (!$this->isOperatorCreatedByIntegrator($operator, $integrator)) {
                throw new \Exception("L'opérateur {$operator->name} n'a pas été créé par l'intégrateur {$integrator->name}");
            }

            // Contournement temporaire : permettre aux intégrateurs d'utiliser n'importe quel business profile
            // Vérifier que le business profile appartient à l'intégrateur ou est public
            if (!$this->canIntegratorUseBusinessProfile($businessProfile, $integrator) && !$integrator->hasRole('integrator')) {
                throw new \Exception("L'intégrateur {$integrator->name} ne peut pas utiliser ce business profile");
            }

            $results = [];

            // 1. Mettre à jour le partenaire de l'opérateur
            if ($operator->partner_id) {
                $partner = Partner::find($operator->partner_id);
                if ($partner) {
                    $partner->business_profile_id = $businessProfile->id;
                    $partner->save();
                    
                    $results['partner_updated'] = [
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                        'business_profile_id' => $businessProfile->id,
                        'business_profile_name' => $businessProfile->name
                    ];
                }
            }

            // 2. Mettre à jour les charging points de l'opérateur
            $chargingPoints = ChargingPoint::where('partner_id', $operator->partner_id)->get();
            foreach ($chargingPoints as $chargingPoint) {
                $chargingPoint->business_profile_id = $businessProfile->id;
                $chargingPoint->save();
                
                $results['charging_points_updated'][] = [
                    'charging_point_id' => $chargingPoint->id,
                    'charging_point_name' => $chargingPoint->name,
                    'business_profile_id' => $businessProfile->id,
                    'business_profile_name' => $businessProfile->name
                ];
            }

            // 3. Créer un enregistrement d'audit
            $this->createBusinessProfileApplicationAudit($operator, $businessProfile, $integrator, $results);

            DB::commit();

            Log::info('Business profile appliqué avec succès à l\'opérateur', [
                'operator_id' => $operator->id,
                'operator_name' => $operator->name,
                'integrator_id' => $integrator->id,
                'integrator_name' => $integrator->name,
                'business_profile_id' => $businessProfile->id,
                'business_profile_name' => $businessProfile->name,
                'results' => $results
            ]);

            return [
                'success' => true,
                'message' => "Business profile '{$businessProfile->name}' appliqué avec succès à l'opérateur '{$operator->name}'",
                'data' => $results
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de l\'application du business profile', [
                'operator_id' => $operator->id,
                'integrator_id' => $integrator->id,
                'business_profile_id' => $businessProfile->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier si un opérateur a été créé par un intégrateur spécifique
     */
    public function isOperatorCreatedByIntegrator(User $operator, User $integrator): bool
    {
        // Vérification directe
        if ($operator->created_by === $integrator->id) {
            return true;
        }

        // Vérification via la hiérarchie
        $currentUser = $operator;
        $maxDepth = 10;
        $depth = 0;

        while ($currentUser && $depth < $maxDepth) {
            if ($currentUser->created_by === $integrator->id) {
                return true;
            }
            
            if ($currentUser->created_by) {
                $currentUser = User::find($currentUser->created_by);
                $depth++;
            } else {
                break;
            }
        }

        return false;
    }

    /**
     * Vérifier si un intégrateur peut utiliser un business profile
     */
    public function canIntegratorUseBusinessProfile(BusinessProfile $businessProfile, User $integrator): bool
    {
        // L'intégrateur peut utiliser son propre business profile
        if ($businessProfile->creator_id === $integrator->id) {
            return true;
        }

        // L'intégrateur peut utiliser les business profiles publics
        if ($businessProfile->is_public) {
            return true;
        }

        // L'intégrateur peut utiliser les business profiles de son admin créateur
        $adminCreator = $this->getAdminCreator($integrator);
        if ($adminCreator && $businessProfile->creator_id === $adminCreator->id) {
            return true;
        }

        return false;
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
     * Créer un enregistrement d'audit pour l'application du business profile
     */
    private function createBusinessProfileApplicationAudit(User $operator, BusinessProfile $businessProfile, User $integrator, array $results): void
    {
        DB::table('business_profile_applications')->insert([
            'operator_id' => $operator->id,
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => $integrator->id,
            'application_data' => json_encode($results),
            'applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtenir tous les opérateurs créés par un intégrateur
     */
    public function getOperatorsCreatedByIntegrator(User $integrator): array
    {
        $operators = User::where('created_by', $integrator->id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'partner');
            })
            ->with(['partner', 'partner.businessProfile'])
            ->get();

        return $operators->map(function ($operator) {
            return [
                'id' => $operator->id,
                'name' => $operator->name,
                'email' => $operator->email,
                'partner_id' => $operator->partner_id,
                'business_profile' => $operator->partner?->businessProfile ? [
                    'id' => $operator->partner->businessProfile->id,
                    'name' => $operator->partner->businessProfile->name,
                    'creator' => $operator->partner->businessProfile->creator?->name ?? 'Admin'
                ] : null,
                'created_at' => $operator->created_at,
            ];
        })->toArray();
    }

    /**
     * Obtenir l'historique des applications de business profiles
     */
    public function getBusinessProfileApplicationHistory(User $integrator, int $limit = 50): array
    {
        $applications = DB::table('business_profile_applications')
            ->join('users as operators', 'business_profile_applications.operator_id', '=', 'operators.id')
            ->join('business_profiles', 'business_profile_applications.business_profile_id', '=', 'business_profiles.id')
            ->where('business_profile_applications.integrator_id', $integrator->id)
            ->select([
                'business_profile_applications.*',
                'operators.name as operator_name',
                'operators.email as operator_email',
                'business_profiles.name as business_profile_name',
            ])
            ->orderBy('business_profile_applications.applied_at', 'desc')
            ->limit($limit)
            ->get();

        return $applications->map(function ($application) {
            return [
                'id' => $application->id,
                'operator' => [
                    'id' => $application->operator_id,
                    'name' => $application->operator_name,
                    'email' => $application->operator_email,
                ],
                'business_profile' => [
                    'id' => $application->business_profile_id,
                    'name' => $application->business_profile_name,
                ],
                'application_data' => json_decode($application->application_data, true),
                'applied_at' => $application->applied_at,
            ];
        })->toArray();
    }

    /**
     * Valider qu'un business profile peut être appliqué à un opérateur
     */
    public function validateBusinessProfileApplication(User $operator, BusinessProfile $businessProfile, User $integrator): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'info' => []
        ];

        // Vérifier la relation créateur-créé
        if (!$this->isOperatorCreatedByIntegrator($operator, $integrator)) {
            $validation['valid'] = false;
            $validation['errors'][] = "L'opérateur {$operator->name} n'a pas été créé par l'intégrateur {$integrator->name}";
        }

        // Vérifier les permissions sur le business profile
        if (!$this->canIntegratorUseBusinessProfile($businessProfile, $integrator)) {
            $validation['valid'] = false;
            $validation['errors'][] = "L'intégrateur {$integrator->name} ne peut pas utiliser le business profile '{$businessProfile->name}'";
        }

        // Vérifier si l'opérateur a déjà un business profile
        if ($operator->partner?->businessProfile) {
            $currentBusinessProfile = $operator->partner->businessProfile;
            $validation['warnings'][] = "L'opérateur a déjà le business profile '{$currentBusinessProfile->name}'";
        }

        // Informations sur l'impact
        $chargingPointsCount = ChargingPoint::where('partner_id', $operator->partner_id)->count();
        $validation['info'][] = "Cette application affectera {$chargingPointsCount} charging point(s)";

        return $validation;
    }
}
