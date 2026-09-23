<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Partner;
use App\Models\Group;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class OperatorChargingPointController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:operator');
    }

    /**
     * Display a listing of the operator's charging points
     */
    public function index()
    {
        $operator = Auth::user();
        
        // Get charging points within operator's integrator network
        $chargingPoints = ChargingPoint::where('integrator_id', $operator->integrator_id)
            ->with(['pricingPlan', 'partner', 'group', 'station'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('operator.charging-points.index', compact('chargingPoints'));
    }

    /**
     * Show the form for creating a new charging point
     */
    public function create()
    {
        $operator = Auth::user();
        
        // Get pricing plans available to this operator
        $pricingPlans = PricingPlan::where('is_active', true)->get();
        
        // Get partner for this operator
        $partner = Partner::find($operator->partner_id);
        
        // Get groups available to this operator (only their partner's groups)
        $groups = Group::forUser($operator)->where('is_active', true)->get();

        // Get stations available to this operator
        $stations = Station::where('is_active', true)->get();

        return view('operator.charging-points.create', compact('pricingPlans', 'partner', 'groups', 'stations'));
    }

    /**
     * Store a newly created charging point
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'group_id' => 'nullable|exists:groups,id',
            'station_id' => 'nullable|exists:stations,id',
            'max_power' => 'required|numeric|min:0',
            'connector_type' => 'required|string|max:50',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $operator = Auth::user();
            
            // Create the charging point
            $chargingPoint = ChargingPoint::create([
                'name' => $request->name,
                'description' => $request->description,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'pricing_plan_id' => $request->pricing_plan_id,
                'group_id' => $request->group_id,
                'station_id' => $request->station_id,
                'user_id' => $operator->id,
                'operator_id' => $operator->id,
                'partner_id' => $operator->partner_id,
                'integrator_id' => $operator->integrator_id,
                'max_power' => $request->max_power,
                'connector_type' => $request->connector_type,
                'public_access' => $request->boolean('is_public', true),
                'is_active' => $request->boolean('is_active', true),
                'status' => $request->boolean('is_active', true) ? 'offline' : 'maintenance',
            ]);

            Log::info('Operator created charging point', [
                'operator_id' => $operator->id,
                'charging_point_id' => $chargingPoint->id,
                'charging_point_name' => $chargingPoint->name
            ]);

            DB::commit();

            return redirect()
                ->route('operator.charging-points.show', $chargingPoint)
                ->with('success', 'Charging point créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating charging point', [
                'operator_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la création du charging point: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified charging point
     */
    public function show(ChargingPoint $chargingPoint)
    {
        // Check if operator can view this charging point (must belong to their integrator)
        $operator = Auth::user();
        if ($chargingPoint->integrator_id !== $operator->integrator_id) {
            return redirect()->route('operator.charging-points.index')
                ->with('error', 'Vous n\'avez pas accès à ce charging point.');
        }

        $chargingPoint->load(['pricingPlan', 'partner', 'group', 'station']);

        return view('operator.charging-points.show', compact('chargingPoint'));
    }

    /**
     * Show the form for editing the specified charging point
     */
    public function edit(ChargingPoint $chargingPoint)
    {
        // Check if operator can edit this charging point (must belong to their integrator)
        $operator = Auth::user();
        if ($chargingPoint->integrator_id !== $operator->integrator_id) {
            return redirect()->route('operator.charging-points.index')
                ->with('error', 'Vous n\'avez pas la permission de modifier ce charging point.');
        }
        
        // Get pricing plans available to this operator
        $pricingPlans = PricingPlan::where('is_active', true)->get();
        
        // Get groups available to this operator (only their partner's groups)
        $groups = Group::forUser($operator)->where('is_active', true)->get();

        // Get stations available to this operator
        $stations = Station::where('is_active', true)->get();

        return view('operator.charging-points.edit', compact('chargingPoint', 'pricingPlans', 'groups', 'stations'));
    }

    /**
     * Update the specified charging point
     */
    public function update(Request $request, ChargingPoint $chargingPoint)
    {
        // Check if operator can update this charging point (must belong to their integrator)
        $operator = Auth::user();
        if ($chargingPoint->integrator_id !== $operator->integrator_id) {
            return redirect()->route('operator.charging-points.index')
                ->with('error', 'Vous n\'avez pas la permission de modifier ce charging point.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'group_id' => 'nullable|exists:groups,id',
            'station_id' => 'nullable|exists:stations,id',
            'max_power' => 'required|numeric|min:0',
            'connector_type' => 'required|string|max:50',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            $chargingPoint->update([
                'name' => $request->name,
                'description' => $request->description,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'pricing_plan_id' => $request->pricing_plan_id,
                'group_id' => $request->group_id,
                'station_id' => $request->station_id,
                'max_power' => $request->max_power,
                'connector_type' => $request->connector_type,
                'public_access' => $request->boolean('is_public'),
                'is_active' => $request->boolean('is_active'),
            ]);

            Log::info('Operator updated charging point', [
                'operator_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'charging_point_name' => $chargingPoint->name
            ]);

            return redirect()
                ->route('operator.charging-points.show', $chargingPoint)
                ->with('success', 'Charging point mis à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Error updating charging point', [
                'operator_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour du charging point: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified charging point
     */
    public function destroy(ChargingPoint $chargingPoint)
    {
        // Check if operator can delete this charging point (must belong to their integrator)
        $operator = Auth::user();
        if ($chargingPoint->integrator_id !== $operator->integrator_id) {
            return redirect()->route('operator.charging-points.index')
                ->with('error', 'Vous n\'avez pas la permission de supprimer ce charging point.');
        }

        try {
            $chargingPointName = $chargingPoint->name;
            $chargingPoint->delete();

            Log::info('Operator deleted charging point', [
                'operator_id' => Auth::id(),
                'charging_point_name' => $chargingPointName
            ]);

            return redirect()
                ->route('operator.charging-points.index')
                ->with('success', 'Charging point supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error deleting charging point', [
                'operator_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du charging point: ' . $e->getMessage());
        }
    }

    /**
     * Toggle charging point status
     */
    public function toggleStatus(ChargingPoint $chargingPoint)
    {
        // Check if operator can manage this charging point (must belong to their integrator)
        $operator = Auth::user();
        if ($chargingPoint->integrator_id !== $operator->integrator_id) {
            return redirect()->route('operator.charging-points.index')
                ->with('error', 'Vous n\'avez pas la permission de gérer ce charging point.');
        }

        try {
            $chargingPoint->is_active = !$chargingPoint->is_active;
            $chargingPoint->save();

            $status = $chargingPoint->is_active ? 'activé' : 'désactivé';
            
            Log::info('Operator toggled charging point status', [
                'operator_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'new_status' => $chargingPoint->is_active
            ]);

            return redirect()
                ->back()
                ->with('success', "Charging point {$status} avec succès.");

        } catch (\Exception $e) {
            Log::error('Error toggling charging point status', [
                'operator_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
        }
    }
}
