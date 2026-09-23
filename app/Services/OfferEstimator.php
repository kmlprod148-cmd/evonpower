<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

class OfferEstimator
{
    protected $chargingPoint;

    public function __construct(ChargingPoint $chargingPoint)
    {
        $this->chargingPoint = $chargingPoint;
    }

    /**
     * Estime le coût d'une réservation selon les options choisies
     *
     * @param array $options ['mode' => 'per_kw'|'per_min', 'value' => float]
     * @return array
     */
    public function estimate(array $options): array
    {
        $mode = $options['mode'] ?? 'per_kw';
        $value = (float) ($options['value'] ?? 0);

        if ($value <= 0) {
            return [
                'energy' => 0,
                'base' => 0,
                'fees' => 0,
                'total' => 0,
                'currency' => 'EUR'
            ];
        }

        // Récupérer le plan tarifaire
        $pricingPlan = $this->chargingPoint->pricingPlan;
        if (!$pricingPlan) {
            throw new \Exception('Aucun plan tarifaire associé à cette borne');
        }

        $energy = 0;
        $base = 0;

        if ($mode === 'per_kw') {
            // Mode facturation par kWh
            $energy = $value;
            $base = $energy * $pricingPlan->price_per_kwh;
        } else {
            // Mode facturation par minute
            $minutes = $value;
            // Conversion minutes -> kWh selon le taux de charge de la borne
            $kwPerMinute = $this->chargingPoint->power_output / 60; // kW par minute (puissance/60)
            $energy = $minutes * $kwPerMinute;
            $base = $minutes * $pricingPlan->price_per_minute;
        }

        // Ajouter les frais d'activation
        $base += $pricingPlan->activation_fee ?? 0;

        // Appliquer les frais variables du BusinessProfile
        $fees = $this->calculateBusinessProfileFees($base);

        $total = $base + $fees;

        return [
            'energy' => round($energy, 2),
            'base' => round($base, 2),
            'fees' => round($fees, 2),
            'total' => round($total, 2),
            'currency' => $pricingPlan->currency ?? 'EUR',
            'breakdown' => [
                'energy_cost' => $mode === 'per_kw' ? $base - ($pricingPlan->activation_fee ?? 0) : 0,
                'time_cost' => $mode === 'per_min' ? $base - ($pricingPlan->activation_fee ?? 0) : 0,
                'activation_fee' => $pricingPlan->activation_fee ?? 0,
                'business_fees' => $fees
            ]
        ];
    }

