<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Log;

class ReservationCostCalculationService
{
    /**
     * Calcule le coût total d'une réservation basé sur le plan tarifaire
     */
    public function calculateReservationCost(Reservation $reservation): float
    {
        $pricingPlan = $reservation->pricingPlan;
        
        if (!$pricingPlan) {
            Log::warning('Aucun plan tarifaire trouvé pour la réservation', [
                'reservation_id' => $reservation->id
            ]);
            return 0;
        }

        $cost = 0;

        // Frais d'activation
        if ($pricingPlan->activation_fee) {
            $cost += (float) $pricingPlan->activation_fee;
        }

        // Tarif de base
        if ($pricingPlan->base_rate) {
            $cost += (float) $pricingPlan->base_rate;
        }

        // Calcul selon le type de réservation
        if ($reservation->reservation_type === 'kwh' && $pricingPlan->price_per_kwh) {
            $cost += (float) $reservation->reservation_value * (float) $pricingPlan->price_per_kwh;
        } elseif ($reservation->reservation_type === 'minute' && $pricingPlan->price_per_minute) {
            $cost += (float) $reservation->reservation_value * (float) $pricingPlan->price_per_minute;
        }

        // Appliquer la TVA si configurée
        if ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) {
            $cost = $cost * (1 + ($pricingPlan->vatRate->rate / 100));
        } elseif (isset($pricingPlan->vat_rate) && $pricingPlan->vat_rate > 0) {
            $cost = $cost * (1 + ($pricingPlan->vat_rate / 100));
        }

        $finalCost = round($cost, 2);

        Log::info('Coût de réservation calculé', [
            'reservation_id' => $reservation->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_type' => $reservation->reservation_type,
            'reservation_value' => $reservation->reservation_value,
            'activation_fee' => $pricingPlan->activation_fee,
            'base_rate' => $pricingPlan->base_rate,
            'price_per_kwh' => $pricingPlan->price_per_kwh,
            'price_per_minute' => $pricingPlan->price_per_minute,
            'vat_rate' => $pricingPlan->vatRate ? $pricingPlan->vatRate->rate : 0,
            'calculated_cost' => $finalCost
        ]);

