<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\PricingPlan;

class PricingPlanSelectorService
{
    /**
     * Retourne le plan tarifaire applicable selon la hiérarchie Groupe > Station > Borne.
     * @param ChargingPoint $chargingPoint
     * @return PricingPlan|null
     */
    public function getApplicablePlan(ChargingPoint $chargingPoint): ?PricingPlan
    {
        try {
            // Charger les relations nécessaires avec gestion d'erreur
            $chargingPoint->loadMissing([
                'station.owner.businessProfile.pricingPlan',
                'group.pricingPlans' // Charger pricingPlans au lieu de pricingPlan
            ]);

            // 1. Plan au niveau de la borne (le plus spécifique)
            if ($chargingPoint->pricingPlan) {
                return $chargingPoint->pricingPlan;
            }

            // 2. Plan au niveau de la station
            if ($chargingPoint->station && $chargingPoint->station->pricingPlan) {
                return $chargingPoint->station->pricingPlan;
            }

            // 3. Plan au niveau du groupe
            if ($chargingPoint->group) {
                try {
                    $groupPlan = $chargingPoint->group->pricingPlan();
                    if ($groupPlan) {
                        return $groupPlan;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error loading group pricing plan', [
                        'group_id' => $chargingPoint->group->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 4. Plan au niveau du propriétaire (BusinessProfile)
            if ($chargingPoint->station &&
                $chargingPoint->station->owner &&
                $chargingPoint->station->owner->businessProfile &&
                $chargingPoint->station->owner->businessProfile->pricingPlan) {
                return $chargingPoint->station->owner->businessProfile->pricingPlan;
            }

            // 5. Aucun plan trouvé
            return null;
        } catch (\Exception $e) {
            \Log::error('Error in PricingPlanSelectorService::getApplicablePlan', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
} 