<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service pour configurer les frais des business profiles
 */
class BusinessProfileFeesSetupService
{
    /**
     * Configure les frais par défaut pour tous les business profiles
     */
    public function setupDefaultFees()
    {
        try {
            DB::beginTransaction();

            $businessProfiles = BusinessProfile::all();
            $updatedCount = 0;

            foreach ($businessProfiles as $businessProfile) {
                $this->setupBusinessProfileFees($businessProfile);
                $updatedCount++;
            }

            DB::commit();

            Log::info('Frais par défaut configurés', [
                'business_profiles_updated' => $updatedCount
            ]);

            return $updatedCount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la configuration des frais', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Configure les frais pour un business profile spécifique
     */
    public function setupBusinessProfileFees(BusinessProfile $businessProfile)
    {
        // Frais admin (frais de transaction)
        $businessProfile->admin_fee_fixed = 2.50; // 2.50€ fixe
        $businessProfile->admin_fee_percentage = 5.0; // 5% du montant

        // Frais intégrateur (frais de recharge)
        $businessProfile->integrator_fee_fixed = 1.00; // 1.00€ fixe
        $businessProfile->integrator_fee_percentage = 3.0; // 3% du montant

        // Frais partenaire (frais de maintenance)
        $businessProfile->partner_fee_fixed = 0.50; // 0.50€ fixe
        $businessProfile->partner_fee_percentage = 2.0; // 2% du montant

        // Frais de maintenance
        $businessProfile->maintenance_fee_type = 'percentage';
        $businessProfile->maintenance_fee_amount = 1.5; // 1.5% du montant

        // Frais de terminal
        $businessProfile->terminal_fee_amount = 0.25; // 0.25€ par transaction

        // Frais de base
        $businessProfile->base_fee_amount = 1.00; // 1.00€ de base

        // Configuration des frais de transaction
        $businessProfile->transaction_fee_config = json_encode([
            'fixed' => 2.50,
            'percentage' => 5.0
        ]);

        // Configuration des frais de charge
        $businessProfile->charge_fee_config = json_encode([
            'fixed' => 1.00,
            'percentage' => 3.0
        ]);

        $businessProfile->save();

        Log::info('Frais configurés pour business profile', [
            'business_profile_id' => $businessProfile->id,
            'name' => $businessProfile->name,
            'admin_fee_fixed' => $businessProfile->admin_fee_fixed,
            'admin_fee_percentage' => $businessProfile->admin_fee_percentage,
            'integrator_fee_fixed' => $businessProfile->integrator_fee_fixed,
            'integrator_fee_percentage' => $businessProfile->integrator_fee_percentage,
            'partner_fee_fixed' => $businessProfile->partner_fee_fixed,
            'partner_fee_percentage' => $businessProfile->partner_fee_percentage
        ]);
    }

    /**
     * Crée un business profile avec des frais réalistes
     */
    public function createBusinessProfileWithFees($name = 'Business Profile Test')
    {
        try {
            DB::beginTransaction();

            $businessProfile = BusinessProfile::create([
                'name' => $name,
                'description' => 'Business profile de test avec frais configurés',
                'is_public' => true,
                'is_active' => true,
                'target_audience' => json_encode(['integrator', 'partner']),
                'created_by_type' => 'admin',
                'created_by_id' => 1
            ]);

            $this->setupBusinessProfileFees($businessProfile);

            DB::commit();

            Log::info('Business profile créé avec frais', [
                'business_profile_id' => $businessProfile->id,
                'name' => $businessProfile->name
            ]);

            return $businessProfile;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du business profile', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Assigne un business profile à un point de charge
     */
    public function assignBusinessProfileToChargingPoint(ChargingPoint $chargingPoint, BusinessProfile $businessProfile)
    {
        $chargingPoint->business_profile_id = $businessProfile->id;
        $chargingPoint->save();

        Log::info('Business profile assigné au point de charge', [
            'charging_point_id' => $chargingPoint->id,
            'charging_point_name' => $chargingPoint->name,
            'business_profile_id' => $businessProfile->id,
            'business_profile_name' => $businessProfile->name
        ]);
    }

    /**
     * Configure les frais pour tous les points de charge sans business profile
     */
    public function setupFeesForChargingPointsWithoutBusinessProfile()
    {
        try {
            DB::beginTransaction();

            $chargingPointsWithoutProfile = ChargingPoint::whereNull('business_profile_id')->get();
            $updatedCount = 0;

            foreach ($chargingPointsWithoutProfile as $chargingPoint) {
                // Créer un business profile pour ce point de charge
                $businessProfile = $this->createBusinessProfileWithFees(
                    'Business Profile - ' . $chargingPoint->name
                );

                // Assigner le business profile au point de charge
                $this->assignBusinessProfileToChargingPoint($chargingPoint, $businessProfile);

                $updatedCount++;
            }

            DB::commit();

            Log::info('Business profiles créés pour les points de charge', [
                'charging_points_updated' => $updatedCount
            ]);

            return $updatedCount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la configuration des frais pour les points de charge', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtient un résumé des frais configurés
     */
    public function getFeesSummary()
    {
        $businessProfiles = BusinessProfile::all();
        $summary = [];

        foreach ($businessProfiles as $businessProfile) {
            $summary[] = [
                'id' => $businessProfile->id,
                'name' => $businessProfile->name,
                'admin_fee_fixed' => $businessProfile->admin_fee_fixed ?? 0,
                'admin_fee_percentage' => $businessProfile->admin_fee_percentage ?? 0,
                'integrator_fee_fixed' => $businessProfile->integrator_fee_fixed ?? 0,
                'integrator_fee_percentage' => $businessProfile->integrator_fee_percentage ?? 0,
                'partner_fee_fixed' => $businessProfile->partner_fee_fixed ?? 0,
                'partner_fee_percentage' => $businessProfile->partner_fee_percentage ?? 0,
                'maintenance_fee_type' => $businessProfile->maintenance_fee_type,
                'maintenance_fee_amount' => $businessProfile->maintenance_fee_amount ?? 0,
                'terminal_fee_amount' => $businessProfile->terminal_fee_amount ?? 0,
                'base_fee_amount' => $businessProfile->base_fee_amount ?? 0
            ];
        }

        return $summary;
    }
}