        return $finalCost;
    }

    /**
     * Calcule le coût estimé pour une réservation basé sur les données de validation
     */
    public function calculateEstimatedCostFromData(array $validatedData, PricingPlan $pricingPlan): float
    {
        $cost = 0;

        // Frais d'activation
        if ($pricingPlan->activation_fee) {
            $cost += (float) $pricingPlan->activation_fee;
        }

        // Tarif de base
        if ($pricingPlan->base_rate) {
            $cost += (float) $pricingPlan->base_rate;
        }

        // Calcul selon le type de réservation
        $reservationType = $validatedData['reservation_type'] ?? null;
        $reservationValue = (float) ($validatedData['reservation_value'] ?? 0);

        if ($reservationType === 'kwh' && $pricingPlan->price_per_kwh && $reservationValue > 0) {
            $cost += $reservationValue * (float) $pricingPlan->price_per_kwh;
        } elseif ($reservationType === 'minute' && $pricingPlan->price_per_minute && $reservationValue > 0) {
            $cost += $reservationValue * (float) $pricingPlan->price_per_minute;
        }

        // Appliquer la TVA si configurée
        if ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) {
            $cost = $cost * (1 + ($pricingPlan->vatRate->rate / 100));
        } elseif (isset($pricingPlan->vat_rate) && $pricingPlan->vat_rate > 0) {
            $cost = $cost * (1 + ($pricingPlan->vat_rate / 100));
        }

        return round($cost, 2);
    }

    /**
     * Valide et corrige le coût d'une réservation existante
     */
    public function validateAndFixReservationCost(Reservation $reservation): bool
    {
        $currentCost = $reservation->estimated_cost ?? 0;
        $calculatedCost = $this->calculateReservationCost($reservation);

        if ($calculatedCost <= 0) {
            Log::warning('Impossible de calculer un coût valide pour la réservation', [
                'reservation_id' => $reservation->id,
                'current_cost' => $currentCost,
                'calculated_cost' => $calculatedCost
            ]);
            return false;
        }

        if ($currentCost != $calculatedCost) {
            $reservation->update([
                'estimated_cost' => $calculatedCost,
                'actual_cost' => $calculatedCost
            ]);

            Log::info('Coût de réservation corrigé', [
                'reservation_id' => $reservation->id,
                'old_cost' => $currentCost,
                'new_cost' => $calculatedCost
            ]);

            return true;
        }

        return false;
    }

    /**
     * Calcule le coût avec des valeurs par défaut si les données sont insuffisantes
     */
    public function calculateCostWithDefaults(Reservation $reservation, float $defaultValue = 1.0): float
    {
        $pricingPlan = $reservation->pricingPlan;
        
        if (!$pricingPlan) {
            return 0;
        }

        $cost = 0;

        // Frais d'activation
        if ($pricingPlan->activation_fee) {
            $cost += (float) $pricingPlan->activation_fee;
        }

        // Tarif de base
        if ($pricingPlan->base_rate) {
            $cost += (float) $pricingPlan->base_rate;
        }

        // Utiliser la valeur de réservation ou une valeur par défaut
        $reservationValue = $reservation->reservation_value > 0 ? $reservation->reservation_value : $defaultValue;

        if ($reservation->reservation_type === 'kwh' && $pricingPlan->price_per_kwh) {
            $cost += (float) $reservationValue * (float) $pricingPlan->price_per_kwh;
        } elseif ($reservation->reservation_type === 'minute' && $pricingPlan->price_per_minute) {
            $cost += (float) $reservationValue * (float) $pricingPlan->price_per_minute;
        }

        // Appliquer la TVA si configurée
        if ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) {
            $cost = $cost * (1 + ($pricingPlan->vatRate->rate / 100));
        } elseif (isset($pricingPlan->vat_rate) && $pricingPlan->vat_rate > 0) {
            $cost = $cost * (1 + ($pricingPlan->vat_rate / 100));
        }

        return round($cost, 2);
    }

    /**
     * Obtient un résumé détaillé du calcul des coûts
     */
    public function getCostBreakdown(Reservation $reservation): array
    {
        $pricingPlan = $reservation->pricingPlan;
        
        if (!$pricingPlan) {
            return [
                'error' => 'Aucun plan tarifaire trouvé',
                'total_cost' => 0
            ];
        }

        $breakdown = [
            'activation_fee' => (float) ($pricingPlan->activation_fee ?? 0),
            'base_rate' => (float) ($pricingPlan->base_rate ?? 0),
            'energy_cost' => 0,
            'time_cost' => 0,
            'subtotal' => 0,
            'vat_rate' => $pricingPlan->vatRate ? (float) $pricingPlan->vatRate->rate : 0,
            'vat_amount' => 0,
            'total_cost' => 0
        ];

        // Calculer les coûts selon le type de réservation
        if ($reservation->reservation_type === 'kwh' && $pricingPlan->price_per_kwh) {
            $breakdown['energy_cost'] = (float) $reservation->reservation_value * (float) $pricingPlan->price_per_kwh;
        } elseif ($reservation->reservation_type === 'minute' && $pricingPlan->price_per_minute) {
            $breakdown['time_cost'] = (float) $reservation->reservation_value * (float) $pricingPlan->price_per_minute;
        }

        // Calculer le sous-total
        $breakdown['subtotal'] = $breakdown['activation_fee'] + $breakdown['base_rate'] + $breakdown['energy_cost'] + $breakdown['time_cost'];

        // Calculer la TVA
        if ($breakdown['vat_rate'] > 0) {
            $breakdown['vat_amount'] = $breakdown['subtotal'] * ($breakdown['vat_rate'] / 100);
        }

        // Calculer le total
        $breakdown['total_cost'] = $breakdown['subtotal'] + $breakdown['vat_amount'];

        return $breakdown;
    }
}
