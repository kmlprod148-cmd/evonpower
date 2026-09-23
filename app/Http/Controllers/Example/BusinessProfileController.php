<?php

namespace App\Http\Controllers\Example;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Services\ChargingPointService;
use App\Services\BusinessProfileAutoLinkService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BusinessProfileController extends Controller
{
    protected $chargingPointService;

    public function __construct(ChargingPointService $chargingPointService)
    {
        $this->chargingPointService = $chargingPointService;
    }

    /**
     * Get available business profiles for a charging point
     */
    public function getAvailableProfiles(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $profiles = $this->chargingPointService->getAvailableBusinessProfiles($chargingPoint);
            
            return response()->json([
                'success' => true,
                'data' => $profiles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available business profiles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recommended business profile for a charging point
     */
    public function getRecommendedProfile(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $recommended = $this->chargingPointService->getRecommendedBusinessProfile($chargingPoint);
            
            return response()->json([
                'success' => true,
                'data' => $recommended
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recommended business profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-link business profile for a charging point
     */
    public function autoLinkProfile(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $businessProfile = $this->chargingPointService->autoLinkBusinessProfile($chargingPoint);
            
            return response()->json([
                'success' => true,
                'message' => 'Business profile auto-linked successfully',
                'data' => [
                    'business_profile_id' => $businessProfile->id,
                    'business_profile_name' => $businessProfile->name,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-link business profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update business profile for a charging point
     */
    public function updateProfile(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $request->validate([
            'business_profile_id' => 'required|integer|exists:business_profiles,id'
        ]);

        try {
            $businessProfile = $this->chargingPointService->updateBusinessProfile(
                $chargingPoint, 
                $request->business_profile_id
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Business profile updated successfully',
                'data' => [
                    'business_profile_id' => $businessProfile->id,
                    'business_profile_name' => $businessProfile->name,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update business profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove business profile from a charging point
     */
    public function removeProfile(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $this->chargingPointService->removeBusinessProfile($chargingPoint);
            
            return response()->json([
                'success' => true,
                'message' => 'Business profile removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove business profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get business profile statistics for a charging point
     */
    public function getStats(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $stats = $this->chargingPointService->getBusinessProfileStats($chargingPoint);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get business profile statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate business profile compatibility
     */
    public function validateCompatibility(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $request->validate([
            'business_profile_id' => 'required|integer|exists:business_profiles,id'
        ]);

        try {
            $isCompatible = $this->chargingPointService->validateBusinessProfileCompatibility(
                $chargingPoint, 
                $request->business_profile_id
            );
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_compatible' => $isCompatible,
                    'business_profile_id' => $request->business_profile_id
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate business profile compatibility',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get charging point with business profile information
     */
    public function getChargingPointWithProfile(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $chargingPointWithProfile = $this->chargingPointService->getChargingPointWithBusinessProfile($chargingPoint->id);
            
            if (!$chargingPointWithProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Charging point not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'charging_point' => $chargingPointWithProfile,
                    'business_profile' => $chargingPointWithProfile->businessProfile,
                    'available_profiles' => $this->chargingPointService->getAvailableBusinessProfiles($chargingPointWithProfile),
                    'recommended_profile' => $this->chargingPointService->getRecommendedBusinessProfile($chargingPointWithProfile),
                    'stats' => $this->chargingPointService->getBusinessProfileStats($chargingPointWithProfile),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get charging point with business profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
