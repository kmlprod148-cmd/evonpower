<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Partner;
use App\Models\Group;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class AdminChargingPointController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of all charging points
     */
    public function index(Request $request)
    {
        $query = ChargingPoint::with(['pricingPlan', 'partner', 'group', 'station', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by public/private
        if ($request->has('visibility') && $request->visibility !== '') {
            $query->where('is_public', $request->visibility === 'public');
        }

        // Filter by creator
        if ($request->has('creator') && $request->creator !== '') {
            $query->where('user_id', $request->creator);
        }

        // Search by name or location
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $chargingPoints = $query->paginate(15);

        // Get creators for filter dropdown
        $creators = User::whereIn('id', ChargingPoint::distinct()->pluck('user_id'))
            ->select('id', 'name', 'email')
            ->get();

        return view('admin.charging-points.index', compact('chargingPoints', 'creators'));
    }

    /**
     * Show the form for creating a new charging point
     */
    public function create()
    {
        // Get all pricing plans
        $pricingPlans = PricingPlan::where('is_active', true)->get();
        
        // Get all partners
        $partners = Partner::where('is_active', true)->get();
        
        // Get all groups
        $groups = Group::where('is_active', true)->get();
        
        // Get all stations
        $stations = Station::where('is_active', true)->get();

        // Get all users who can own charging points
        $users = User::whereIn('role', ['operator', 'partner', 'integrator'])
            ->where('is_active', true)
            ->get();

        return view('admin.charging-points.create', compact('pricingPlans', 'partners', 'groups', 'stations', 'users'));
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
            'partner_id' => 'nullable|exists:partners,id',
            'group_id' => 'nullable|exists:groups,id',
            'station_id' => 'nullable|exists:stations,id',
            'user_id' => 'required|exists:users,id',
            'max_power' => 'required|numeric|min:0',
            'connector_type' => 'required|string|max:50',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $admin = Auth::user();
            
            // Create the charging point
            $chargingPoint = ChargingPoint::create([
                'name' => $request->name,
                'description' => $request->description,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'pricing_plan_id' => $request->pricing_plan_id,
                'partner_id' => $request->partner_id,
                'group_id' => $request->group_id,
                'station_id' => $request->station_id,
                'user_id' => $request->user_id,
                'user_id' => $admin->id,
                'max_power' => $request->max_power,
                'connector_type' => $request->connector_type,
                'is_active' => $request->boolean('is_active', true),
                'is_public' => $request->boolean('is_public', false),
            ]);

            Log::info('Admin created charging point', [
                'admin_id' => $admin->id,
                'charging_point_id' => $chargingPoint->id,
                'charging_point_name' => $chargingPoint->name,
                'owner_id' => $chargingPoint->user_id
            ]);

            DB::commit();

            return redirect()
                ->route('admin.charging-points.show', $chargingPoint)
                ->with('success', 'Charging point créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating charging point', [
                'admin_id' => Auth::id(),
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
        $chargingPoint->load(['pricingPlan', 'partner', 'group', 'station', 'user']);

        return view('admin.charging-points.show', compact('chargingPoint'));
    }

    /**
     * Show the form for editing the specified charging point
     */
    public function edit(ChargingPoint $chargingPoint)
    {
        // Get all pricing plans
        $pricingPlans = PricingPlan::where('is_active', true)->get();
        
        // Get all partners
        $partners = Partner::where('is_active', true)->get();
        
        // Get all groups
        $groups = Group::where('is_active', true)->get();
        
        // Get all stations
        $stations = Station::where('is_active', true)->get();

        // Get all users who can own charging points
        $users = User::whereIn('role', ['operator', 'partner', 'integrator'])
            ->where('is_active', true)
            ->get();

        return view('admin.charging-points.edit', compact('chargingPoint', 'pricingPlans', 'partners', 'groups', 'stations', 'users'));
    }

    /**
     * Update the specified charging point
     */
    public function update(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'partner_id' => 'nullable|exists:partners,id',
            'group_id' => 'nullable|exists:groups,id',
            'station_id' => 'nullable|exists:stations,id',
            'user_id' => 'required|exists:users,id',
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
                'partner_id' => $request->partner_id,
                'group_id' => $request->group_id,
                'station_id' => $request->station_id,
                'user_id' => $request->user_id,
                'max_power' => $request->max_power,
                'connector_type' => $request->connector_type,
                'is_active' => $request->boolean('is_active'),
                'is_public' => $request->boolean('is_public'),
            ]);

            Log::info('Admin updated charging point', [
                'admin_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'charging_point_name' => $chargingPoint->name
            ]);

            return redirect()
                ->route('admin.charging-points.show', $chargingPoint)
                ->with('success', 'Charging point mis à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Error updating charging point', [
                'admin_id' => Auth::id(),
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
        try {
            $chargingPointName = $chargingPoint->name;
            $chargingPoint->delete();

            Log::info('Admin deleted charging point', [
                'admin_id' => Auth::id(),
                'charging_point_name' => $chargingPointName
            ]);

            return redirect()
                ->route('admin.charging-points.index')
                ->with('success', 'Charging point supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error deleting charging point', [
                'admin_id' => Auth::id(),
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
        try {
            $chargingPoint->is_active = !$chargingPoint->is_active;
            $chargingPoint->save();

            $status = $chargingPoint->is_active ? 'activé' : 'désactivé';
            
            Log::info('Admin toggled charging point status', [
                'admin_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'new_status' => $chargingPoint->is_active
            ]);

            return redirect()
                ->back()
                ->with('success', "Charging point {$status} avec succès.");

        } catch (\Exception $e) {
            Log::error('Error toggling charging point status', [
                'admin_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
        }
    }

    /**
     * Assign charging point to different user
     */
    public function assign(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $oldUserId = $chargingPoint->user_id;
            $chargingPoint->update(['user_id' => $request->user_id]);

            Log::info('Admin assigned charging point', [
                'admin_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'old_user_id' => $oldUserId,
                'new_user_id' => $request->user_id
            ]);

            return redirect()
                ->back()
                ->with('success', 'Charging point réassigné avec succès.');

        } catch (\Exception $e) {
            Log::error('Error assigning charging point', [
                'admin_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la réassignation: ' . $e->getMessage());
        }
    }

    /**
     * Export charging points
     */
    public function export(Request $request)
    {
        $query = ChargingPoint::with(['pricingPlan', 'partner', 'group', 'station', 'user']);

        // Apply same filters as index
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('visibility') && $request->visibility !== '') {
            $query->where('is_public', $request->visibility === 'public');
        }

        if ($request->has('creator') && $request->creator !== '') {
            $query->where('user_id', $request->creator);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $chargingPoints = $query->get();

        // Create CSV content
        $csvData = [];
        $csvData[] = ['ID', 'Nom', 'Localisation', 'Latitude', 'Longitude', 'Puissance Max', 'Type Connecteur', 'Propriétaire', 'Plan Tarifaire', 'Statut', 'Public', 'Créé le'];

        foreach ($chargingPoints as $cp) {
            $csvData[] = [
                $cp->id,
                $cp->name,
                $cp->location,
                $cp->latitude,
                $cp->longitude,
                $cp->max_power,
                $cp->connector_type,
                $cp->user ? $cp->user->name : 'N/A',
                $cp->pricingPlan ? $cp->pricingPlan->name : 'N/A',
                $cp->is_active ? 'Actif' : 'Inactif',
                $cp->is_public ? 'Public' : 'Privé',
                $cp->created_at->format('d/m/Y H:i')
            ];
        }

        $filename = 'charging_points_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get charging point statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => ChargingPoint::count(),
            'active' => ChargingPoint::where('is_active', true)->count(),
            'inactive' => ChargingPoint::where('is_active', false)->count(),
            'public' => ChargingPoint::where('is_public', true)->count(),
            'private' => ChargingPoint::where('is_public', false)->count(),
            'by_connector_type' => ChargingPoint::selectRaw('connector_type, COUNT(*) as count')
                ->groupBy('connector_type')
                ->get(),
            'by_power_range' => [
                'low' => ChargingPoint::where('max_power', '<', 50)->count(),
                'medium' => ChargingPoint::whereBetween('max_power', [50, 150])->count(),
                'high' => ChargingPoint::where('max_power', '>', 150)->count(),
            ]
        ];

        return response()->json($stats);
    }
}
