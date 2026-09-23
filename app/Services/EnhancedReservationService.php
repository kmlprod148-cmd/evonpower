<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * Service amélioré pour la gestion des réservations
 * Avec une meilleure gestion des erreurs et validation
 */
class EnhancedReservationService
{
    /**
     * Créer une réservation avec validation améliorée
     */
    public function createReservation(array $reservationData, ChargingPoint $chargingPoint)
    {
        try {
            DB::beginTransaction();
            
            // Validation des données
            $this->validateReservationData($reservationData, $chargingPoint);
            
            // Récupérer le plan tarifaire
            $pricingPlan = $this->getPricingPlan($reservationData['pricing_plan_id']);
            
            // Validation des limites du plan
            $this->validatePlanLimits($reservationData, $pricingPlan, $chargingPoint);
            
            // Créer la réservation
            $reservation = $this->createReservationRecord($reservationData, $chargingPoint, $pricingPlan);
            
            // Calculer le coût estimé
            $estimatedCost = $this->calculateEstimatedCost($reservation, $pricingPlan);
            
            // Mettre à jour la réservation avec le coût
            $reservation->update([
                'estimated_cost' => $estimatedCost,
                'actual_cost' => $estimatedCost,
                'total_cost' => $estimatedCost
            ]);
            
            DB::commit();
            
            Log::info('Reservation created successfully', [
                'reservation_id' => $reservation->id,
                'charging_point_id' => $chargingPoint->id,
                'estimated_cost' => $estimatedCost
            ]);
            
            return [
                'status' => 'success',
                'reservation' => $reservation,
                'message' => 'Réservation créée avec succès'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating reservation: ' . $e->getMessage(), [
                'reservation_data' => $reservationData,
                'charging_point_id' => $chargingPoint->id,
                'exception' => $e
            ]);
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'error' => 'reservation_creation_failed'
            ];
        }
    }
    
    /**
     * Valider les données de réservation
     */
    private function validateReservationData(array $data, ChargingPoint $chargingPoint)
    {
        $requiredFields = [
            'pricing_plan_id',
            'reservation_type',
            'reservation_value',
            'payment_type'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ '{$field}' est requis.");
            }
        }
        
        // Validation du type de réservation
        if (!in_array($data['reservation_type'], ['kwh', 'minute'])) {
            throw new \InvalidArgumentException("Le type de réservation doit être 'kwh' ou 'minute'.");
        }
        
        // Validation de la valeur
        if (!is_numeric($data['reservation_value']) || $data['reservation_value'] <= 0) {
            throw new \InvalidArgumentException("La valeur de réservation doit être un nombre positif.");
        }
        
        // Validation du type de paiement
        if (!in_array($data['payment_type'], ['cmi', 'offline'])) {
            throw new \InvalidArgumentException("Le type de paiement doit être 'cmi' ou 'offline'.");
        }
    }
    
    /**
     * Récupérer et valider le plan tarifaire
     */
    private function getPricingPlan($pricingPlanId)
    {
        $pricingPlan = PricingPlan::find($pricingPlanId);
        
        if (!$pricingPlan) {
            throw new \InvalidArgumentException("Le plan tarifaire sélectionné n'existe pas.");
        }
        
        if (!$pricingPlan->is_active) {
            throw new \InvalidArgumentException("Le plan tarifaire sélectionné n'est pas actif.");
        }
        
        // Vérifier la période de validité
        $now = now();
        if ($pricingPlan->valid_from && $now->lt($pricingPlan->valid_from)) {
            throw new \InvalidArgumentException("Ce plan tarifaire n'est pas encore disponible.");
        }
        
        if ($pricingPlan->valid_until && $now->gt($pricingPlan->valid_until)) {
            throw new \InvalidArgumentException("Ce plan tarifaire n'est plus disponible.");
        }
        
        return $pricingPlan;
    }
    
    /**
     * Valider les limites du plan tarifaire
     */
    private function validatePlanLimits(array $data, PricingPlan $pricingPlan, ChargingPoint $chargingPoint)
    {
        $reservationType = $data['reservation_type'];
        $reservationValue = $data['reservation_value'];
        
        if ($reservationType === 'minute' && $pricingPlan->max_duration) {
            if ($reservationValue > $pricingPlan->max_duration) {
                throw new \InvalidArgumentException(
                    "La durée de réservation ne peut pas dépasser {$pricingPlan->max_duration} minutes."
                );
            }
        }
        
        if ($reservationType === 'kwh' && $pricingPlan->max_duration && $chargingPoint->power_output) {
            $maxEnergy = $chargingPoint->power_output * ($pricingPlan->max_duration / 60);
            if ($reservationValue > $maxEnergy) {
                throw new \InvalidArgumentException(
                    "La quantité d'énergie ne peut pas dépasser " . number_format($maxEnergy, 2) . " kWh."
                );
            }
        }
    }
    
    /**
     * Créer l'enregistrement de réservation
     */
    private function createReservationRecord(array $data, ChargingPoint $chargingPoint, PricingPlan $pricingPlan)
    {
        $reservationData = [
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_type' => $data['reservation_type'],
            'reservation_value' => $data['reservation_value'],
            'payment_type' => $data['payment_type'],
            'start_time' => $data['start_time'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'guest_phone' => $data['guest_phone'] ?? null,
            'status' => 'pending',
            'user_id' => auth()->id()
        ];
        
        // Ajouter les données spécifiques au type
        if ($data['reservation_type'] === 'kwh') {
            $reservationData['energy_kwh'] = $data['reservation_value'];
        } elseif ($data['reservation_type'] === 'minute') {
            $reservationData['duration_minutes'] = $data['reservation_value'];
        }
        
        return Reservation::create($reservationData);
    }
    
    /**
     * Calculer le coût estimé de la réservation
     */
    private function calculateEstimatedCost(Reservation $reservation, PricingPlan $pricingPlan)
    {
        $cost = 0;
        
        // Frais d'activation
        if ($pricingPlan->activation_fee) {
            $cost += $pricingPlan->activation_fee;
        }
        
        // Tarif de base
        if ($pricingPlan->base_rate) {
            $cost += $pricingPlan->base_rate;
        }
        
        // Calcul selon le type de réservation
        if ($reservation->reservation_type === 'kwh' && $pricingPlan->price_per_kwh) {
            $cost += $reservation->reservation_value * $pricingPlan->price_per_kwh;
        } elseif ($reservation->reservation_type === 'minute' && $pricingPlan->price_per_minute) {
            $cost += $reservation->reservation_value * $pricingPlan->price_per_minute;
        }
        
        // Appliquer la TVA si configurée
        if ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) {
            $cost = $cost * (1 + ($pricingPlan->vatRate->rate / 100));
        }
        
        return round($cost, 2);
    }
    
    /**
     * Valider une valeur de réservation
     */
    public function validateReservationValue($value, $type, PricingPlan $pricingPlan, ChargingPoint $chargingPoint)
    {
        if ($type === 'minute') {
            return $this->validateDurationLimit($value, $pricingPlan);
        } elseif ($type === 'kwh') {
            return $this->validateEnergyLimit($value, $pricingPlan, $chargingPoint);
        }
        
        return ['valid' => true];
    }
    
    /**
     * Valider la limite de durée
     */
    private function validateDurationLimit($durationMinutes, PricingPlan $pricingPlan)
    {
        if ($pricingPlan->max_duration && $durationMinutes > $pricingPlan->max_duration) {
            return [
                'valid' => false,
                'message' => "La durée ne peut pas dépasser {$pricingPlan->max_duration} minutes selon ce plan tarifaire."
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Valider la limite d'énergie
     */
    private function validateEnergyLimit($energyKwh, PricingPlan $pricingPlan, ChargingPoint $chargingPoint)
    {
        if ($pricingPlan->max_duration && $chargingPoint->power_output) {
            $maxEnergy = $chargingPoint->power_output * ($pricingPlan->max_duration / 60);
            if ($energyKwh > $maxEnergy) {
                return [
                    'valid' => false,
                    'message' => "La quantité d'énergie ne peut pas dépasser " . number_format($maxEnergy, 2) . " kWh selon ce plan tarifaire."
                ];
            }
        }
        
        return ['valid' => true];
    }
}
