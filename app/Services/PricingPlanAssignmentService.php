<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Connector;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PricingPlanAssignmentService
{
    /**
     * Applique un plan tarifaire à une borne de recharge
     *
     * @param int $chargingPointId
     * @param int $pricingPlanId
     * @param bool $applyToConnectors
     * @return bool
     */
    public function assignPlanToChargingPoint(int $chargingPointId, int $pricingPlanId, bool $applyToConnectors = false): bool
    {
        try {
            DB::beginTransaction();

            // Vérifier que la borne et le plan existent
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $pricingPlan = PricingPlan::findOrFail($pricingPlanId);

            // Appliquer le plan à la borne
            $chargingPoint->pricing_plan_id = $pricingPlanId;
            $chargingPoint->save();

            // Si demandé, appliquer également le plan à tous les connecteurs de la borne
                    // Connecteurs supprimés - pas de mise à jour des connecteurs

            DB::commit();
            
            Log::info('Plan tarifaire appliqué à la borne', [
                'charging_point_id' => $chargingPointId,
                'pricing_plan_id' => $pricingPlanId,
                'apply_to_connectors' => $applyToConnectors
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de l\'application du plan tarifaire à la borne', [
                'charging_point_id' => $chargingPointId,
                'pricing_plan_id' => $pricingPlanId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Applique un plan tarifaire à un connecteur spécifique
     *
     * @param int $connectorId
     * @param int $pricingPlanId
     * @return bool
     */
    public function assignPlanToConnector(int $connectorId, int $pricingPlanId): bool
    {
        try {
            DB::beginTransaction();

            // Vérifier que le connecteur et le plan existent
            $connector = Connector::findOrFail($connectorId);
            $pricingPlan = PricingPlan::findOrFail($pricingPlanId);

            // Appliquer le plan au connecteur
            $connector->pricing_plan_id = $pricingPlanId;
            $connector->save();

            DB::commit();
            
            Log::info('Plan tarifaire appliqué au connecteur', [
                'connector_id' => $connectorId,
                'pricing_plan_id' => $pricingPlanId
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de l\'application du plan tarifaire au connecteur', [
                'connector_id' => $connectorId,
                'pricing_plan_id' => $pricingPlanId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Applique un plan tarifaire à plusieurs bornes
     *
     * @param array $chargingPointIds
     * @param int $pricingPlanId
     * @param bool $applyToConnectors
     * @return array
     */
    public function assignPlanToMultipleChargingPoints(array $chargingPointIds, int $pricingPlanId, bool $applyToConnectors = false): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'failed_ids' => []
        ];

        foreach ($chargingPointIds as $chargingPointId) {
            $success = $this->assignPlanToChargingPoint($chargingPointId, $pricingPlanId, $applyToConnectors);
            
            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['failed_ids'][] = $chargingPointId;
            }
        }

        return $results;
    }

    /**
     * Récupère les plans tarifaires compatibles pour une borne donnée
     * en fonction de l'intégrateur, du partenaire et du groupe
     *
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompatiblePlansForChargingPoint(ChargingPoint $chargingPoint)
    {
        $query = PricingPlan::where('is_active', true);

        // Plans globaux
        $query->where(function($q) use ($chargingPoint) {
            // Plans globaux
            $q->where(function($subQuery) {
                $subQuery->where('applies_to_type', 'global')
                         ->whereNull('applies_to_id');
            });

            // Plans spécifiques à l'intégrateur
            if ($chargingPoint->integrator_id) {
                $q->orWhere(function($subQuery) use ($chargingPoint) {
                    $subQuery->where('applies_to_type', 'integrator')
                             ->where('applies_to_id', $chargingPoint->integrator_id);
                });
            }

            // Plans spécifiques au partenaire
            if ($chargingPoint->partner_id) {
                $q->orWhere(function($subQuery) use ($chargingPoint) {
                    $subQuery->where('applies_to_type', 'partner')
                             ->where('applies_to_id', $chargingPoint->partner_id);
                });
            }

            // Plans spécifiques au groupe
            if ($chargingPoint->group_id) {
                $q->orWhere(function($subQuery) use ($chargingPoint) {
                    $subQuery->where('applies_to_type', 'group')
                             ->where('applies_to_id', $chargingPoint->group_id);
                });
            }
        });

        // Trier par priorité (décroissante) et par ID (croissant)
        return $query->orderBy('priority', 'desc')
                     ->orderBy('id', 'asc')
                     ->get();
    }

    /**
     * Récupère le plan tarifaire par défaut pour une borne
     * en fonction de sa hiérarchie (intégrateur, partenaire, groupe)
     *
     * @param ChargingPoint $chargingPoint
     * @return PricingPlan|null
     */
    public function getDefaultPlanForChargingPoint(ChargingPoint $chargingPoint): ?PricingPlan
    {
        // Vérifier s'il y a un plan par défaut pour le groupe
        if ($chargingPoint->group_id) {
            $groupPlan = PricingPlan::where('is_active', true)
                ->where('is_default', true)
                ->where('applies_to_type', 'group')
                ->where('applies_to_id', $chargingPoint->group_id)
                ->first();
            
            if ($groupPlan) {
                return $groupPlan;
            }
        }

        // Vérifier s'il y a un plan par défaut pour le partenaire
        if ($chargingPoint->partner_id) {
            $partnerPlan = PricingPlan::where('is_active', true)
                ->where('is_default', true)
                ->where('applies_to_type', 'partner')
                ->where('applies_to_id', $chargingPoint->partner_id)
                ->first();
            
            if ($partnerPlan) {
                return $partnerPlan;
            }
        }

        // Vérifier s'il y a un plan par défaut pour l'intégrateur
        if ($chargingPoint->integrator_id) {
            $integratorPlan = PricingPlan::where('is_active', true)
                ->where('is_default', true)
                ->where('applies_to_type', 'integrator')
                ->where('applies_to_id', $chargingPoint->integrator_id)
                ->first();
            
            if ($integratorPlan) {
                return $integratorPlan;
            }
        }

        // Sinon, retourner le plan global par défaut
        return PricingPlan::where('is_active', true)
            ->where('is_default', true)
            ->where('applies_to_type', 'global')
            ->first();
    }
}