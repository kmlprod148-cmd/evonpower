<?php

namespace App\Http\Controllers\API;

use App\Http\Traits\ApiResponse; // Use the standardized ApiResponse trait
use App\Models\Integrator;
use App\Http\Requests\Integrator\IntegratorStoreRequest;
use App\Http\Requests\Integrator\IntegratorUpdateRequest;
use App\Http\Resources\ChargingPointCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntegratorController extends BaseApiController
{
    use ApiResponse; // Use the ApiResponse trait

    /**
     * Display a listing of integrators.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Integrator::query();
        
        // Apply filters if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_dir', 'desc');
        
        // Check if sort field exists in table to prevent SQL injection
        $allowedSortFields = ['name', 'email', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $integrators = $query->paginate($perPage);

        return $this->sendSuccess(
            $integrators,
            'Integrators retrieved successfully'
        );
    }

    /**
     * Store a newly created integrator.
     *
     * @param  IntegratorStoreRequest  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // In production, you would use a proper request validator
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:integrators,email',
                'phone' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'business_profile_id' => 'nullable|exists:business_profiles,id',
                'address' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'contact_name' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'user_id' => 'nullable|exists:users,id',
            ]);

            DB::beginTransaction();
            
            // Create the integrator
            $integrator = Integrator::create($validatedData);

            // Si un user_id est fourni, s'assurer que l'utilisateur a le rôle integrator
            if (isset($validatedData['user_id']) && $validatedData['user_id']) {
                $user = \App\Models\User::find($validatedData['user_id']);
                if ($user && !$user->hasRole('integrator')) {
                    $user->assignRole('integrator');
                    
                    // Mettre à jour l'integrator_id de l'utilisateur s'il n'est pas déjà défini
                    if (!$user->integrator_id) {
                        $user->update(['integrator_id' => $integrator->id]);
                    }
                    
                    \Illuminate\Support\Facades\Log::info("Rôle 'integrator' assigné à l'utilisateur {$user->id} lors de la création de l'intégrateur {$integrator->id} via API");
                }
            }

            // Create admin user for integrator if requested
            if ($request->has('create_admin_user') && $request->input('create_admin_user')) {
                // Validate admin user data
                $adminData = $request->validate([
                    'admin_name' => 'required|string|max:255',
                    'admin_email' => 'required|email|unique:users,email',
                    'admin_password' => 'required|string|min:8',
                ]);

                // Create the admin user
                $user = \App\Models\User::create([
                    'name' => $adminData['admin_name'],
                    'email' => $adminData['admin_email'],
                    'password' => \Illuminate\Support\Facades\Hash::make($adminData['admin_password']),
                    'integrator_id' => $integrator->id,
                    'created_by' => auth()->id(),
                ]);

                // Assign integrator role (garantir qu'il est toujours assigné)
                $user->assignRole('integrator');
                
                // Mettre à jour l'intégrateur avec le user_id si ce n'est pas déjà fait
                if (!$integrator->user_id) {
                    $integrator->update(['user_id' => $user->id]);
                }
                
                \Illuminate\Support\Facades\Log::info("Utilisateur admin créé avec le rôle 'integrator' pour l'intégrateur {$integrator->id} via API");
            }

            DB::commit();

            return $this->sendSuccess(
                $integrator,
                'Integrator created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(
                'Error creating integrator: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified integrator.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $integrator = Integrator::with(['chargingPoints', 'users'])->find($id);

            if (!$integrator) {
                return $this->sendNotFound('Integrator not found');
            }
            $this->authorize('view', $integrator);

            // Add some statistics for the response
            $stats = [
                'total_charging_points' => $integrator->chargingPoints->count(),
                'total_users' => $integrator->users->count(),
                'active_charging_points' => $integrator->chargingPoints->where('status', 'online')->count(),
            ];

            $responseData = $integrator->toArray();
            $responseData['stats'] = $stats;

            return $this->sendSuccess(
                $responseData,
                'Integrator retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error retrieving integrator: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified integrator.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $integrator = Integrator::find($id);

            if (!$integrator) {
                return $this->sendNotFound('Integrator not found');
            }
            $this->authorize('update', $integrator);

            // In production, you would use a proper request validator
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:integrators,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'business_profile_id' => 'nullable|exists:business_profiles,id',
                'address' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'contact_name' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'user_id' => 'sometimes|exists:users,id',
            ]);

            $oldUserId = $integrator->user_id;
            $integrator->update($validatedData);

            // Si user_id a été modifié, s'assurer que le nouvel utilisateur a le rôle integrator
            if (isset($validatedData['user_id']) && $validatedData['user_id'] != $oldUserId) {
                $user = \App\Models\User::find($validatedData['user_id']);
                if ($user && !$user->hasRole('integrator')) {
                    $user->assignRole('integrator');
                    
                    // Mettre à jour l'integrator_id de l'utilisateur
                    $user->update(['integrator_id' => $integrator->id]);
                    
                    \Illuminate\Support\Facades\Log::info("Rôle 'integrator' assigné à l'utilisateur {$user->id} lors de la mise à jour de l'intégrateur {$integrator->id} via API");
                }
            }

            return $this->sendSuccess(
                $integrator,
                'Integrator updated successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error updating integrator: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified integrator.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $integrator = Integrator::find($id);

            if (!$integrator) {
                return $this->sendNotFound('Integrator not found');
            }
            $this->authorize('delete', $integrator);

            // Check if there are dependent charging points
            if ($integrator->chargingPoints()->count() > 0) {
                return $this->sendError(
                    'Cannot delete integrator with associated charging points',
                    409 // Conflict
                );
            }

            DB::beginTransaction();

            // Delete users associated with this integrator
            \App\Models\User::where('integrator_id', $id)->delete();

            // Delete the integrator
            $integrator->delete();

            DB::commit();

            return $this->sendSuccess(
                null,
                'Integrator deleted successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(
                'Error deleting integrator: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get charging points for a specific integrator.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function getChargingPoints(Request $request, $id): JsonResponse
    {
        try {
            $integrator = Integrator::find($id);

            if (!$integrator) {
                return $this->sendNotFound('Integrator not found');
            }

            // Apply filters if provided
            $query = $integrator->chargingPoints();

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Paginate results
            $perPage = $request->input('per_page', 15);
            $chargingPoints = $query->paginate($perPage);

            return $this->sendSuccess(
                new ChargingPointCollection($chargingPoints),
                'Charging points retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error retrieving charging points: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get statistics for a specific integrator.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getStats($id): JsonResponse
    {
        try {
            $integrator = Integrator::find($id);

            if (!$integrator) {
                return $this->sendNotFound('Integrator not found');
            }

            // Generate statistics
            $stats = [
                'total_charging_points' => $integrator->chargingPoints()->count(),
                'by_status' => [
                    'online' => $integrator->chargingPoints()->where('status', 'online')->count(),
                    'offline' => $integrator->chargingPoints()->where('status', 'offline')->count(),
                    'maintenance' => $integrator->chargingPoints()->where('status', 'maintenance')->count(),
                    'error' => $integrator->chargingPoints()->where('status', 'error')->count(),
                ],
                'total_users' => $integrator->users()->count(),
                // Add more statistics as needed
            ];

            return $this->sendSuccess(
                $stats,
                'Integrator statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error retrieving integrator statistics: ' . $e->getMessage(),
                500
            );
        }
    }
}