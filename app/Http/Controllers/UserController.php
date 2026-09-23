<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Integrator;
use App\Services\AutoAssignmentService;
use App\Traits\PolicyEnforcement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use PolicyEnforcement;

    /**
     * Display a listing of users (operators) accessible to the authenticated user
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = User::query();

        // Apply role-based filtering
        if ($user->hasRole(['admin', 'super_admin', 'Admin', 'Super-Admin', 'super_admin'])) {
            // Admin sees all users
        } elseif ($user->hasRole(['integrator', 'Integrator'])) {
            // Integrator sees only their own operators
            $query->where('integrator_id', $user->integrator_id);
        } else {
            // Other roles see only operators
            $query->where('integrator_id', $user->integrator_id);
        }

        // Filter only operators if not admin
        if (!$user->hasRole(['admin', 'super_admin', 'Admin', 'Super-Admin', 'super_admin'])) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'operator');
            });
        }

        $users = $query->with(['integrator', 'partner', 'roles'])->paginate(15);

        if ($request->expectsJson()) {
            return response()->json([
                'users' => $users,
                'available_integrators' => AutoAssignmentService::getAvailableOptions('integrators'),
                'default_values' => AutoAssignmentService::getDefaultValues('user'),
            ]);
        }

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user (operator)
     */
    public function create()
    {
        $this->authorizeCreate(User::class);

        $availableIntegrators = AutoAssignmentService::getAvailableOptions('integrators');
        $defaultValues = AutoAssignmentService::getDefaultValues('user');

        return view('users.create', compact('availableIntegrators', 'defaultValues'));
    }

    /**
     * Store a newly created user (operator)
     */
    public function store(Request $request)
    {
        $this->authorizeCreate(User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'integrator_id' => [
                'nullable',
                'integer',
                'exists:integrators,id',
                function ($attribute, $value, $fail) {
                    if (!AutoAssignmentService::validateAssignment('user', ['integrator_id' => $value])) {
                        $fail('You do not have permission to assign this integrator.');
                    }
                },
            ],
            'role' => 'required|string|in:operator,integrator,partner',
        ]);

        // Auto-assign relationships based on authenticated user's role
        $autoAssignedData = AutoAssignmentService::assignUserRelationships(new User(), $validated);
        
        // Merge validated data with auto-assigned data
        $userData = array_merge($validated, $autoAssignedData);
        
        // Hash password
        $userData['password'] = Hash::make($userData['password']);

        // Create user
        $user = User::create($userData);

        // Assign role
        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $user->assignRole($role);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'User created successfully',
                'user' => $user->load(['integrator', 'partner', 'roles']),
                'auto_assigned_fields' => $autoAssignedData,
            ], 201);
        }

        return redirect()->route('users.show', $user)
            ->with('success', 'User created successfully.')
            ->with('auto_assigned', $autoAssignedData);
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $this->authorizeView($user);

        $user->load(['integrator', 'partner', 'roles', 'creator']);

        if (request()->expectsJson()) {
            return response()->json([
                'user' => $user,
                'can_update' => $this->canPerformAction('update', $user),
                'can_delete' => $this->canPerformAction('delete', $user),
            ]);
        }

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $this->authorizeUpdate($user);

        $availableIntegrators = AutoAssignmentService::getAvailableOptions('integrators');
        $defaultValues = AutoAssignmentService::getDefaultValues('user');

        return view('users.edit', compact('user', 'availableIntegrators', 'defaultValues'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeUpdate($user);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'integrator_id' => [
                'nullable',
                'integer',
                'exists:integrators,id',
                function ($attribute, $value, $fail) {
                    if (!AutoAssignmentService::validateAssignment('user', ['integrator_id' => $value])) {
                        $fail('You do not have permission to assign this integrator.');
                    }
                },
            ],
            'is_active' => 'sometimes|boolean',
        ]);

        // Auto-assign relationships for updates (if integrator_id is being changed)
        if (isset($validated['integrator_id'])) {
            $autoAssignedData = AutoAssignmentService::assignUserRelationships($user, $validated);
            $validated = array_merge($validated, $autoAssignedData);
        }

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user->load(['integrator', 'partner', 'roles']),
            ]);
        }

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        $this->authorizeDelete($user);

        $user->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'User deleted successfully',
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Get available integrators for the authenticated user
     */
    public function getAvailableIntegrators()
    {
        $integrators = AutoAssignmentService::getAvailableOptions('integrators');
        
        return response()->json([
            'integrators' => $integrators,
        ]);
    }

    /**
     * Get default values for creating a new user
     */
    public function getDefaultValues()
    {
        $defaults = AutoAssignmentService::getDefaultValues('user');
        
        return response()->json([
            'default_values' => $defaults,
        ]);
    }

    /**
     * Validate if user can assign to specific integrator
     */
    public function validateIntegratorAssignment(Request $request)
    {
        $integratorId = $request->input('integrator_id');
        
        $canAssign = AutoAssignmentService::validateAssignment('user', [
            'integrator_id' => $integratorId
        ]);

        return response()->json([
            'can_assign' => $canAssign,
            'integrator_id' => $integratorId,
        ]);
    }
}