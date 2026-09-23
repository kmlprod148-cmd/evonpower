<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;
use Illuminate\Support\Facades\Log;

class BusinessProfileAutoLinkService
{
    /**
     * Auto-link business profile for a charging point based on hierarchy
     */
    public static function autoLinkBusinessProfile(ChargingPoint $chargingPoint): BusinessProfile
    {
        $businessProfile = self::findBusinessProfileForChargingPoint($chargingPoint);
        
        if (!$businessProfile) {
            throw new \Exception('No business profile found for this charging point hierarchy.');
        }
        
        // Update the charging point with the business profile
        $chargingPoint->update(['business_profile_id' => $businessProfile->id]);
        
        Log::info('Business profile auto-linked', [
            'charging_point_id' => $chargingPoint->id,
            'business_profile_id' => $businessProfile->id,
            'business_profile_name' => $businessProfile->name,
        ]);
        
        return $businessProfile;
    }
    
    /**
     * Find the appropriate business profile for a charging point
     */
    public static function findBusinessProfileForChargingPoint(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        // Load relationships
        $chargingPoint->load(['group.partner.integrator', 'user', 'integrator']);
        
        // Priority order: Operator > Partner > Integrator > Default
        $businessProfile = self::findOperatorBusinessProfile($chargingPoint) ??
                          self::findPartnerBusinessProfile($chargingPoint) ??
                          self::findIntegratorBusinessProfile($chargingPoint) ??
                          self::findDefaultBusinessProfile();
        
        return $businessProfile;
    }
    
    /**
     * Find business profile for the operator
     */
    protected static function findOperatorBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        if (!$chargingPoint->user) {
            return null;
        }
        
        // Check if operator has a specific business profile
        $operatorProfile = BusinessProfile::where('owner_type', User::class)
            ->where('owner_id', $chargingPoint->user->id)
            ->where('is_active', true)
            ->first();
            
        if ($operatorProfile) {
            Log::debug('Found operator business profile', [
                'charging_point_id' => $chargingPoint->id,
                'operator_id' => $chargingPoint->user->id,
                'business_profile_id' => $operatorProfile->id,
            ]);
            return $operatorProfile;
        }
        
