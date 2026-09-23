<?php

namespace App\Services;

use App\Models\AdditionalRate;
use App\Models\PlanRate;
use App\Models\PricingPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PricingPlanService
{
    protected PricingCalculationService $calculationService;

    public function __construct(PricingCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Créer un plan tarifaire complet
     */
    public function createPricingPlan(array $data)
    {
        DB::beginTransaction();

        try {
            // Créer le plan tarifaire principal
            $pricingPlan = PricingPlan::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'priority' => $data['priority'] ?? 0,
                'currency' => $data['currency'] ?? 'EUR',
            ]);

            // Créer les tarifs de base
            if (isset($data['base_rates'])) {
                foreach ($data['base_rates'] as $rate) {
                    PlanRate::create([
                        'pricing_plan_id' => $pricingPlan->id,
                        'type' => $rate['type'], // 'per_minute', 'per_kwh', 'fixed'
                        'value' => $rate['value'],
                        'vat_rate_id' => $rate['vat_rate_id'] ?? null,
                        'is_active' => $rate['is_active'] ?? true,
                    ]);
                }
            }

            // Créer les tarifs supplémentaires (Pick Time)
            if (isset($data['additional_rates'])) {
                foreach ($data['additional_rates'] as $additionalRate) {
                    $this->createAdditionalRate($pricingPlan->id, $additionalRate);
                }
            }

            DB::commit();

            Log::info('Plan tarifaire créé avec succès', [
                'plan_id' => $pricingPlan->id,
                'name' => $pricingPlan->name,
            ]);

            return [
                'success' => true,
                'pricing_plan' => $pricingPlan,
                'message' => 'Plan tarifaire créé avec succès',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la création du plan tarifaire', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création du plan tarifaire: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Mettre à jour un plan tarifaire
     */
    public function updatePricingPlan(PricingPlan $pricingPlan, array $data)
    {
        DB::beginTransaction();

        try {
            // Mettre à jour le plan principal
            $pricingPlan->update([
                'name' => $data['name'] ?? $pricingPlan->name,
                'description' => $data['description'] ?? $pricingPlan->description,
                'is_active' => $data['is_active'] ?? $pricingPlan->is_active,
                'priority' => $data['priority'] ?? $pricingPlan->priority,
            ]);

            // Mettre à jour les tarifs de base
            if (isset($data['base_rates'])) {
                // Supprimer les anciens tarifs
                $pricingPlan->planRates()->delete();

                // Créer les nouveaux tarifs
                foreach ($data['base_rates'] as $rate) {
                    PlanRate::create([
                        'pricing_plan_id' => $pricingPlan->id,
                        'type' => $rate['type'],
                        'value' => $rate['value'],
                        'vat_rate_id' => $rate['vat_rate_id'] ?? null,
                        'is_active' => $rate['is_active'] ?? true,
                    ]);
                }
            }

            // Mettre à jour les tarifs supplémentaires
            if (isset($data['additional_rates'])) {
                // Supprimer les anciens tarifs supplémentaires
                $pricingPlan->additionalRates()->delete();

                // Créer les nouveaux tarifs supplémentaires
                foreach ($data['additional_rates'] as $additionalRate) {
                    $this->createAdditionalRate($pricingPlan->id, $additionalRate);
                }
            }

            DB::commit();

            Log::info('Plan tarifaire mis à jour avec succès', [
                'plan_id' => $pricingPlan->id,
                'name' => $pricingPlan->name,
            ]);

            return [
                'success' => true,
                'pricing_plan' => $pricingPlan->fresh(),
                'message' => 'Plan tarifaire mis à jour avec succès',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la mise à jour du plan tarifaire', [
                'plan_id' => $pricingPlan->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du plan tarifaire: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Créer un tarif supplémentaire (Pick Time)
     */
    protected function createAdditionalRate(int $pricingPlanId, array $data)
    {
        $additionalRate = AdditionalRate::create([
            'pricing_plan_id' => $pricingPlanId,
            'name' => $data['name'],
            'type' => $data['type'], // 'per_minute', 'per_kwh'
            'value' => $data['value'],
            'vat_rate_id' => $data['vat_rate_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Créer les jours et heures d'application
        if (isset($data['schedule'])) {
            foreach ($data['schedule'] as $schedule) {
                $additionalRate->days()->create([
                    'day_of_week' => $schedule['day_of_week'], // 1-7 (Lundi-Dimanche)
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                ]);
            }
        }

        return $additionalRate;
    }

    /**
     * Calculer le prix pour une session de recharge
     * Delegates to PricingCalculationService for comprehensive pricing logic
     */
    public function calculatePrice(PricingPlan $pricingPlan, array $sessionData)
    {
        try {
            return $this->calculationService->calculatePrice($pricingPlan, $sessionData);
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul du prix', [
                'pricing_plan_id' => $pricingPlan->id,
                'session_data' => $sessionData,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du calcul du prix: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier si un tarif supplémentaire est applicable
     */
    protected function isAdditionalRateApplicable(AdditionalRate $additionalRate, $startTime)
    {
        $startDateTime = Carbon::parse($startTime);
        $dayOfWeek = $startDateTime->dayOfWeek; // 0-6 (Dimanche-Samedi)
        $time = $startDateTime->format('H:i:s');

        // Convertir en format 1-7 (Lundi-Dimanche)
        $dayOfWeek = $dayOfWeek === 0 ? 7 : $dayOfWeek;

        foreach ($additionalRate->days as $day) {
            if ($day->day_of_week == $dayOfWeek) {
                if ($time >= $day->start_time && $time <= $day->end_time) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Obtenir les plans tarifaires avec statistiques
     */
    public function getPricingPlansWithStats($filters = [])
    {
        $query = PricingPlan::with(['planRates', 'additionalRates', 'chargingPoints']);

        // Appliquer les filtres
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        $pricingPlans = $query->get();

        // Ajouter les statistiques
        foreach ($pricingPlans as $plan) {
            $plan->stats = [
                'charging_points_count' => $plan->chargingPoints()->count(),
                'groups_count' => $plan->groups()->count(),
                'total_revenue' => $this->calculatePlanRevenue($plan),
            ];
        }

        return $pricingPlans;
    }

    /**
     * Calculer les revenus d'un plan tarifaire
     */
    protected function calculatePlanRevenue(PricingPlan $pricingPlan)
    {
        return $pricingPlan->chargingPoints()
            ->join('transactions', 'charging_points.id', '=', 'transactions.charging_point_id')
            ->where('transactions.status', 'completed')
            ->sum('transactions.amount');
    }

    /**
     * Dupliquer un plan tarifaire
     */
    public function duplicatePricingPlan(PricingPlan $originalPlan, array $data)
    {
        DB::beginTransaction();

        try {
            // Créer le nouveau plan
            $newPlan = $originalPlan->replicate();
            $newPlan->name = $data['name'] ?? $originalPlan->name.' (Copie)';
            $newPlan->is_active = $data['is_active'] ?? false;
            $newPlan->save();

            // Dupliquer les tarifs de base
            foreach ($originalPlan->planRates as $rate) {
                $newRate = $rate->replicate();
                $newRate->pricing_plan_id = $newPlan->id;
                $newRate->save();
            }

            // Dupliquer les tarifs supplémentaires
            foreach ($originalPlan->additionalRates as $additionalRate) {
                $newAdditionalRate = $additionalRate->replicate();
                $newAdditionalRate->pricing_plan_id = $newPlan->id;
                $newAdditionalRate->save();

                // Dupliquer les jours
                foreach ($additionalRate->days as $day) {
                    $newDay = $day->replicate();
                    $newDay->additional_rate_id = $newAdditionalRate->id;
                    $newDay->save();
                }
            }

            DB::commit();

            Log::info('Plan tarifaire dupliqué avec succès', [
                'original_plan_id' => $originalPlan->id,
                'new_plan_id' => $newPlan->id,
            ]);

            return [
                'success' => true,
                'pricing_plan' => $newPlan,
                'message' => 'Plan tarifaire dupliqué avec succès',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la duplication du plan tarifaire', [
                'original_plan_id' => $originalPlan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la duplication du plan tarifaire: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Désactiver un plan tarifaire
     */
    public function deactivatePricingPlan(PricingPlan $pricingPlan)
    {
        try {
            $pricingPlan->update(['is_active' => false]);

            Log::info('Plan tarifaire désactivé', [
                'plan_id' => $pricingPlan->id,
                'name' => $pricingPlan->name,
            ]);

            return [
                'success' => true,
                'message' => 'Plan tarifaire désactivé avec succès',
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la désactivation du plan tarifaire', [
                'plan_id' => $pricingPlan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la désactivation du plan tarifaire',
            ];
        }
    }
}
