<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service pour corriger les incohérences dans les business profiles
 */
class BusinessProfileConsistencyService
{
    /**
     * Corrige toutes les incohérences dans les business profiles
     */
    public function fixAllBusinessProfileInconsistencies(): array
    {
        $results = [
            'total_processed' => 0,
            'fixed' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            DB::beginTransaction();

            $businessProfiles = BusinessProfile::all();
            
            foreach ($businessProfiles as $businessProfile) {
                $results['total_processed']++;
                
                try {
                    $this->fixBusinessProfileInconsistencies($businessProfile);
                    $results['fixed']++;
                    $results['details'][] = [
                        'business_profile_id' => $businessProfile->id,
                        'name' => $businessProfile->name,
                        'status' => 'fixed'
                    ];
                    
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'business_profile_id' => $businessProfile->id,
                        'name' => $businessProfile->name,
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();
            
            Log::info('Correction des business profiles terminée', $results);
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la correction des business profiles', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Corrige les incohérences d'un business profile spécifique
     */
    public function fixBusinessProfileInconsistencies(BusinessProfile $businessProfile): void
    {
        $changes = [];
        
        // 1. Corriger les pourcentages incohérents
        $totalPercentage = ($businessProfile->admin_fee_percentage ?? 0) + 
                          ($businessProfile->integrator_fee_percentage ?? 0) + 
                          ($businessProfile->partner_fee_percentage ?? 0);
        
        if ($totalPercentage > 100) {
            // Réduire proportionnellement
            $ratio = 100 / $totalPercentage;
            $businessProfile->admin_fee_percentage = round(($businessProfile->admin_fee_percentage ?? 0) * $ratio, 2);
            $businessProfile->integrator_fee_percentage = round(($businessProfile->integrator_fee_percentage ?? 0) * $ratio, 2);
            $businessProfile->partner_fee_percentage = round(($businessProfile->partner_fee_percentage ?? 0) * $ratio, 2);
            
            $changes[] = "Pourcentages réduits proportionnellement (total: {$totalPercentage}% → 100%)";
        }
        
        // 2. S'assurer que les frais fixes sont cohérents
        $totalFixedFees = ($businessProfile->admin_fee_fixed ?? 0) + 
                         ($businessProfile->integrator_fee_fixed ?? 0) + 
                         ($businessProfile->partner_fee_fixed ?? 0) + 
                         ($businessProfile->terminal_fee_amount ?? 0) + 
                         ($businessProfile->base_fee_amount ?? 0);
        
        if ($totalFixedFees > 0) {
            // Vérifier que les frais fixes ne dépassent pas un montant raisonnable
            $maxReasonableFixedFees = 10.0; // 10€ maximum pour les frais fixes
            if ($totalFixedFees > $maxReasonableFixedFees) {
                $ratio = $maxReasonableFixedFees / $totalFixedFees;
                $businessProfile->admin_fee_fixed = round(($businessProfile->admin_fee_fixed ?? 0) * $ratio, 2);
                $businessProfile->integrator_fee_fixed = round(($businessProfile->integrator_fee_fixed ?? 0) * $ratio, 2);
                $businessProfile->partner_fee_fixed = round(($businessProfile->partner_fee_fixed ?? 0) * $ratio, 2);
                $businessProfile->terminal_fee_amount = round(($businessProfile->terminal_fee_amount ?? 0) * $ratio, 2);
                $businessProfile->base_fee_amount = round(($businessProfile->base_fee_amount ?? 0) * $ratio, 2);
                
                $changes[] = "Frais fixes réduits (total: {$totalFixedFees}€ → {$maxReasonableFixedFees}€)";
            }
        }
        
        // 3. S'assurer que les valeurs négatives sont corrigées
        if (($businessProfile->admin_fee_fixed ?? 0) < 0) {
            $businessProfile->admin_fee_fixed = 0;
            $changes[] = "Admin fee fixed corrigé (valeur négative → 0)";
        }
        
        if (($businessProfile->integrator_fee_fixed ?? 0) < 0) {
            $businessProfile->integrator_fee_fixed = 0;
            $changes[] = "Integrator fee fixed corrigé (valeur négative → 0)";
        }
        
        if (($businessProfile->partner_fee_fixed ?? 0) < 0) {
            $businessProfile->partner_fee_fixed = 0;
            $changes[] = "Partner fee fixed corrigé (valeur négative → 0)";
        }
        
        if (($businessProfile->admin_fee_percentage ?? 0) < 0) {
            $businessProfile->admin_fee_percentage = 0;
            $changes[] = "Admin fee percentage corrigé (valeur négative → 0)";
        }
        
        if (($businessProfile->integrator_fee_percentage ?? 0) < 0) {
            $businessProfile->integrator_fee_percentage = 0;
            $changes[] = "Integrator fee percentage corrigé (valeur négative → 0)";
        }
        
        if (($businessProfile->partner_fee_percentage ?? 0) < 0) {
            $businessProfile->partner_fee_percentage = 0;
            $changes[] = "Partner fee percentage corrigé (valeur négative → 0)";
        }
        
        // 4. S'assurer que les valeurs sont dans des plages raisonnables
        if (($businessProfile->admin_fee_percentage ?? 0) > 50) {
            $businessProfile->admin_fee_percentage = 50;
            $changes[] = "Admin fee percentage limité à 50%";
        }
        
        if (($businessProfile->integrator_fee_percentage ?? 0) > 30) {
            $businessProfile->integrator_fee_percentage = 30;
            $changes[] = "Integrator fee percentage limité à 30%";
        }
        
        if (($businessProfile->partner_fee_percentage ?? 0) > 20) {
            $businessProfile->partner_fee_percentage = 20;
            $changes[] = "Partner fee percentage limité à 20%";
        }
        
        // 5. Configurer des valeurs par défaut si manquantes
        if (is_null($businessProfile->admin_fee_fixed)) {
            $businessProfile->admin_fee_fixed = 2.50;
            $changes[] = "Admin fee fixed défini par défaut (2.50€)";
        }
        
        if (is_null($businessProfile->admin_fee_percentage)) {
            $businessProfile->admin_fee_percentage = 5.0;
            $changes[] = "Admin fee percentage défini par défaut (5%)";
        }
        
        if (is_null($businessProfile->integrator_fee_fixed)) {
            $businessProfile->integrator_fee_fixed = 1.00;
            $changes[] = "Integrator fee fixed défini par défaut (1.00€)";
        }
        
        if (is_null($businessProfile->integrator_fee_percentage)) {
            $businessProfile->integrator_fee_percentage = 3.0;
            $changes[] = "Integrator fee percentage défini par défaut (3%)";
        }
        
        if (is_null($businessProfile->partner_fee_fixed)) {
            $businessProfile->partner_fee_fixed = 0.50;
            $changes[] = "Partner fee fixed défini par défaut (0.50€)";
        }
        
        if (is_null($businessProfile->partner_fee_percentage)) {
            $businessProfile->partner_fee_percentage = 2.0;
            $changes[] = "Partner fee percentage défini par défaut (2%)";
        }
        
        if (is_null($businessProfile->terminal_fee_amount)) {
            $businessProfile->terminal_fee_amount = 0.25;
            $changes[] = "Terminal fee amount défini par défaut (0.25€)";
        }
        
        if (is_null($businessProfile->base_fee_amount)) {
            $businessProfile->base_fee_amount = 1.00;
            $changes[] = "Base fee amount défini par défaut (1.00€)";
        }
        
        // 6. Sauvegarder les changements
        if (!empty($changes)) {
            $businessProfile->save();
            
            Log::info('Business profile corrigé', [
                'business_profile_id' => $businessProfile->id,
                'name' => $businessProfile->name,
                'changes' => $changes
            ]);
        }
    }

    /**
     * Valide la cohérence d'un business profile
     */
    public function validateBusinessProfileConsistency(BusinessProfile $businessProfile): array
    {
        $issues = [];
        
        // Vérifier les pourcentages
        $totalPercentage = ($businessProfile->admin_fee_percentage ?? 0) + 
                          ($businessProfile->integrator_fee_percentage ?? 0) + 
                          ($businessProfile->partner_fee_percentage ?? 0);
        
        if ($totalPercentage > 100) {
            $issues[] = "Total des pourcentages ({$totalPercentage}%) dépasse 100%";
        }
        
        // Vérifier les valeurs négatives
        if (($businessProfile->admin_fee_fixed ?? 0) < 0) {
            $issues[] = "Admin fee fixed est négatif";
        }
        
        if (($businessProfile->integrator_fee_fixed ?? 0) < 0) {
            $issues[] = "Integrator fee fixed est négatif";
        }
        
        if (($businessProfile->partner_fee_fixed ?? 0) < 0) {
            $issues[] = "Partner fee fixed est négatif";
        }
        
        if (($businessProfile->admin_fee_percentage ?? 0) < 0) {
            $issues[] = "Admin fee percentage est négatif";
        }
        
        if (($businessProfile->integrator_fee_percentage ?? 0) < 0) {
            $issues[] = "Integrator fee percentage est négatif";
        }
        
        if (($businessProfile->partner_fee_percentage ?? 0) < 0) {
            $issues[] = "Partner fee percentage est négatif";
        }
        
        // Vérifier les valeurs manquantes
        if (is_null($businessProfile->admin_fee_fixed)) {
            $issues[] = "Admin fee fixed manquant";
        }
        
        if (is_null($businessProfile->admin_fee_percentage)) {
            $issues[] = "Admin fee percentage manquant";
        }
        
        if (is_null($businessProfile->integrator_fee_fixed)) {
            $issues[] = "Integrator fee fixed manquant";
        }
        
        if (is_null($businessProfile->integrator_fee_percentage)) {
            $issues[] = "Integrator fee percentage manquant";
        }
        
        if (is_null($businessProfile->partner_fee_fixed)) {
            $issues[] = "Partner fee fixed manquant";
        }
        
        if (is_null($businessProfile->partner_fee_percentage)) {
            $issues[] = "Partner fee percentage manquant";
        }
        
        return [
            'is_consistent' => empty($issues),
            'issues' => $issues,
            'total_percentage' => $totalPercentage,
            'total_fixed_fees' => ($businessProfile->admin_fee_fixed ?? 0) + 
                                 ($businessProfile->integrator_fee_fixed ?? 0) + 
                                 ($businessProfile->partner_fee_fixed ?? 0) + 
                                 ($businessProfile->terminal_fee_amount ?? 0) + 
                                 ($businessProfile->base_fee_amount ?? 0)
        ];
    }

    /**
     * Valide tous les business profiles
     */
    public function validateAllBusinessProfiles(): array
    {
        $businessProfiles = BusinessProfile::all();
        $results = [
            'total' => $businessProfiles->count(),
            'consistent' => 0,
            'inconsistent' => 0,
            'inconsistent_details' => []
        ];

        foreach ($businessProfiles as $businessProfile) {
            $validation = $this->validateBusinessProfileConsistency($businessProfile);
            
            if ($validation['is_consistent']) {
                $results['consistent']++;
            } else {
                $results['inconsistent']++;
                $results['inconsistent_details'][] = [
                    'business_profile_id' => $businessProfile->id,
                    'name' => $businessProfile->name,
                    'issues' => $validation['issues'],
                    'total_percentage' => $validation['total_percentage'],
                    'total_fixed_fees' => $validation['total_fixed_fees']
                ];
            }
        }

        return $results;
    }
}