    /**
     * Vérifie si la limite de réservations est respectée
     *
     * @return bool
     */
    public function checkReservationLimit(): bool
    {
        $plan = $this->chargingPoint->pricingPlan;
        if (!$plan) {
            return true; // Pas de plan = pas de limite
        }

        // Récupérer la limite depuis le plan
        $limit = $plan->max_reservations ?? null;
        if (!$limit) {
            return true; // Pas de limite définie
        }

        try {
            // Compter les réservations actives
            $activeReservations = $this->chargingPoint->reservations()
                ->where('status', 'active')
                ->orWhere('status', 'pending')
                ->count();

            return $activeReservations < $limit;
        } catch (\Exception $e) {
            // Si la table n'existe pas ou autre erreur, considérer qu'il n'y a pas de limite
            Log::warning('Impossible de vérifier la limite de réservations: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Récupère les informations sur la limite de réservations
     *
     * @return array
     */
    public function getReservationLimitInfo(): array
    {
        $plan = $this->chargingPoint->pricingPlan;
        if (!$plan) {
            return [
                'has_limit' => false,
                'current' => 0,
                'max' => null,
                'available' => null
            ];
        }

        $limit = $plan->max_reservations ?? null;
        if (!$limit) {
            return [
                'has_limit' => false,
                'current' => 0,
                'max' => null,
                'available' => null
            ];
        }

        try {
            $current = $this->chargingPoint->reservations()
                ->where('status', 'active')
                ->orWhere('status', 'pending')
                ->count();

            return [
                'has_limit' => true,
                'current' => $current,
                'max' => $limit,
                'available' => max(0, $limit - $current)
            ];
        } catch (\Exception $e) {
            // Si la table n'existe pas ou autre erreur, considérer qu'il n'y a pas de limite
            Log::warning('Impossible de récupérer les informations de limite de réservations: ' . $e->getMessage());
            return [
                'has_limit' => false,
                'current' => 0,
                'max' => null,
                'available' => null
            ];
        }
    }

    /**
     * Calcule les frais variables du BusinessProfile
     *
     * @param float $baseAmount
     * @return float
     */
    protected function calculateBusinessProfileFees(float $baseAmount): float
    {
        $businessProfile = $this->chargingPoint->businessProfile;
        if (!$businessProfile) {
            return 0;
        }

        $fees = 0;

        // Frais de recharge (pourcentage ou fixe)
        if (isset($businessProfile->recharge_fee)) {
            $fees += (float) $businessProfile->recharge_fee;
        }

        // Autres frais
        if (isset($businessProfile->other_fees)) {
            $fees += (float) $businessProfile->other_fees;
        }

        // Frais basés sur la configuration des frais de transaction
        $transactionFeeConfig = $businessProfile->transaction_fee_config ?? [];
        if (is_array($transactionFeeConfig)) {
            // Frais fixes
            if (isset($transactionFeeConfig['fixed_fee'])) {
                $fees += (float) $transactionFeeConfig['fixed_fee'];
            }
            
            // Frais en pourcentage
            if (isset($transactionFeeConfig['percentage_fee'])) {
                $fees += $baseAmount * ((float) $transactionFeeConfig['percentage_fee'] / 100);
            }
        }

        return $fees;
    }

    /**
     * Valide les options d'estimation
     *
     * @param array $options
     * @return array
     */
    public function validateOptions(array $options): array
    {
        $errors = [];

        if (!isset($options['mode'])) {
            $errors[] = 'Le mode de facturation est requis';
        } elseif (!in_array($options['mode'], ['per_kw', 'per_min'])) {
            $errors[] = 'Le mode de facturation doit être "per_kw" ou "per_min"';
        }

        if (!isset($options['value'])) {
            $errors[] = 'La valeur est requise';
        } elseif (!is_numeric($options['value']) || $options['value'] <= 0) {
            $errors[] = 'La valeur doit être un nombre positif';
        }

        // Vérifications spécifiques selon le mode
        if (isset($options['mode']) && isset($options['value'])) {
            if ($options['mode'] === 'per_kw' && $options['value'] > 100) {
                $errors[] = 'La quantité d\'énergie ne peut pas dépasser 100 kWh';
            }
            
            if ($options['mode'] === 'per_min' && $options['value'] > 480) {
                $errors[] = 'La durée ne peut pas dépasser 8 heures (480 minutes)';
            }
        }

        return $errors;
    }

    /**
     * Récupère les informations de la borne pour l'affichage
     *
     * @return array
     */
    public function getChargingPointInfo(): array
    {
        $pricingPlan = $this->chargingPoint->pricingPlan;
        
        return [
            'id' => $this->chargingPoint->id,
            'name' => $this->chargingPoint->name,
            'status' => $this->chargingPoint->status,
            'power_output' => $this->chargingPoint->power_output,
            'pricing_plan' => $pricingPlan ? [
                'name' => $pricingPlan->name,
                'price_per_kwh' => $pricingPlan->price_per_kwh,
                'price_per_minute' => $pricingPlan->price_per_minute,
                'activation_fee' => $pricingPlan->activation_fee,
                'currency' => $pricingPlan->currency ?? 'EUR',
                'max_duration' => $pricingPlan->max_duration,
                'max_reservations' => $pricingPlan->max_reservations
            ] : null,
            'business_profile' => $this->chargingPoint->businessProfile ? [
                'name' => $this->chargingPoint->businessProfile->name,
                'recharge_fee' => $this->chargingPoint->businessProfile->recharge_fee ?? 0,
                'other_fees' => $this->chargingPoint->businessProfile->other_fees ?? 0
            ] : null
        ];
    }
}