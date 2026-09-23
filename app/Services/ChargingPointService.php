<?php

namespace App\Services;

use App\Exceptions\SteVeConfigurationException;
use App\Exceptions\SteVeProvisioningFailedException;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\Group;
use App\Observers\ChargingPointSteveObserver;
use App\Services\SteVe\ChargePointFormMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ChargingPointService
{
    /**
     * Create a new charging point and (optionally) provision it on SteVe.
     *
     * Atomicity contract — TWO modes:
     *
     *  - $registerOnSteve = true (default, canonical strict mode): the local row
     *    insert AND the SteVe POST happen in the same DB transaction. If SteVe
     *    rejects the create or is unreachable, the transaction rolls back and
     *    no local row is persisted. Every CP returned in this mode carries a
     *    valid steve_charge_box_pk.
     *
     *  - $registerOnSteve = false (escape hatch, gated by
     *    config('steve.auto_provision_on_create') === false): SteVe is not
     *    contacted at all. The local row is persisted with
     *    steve_provisioned_at = null and steve_connection_status.awaiting_sync
     *    = true, so it is clearly identifiable in the admin index as needing
     *    a manual sync via the existing `sync-steve` route.
     *
     * @throws \App\Exceptions\SteVeProvisioningFailedException On any SteVe failure when $registerOnSteve is true.
     * @throws \App\Exceptions\SteVeConfigurationException When STEVE_API_URL is not configured AND $registerOnSteve is true.
     */
    public function createChargingPoint(array $data, bool $registerOnSteve = true): ChargingPoint
    {
        return DB::transaction(function () use ($data, $registerOnSteve) {
            // Seed steve_charging_point_id from serial_number regardless of mode
            // so the OCPP-side chargeBoxId is stable whether SteVe is contacted
            // now or later via a deferred sync.
            if (empty($data['steve_charging_point_id'])) {
                $data['steve_charging_point_id'] = $data['serial_number'] ?? null;
            }

            $chargingPoint = ChargingPointSteveObserver::withoutCreateSync(
                fn () => ChargingPoint::create($data)
            );

            // If still missing, derive a stable local identifier from the primary
            // key. Padding to 6 digits keeps it sortable + readable in dashboards.
            if (empty($chargingPoint->steve_charging_point_id)) {
                $prefix = 'CP-' . str_pad((string) $chargingPoint->id, 6, '0', STR_PAD_LEFT);
                $suffix = !empty($chargingPoint->serial_number) ? '-' . $chargingPoint->serial_number : '';
                $chargingPoint->steve_charging_point_id = $prefix . $suffix;
                $chargingPoint->saveQuietly();
            }

            if (!$chargingPoint->business_profile_id) {
                try {
                    $businessProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
                    Log::info('Business profile auto-linked for charging point', [
                        'charging_point_id'    => $chargingPoint->id,
                        'business_profile_id'  => $businessProfile->id,
                    ]);
                } catch (\Exception $e) {
                    // Business-profile linking is best-effort and orthogonal to
                    // SteVe provisioning — don't let it break the create flow.
                    Log::warning('Failed to auto-link business profile', [
                        'charging_point_id' => $chargingPoint->id,
                        'error'             => $e->getMessage(),
                    ]);
                }
            }

            if ($registerOnSteve) {
                $this->provisionOnSteve($chargingPoint);
            } else {
                $this->markAwaitingSteveSync($chargingPoint);
            }

            return $chargingPoint;
        });
    }

    /**
     * Persist the local row with a clear "needs SteVe sync" marker. Used when
     * config('steve.auto_provision_on_create') is false. The flag is consumed
     * by the admin dashboard and the existing `sync-steve` route to retry
     * provisioning out-of-band.
     */
    protected function markAwaitingSteveSync(ChargingPoint $chargingPoint): void
    {
        $this->assignIfColumnExists($chargingPoint, 'steve_provisioned_at', null);
        $this->assignIfColumnExists($chargingPoint, 'steve_sync_status', 'not_synced');
        $this->assignIfColumnExists($chargingPoint, 'steve_connection_status', array_merge(
            (array) ($chargingPoint->steve_connection_status ?? []),
            [
                'awaiting_sync' => true,
                'skipped_reason' => 'auto_provision_disabled',
                'marked_at' => now()->toIso8601String(),
            ],
        ));
        $chargingPoint->saveQuietly();

        Log::info('ChargingPointService: SteVe auto-provision disabled, row marked awaiting_sync', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxId'       => $chargingPoint->steve_charging_point_id,
        ]);
    }

    /**
     * Provision a freshly-created local CP on SteVe via the canonical REST API.
     *
     * Reads back the SteVe-assigned chargeBoxPk (the canonical handle for every
     * subsequent OCPP operation) and the SteVe-confirmed chargeBoxId, and stamps
     * `steve_provisioned_at` so downstream consumers can tell a provisioned CP
     * from one that only has a local id. Throws on any SteVe failure so the
     * outer transaction rolls back.
     *
     * @throws \App\Exceptions\SteVeProvisioningFailedException
     */
    protected function provisionOnSteve(ChargingPoint $chargingPoint): void
    {
        // Fail fast: SteVe needs at least a chargeBoxId and coordinates. Mapping
        // an under-specified row would produce a 400 from upstream anyway, but
        // the diagnostic is clearer when raised on this side.
        $hasIdentifier = !empty($chargingPoint->steve_charging_point_id)
            || !empty($chargingPoint->serial_number)
            || !empty($chargingPoint->charge_box_id);
        $hasCoords = $chargingPoint->latitude !== null && $chargingPoint->longitude !== null;

        if (!$hasIdentifier || !$hasCoords || empty($chargingPoint->name)) {
            throw new SteVeProvisioningFailedException(
                'Cannot provision on SteVe: chargeBoxId, name and coordinates are required.',
                null,
                [
                    'has_identifier' => $hasIdentifier,
                    'has_coords'     => $hasCoords,
                    'has_name'       => !empty($chargingPoint->name),
                ],
            );
        }

        $payload = ChargePointFormMapper::fromModel($chargingPoint);

        Log::info('ChargingPointService: provisioning on SteVe', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxId'       => $payload['chargeBoxId'] ?? null,
        ]);

        $client = app(SteVeHttpClientService::class);

        // Adopt-on-duplicate pre-flight. SteVe enforces UNIQUE on chargeBoxId
        // and returns HTTP 500 "Failed to add the charge point with chargeBoxId
        // 'X'" on conflict — opaque from the client side. If a previous attempt
        // half-succeeded (SteVe accepted the row but the local transaction
        // rolled back), every retry hits that 500 and the user can never
        // recreate that chargeBoxId. Detect the existing row and link to it
        // instead of double-POSTing.
        $existing = $this->findExistingSteveChargePoint($client, $payload['chargeBoxId'] ?? null);
        if ($existing !== null) {
            Log::info('ChargingPointService: chargeBoxId already exists on SteVe — adopting', [
                'charging_point_id' => $chargingPoint->id,
                'chargeBoxId'       => $existing['chargeBoxId'] ?? null,
                'chargeBoxPk'       => $existing['chargeBoxPk'] ?? null,
            ]);
            $this->applyProvisioningResult($chargingPoint, $existing, adopted: true);
            return;
        }

        try {
            $result = $client->createChargePoint($payload);
        } catch (SteVeConfigurationException $e) {
            // Bubble configuration errors verbatim — they need a distinct 503
            // shape so ops can spot them in dashboards.
            throw $e;
        } catch (\Throwable $e) {
            throw new SteVeProvisioningFailedException(
                'SteVe is unreachable: ' . $e->getMessage(),
                null,
                ['exception' => $e::class],
                $e,
            );
        }

        if (($result['success'] ?? false) !== true) {
            // Race: another worker (or out-of-band UI) created the chargeBoxId
            // between our pre-flight and our POST. Re-check before giving up —
            // surfaces a clean "adopted" outcome instead of a confusing 500.
            $raced = $this->findExistingSteveChargePoint($client, $payload['chargeBoxId'] ?? null);
            if ($raced !== null) {
                Log::warning('ChargingPointService: SteVe POST 500 but row exists — adopting after race', [
                    'charging_point_id' => $chargingPoint->id,
                    'chargeBoxId'       => $raced['chargeBoxId'] ?? null,
                    'chargeBoxPk'       => $raced['chargeBoxPk'] ?? null,
                ]);
                $this->applyProvisioningResult($chargingPoint, $raced, adopted: true);
                return;
            }

            Log::error('ChargingPointService: SteVe rejected createChargePoint', [
                'charging_point_id' => $chargingPoint->id,
                'envelope'          => $result,
            ]);
            throw SteVeProvisioningFailedException::fromEnvelope($result);
        }

        // SteVe's POST /chargePoints returns the created entity. The chargeBoxPk
        // is server-assigned and is the canonical handle for subsequent calls.
        $body = $result['data'] ?? [];
        $chargeBoxPk = $body['chargeBoxPk'] ?? $body['chargePointPk'] ?? $body['id'] ?? null;
        $chargeBoxId = $body['chargeBoxId'] ?? $payload['chargeBoxId'] ?? null;

        if (!is_numeric($chargeBoxPk) || (int) $chargeBoxPk <= 0) {
            // SteVe replied 2xx but the body was malformed — treat as a failure
            // so we don't carry a half-known identity forward.
            throw new SteVeProvisioningFailedException(
                'SteVe accepted the create but did not return a chargeBoxPk.',
                null,
                ['body' => $body],
            );
        }

        $this->persistSteveProvisioningState($chargingPoint, (int) $chargeBoxPk, $chargeBoxId);

        Log::info('ChargingPointService: SteVe provisioning successful', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxPk'       => $chargingPoint->steve_charge_box_pk,
            'chargeBoxId'       => $chargingPoint->charge_box_id,
        ]);
    }
    
    protected function assignIfColumnExists(ChargingPoint $chargingPoint, string $column, mixed $value): void
    {
        if (Schema::hasColumn($chargingPoint->getTable(), $column)) {
            $chargingPoint->{$column} = $value;
        }
    }

    protected function persistSteveProvisioningState(
        ChargingPoint $chargingPoint,
        int $chargeBoxPk,
        ?string $chargeBoxId,
        bool $adopted = false
    ): void {
        $now = now();

        $this->assignIfColumnExists($chargingPoint, 'steve_charge_box_pk', $chargeBoxPk);
        $this->assignIfColumnExists($chargingPoint, 'steve_provisioned_at', $now);
        $this->assignIfColumnExists($chargingPoint, 'steve_synced_at', $now);
        $this->assignIfColumnExists($chargingPoint, 'steve_sync_status', 'synced');

        if (!empty($chargeBoxId)) {
            $this->assignIfColumnExists($chargingPoint, 'charge_box_id', $chargeBoxId);
            $this->assignIfColumnExists($chargingPoint, 'steve_charging_point_id', $chargeBoxId);
        }

        $this->assignIfColumnExists($chargingPoint, 'steve_connection_status', array_merge(
            (array) ($chargingPoint->steve_connection_status ?? []),
            [
                'provisioned'    => true,
                'provisioned_at' => $now->toIso8601String(),
                'chargeBoxPk'    => $chargeBoxPk,
                'adopted'        => $adopted,
            ],
        ));

        $chargingPoint->saveQuietly();
    }

    protected function findExistingSteveChargePoint(SteVeHttpClientService $client, ?string $chargeBoxId): ?array
    {
        return $client->findChargePointByChargeBoxId($chargeBoxId);
    }

    protected function applyProvisioningResult(
        ChargingPoint $chargingPoint,
        array $body,
        bool $adopted = false
    ): void {
        $chargeBoxPk = $body['chargeBoxPk'] ?? $body['chargePointPk'] ?? $body['id'] ?? null;
        $chargeBoxId = $body['chargeBoxId']
            ?? $chargingPoint->charge_box_id
            ?? $chargingPoint->steve_charging_point_id
            ?? null;

        if (!is_numeric($chargeBoxPk) || (int) $chargeBoxPk <= 0) {
            throw new SteVeProvisioningFailedException(
                'SteVe accepted the create but did not return a chargeBoxPk.',
                null,
                ['body' => $body],
            );
        }

        $this->persistSteveProvisioningState($chargingPoint, (int) $chargeBoxPk, $chargeBoxId, $adopted);

        Log::info('ChargingPointService: SteVe provisioning successful', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxPk'       => $chargingPoint->steve_charge_box_pk,
            'chargeBoxId'       => $chargingPoint->charge_box_id,
            'adopted'           => $adopted,
        ]);
    }

    /**
     * Update a charging point with business profile validation
     */
    public function updateChargingPoint(ChargingPoint $chargingPoint, array $data): ChargingPoint
    {
        return DB::transaction(function () use ($chargingPoint, $data) {
            // Validate business profile if provided
            if (isset($data['business_profile_id'])) {
                try {
                    BusinessProfileAutoLinkService::validateBusinessProfile($data['business_profile_id']);
                } catch (\Exception $e) {
                    throw new \Exception("Invalid business profile: {$e->getMessage()}");
                }
            }
            
            // Update the charging point
            $chargingPoint->update($data);
            
            return $chargingPoint;
        });
    }
    
    /**
     * Get available business profiles for a charging point
     */
    public function getAvailableBusinessProfiles(ChargingPoint $chargingPoint): array
    {
        return BusinessProfileAutoLinkService::getAvailableBusinessProfiles($chargingPoint);
    }
    
    /**
     * Get recommended business profile for a charging point
     */
    public function getRecommendedBusinessProfile(ChargingPoint $chargingPoint): ?array
    {
        return BusinessProfileAutoLinkService::getRecommendedBusinessProfile($chargingPoint);
    }
    
    /**
     * Auto-link business profile for an existing charging point
     */
    public function autoLinkBusinessProfile(ChargingPoint $chargingPoint): BusinessProfile
    {
        return BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
    }
    
    /**
     * Validate business profile compatibility
     */
    public function validateBusinessProfileCompatibility(ChargingPoint $chargingPoint, int $businessProfileId): bool
    {
        return BusinessProfileAutoLinkService::isBusinessProfileCompatible($businessProfileId, $chargingPoint);
    }
    
    /**
     * Get business profile statistics for a charging point
     */
    public function getBusinessProfileStats(ChargingPoint $chargingPoint): array
    {
        return BusinessProfileAutoLinkService::getBusinessProfileStats($chargingPoint);
    }
    
    /**
     * Get create form data with business profile options
     */
    public function getCreateFormData(User $user): array
    {
        $data = [
            'pricingPlans' => \App\Models\PricingPlan::where('is_active', true)->get(),
            'partners' => Partner::where('is_active', true)->get(),
            'groups' => Group::where('is_active', true)->get(),
            'stations' => \App\Models\Station::where('is_active', true)->get(),
            'users' => User::whereIn('role', ['operator', 'partner', 'integrator'])
                ->where('is_active', true)
                ->get(),
        ];
        
        // Add business profile options based on user role
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        
        if ($user->hasRole('admin')) {
            $data['businessProfiles'] = BusinessProfile::where('is_active', true)->get();
        } elseif ($user->hasRole('integrator')) {
            // UNIQUEMENT les business profiles de l'intégrateur
            $data['businessProfiles'] = $businessProfileService->getIntegratorOwnBusinessProfiles($user);
        } elseif ($user->hasRole('partner')) {
            $data['businessProfiles'] = BusinessProfile::where('owner_type', Partner::class)
                ->where('owner_id', $user->partner_id)
                ->where('is_active', true)
                ->get();
        } else {
            $data['businessProfiles'] = collect();
        }
        
        return $data;
    }
    
    /**
     * Get edit form data with business profile options
     */
    public function getEditFormData(ChargingPoint $chargingPoint, User $user): array
    {
        $data = $this->getCreateFormData($user);
        
        // Add current charging point data
        $data['chargingPoint'] = $chargingPoint;
        $data['currentBusinessProfile'] = $chargingPoint->businessProfile;
        $data['availableBusinessProfiles'] = $this->getAvailableBusinessProfiles($chargingPoint);
        $data['recommendedBusinessProfile'] = $this->getRecommendedBusinessProfile($chargingPoint);
        $data['businessProfileStats'] = $this->getBusinessProfileStats($chargingPoint);
        
        return $data;
    }
    
    /**
     * Create charging point with business profile validation
     */
    public function createWithBusinessProfile(array $data, ?int $businessProfileId = null): ChargingPoint
    {
        return DB::transaction(function () use ($data, $businessProfileId) {
            // Create the charging point
            $chargingPoint = ChargingPoint::create($data);
            
            // Handle business profile assignment
            if ($businessProfileId) {
                // Validate the provided business profile
                try {
                    $businessProfile = BusinessProfileAutoLinkService::validateBusinessProfile($businessProfileId);
                    
                    // Check if it's compatible with this charging point
                    if (!BusinessProfileAutoLinkService::isBusinessProfileCompatible($businessProfileId, $chargingPoint)) {
                        throw new \Exception('The selected business profile is not compatible with this charging point.');
                    }
                    
                    // Assign the business profile
                    $chargingPoint->update(['business_profile_id' => $businessProfileId]);
                    
                    Log::info('Business profile assigned to charging point', [
                        'charging_point_id' => $chargingPoint->id,
                        'business_profile_id' => $businessProfileId,
                        'business_profile_name' => $businessProfile->name,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to assign business profile to charging point', [
                        'charging_point_id' => $chargingPoint->id,
                        'business_profile_id' => $businessProfileId,
                        'error' => $e->getMessage()
                    ]);
                    
                    throw new \Exception("Failed to assign business profile: {$e->getMessage()}");
                }
            } else {
                // Auto-link business profile
                try {
                    $businessProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
                    
                    Log::info('Business profile auto-linked to charging point', [
                        'charging_point_id' => $chargingPoint->id,
                        'business_profile_id' => $businessProfile->id,
                        'business_profile_name' => $businessProfile->name,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to auto-link business profile to charging point', [
                        'charging_point_id' => $chargingPoint->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Don't fail the creation, just log the warning
                }
            }
            
            return $chargingPoint;
        });
    }
    
    /**
     * Update business profile for a charging point
     */
    public function updateBusinessProfile(ChargingPoint $chargingPoint, int $businessProfileId): BusinessProfile
    {
        return DB::transaction(function () use ($chargingPoint, $businessProfileId) {
            // Validate the business profile
            $businessProfile = BusinessProfileAutoLinkService::validateBusinessProfile($businessProfileId);
            
            // Check if it's compatible with this charging point
            if (!BusinessProfileAutoLinkService::isBusinessProfileCompatible($businessProfileId, $chargingPoint)) {
                throw new \Exception('The selected business profile is not compatible with this charging point.');
            }
            
            // Update the charging point
            $chargingPoint->update(['business_profile_id' => $businessProfileId]);
            
            Log::info('Business profile updated for charging point', [
                'charging_point_id' => $chargingPoint->id,
                'business_profile_id' => $businessProfileId,
                'business_profile_name' => $businessProfile->name,
            ]);
            
            return $businessProfile;
        });
    }
    
    /**
     * Remove business profile from a charging point
     */
    public function removeBusinessProfile(ChargingPoint $chargingPoint): void
    {
        $chargingPoint->update(['business_profile_id' => null]);
        
        Log::info('Business profile removed from charging point', [
            'charging_point_id' => $chargingPoint->id,
        ]);
    }
    
    /**
     * Get charging point with business profile information
     */
    public function getChargingPointWithBusinessProfile(int $id): ?ChargingPoint
    {
        return ChargingPoint::with(['businessProfile', 'group.partner.integrator', 'operator', 'integrator'])
            ->find($id);
    }
    
    /**
     * Get charging points with business profile information
     */
    public function getChargingPointsWithBusinessProfiles(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = ChargingPoint::with(['businessProfile', 'group.partner.integrator', 'operator', 'integrator']);
        
        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }
        
        if (isset($filters['integrator_id'])) {
            $query->where('integrator_id', $filters['integrator_id']);
        }
        
        if (isset($filters['business_profile_id'])) {
            $query->where('business_profile_id', $filters['business_profile_id']);
        }
        
        return $query->get();
    }

    /**
     * Get charging point with detailed information for public offer
     */
    public function getChargingPointWithDetails(int $id): ?ChargingPoint
    {
        return ChargingPoint::with(['pricingPlan', 'group'])
            ->find($id);
    }
}
