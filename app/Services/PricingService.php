<?php

namespace App\Services;

use App\Models\AdditionalRate;
use App\Models\PricingPlan;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calcule le prix final en appliquant les tarifs supplémentaires.
     */
    public function calculateFinalPrice(
        PricingPlan $pricingPlan,
        float $basePrice,
        Carbon $datetime,
        ?float $power = null,
        ?int $durationMinutes = null
    ): float {
        $finalPrice = $basePrice;

        // Récupérer les tarifs supplémentaires applicables
        $additionalRates = AdditionalRate::where('pricing_plan_id', $pricingPlan->id)
            ->active()
            ->applicableAt($datetime)
            ->get();

        foreach ($additionalRates as $rate) {
            // Appliquer les conditions de puissance et de durée si spécifiées
            if ($power !== null && ! $rate->isApplicableToPower($power)) {
                continue;
            }

            if ($durationMinutes !== null && ! $rate->isApplicableToDuration($durationMinutes)) {
                continue;
            }

            $finalPrice = $rate->apply($finalPrice);
        }

        return $finalPrice;
    }

    /**
     * Calcule l'estimation du coût pour une réservation
     *
     * @param  string  $type  'energy' ou 'duration'
     * @param  float  $value  Valeur en kWh ou minutes
     */
    public function calculateEstimatedCost(string $type, float $value, PricingPlan $pricingPlan): float
    {
        $cost = 0;

        if ($type === 'energy') {
            // Coût basé sur l'énergie
            $cost = $value * ($pricingPlan->price_per_kwh ?? 0);
        } else {
            // Coût basé sur la durée
            $cost = $value * ($pricingPlan->price_per_minute ?? 0);
        }

        // Ajouter les frais d'activation
        if ($pricingPlan->activation_fee) {
            $cost += $pricingPlan->activation_fee;
        }

        // Appliquer la TVA
        $vatRate = $pricingPlan->vatRate?->rate ?? ($pricingPlan->vat_rate ?? 20);
        $cost = $cost * (1 + ($vatRate / 100));

        return round($cost, 2);
    }

    /**
     * Calcule l'estimation pour l'API (retour structuré)
     */
    public function calculateEstimate(string $type, float $value, PricingPlan $pricingPlan): array
    {
        $subtotal = 0;
        $energyCost = 0;
        $durationCost = 0;

        if ($type === 'energy') {
            $energyCost = $value * ($pricingPlan->price_per_kwh ?? 0);
            $subtotal = $energyCost;
        } else {
            $durationCost = $value * ($pricingPlan->price_per_minute ?? 0);
            $subtotal = $durationCost;
        }

        $activationFee = $pricingPlan->activation_fee ?? 0;
        $subtotal += $activationFee;

        $vatRate = $pricingPlan->vatRate?->rate ?? ($pricingPlan->vat_rate ?? 20);
        $vatAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $vatAmount;

        return [
            'type' => $type,
            'value' => $value,
            'energy_cost' => round($energyCost, 2),
            'duration_cost' => round($durationCost, 2),
            'activation_fee' => round($activationFee, 2),
            'subtotal' => round($subtotal, 2),
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmount, 2),
            'total' => round($total, 2),
            'currency' => $pricingPlan->currency ?? 'EUR',
        ];
    }

    /**
     * Calcule le coût réel à la fin de la session
     *
     * @param  float  $energyConsumed  Energie consommée en kWh
     * @param  int  $durationMinutes  Durée en minutes
     */
    public function calculateActualCost(float $energyConsumed, int $durationMinutes, PricingPlan $pricingPlan): float
    {
        // Utiliser le plus avantageux : énergie ou durée
        $energyCost = $energyConsumed * ($pricingPlan->price_per_kwh ?? 0);
        $durationCost = $durationMinutes * ($pricingPlan->price_per_minute ?? 0);

        // Pour les forfaits énergie, on facture selon l'énergie
        // Pour les forfaits durée, on facture selon le temps
        // Choisir le plus élevé pour assurer la rentabilité
        $baseCost = max($energyCost, $durationCost);

        // Appliquer les tarifs supplémentaires (heures pleines/creuses)
        $baseCost = $this->calculateFinalPrice($pricingPlan, $baseCost, now());

        return round($baseCost, 2);
    }

    /**
     * Obtient les tarifs prédéfinis en français.
     */
    public static function getPredefinedRates(): array
    {
        return [
            [
                'name' => 'Tarif Heures Creuses',
                'description' => '22h-6h',
                'type' => AdditionalRate::TYPE_FIXED,
                'rate_type' => AdditionalRate::TYPE_FIXED,
                'value' => -0.30, // Réduction de 30% (négatif = réduction)
                'price' => -30, // Store as percentage
                'is_percentage' => true,
                'percentage_value' => 30,
                'apply_type' => AdditionalRate::APPLY_MULTIPLY,
                'start_time' => '22:00',
                'end_time' => '06:00',
                'days' => [1, 2, 3, 4, 5, 6, 7], // Tous les jours
                'condition_type' => AdditionalRate::CONDITION_NIGHT,
            ],
            [
                'name' => 'Tarif Week-end',
                'description' => 'Samedi-Dimanche',
                'type' => AdditionalRate::TYPE_FIXED,
                'rate_type' => AdditionalRate::TYPE_FIXED,
                'value' => -0.20, // 20% de réduction
                'price' => -20,
                'is_percentage' => true,
                'percentage_value' => 20,
                'apply_type' => AdditionalRate::APPLY_MULTIPLY,
                'days' => [6, 7], // Samedi (6) et Dimanche (7)
                'condition_type' => AdditionalRate::CONDITION_WEEKEND,
            ],
            [
                'name' => 'Tarif Heures de Pointe',
                'description' => '17h-20h',
                'type' => AdditionalRate::TYPE_FIXED,
                'rate_type' => AdditionalRate::TYPE_FIXED,
                'value' => 0.25, // 25% d'augmentation
                'price' => 25,
                'is_percentage' => true,
                'percentage_value' => 25,
                'apply_type' => AdditionalRate::APPLY_MULTIPLY,
                'start_time' => '17:00',
                'end_time' => '20:00',
                'days' => [1, 2, 3, 4, 5], // Lundi au Vendredi
            ],
            [
                'name' => 'Tarif Pause Déjeuner',
                'description' => '12h-14h',
                'type' => AdditionalRate::TYPE_FIXED,
                'rate_type' => AdditionalRate::TYPE_FIXED,
                'value' => -0.15, // 15% de réduction
                'price' => -15,
                'is_percentage' => true,
                'percentage_value' => 15,
                'apply_type' => AdditionalRate::APPLY_MULTIPLY,
                'start_time' => '12:00',
                'end_time' => '14:00',
                'days' => [1, 2, 3, 4, 5], // Lundi au Vendredi
            ],
            [
                'name' => 'Tarif Matinal',
                'description' => '6h-9h',
                'type' => AdditionalRate::TYPE_FIXED,
                'rate_type' => AdditionalRate::TYPE_FIXED,
                'value' => -0.10, // 10% de réduction
                'price' => -10,
                'is_percentage' => true,
                'percentage_value' => 10,
                'apply_type' => AdditionalRate::APPLY_MULTIPLY,
                'start_time' => '06:00',
                'end_time' => '09:00',
                'days' => [1, 2, 3, 4, 5], // Lundi au Vendredi
            ],
        ];
    }
}
