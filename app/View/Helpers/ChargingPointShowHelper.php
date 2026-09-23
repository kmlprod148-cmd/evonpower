<?php

namespace App\View\Helpers;

use App\Models\ChargingPoint;
use App\Models\PricingPlan;

class ChargingPointShowHelper
{
    /**
     * Détermine les types de réservation disponibles selon le plan tarifaire
     */
    public static function getAvailableReservationTypes(?PricingPlan $pricingPlan): array
    {
        $availableTypes = [];
        
        if ($pricingPlan) {
            $rateType = strtolower($pricingPlan->rate_type ?? '');
            $hasKwhPricing = !is_null($pricingPlan->price_per_kwh) && $pricingPlan->price_per_kwh > 0;
            $hasMinutePricing = !is_null($pricingPlan->price_per_minute) && $pricingPlan->price_per_minute > 0;
            
            switch ($rateType) {
                case 'time':
                case 'minute':
                    if ($hasMinutePricing) {
                        $availableTypes[] = 'minute';
                    }
                    break;
                case 'energy':
                case 'kwh':
                    if ($hasKwhPricing) {
                        $availableTypes[] = 'kwh';
                    }
                    break;
                case 'mixed':
                case 'both':
                    if ($hasKwhPricing) {
                        $availableTypes[] = 'kwh';
                    }
                    if ($hasMinutePricing) {
                        $availableTypes[] = 'minute';
                    }
                    break;
                default:
                    if ($hasKwhPricing) {
                        $availableTypes[] = 'kwh';
                    }
                    if ($hasMinutePricing) {
                        $availableTypes[] = 'minute';
                    }
                    break;
            }
        } else {
            $availableTypes = ['kwh', 'minute'];
        }
        
        return $availableTypes;
    }

    /**
     * Charge les business profiles nécessaires avec eager loading optimisé
     */
    public static function loadBusinessProfiles(ChargingPoint $chargingPoint): void
    {
        // Charger l'intégrateur avec son business profile
        if ($chargingPoint->integrator && !$chargingPoint->integrator->relationLoaded('businessProfile')) {
            $chargingPoint->integrator->load('businessProfile');
        }
        
        // Charger le partenaire avec son business profile
        if ($chargingPoint->partner && !$chargingPoint->partner->relationLoaded('businessProfile')) {
            $chargingPoint->partner->load('businessProfile');
        }
        
        // Charger le business profile direct de la borne
        if (!$chargingPoint->relationLoaded('businessProfile')) {
            $chargingPoint->load('businessProfile');
        }
    }

    /**
     * Obtient les business profiles pour l'affichage
     */
    public static function getBusinessProfilesForDisplay(ChargingPoint $chargingPoint): array
    {
        self::loadBusinessProfiles($chargingPoint);
        
        return [
            'admin' => optional(optional($chargingPoint->integrator)->businessProfile),
            'integrator' => optional(optional($chargingPoint->partner)->businessProfile),
            'direct' => optional($chargingPoint->businessProfile),
        ];
    }

    /**
     * Valide la complétude d'un business profile
     */
    public static function validateBusinessProfile($businessProfile): array
    {
        $isComplete = false;
        $error = null;
        
        if (!$businessProfile) {
            $error = 'Aucun business profile trouvé';
            return ['is_complete' => $isComplete, 'error' => $error];
        }
        
        if (!$businessProfile->name) {
            $error = 'Le nom du business profile est manquant';
            return ['is_complete' => $isComplete, 'error' => $error];
        }
        
        if (!$businessProfile->transaction_fee_config) {
            $error = 'La configuration des frais de transaction est manquante';
            return ['is_complete' => $isComplete, 'error' => $error];
        }
        
        if (!$businessProfile->charge_fee_config) {
            $error = 'La configuration des frais de charge est manquante';
            return ['is_complete' => $isComplete, 'error' => $error];
        }
        
        $isComplete = true;
        return ['is_complete' => $isComplete, 'error' => null];
    }

    /**
     * Formate la localisation pour l'affichage
     */
    public static function formatLocation($location): string
    {
        if (!$location) {
            return '<span class="text-gray-400">Non définie</span>';
        }
        
        if (is_object($location)) {
            $address = $location->address ?? 'N/A';
            $city = $location->city ?? 'N/A';
            return "{$address}, {$city}";
        }
        
        if (is_string($location)) {
            return $location;
        }
        
        return '<span class="text-gray-400">Non définie</span>';
    }
}
