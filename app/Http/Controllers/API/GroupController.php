<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Traits\ApiResponse; // Use the standardized ApiResponse trait
use App\Models\Group;
use App\Http\Requests\Group\GroupStoreRequest;
use App\Http\Requests\Group\GroupUpdateRequest;
use App\Http\Resources\GroupResource;
use App\Http\Resources\GroupCollection;
use App\Http\Resources\ChargingPointCollection;
use App\Models\Partner;
use App\Models\Integrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\CrudException;

class GroupController extends BaseApiController
{
    use ApiResponse; // Use the ApiResponse trait

    /**
     * Display a listing of groups with filtering options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\GroupCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Redirect if request comes from a browser
        if (!$request->expectsJson() && !$request->wantsJson()) {
            return redirect()->route('groups.index');
        }

        // Original API logic continues here
        try {
            $query = Group::query();

            // Apply filters if provided
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            }

            if ($request->has('type')) {
                $query->where('type', $request->input('type'));
            }

            // Apply sorting
            $sortField = $request->input('sort', 'created_at');
            $sortOrder = $request->input('order', 'desc');

            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['name', 'type', 'city', 'created_at', 'updated_at'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Paginate the results
            $perPage = $request->input('per_page', 15);
            $groups = $query->paginate($perPage);

            return new GroupCollection($groups);
        } catch (\Exception $e) {
            return $this->sendError(
                'Error retrieving groups: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created group.
     *
     * @param  \App\Http\Requests\Group\GroupStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate using a standard validator for now
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:public,private',
                'city' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'user_id' => 'nullable|exists:users,id',
                'business_profile_id' => 'nullable|exists:business_profiles,id', // Assuming business_profile_id is used for partner
            ]);

            // Validate partner access based on user role
            $this->validatePartnerAccess($validatedData, $user);

            // Set relationships based on user role
            $validatedData = $this->setRelationshipsByRole($validatedData, $user);

            // If no user_id is provided, use the authenticated user's ID
            if (!isset($validatedData['user_id']) && $user) {
                $validatedData['user_id'] = $user->id;
            }

            $group = Group::create($validatedData);

            return $this->sendSuccess(
                new GroupResource($group),
                'Group created successfully',
                201
            );
        } catch (CrudException $e) {
             return $this->sendError(
                'Validation Error: ' . $e->getMessage(),
                400 // Bad Request
            );
        }
        catch (\Exception $e) {
            return $this->sendError(
                'Error creating group: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified group.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $group = Group::with(['user', 'businessProfile'])->find($id);

            if (!$group) {
                return $this->sendNotFound('Group not found');
            }
            $this->authorize('view', $group);

            return $this->sendSuccess(
                new GroupResource($group),
                'Group retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error retrieving group: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified group.
     *
     * @param  \App\Http\Requests\Group\GroupUpdateRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $group = Group::find($id);

            if (!$group) {
                return $this->sendNotFound('Group not found');
            }
            $this->authorize('update', $group);

            $user = $request->user();

            // Validate using a standard validator for now
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'sometimes|required|in:public,private',
                'city' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'user_id' => 'nullable|exists:users,id',
                'business_profile_id' => 'nullable|exists:business_profiles,id', // Assuming business_profile_id is used for partner
            ]);

            // Validate partner access based on user role
            $this->validatePartnerAccess($validatedData, $user);

            // Set relationships based on user role
            $validatedData = $this->setRelationshipsByRole($validatedData, $user);

            $group->update($validatedData);

            return $this->sendSuccess(
                new GroupResource($group),
                'Group updated successfully'
            );
        } catch (CrudException $e) {
             return $this->sendError(
                'Validation Error: ' . $e->getMessage(),
                400 // Bad Request
            );
        }
        catch (\Exception $e) {
            return $this->sendError(
                'Error updating group: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified group.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $group = Group::find($id);

            if (!$group) {
                return $this->sendNotFound('Group not found');
            }
            $this->authorize('delete', $group);

            // Check if there are any charging points associated with this group
            if ($group->chargingPoints()->count() > 0) {
                return $this->sendError(
                    'Cannot delete group with associated charging points',
                    409 // Conflict
                );
            }

            $group->delete();

            return $this->sendSuccess(
                null,
                'Group deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error deleting group: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get charging points for a specific group.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChargingPoints(int $id, Request $request): JsonResponse
    {
        try {
            $group = Group::find($id);

            if (!$group) {
                return $this->sendNotFound('Group not found');
            }

            // Apply filters if provided
            $query = $group->chargingPoints();

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Paginate the results
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
     * Get statistics for a specific group.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(int $id): JsonResponse
    {
        try {
            $group = Group::find($id);

            if (!$group) {
                return $this->sendNotFound('Group not found');
            }

            // Generate statistics
            $stats = [
                'total_charging_points' => $group->chargingPoints()->count(),
                'by_status' => [
                    'online' => $group->chargingPoints()->where('status', 'online')->count(),
                    'offline' => $group->chargingPoints()->where('status', 'offline')->count(),
                    'maintenance' => $group->chargingPoints()->where('status', 'maintenance')->count(),
                    'error' => $group->chargingPoints()->where('status', 'error')->count(),
                ],
                // Add more statistics as needed
            ];

            return $this->sendSuccess(
                $stats,
                'Group statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Error retrieving group statistics: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Validate partner access based on user role.
     *
     * @param  array  $data
     * @param  \App\Models\User  $user
     * @throws \App\Exceptions\CrudException
     * @return void
     */
    private function validatePartnerAccess(array $data, \App\Models\User $user): void
    {
        // If partner_id is provided, validate access
        if (isset($data['business_profile_id']) && $data['business_profile_id']) {
            $partner = Partner::find($data['business_profile_id']);

            if (!$partner) {
                throw new \App\Exceptions\CrudException('Invalid partner selected.');
            }

            // Admin can assign any partner
            if ($user->hasRole('admin')) {
                return;
            }

            // Integrator can only assign partners within their organization
            if ($user->hasRole('integrator') && $user->integrator_id && $partner->integrator_id !== $user->integrator_id) {
                throw new \App\Exceptions\CrudException('You cannot assign groups to partners outside your organization.');
            }

            // Partner/Operator can only assign groups to their own partner profile
            if ($user->partner_id && $partner->id !== $user->partner_id) {
                throw new \App\Exceptions\CrudException('You cannot assign groups to other partners.');
            }
        } else {
             // If no partner_id is provided, ensure non-admins have a partner assigned
             if (!$user->hasRole('admin') && !$user->partner_id) {
                 throw new \App\Exceptions\CrudException('Partner must be assigned for non-admin users.');
             }
        }
    }

    /**
     * Set partner and integrator relationships based on user role.
     *
     * @param  array  $data
     * @param  \App\Models\User  $user
     * @return array
     */
    private function setRelationshipsByRole(array $data, \App\Models\User $user): array
    {
        // Admin can set all relationships
        if ($user->hasRole('admin')) {
            return $data;
        }

        // Integrator constraints
        if ($user->hasRole('integrator') && $user->integrator_id) {
            $data['integrator_id'] = $user->integrator_id;

            // If partner_id is provided, validate it belongs to the integrator
            if (isset($data['business_profile_id'])) {
                $partner = Partner::find($data['business_profile_id']);
                if (!$partner || $partner->integrator_id !== $user->integrator_id) {
                    // Unset if invalid, validation should catch this earlier but as a safeguard
                    unset($data['business_profile_id']);
                }
            }
        }

        // Partner/Operator constraints
        if ($user->partner_id) {
            $partner = Partner::find($user->partner_id);
            if ($partner) {
                $data['business_profile_id'] = $partner->id;
                $data['integrator_id'] = $partner->integrator_id;
            }
        }

        return $data;
}
    }