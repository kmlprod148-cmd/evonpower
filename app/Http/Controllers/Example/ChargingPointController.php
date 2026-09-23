<?php

namespace App\Http\Controllers\Example;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Services\AutoAssignmentService;
use App\Traits\PolicyEnforcement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChargingPointController extends Controller
{
    use PolicyEnforcement;

    /**
     * Display a listing of charging points accessible to the user
     */
    public function index(Request $request)
    {
        $chargingPoints = $this->getAccessibleModels(ChargingPoint::class, 15);
        
        if ($request->expectsJson()) {
            return response()->json([
                'charging_points' => $chargingPoints,
                'user_role' => auth()->user()->getRoleNames()->first(),
            ]);
        }

        return view('charging-points.index', compact('chargingPoints'));
    }

    /**
     * Display the specified charging point
     */
    public function show(ChargingPoint $chargingPoint)
    {
        // Authorize viewing the charging point
        $this->authorizeView($chargingPoint);

        if (request()->expectsJson()) {
            return response()->json([
                'charging_point' => $chargingPoint,
                'can_update' => $this->canPerformAction('update', $chargingPoint),
                'can_delete' => $this->canPerformAction('delete', $chargingPoint),
                'can_control' => $this->canPerformAction('control', $chargingPoint),
            ]);
        }

        return view('charging-points.show', compact('chargingPoint'));
    }

    /**
     * Show the form for creating a new charging point
     */
    public function create()
    {
        // Authorize creating charging points
        $this->authorizeCreate(ChargingPoint::class);

        $availableGroups = AutoAssignmentService::getAvailableOptions('groups');
        $defaultValues = AutoAssignmentService::getDefaultValues('charging_point');

        return view('charging-points.create', compact('availableGroups', 'defaultValues'));
    }

    /**
     * Store a newly created charging point
     */
    public function store(Request $request)
    {
        // Authorize creating charging points
        $this->authorizeCreate(ChargingPoint::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|unique:charging_points',
            'group_id' => [
                'nullable',
                'integer',
                'exists:groups,id',
                function ($attribute, $value, $fail) {
                    if (!AutoAssignmentService::validateAssignment('charging_point', ['group_id' => $value])) {
                        $fail('You do not have permission to assign this group.');
                    }
                },
            ],
            'status' => 'required|in:online,offline,maintenance',
            'description' => 'nullable|string',
            'power_output' => 'nullable|numeric|min:0',
            'connector_type' => 'nullable|string|max:50',
        ]);

        // Auto-assign relationships based on authenticated user's role
        $autoAssignedData = AutoAssignmentService::assignChargingPointRelationships(new ChargingPoint(), $validated);
        
        // Merge validated data with auto-assigned data
        $chargingPointData = array_merge($validated, $autoAssignedData);

        $chargingPoint = ChargingPoint::create($chargingPointData);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Charging point created successfully',
                'charging_point' => $chargingPoint->load(['group', 'partner', 'integrator']),
                'auto_assigned_fields' => $autoAssignedData,
            ], 201);
        }

        return redirect()->route('charging-points.show', $chargingPoint)
            ->with('success', 'Charging point created successfully.')
            ->with('auto_assigned', $autoAssignedData);
    }

    /**
     * Show the form for editing the specified charging point
     */
    public function edit(ChargingPoint $chargingPoint)
    {
        // Authorize updating the charging point
        $this->authorizeUpdate($chargingPoint);

        return view('charging-points.edit', compact('chargingPoint'));
    }

    /**
     * Update the specified charging point
     */
    public function update(Request $request, ChargingPoint $chargingPoint)
    {
        // Authorize updating the charging point
        $this->authorizeUpdate($chargingPoint);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:online,offline,maintenance',
            'description' => 'nullable|string',
        ]);

        $chargingPoint->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Charging point updated successfully',
                'charging_point' => $chargingPoint,
            ]);
        }

        return redirect()->route('charging-points.show', $chargingPoint)
            ->with('success', 'Charging point updated successfully.');
    }

    /**
     * Remove the specified charging point
     */
    public function destroy(ChargingPoint $chargingPoint)
    {
        // Authorize deleting the charging point
        $this->authorizeDelete($chargingPoint);

        $chargingPoint->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Charging point deleted successfully',
            ]);
        }

        return redirect()->route('charging-points.index')
            ->with('success', 'Charging point deleted successfully.');
    }

    /**
     * Control the charging point (start/stop charging)
     */
    public function control(Request $request, ChargingPoint $chargingPoint)
    {
        // Authorize controlling the charging point
        $this->authorizePolicy('control', $chargingPoint);

        $validated = $request->validate([
            'action' => 'required|in:start,stop',
            'session_id' => 'nullable|string',
        ]);

        // Implement charging control logic here
        $result = $this->performChargingControl($chargingPoint, $validated['action'], $validated['session_id'] ?? null);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Charging {$validated['action']}ed successfully",
                'result' => $result,
            ]);
        }

        return back()->with('success', "Charging {$validated['action']}ed successfully.");
    }

    /**
     * Get charging point statistics
     */
    public function statistics(ChargingPoint $chargingPoint)
    {
        // Authorize viewing statistics
        $this->authorizePolicy('viewStatistics', $chargingPoint);

        $statistics = $this->getChargingPointStatistics($chargingPoint);

        if (request()->expectsJson()) {
            return response()->json([
                'statistics' => $statistics,
            ]);
        }

        return view('charging-points.statistics', compact('chargingPoint', 'statistics'));
    }

    /**
     * Get available groups for the authenticated user
     */
    public function getAvailableGroups()
    {
        $groups = AutoAssignmentService::getAvailableOptions('groups');
        
        return response()->json([
            'groups' => $groups,
        ]);
    }

    /**
     * Get default values for creating a new charging point
     */
    public function getDefaultValues()
    {
        $defaults = AutoAssignmentService::getDefaultValues('charging_point');
        
        return response()->json([
            'default_values' => $defaults,
        ]);
    }

    /**
     * Validate if user can assign to specific group
     */
    public function validateGroupAssignment(Request $request)
    {
        $groupId = $request->input('group_id');
        
        $canAssign = AutoAssignmentService::validateAssignment('charging_point', [
            'group_id' => $groupId
        ]);

        return response()->json([
            'can_assign' => $canAssign,
            'group_id' => $groupId,
        ]);
    }

    /**
     * Perform charging control action
     */
    protected function performChargingControl(ChargingPoint $chargingPoint, string $action, ?string $sessionId = null)
    {
        // Implement actual charging control logic here
        return [
            'charging_point_id' => $chargingPoint->id,
            'action' => $action,
            'session_id' => $sessionId,
            'timestamp' => now(),
            'status' => 'success',
        ];
    }

    /**
     * Get charging point statistics
     */
    protected function getChargingPointStatistics(ChargingPoint $chargingPoint)
    {
        // Implement actual statistics calculation here
        return [
            'total_sessions' => $chargingPoint->chargingSessions()->count(),
            'total_energy_delivered' => $chargingPoint->total_energy_delivered ?? 0,
            'last_connection' => $chargingPoint->last_connection,
            'status' => $chargingPoint->status,
        ];
    }
}
