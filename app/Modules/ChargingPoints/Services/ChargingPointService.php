<?php

namespace App\Modules\ChargingPoints\Services;

use App\Core\Services\BaseService;
use App\Repositories\Interfaces\ChargingPointRepositoryInterface;
use App\Modules\ChargingPoints\Interfaces\ChargingPointServiceInterface;
use App\Modules\ChargingPoints\DTOs\ChargingPointDTO;
use App\Modules\ChargingPoints\DTOs\ConnectorDTO;
use App\Modules\ChargingPoints\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Services\OCPPService;

class ChargingPointService extends BaseService implements ChargingPointServiceInterface
{
    protected $ocppService;

    public function __construct(
        ChargingPointRepositoryInterface $repository,
        OCPPService $ocppService
    ) {
        parent::__construct($repository);
        $this->ocppService = $ocppService;
        $this->cachePrefix = 'charging_points';
    }

    public function getAll(array $filters = []): Collection
    {
        if (empty($filters)) {
            return $this->repository->all();
        }
        return $this->repository->getFiltered($filters);
    }

    public function getById(int $id)
    {
        return $this->repository->find($id);
    }

    public function getFiltered(array $filters = []): Collection
    {
        return $this->repository->getFiltered($filters);
    }

    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->getPaginated($perPage, $filters);
    }

    public function createWithConnectors(ChargingPointDTO $dto): ChargingPoint
    {
        DB::beginTransaction();
        
        try {
            $data = $dto->toArray();

            // If group_id is provided, inherit partner and integrator
            if (isset($data['group_id'])) {
                $group = \App\Models\Group::find($data['group_id']);
                if ($group) {
                    $data['partner_id'] = $group->partner_id;
                    $data['integrator_id'] = $group->integrator_id;
                }
            }

            // CORRECTION: Si l'utilisateur est un opérateur attaché à un intégrateur,
            // hériter automatiquement de l'integrator_id de l'utilisateur
            if (isset($data['user_id'])) {
                $user = \App\Models\User::find($data['user_id']);
                if ($user && $user->integrator_id && !isset($data['integrator_id'])) {
                    $data['integrator_id'] = $user->integrator_id;
                }
            }

            $chargingPoint = $this->repository->create($data);
            
            if (isset($dto->connectors) && is_array($dto->connectors)) {
                foreach ($dto->connectors as $connectorData) {
                    $this->repository->addConnector($chargingPoint->id, $connectorData);
                }
            }
            
            DB::commit();
            $this->clearCache();
            
            return $chargingPoint;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating charging point with connectors', [
                'error' => $e->getMessage(),
                'dto' => $dto->toArray()
            ]);
            throw $e;
        }
    }

    public function updateWithConnectors(int $id, ChargingPointDTO $dto): ChargingPoint
    {
        DB::beginTransaction();
        
        try {
            $chargingPoint = $this->repository->update($id, $dto->toArray());
            
            // Suppression de la gestion des connecteurs dans update()
            
            DB::commit();
            $this->clearCache();
            
            return $chargingPoint;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating charging point with connectors', [
                'error' => $e->getMessage(),
                'id' => $id,
                'dto' => $dto->toArray()
            ]);
            throw $e;
        }
    }

    /**
     * Connect a charging point by its ID with validation and error handling
     * 
     * @param int $chargingPointId
     * @return ChargingPoint
     * @throws \Exception
     */
    public function connectById(int $chargingPointId): ChargingPoint
    {
        try {
            $chargingPoint = $this->repository->find($chargingPointId);
            
            if (!$chargingPoint) {
                throw new \Exception('Borne introuvable pour l\'ID fourni.');
            }
            
            // Validate charging point status
            if ($chargingPoint->status === 'offline') {
                throw new \Exception('Cette borne est actuellement hors ligne et ne peut pas être connectée.');
            }
            
            if ($chargingPoint->status === 'maintenance') {
                throw new \Exception('Cette borne est en maintenance et ne peut pas être connectée.');
            }
            
            if ($chargingPoint->status === 'error') {
                throw new \Exception('Cette borne est en erreur et ne peut pas être connectée.');
            }
            
            // Additional validation for connection readiness
            if (!$chargingPoint->charge_box_id) {
                throw new \Exception('Cette borne n\'a pas d\'identifiant de charge configuré.');
            }
            
            if (!$chargingPoint->websocket_url) {
                throw new \Exception('Cette borne n\'a pas d\'URL WebSocket configurée.');
            }
            
            Log::info('Borne connectée avec succès', [
                'charging_point_id' => $chargingPointId,
                'charging_point_name' => $chargingPoint->name,
                'status' => $chargingPoint->status,
                'charge_box_id' => $chargingPoint->charge_box_id
            ]);
            
            return $chargingPoint;
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion de la borne', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function connect(int $id, array $connectionDetails): array
    {
        $chargingPoint = $this->repository->findOrFail($id);
        $this->validateConnectionDetails($connectionDetails);
        
        $result = $this->ocppService->connectChargingPoint($chargingPoint, $connectionDetails);
        
        $this->repository->update($id, [
            'status' => 'online',
            'connection_protocol' => $connectionDetails['protocol'] ?? null,
            'connection_type' => $connectionDetails['connection_type'] ?? null,
            'last_connected_at' => now()
        ]);
        
        $this->clearCacheItem($id);
        
        return [
            'success' => true,
            'message' => 'Point de charge connecté avec succès',
            'data' => $result
        ];
    }

    public function disconnect(int $id): array
    {
        $chargingPoint = $this->repository->findOrFail($id);
        
        $result = $this->ocppService->disconnectChargingPoint($chargingPoint);
        
        $this->repository->update($id, [
            'status' => 'offline',
            'last_disconnected_at' => now()
        ]);
        
        $this->clearCacheItem($id);
        
        return [
            'success' => true,
            'message' => 'Point de charge déconnecté avec succès',
            'data' => $result
        ];
    }

    public function sendCommand(int $id, string $command, array $params = []): array
    {
        $chargingPoint = $this->repository->findOrFail($id);
        
        $result = $this->ocppService->sendCommand($chargingPoint, $command, $params);
        
        return [
            'success' => true,
            'message' => 'Commande envoyée avec succès',
            'data' => $result
        ];
    }

    private function validateConnectionDetails(array $details): void
    {
        $validator = validator($details, [
            'protocol' => 'required|in:ocpp1.6,ocpp2.0.1,custom',
            'connection_type' => 'required|in:websocket,soap,json',
            'endpoint' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }

    /**
     * Find charging point with relations
     */
    public function findChargingPointWithRelations(int $id)
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new charging point with optional Steve API integration
     */
    public function createChargingPoint(array $data, bool $registerWithSteve = false): ChargingPoint
    {
        DB::beginTransaction();
        
        try {
            // Create in local database
            $chargingPoint = $this->repository->create($data);
            
            // Optionally register with Steve API
            if ($registerWithSteve && config('services.steve.enabled', false)) {
                try {
                    $steveService = app(\App\Services\SteveService::class);
                    $steveResponse = $steveService->createChargePoint([
                        'chargeBoxId' => $chargingPoint->charge_box_id ?? $chargingPoint->serial_number,
                        'endpointAddress' => $chargingPoint->ip_address ?? '',
                        'notes' => $chargingPoint->notes ?? 'Created from EVON system'
                    ]);
                    
                    if ($steveResponse && isset($steveResponse['id'])) {
                        $chargingPoint->update([
                            'steve_charging_point_id' => $steveResponse['id'],
                            'steve_sync_status' => 'synced'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to register charging point with Steve API: ' . $e->getMessage());
                    // Continue without Steve registration
                }
            }
            
            DB::commit();
            $this->clearCache();
            
            return $chargingPoint;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating charging point: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing charging point
     */
    public function updateChargingPoint(ChargingPoint $chargingPoint, array $data): ChargingPoint
    {
        DB::beginTransaction();
        
        try {
            $chargingPoint->update($data);
            
            // Optionally sync with Steve API if steve_charging_point_id exists
            if ($chargingPoint->steve_charging_point_id && config('services.steve.enabled', false)) {
                try {
                    $steveService = app(\App\Services\SteveService::class);
                    $steveService->updateChargePoint($chargingPoint->steve_charging_point_id, [
                        'endpointAddress' => $chargingPoint->ip_address ?? '',
                        'notes' => $chargingPoint->notes ?? ''
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to update charging point in Steve API: ' . $e->getMessage());
                    // Continue without Steve update
                }
            }
            
            DB::commit();
            $this->clearCache();
            
            return $chargingPoint->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating charging point: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update charging point status
     */
    public function updateChargingPointStatus(ChargingPoint $chargingPoint, string $status): ChargingPoint
    {
        $chargingPoint->update(['status' => $status]);
        $this->clearCache();
        
        return $chargingPoint->fresh();
    }

    /**
     * Get data needed for the create form
     */
    public function getCreateFormData($user): array
    {
        $data = [];
        
        // Get available groups based on user role
        if ($user && $user->hasRole('integrator')) {
            $data['groups'] = \App\Models\Group::where('integrator_id', $user->integrator_id)->get();
        } elseif ($user && $user->hasRole('admin')) {
            $data['groups'] = \App\Models\Group::all();
        } elseif ($user && $user->hasRole('partner')) {
            // Partners can only see groups belonging to their partner
            $data['groups'] = \App\Models\Group::where('partner_id', $user->partner_id)->get();
        } else {
            $data['groups'] = collect();
        }
        
        // Get available partners based on user role
        if ($user && $user->hasRole('admin')) {
            // Admin sees all active partners
            $data['partners'] = \App\Models\Partner::where('is_active', true)->get();
        } elseif ($user && $user->hasRole('integrator')) {
            // Integrator sees only their partners
            $data['partners'] = \App\Models\Partner::where('integrator_id', $user->integrator_id)
                ->where('is_active', true)
                ->get();
        } elseif ($user && $user->hasRole('partner')) {
            // Partner sees only themselves
            $data['partners'] = \App\Models\Partner::where('id', $user->partner_id)->get();
        } else {
            $data['partners'] = collect();
        }
        
        // Get available integrators based on user role
        if ($user && $user->hasRole('admin')) {
            $data['integrators'] = \App\Models\Integrator::where('is_active', true)->get();
        } else {
            $data['integrators'] = collect();
        }
        
        // Get available pricing plans based on user role
        if ($user) {
            $data['pricingPlans'] = \App\Models\PricingPlan::forUser($user)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $data['pricingPlans'] = collect();
        }
        
        return $data;
    }

    /**
     * Get data needed for the edit form
     */
    public function getEditFormData(ChargingPoint $chargingPoint, $user): array
    {
        $data = [
            'chargingPoint' => $chargingPoint
        ];
        
        // Get available groups based on user role
        if ($user && $user->hasRole('integrator')) {
            $data['groups'] = \App\Models\Group::where('integrator_id', $user->integrator_id)->get();
        } elseif ($user && $user->hasRole('admin')) {
            $data['groups'] = \App\Models\Group::all();
        } elseif ($user && $user->hasRole('partner')) {
            // Partners can only see groups belonging to their partner
            $data['groups'] = \App\Models\Group::where('partner_id', $user->partner_id)->get();
        } else {
            $data['groups'] = collect();
        }
        
        // Get available partners based on user role
        if ($user && $user->hasRole('admin')) {
            // Admin sees all active partners
            $data['partners'] = \App\Models\Partner::where('is_active', true)->get();
        } elseif ($user && $user->hasRole('integrator')) {
            // Integrator sees only their partners
            $data['partners'] = \App\Models\Partner::where('integrator_id', $user->integrator_id)
                ->where('is_active', true)
                ->get();
        } elseif ($user && $user->hasRole('partner')) {
            // Partner sees only themselves
            $data['partners'] = \App\Models\Partner::where('id', $user->partner_id)->get();
        } else {
            $data['partners'] = collect();
        }
        
        // Get available integrators based on user role
        if ($user && $user->hasRole('admin')) {
            $data['integrators'] = \App\Models\Integrator::where('is_active', true)->get();
        } else {
            $data['integrators'] = collect();
        }
        
        // Get available pricing plans based on user role
        if ($user) {
            $data['pricingPlans'] = \App\Models\PricingPlan::forUser($user)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $data['pricingPlans'] = collect();
        }
        
        return $data;
    }

    /**
     * Get filtered charging points for a specific user with pagination and stats
     */
    public function getFilteredForUser($user, array $filters = []): array
    {
        // Get base model query
        $modelClass = ChargingPoint::class;
        $query = $modelClass::query();

        // Use the comprehensive visibleToUser scope that handles all permission logic
        // This ensures consistency between query filtering and authorization policy
        if ($user) {
            $query = $query->visibleToUser($user);
        }
        
        // Apply user-based filtering based on role (KEPT FOR BACKWARD COMPATIBILITY BUT visibleToUser handles this more comprehensively)

        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            if ($filters['status'] === 'online') {
                $query->where('status', 'online');
            } elseif ($filters['status'] === 'offline') {
                $query->where('status', 'offline');
            } elseif ($filters['status'] === 'maintenance') {
                $query->where('status', 'maintenance');
            }
        }

        // Apply filter parameter (used by quick filters)
        if (isset($filters['filter']) && !empty($filters['filter'])) {
            if ($filters['filter'] === 'online') {
                $query->where('status', 'online');
            } elseif ($filters['filter'] === 'maintenance') {
                $query->where('status', 'maintenance');
            }
        }

        // Load relationships
        $query->with(['group', 'partner', 'integrator']);

        // Get paginated results
        $chargingPoints = $query->orderBy('created_at', 'desc')->paginate(15);

        // Calculate stats
        $statsQuery = $modelClass::query();
        if ($user) {
            // Use the same comprehensive visibleToUser scope for stats consistency
            $statsQuery = $statsQuery->visibleToUser($user);
        }

        $stats = [
            'activeChargingPoints' => (clone $statsQuery)->where('status', 'online')->count(),
            'maintenanceChargingPoints' => (clone $statsQuery)->where('status', 'maintenance')->count(),
        ];

        return [
            'chargingPoints' => $chargingPoints,
            'stats' => $stats
        ];
    }

    /**
     * Delete a charging point with SteVe API integration
     */
    public function deleteChargingPoint($chargingPoint): bool
    {
        DB::beginTransaction();
        
        try {
            // Supprimer dans SteVe API si le point de charge a un steve_charging_point_id
            if (!empty($chargingPoint->steve_charging_point_id)) {
                try {
                    $steveService = app(\App\Services\SteveService::class);
                    $deletedInSteve = $steveService->deleteChargePoint($chargingPoint->steve_charging_point_id);
                    
                    if ($deletedInSteve) {
                        Log::info('Charging point deleted from SteVe API successfully', [
                            'charging_point_id' => $chargingPoint->id,
                            'steve_charging_point_id' => $chargingPoint->steve_charging_point_id
                        ]);
                    } else {
                        Log::warning('Failed to delete charging point from SteVe API, continuing with local deletion', [
                            'charging_point_id' => $chargingPoint->id,
                            'steve_charging_point_id' => $chargingPoint->steve_charging_point_id
                        ]);
                        // Continue avec la suppression locale même si SteVe échoue
                    }
                } catch (\Exception $e) {
                    Log::error('Error deleting charging point from SteVe API, continuing with local deletion', [
                        'charging_point_id' => $chargingPoint->id,
                        'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue avec la suppression locale même si SteVe échoue
                }
            } else {
                Log::info('Charging point has no steve_charging_point_id, skipping SteVe deletion', [
                    'charging_point_id' => $chargingPoint->id
                ]);
            }

            // Delete associated connectors first
            if (method_exists($chargingPoint, 'connectors')) {
                $chargingPoint->connectors()->delete();
                Log::info('Deleted associated connectors for charging point ID: ' . $chargingPoint->id);
            }

            // Delete the charging point
            $deleted = $this->repository->delete($chargingPoint);
            
            if ($deleted) {
                DB::commit();
                $this->clearCache();
                Log::info('Successfully deleted charging point with ID: ' . $chargingPoint->id);
                return true;
            } else {
                DB::rollBack();
                Log::error('Failed to delete charging point', [
                    'charging_point_id' => $chargingPoint->id
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting charging point', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}