        return null;
    }
    
    /**
     * Find business profile for the partner
     */
    protected static function findPartnerBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        if (!$chargingPoint->group || !$chargingPoint->group->partner) {
            return null;
        }
        
        $partner = $chargingPoint->group->partner;
        
        // Check if partner has a specific business profile
        $partnerProfile = BusinessProfile::where('owner_type', Partner::class)
            ->where('owner_id', $partner->id)
            ->where('is_active', true)
            ->first();
            
        if ($partnerProfile) {
            Log::debug('Found partner business profile', [
                'charging_point_id' => $chargingPoint->id,
                'partner_id' => $partner->id,
                'business_profile_id' => $partnerProfile->id,
            ]);
            return $partnerProfile;
        }
        
        return null;
    }
    
    /**
     * Find business profile for the integrator
     */
    protected static function findIntegratorBusinessProfile(ChargingPoint $chargingPoint): ?BusinessProfile
    {
        $integrator = null;
        
        // Get integrator from different sources
        if ($chargingPoint->integrator) {
            $integrator = $chargingPoint->integrator;
        } elseif ($chargingPoint->group && $chargingPoint->group->partner && $chargingPoint->group->partner->integrator) {
            $integrator = $chargingPoint->group->partner->integrator;
        }
        
        if (!$integrator) {
            return null;
        }
        
        // Check if integrator has a specific business profile
        $integratorProfile = BusinessProfile::where('owner_type', Integrator::class)
            ->where('owner_id', $integrator->id)
            ->where('is_active', true)
            ->first();
            
        if ($integratorProfile) {
            Log::debug('Found integrator business profile', [
                'charging_point_id' => $chargingPoint->id,
                'integrator_id' => $integrator->id,
                'business_profile_id' => $integratorProfile->id,
            ]);
            return $integratorProfile;
        }
        
        return null;
    }
    
    /**
     * Find default business profile
     */
    protected static function findDefaultBusinessProfile(): ?BusinessProfile
    {
        $defaultProfile = BusinessProfile::where('is_default', true)
            ->where('is_active', true)
            ->first();
            
        if ($defaultProfile) {
            Log::debug('Found default business profile', [
                'business_profile_id' => $defaultProfile->id,
            ]);
            return $defaultProfile;
        }
        
        return null;
    }
    
    /**
     * Validate that a business profile exists and is active
     */
    public static function validateBusinessProfile(int $businessProfileId): BusinessProfile
    {
        $businessProfile = BusinessProfile::find($businessProfileId);
        
        if (!$businessProfile) {
            throw new \Exception("Business profile with ID {$businessProfileId} not found.");
        }
        
        if (!$businessProfile->is_active) {
            throw new \Exception("Business profile '{$businessProfile->name}' is not active.");
        }
        
        return $businessProfile;
    }
    
    /**
     * Get available business profiles for a charging point
     */
    public static function getAvailableBusinessProfiles(ChargingPoint $chargingPoint): array
    {
        $profiles = [];
        
        // Load relationships
        $chargingPoint->load(['group.partner.integrator', 'user', 'integrator']);
        
        // Get operator profiles
        if ($chargingPoint->user) {
            $operatorProfiles = BusinessProfile::where('owner_type', User::class)
                ->where('owner_id', $chargingPoint->user->id)
                ->where('is_active', true)
                ->get();
                
            foreach ($operatorProfiles as $profile) {
                $profiles[] = [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'type' => 'operator',
                    'owner_name' => $chargingPoint->user->name,
                ];
            }
        }
        
        // Get partner profiles
        if ($chargingPoint->group && $chargingPoint->group->partner) {
            $partnerProfiles = BusinessProfile::where('owner_type', Partner::class)
                ->where('owner_id', $chargingPoint->group->partner->id)
                ->where('is_active', true)
                ->get();
                
            foreach ($partnerProfiles as $profile) {
                $profiles[] = [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'type' => 'partner',
                    'owner_name' => $chargingPoint->group->partner->name,
                ];
            }
        }
        
        // Get integrator profiles
        $integrator = $chargingPoint->integrator ?? 
                     ($chargingPoint->group && $chargingPoint->group->partner ? $chargingPoint->group->partner->integrator : null);
                     
        if ($integrator) {
            $integratorProfiles = BusinessProfile::where('owner_type', Integrator::class)
                ->where('owner_id', $integrator->id)
                ->where('is_active', true)
                ->get();
                
            foreach ($integratorProfiles as $profile) {
                $profiles[] = [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'type' => 'integrator',
                    'owner_name' => $integrator->name,
                ];
            }
        }
        
        // Get default profiles
        $defaultProfiles = BusinessProfile::where('is_default', true)
            ->where('is_active', true)
            ->get();
            
        foreach ($defaultProfiles as $profile) {
            $profiles[] = [
                'id' => $profile->id,
                'name' => $profile->name,
                'type' => 'default',
                'owner_name' => 'System Default',
            ];
        }
        
        return $profiles;
    }
    
    /**
     * Get the recommended business profile for a charging point
     */
    public static function getRecommendedBusinessProfile(ChargingPoint $chargingPoint): ?array
    {
        $businessProfile = self::findBusinessProfileForChargingPoint($chargingPoint);
        
        if (!$businessProfile) {
            return null;
        }
        
        return [
            'id' => $businessProfile->id,
            'name' => $businessProfile->name,
            'type' => self::getBusinessProfileType($businessProfile, $chargingPoint),
            'reason' => self::getBusinessProfileReason($businessProfile, $chargingPoint),
        ];
    }
    
    /**
     * Get the type of business profile
     */
    protected static function getBusinessProfileType(BusinessProfile $businessProfile, ChargingPoint $chargingPoint): string
    {
        if ($businessProfile->owner_type === User::class && $chargingPoint->user && $businessProfile->owner_id === $chargingPoint->user->id) {
            return 'operator';
        }
        
        if ($businessProfile->owner_type === Partner::class && $chargingPoint->group && $chargingPoint->group->partner && $businessProfile->owner_id === $chargingPoint->group->partner->id) {
            return 'partner';
        }
        
        if ($businessProfile->owner_type === Integrator::class) {
            $integrator = $chargingPoint->integrator ?? 
                         ($chargingPoint->group && $chargingPoint->group->partner ? $chargingPoint->group->partner->integrator : null);
            if ($integrator && $businessProfile->owner_id === $integrator->id) {
                return 'integrator';
            }
        }
        
        if ($businessProfile->is_default) {
            return 'default';
        }
        
        return 'unknown';
    }
    
    /**
     * Get the reason for business profile recommendation
     */
    protected static function getBusinessProfileReason(BusinessProfile $businessProfile, ChargingPoint $chargingPoint): string
    {
        $type = self::getBusinessProfileType($businessProfile, $chargingPoint);
        
        switch ($type) {
            case 'operator':
                return "Recommended because this charging point is managed by operator '{$chargingPoint->user->name}'";
            case 'partner':
                return "Recommended because this charging point belongs to partner '{$chargingPoint->group->partner->name}'";
            case 'integrator':
                $integrator = $chargingPoint->integrator ?? 
                             ($chargingPoint->group && $chargingPoint->group->partner ? $chargingPoint->group->partner->integrator : null);
                return "Recommended because this charging point belongs to integrator '{$integrator->name}'";
            case 'default':
                return "Recommended as the default business profile for this charging point";
            default:
                return "Recommended business profile for this charging point";
        }
    }
    
    /**
     * Check if a business profile is compatible with a charging point
     */
    public static function isBusinessProfileCompatible(int $businessProfileId, ChargingPoint $chargingPoint): bool
    {
        try {
            $businessProfile = self::validateBusinessProfile($businessProfileId);
            
            // Check if the business profile is available for this charging point
            $availableProfiles = self::getAvailableBusinessProfiles($chargingPoint);
            $profileIds = array_column($availableProfiles, 'id');
            
            return in_array($businessProfileId, $profileIds);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get business profile statistics for a charging point
     */
    public static function getBusinessProfileStats(ChargingPoint $chargingPoint): array
    {
        $availableProfiles = self::getAvailableBusinessProfiles($chargingPoint);
        $recommendedProfile = self::getRecommendedBusinessProfile($chargingPoint);
        
        $stats = [
            'total_available' => count($availableProfiles),
            'by_type' => [],
            'has_recommended' => $recommendedProfile !== null,
            'recommended_profile' => $recommendedProfile,
        ];
        
        // Count by type
        foreach ($availableProfiles as $profile) {
            $type = $profile['type'];
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;
        }
        
        return $stats;
    }
}
