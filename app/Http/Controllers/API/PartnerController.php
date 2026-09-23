<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Traits\ApiResponse;
use App\Models\Partner;
use App\Http\Requests\Partner\PartnerStoreRequest;
use App\Http\Requests\Partner\PartnerUpdateRequest;
use App\Http\Resources\ChargingPointCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Added Log facade

class PartnerController extends ApiController
{
    /**
     * Display a listing of partners.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Redirect if request comes from a browser
        if (!$request->expectsJson() && !$request->wantsJson()) {
            return redirect()->route('partners.index');
        }

        // Original API logic continues here
        try {
            Log::info('Fetching partners...'); // Added logging
            $query = Partner::query();

            // Apply filters if provided
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('contact_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            }

            if ($request->has('type')) {
                $types = is_array($request->type) ? $request->type : [$request->type];
                $query->whereIn('type', $types);
            }

            if ($request->has('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            if ($request->user() && $request->user()->hasRole('integrator')) {
                // Filter partners by the integrator ID of the authenticated user
                $query->where('integrator_id', $request->user()->integrator_id);
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_dir', 'desc');

            // Check if sort field exists in table to prevent SQL injection
            $allowedSortFields = ['name', 'contact_name', 'email', 'type', 'city', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Paginate results
            $perPage = $request->input('per_page', 15);
            $partners = $query->paginate($perPage);

            return $this->sendSuccess(
                $partners,
                'Partners retrieved successfully'
            );
        } catch (\Exception $e) { // Added catch block
           Log::error('Error retrieving partners: ' . $e->getMessage(), ['exception' => $e]);
           return $this->sendError(
               'Error retrieving partners: ' . $e->getMessage(),
               500
           );
        }
    }

    /**
     * Store a newly created partner.
     *
     * @param PartnerStoreRequest $request
     * @return JsonResponse
     */
    public function store(PartnerStoreRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // If the user is an integrator, override the integrator_id with their own
            if ($request->user() && $request->user()->hasRole('integrator')) {
                $validatedData['integrator_id'] = $request->user()->integrator_id;
            }

            DB::beginTransaction();

            // Create the partner
            $partner = Partner::create($validatedData);

            // Create admin user for partner if requested
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
                    'integrator_id' => $partner->integrator_id,
                    'partner_id' => $partner->id,
                ]);

                // Assign operator role
                if (method_exists($user, 'assignRole')) {
                    $user->assignRole('operator');
                }
            }

            DB::commit();

            return $this->sendSuccess(
                $partner,
                'Partner created successfully',
                201
            );
        } catch (\Exception $e) {
           DB::rollBack();
           Log::error('Error creating partner: ' . $e->getMessage(), ['exception' => $e]);
           return $this->sendError(
               'Error creating partner: ' . $e->getMessage(),
               500
           );
        }
    }

    /**
     * Display the specified partner.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            // Eager load relationships to avoid N+1 problem for statistics
            $partner = Partner::with(['integrator', 'businessProfile', 'chargingPoints', 'users'])->find($id);

            if (!$partner) {
                throw new \App\Exceptions\PartnerNotFoundException('Partner not found');
            }
            $this->authorize('view', $partner);

            // Check if user has permission to view this partner
            if ($request->user() && $request->user()->hasRole('integrator') && $request->user()->integrator_id !== $partner->integrator_id) {
                throw new \App\Exceptions\PermissionDeniedException('You do not have permission to view this partner');
            }

            // Add some statistics for the response
            // Calculate statistics from eager loaded relationships for efficiency
            $stats = [
                'total_charging_points' => $partner->chargingPoints->count(), // Refactored for eager loading
                'total_users' => $partner->users->count(), // Refactored for eager loading
                'active_charging_points' => $partner->chargingPoints->where('status', 'online')->count(), // Refactored for eager loading
            ];

            $responseData = $partner->toArray();
            $responseData['stats'] = $stats;

            return $this->sendSuccess(
                $responseData,
                'Partner retrieved successfully'
            );

        } catch (\App\Exceptions\PartnerNotFoundException $e) {
             return $this->sendNotFound($e->getMessage());
        } catch (\App\Exceptions\PermissionDeniedException $e) {
             return $this->sendError($e->getMessage(), null, 403);
        } catch (\Exception $e) {
           Log::error('Error retrieving partner: ' . $e->getMessage(), ['exception' => $e]);
           return $this->sendError(
               'Error retrieving partner: ' . $e->getMessage(),
               500
           );
        }
    }

    /**
     * Update the specified partner.
     *
     * @param  PartnerUpdateRequest  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(PartnerUpdateRequest $request, $id): JsonResponse
    {
        try {
            $partner = Partner::find($id);

            if (!$partner) {
                throw new \App\Exceptions\PartnerNotFoundException('Partner not found');
            }
            $this->authorize('update', $partner);

            // Check if user has permission to update this partner
            if ($request->user() && $request->user()->hasRole('integrator') && $request->user()->integrator_id !== $partner->integrator_id) {
                throw new \App\Exceptions\PermissionDeniedException('You do not have permission to update this partner');
            }

            $validatedData = $request->validated();

            // If the user is an integrator, they can't change the integrator_id
            if ($request->user() && $request->user()->hasRole('integrator')) {
                if (isset($validatedData['integrator_id']) && $validatedData['integrator_id'] !== $request->user()->integrator_id) {
                    throw new \App\Exceptions\PermissionDeniedException('You cannot change the integrator for this partner');
                }
            }

            $partner->update($validatedData);

            return $this->sendSuccess(
                $partner,
                'Partner updated successfully'
            );
        } catch (\App\Exceptions\PartnerNotFoundException $e) {
             return $this->sendNotFound($e->getMessage());
         } catch (\App\Exceptions\PermissionDeniedException $e) {
              return $this->sendError($e->getMessage(), null, 403);
         } catch (\Exception $e) {
            Log::error('Error updating partner: ' . $e->getMessage(), ['exception' => $e]);
            return $this->sendError(
                'Error updating partner: ' . $e->getMessage(),
                500
            );
         }
     }

    /**
     * Remove the specified partner.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $partner = Partner::find($id);

            if (!$partner) {
                throw new \App\Exceptions\PartnerNotFoundException('Partner not found');
            }
            $this->authorize('delete', $partner);


            // Check if there are dependent charging points
            if ($partner->chargingPoints()->count() > 0) {
                throw new \App\Exceptions\ConflictException('Cannot delete partner with associated charging points');
            }

            DB::beginTransaction();

            // Delete users associated with this partner
            \App\Models\User::where('partner_id', $id)->delete();

            // Delete the partner
            $partner->delete();

            DB::commit();

            return $this->sendSuccess(
                null,
                'Partner deleted successfully'
            );

        } catch (\App\Exceptions\PartnerNotFoundException $e) {
             return $this->sendNotFound($e->getMessage());
        } catch (\App\Exceptions\PermissionDeniedException $e) {
             return $this->sendError($e->getMessage(), null, 403);
        } catch (\App\Exceptions\ConflictException $e) {
             return $this->sendError($e->getMessage(), null, 409);
        } catch (\Exception $e) {
           DB::rollBack();
           Log::error('Error deleting partner: ' . $e->getMessage(), ['exception' => $e]);
           return $this->sendError(
               'Error deleting partner: ' . $e->getMessage(),
               500
           );
        }
    }

    /**
     * Get charging points for a specific partner.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function getChargingPoints(Request $request, $id): JsonResponse
    {
        try {
            $partner = Partner::find($id);

            if (!$partner) {
                return $this->sendNotFound('Partner not found');
            }

            // Check if user has permission to view this partner's charging points
            if ($request->user() && $request->user()->hasRole('integrator') && $request->user()->integrator_id !== $partner->integrator_id) {
                return $this->sendError('You do not have permission to view this partner\'s charging points', null, 403);
            }

            // Apply filters if provided
            $query = $partner->chargingPoints();

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
           Log::error('Error retrieving charging points for partner: ' . $e->getMessage(), ['exception' => $e]);
           return $this->sendError(
               'Error retrieving charging points for partner: ' . $e->getMessage(),
               500
           );
        }
    }

    /**
     * Get statistics for a specific partner.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function getStats(Request $request, $id): JsonResponse
    {
        try {
            Log::info('Fetching partner statistics for ID: ' . $id); // Added logging
            $partner = Partner::find($id);

            if (!$partner) {
                return $this->sendNotFound('Partner not found');
            }

            // Check if user has permission to view this partner's stats
            if ($request->user() && $request->user()->hasRole('integrator') && $request->user()->integrator_id !== $partner->integrator_id) {
                return $this->sendError('You do not have permission to view this partner\'s statistics', null, 403);
            }

            // Generate statistics
            $stats = [
                'total_charging_points' => $partner->chargingPoints()->count(),
                'by_status' => [
                    'online' => $partner->chargingPoints()->where('status', 'online')->count(),
                    'offline' => $partner->chargingPoints()->where('status', 'offline')->count(),
                    'maintenance' => $partner->chargingPoints()->where('status', 'maintenance')->count(),
                    'error' => $partner->chargingPoints()->where('status', 'error')->count(),
                ],
                'total_users' => $partner->users()->count(),
                // Add more statistics as needed
            ];

            return $this->sendSuccess(
                $stats,
                'Partner statistics retrieved successfully'
            );
        } catch (\Exception $e) { // Added catch block
           Log::error('Error retrieving partner statistics: ' . $e->getMessage(), ['exception' => $e]);
           return $this->sendError(
               'Error retrieving partner statistics: ' . $e->getMessage(),
               500
           );
        }
    }
}