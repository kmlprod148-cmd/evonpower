<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusinessProfilePropagationService
{
    /**
     * Propager les changements d'un Business Profile vers toutes les bornes concernées
     * 
     * Cette méthode trouve toutes les bornes qui utilisent ce business profile :
     * 1. Directement (business_profile_id sur la borne)
     * 2. Via l'intégrateur (business_profile_id sur l'intégrateur de la borne)
     * 3. Via le partenaire (business_profile_id sur le partenaire de la borne)
     */
    public function propagateToChargingPoints(BusinessProfile $businessProfile): array
    {
        $results = [
            'direct_charging_points' => [],
            'via_integrator' => [],
            'via_partner' => [],
            'total_updated' => 0
        ];

        try {
            DB::beginTransaction();

            // 1. Mettre à jour les bornes avec business_profile_id direct
            $directChargingPoints = ChargingPoint::where('business_profile_id', $businessProfile->id)->get();
            
            foreach ($directChargingPoints as $chargingPoint) {
                // Les données sont déjà à jour dans la DB, on marque juste pour le log
                $results['direct_charging_points'][] = [
                    'charging_point_id' => $chargingPoint->id,
                    'charging_point_name' => $chargingPoint->name,
                    'update_type' => 'direct'
                ];
                $results['total_updated']++;
            }

            // 2. Mettre à jour les bornes via intégrateurs qui utilisent ce business profile
            $integrators = Integrator::where('business_profile_id', $businessProfile->id)->get();
            
            foreach ($integrators as $integrator) {
                $chargingPoints = ChargingPoint::where('integrator_id', $integrator->id)
                    ->where(function($query) use ($businessProfile) {
                        // Bornes sans business_profile_id direct ou avec un business_profile_id différent
                        $query->whereNull('business_profile_id')
                              ->orWhere('business_profile_id', '!=', $businessProfile->id);
                    })
                    ->get();
                
                foreach ($chargingPoints as $chargingPoint) {
                    // Note: Le business profile de l'intégrateur est déjà mis à jour dans la DB
                    // Les bornes récupèrent automatiquement les nouvelles valeurs via la relation
                    // Mais on peut aussi mettre à jour leur business_profile_id si nécessaire
                    $results['via_integrator'][] = [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'integrator_id' => $integrator->id,
                        'integrator_name' => $integrator->name,
                        'update_type' => 'via_integrator'
                    ];
                    $results['total_updated']++;
                }
            }

            // 3. Mettre à jour les bornes via partenaires qui utilisent ce business profile
            $partners = Partner::where('business_profile_id', $businessProfile->id)->get();
            
            foreach ($partners as $partner) {
                $chargingPoints = ChargingPoint::where('partner_id', $partner->id)
                    ->where(function($query) use ($businessProfile) {
                        // Bornes sans business_profile_id direct ou avec un business_profile_id différent
                        $query->whereNull('business_profile_id')
                              ->orWhere('business_profile_id', '!=', $businessProfile->id);
                    })
                    ->get();
                
                foreach ($chargingPoints as $chargingPoint) {
                    // Note: Le business profile du partenaire est déjà mis à jour dans la DB
                    // Les bornes récupèrent automatiquement les nouvelles valeurs via la relation
                    $results['via_partner'][] = [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                        'update_type' => 'via_partner'
                    ];
                    $results['total_updated']++;
                }
            }

            DB::commit();

            Log::info('Business Profile propagated to charging points', [
                'business_profile_id' => $businessProfile->id,
                'business_profile_name' => $businessProfile->name,
                'results' => $results
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error propagating Business Profile to charging points', [
                'business_profile_id' => $businessProfile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Obtenir toutes les bornes affectées par un Business Profile
     */
    public function getAffectedChargingPoints(BusinessProfile $businessProfile): array
    {
        $chargingPoints = [];

        // 1. Bornes avec business_profile_id direct
        $direct = ChargingPoint::where('business_profile_id', $businessProfile->id)
            ->with(['integrator', 'partner', 'group'])
            ->get();
        $chargingPoints['direct'] = $direct;

        // 2. Bornes via intégrateurs
        $integrators = Integrator::where('business_profile_id', $businessProfile->id)->pluck('id');
        $viaIntegrator = ChargingPoint::whereIn('integrator_id', $integrators)
            ->with(['integrator', 'partner', 'group'])
            ->get();
        $chargingPoints['via_integrator'] = $viaIntegrator;

        // 3. Bornes via partenaires
        $partners = Partner::where('business_profile_id', $businessProfile->id)->pluck('id');
        $viaPartner = ChargingPoint::whereIn('partner_id', $partners)
            ->with(['integrator', 'partner', 'group'])
            ->get();
        $chargingPoints['via_partner'] = $viaPartner;

        return $chargingPoints;
    }

    /**
     * Récupérer le nombre total de bornes affectées
     */
    public function getAffectedChargingPointsCount(BusinessProfile $businessProfile): int
    {
        $directCount = ChargingPoint::where('business_profile_id', $businessProfile->id)->count();
        
        $integratorIds = Integrator::where('business_profile_id', $businessProfile->id)->pluck('id');
        $viaIntegratorCount = $integratorIds->count() > 0 
            ? ChargingPoint::whereIn('integrator_id', $integratorIds)->count() 
            : 0;
        
        $partnerIds = Partner::where('business_profile_id', $businessProfile->id)->pluck('id');
        $viaPartnerCount = $partnerIds->count() > 0 
            ? ChargingPoint::whereIn('partner_id', $partnerIds)->count() 
            : 0;

        // Éviter les doublons (une borne peut être comptée plusieurs fois)
        $uniqueIds = ChargingPoint::where(function($query) use ($businessProfile, $integratorIds, $partnerIds) {
            $query->where('business_profile_id', $businessProfile->id);
            
            if ($integratorIds->count() > 0) {
                $query->orWhereIn('integrator_id', $integratorIds);
            }
            
            if ($partnerIds->count() > 0) {
                $query->orWhereIn('partner_id', $partnerIds);
            }
        })->distinct()->count('id');

        return $uniqueIds;
    }
}

