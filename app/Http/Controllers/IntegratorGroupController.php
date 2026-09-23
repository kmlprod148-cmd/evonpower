<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class IntegratorGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:integrator');
    }

    /**
     * Display a listing of the integrator's groups
     */
    public function index()
    {
        $integrator = Auth::user();
        
        // Get groups created by this integrator
        $groups = Group::where('created_by', $integrator->id)
            ->withCount('chargingPoints')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('integrator.groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new group
     */
    public function create()
    {
        return view('integrator.groups.create');
    }

    /**
     * Store a newly created group
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $integrator = Auth::user();
            
            // Create the group
            $group = Group::create([
                'name' => $request->name,
                'description' => $request->description,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'created_by' => $integrator->id,
                'integrator_id' => $integrator->integrator_id,
                'is_active' => $request->boolean('is_active', true),
                'is_public' => $request->boolean('is_public', false),
            ]);

            Log::info('Integrator created group', [
                'integrator_id' => $integrator->id,
                'group_id' => $group->id,
                'group_name' => $group->name
            ]);

            DB::commit();

            return redirect()
                ->route('integrator.groups.show', $group)
                ->with('success', 'Groupe créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating group', [
                'integrator_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la création du groupe: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified group
     */
    public function show(Group $group)
    {
        // Check if integrator can view this group
        if ($group->created_by !== Auth::id()) {
            return redirect()->route('integrator.groups.index')
                ->with('error', 'Vous n\'avez pas accès à ce groupe.');
        }

        // Get charging points in this group
        $chargingPoints = ChargingPoint::where('group_id', $group->id)
            ->with(['pricingPlan', 'station'])
            ->paginate(10);

        return view('integrator.groups.show', compact('group', 'chargingPoints'));
    }

    /**
     * Show the form for editing the specified group
     */
    public function edit(Group $group)
    {
        // Check if integrator can edit this group
        if ($group->created_by !== Auth::id()) {
            return redirect()->route('integrator.groups.index')
                ->with('error', 'Vous n\'avez pas la permission de modifier ce groupe.');
        }

        return view('integrator.groups.edit', compact('group'));
    }

    /**
     * Update the specified group
     */
    public function update(Request $request, Group $group)
    {
        // Check if integrator can update this group
        if ($group->created_by !== Auth::id()) {
            return redirect()->route('integrator.groups.index')
                ->with('error', 'Vous n\'avez pas la permission de modifier ce groupe.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ]);

        try {
            $group->update([
                'name' => $request->name,
                'description' => $request->description,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_active' => $request->boolean('is_active'),
                'is_public' => $request->boolean('is_public'),
            ]);

            Log::info('Integrator updated group', [
                'integrator_id' => Auth::id(),
                'group_id' => $group->id,
                'group_name' => $group->name
            ]);

            return redirect()
                ->route('integrator.groups.show', $group)
                ->with('success', 'Groupe mis à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Error updating group', [
                'integrator_id' => Auth::id(),
                'group_id' => $group->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour du groupe: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified group
     */
    public function destroy(Group $group)
    {
        // Check if integrator can delete this group
        if ($group->created_by !== Auth::id()) {
            return redirect()->route('integrator.groups.index')
                ->with('error', 'Vous n\'avez pas la permission de supprimer ce groupe.');
        }

        // Check if group has charging points
        $chargingPointsCount = ChargingPoint::where('group_id', $group->id)->count();

        if ($chargingPointsCount > 0) {
            return redirect()
                ->back()
                ->with('error', 'Ce groupe contient ' . $chargingPointsCount . ' charging point(s). Vous ne pouvez pas le supprimer.');
        }

        try {
            $groupName = $group->name;
            $group->delete();

            Log::info('Integrator deleted group', [
                'integrator_id' => Auth::id(),
                'group_name' => $groupName
            ]);

            return redirect()
                ->route('integrator.groups.index')
                ->with('success', 'Groupe supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error deleting group', [
                'integrator_id' => Auth::id(),
                'group_id' => $group->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du groupe: ' . $e->getMessage());
        }
    }

    /**
     * Toggle group status
     */
    public function toggleStatus(Group $group)
    {
        // Check if integrator can manage this group
        if ($group->created_by !== Auth::id()) {
            return redirect()->route('integrator.groups.index')
                ->with('error', 'Vous n\'avez pas la permission de gérer ce groupe.');
        }

        try {
            $group->is_active = !$group->is_active;
            $group->save();

            $status = $group->is_active ? 'activé' : 'désactivé';
            
            Log::info('Integrator toggled group status', [
                'integrator_id' => Auth::id(),
                'group_id' => $group->id,
                'new_status' => $group->is_active
            ]);

            return redirect()
                ->back()
                ->with('success', "Groupe {$status} avec succès.");

        } catch (\Exception $e) {
            Log::error('Error toggling group status', [
                'integrator_id' => Auth::id(),
                'group_id' => $group->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
        }
    }

    /**
     * Get groups for AJAX requests
     */
    public function getGroups()
    {
        $integrator = Auth::user();
        
        $groups = Group::where('created_by', $integrator->id)
            ->where('is_active', true)
            ->select('id', 'name', 'location')
            ->get();

        return response()->json($groups);
    }
}
