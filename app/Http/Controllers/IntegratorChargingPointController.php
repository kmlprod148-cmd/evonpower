<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Group;
use App\Models\PricingPlan;
use App\Modules\ChargingPoints\Services\ChargingPointService;
use App\Modules\ChargingPoints\DTOs\ChargingPointDTO;
use App\Modules\ChargingPoints\DTOs\ConnectorDTO;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IntegratorChargingPointController extends Controller
{
    protected $chargingPointService;

    public function __construct(ChargingPointService $chargingPointService)
    {
        $this->middleware('auth');
        $this->chargingPointService = $chargingPointService;
    }

    /**
     * Affiche la liste des bornes de l'intégrateur
     */
    public function index()
    {
        Gate::authorize('view_charging_points');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $chargingPoints = ChargingPoint::where('integrator_id', $integrator->id)
            ->with(['user', 'partner', 'businessProfile', 'group'])
            ->paginate(15);

        return view('integrator.charging-points.index', compact('chargingPoints'));
    }

    /**
     * Affiche le formulaire de création d'une borne
     */
    public function create()
    {
        Gate::authorize('create_charging_points');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Récupérer les opérateurs de l'intégrateur
        $operators = User::where('integrator_id', $integrator->id)
            ->whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->with('partner.businessProfile')
            ->get();

        // Récupérer tous les business profiles actifs (créés par les admins)
        $businessProfiles = BusinessProfile::where('is_active', true)
            ->whereNull('integrator_id') // Business profiles créés par les admins
            ->get();

        // Récupérer les groupes de l'intégrateur
        $groups = Group::where('integrator_id', $integrator->id)
            ->where('is_active', true)
            ->get();

        // Récupérer les plans tarifaires
        $pricingPlans = PricingPlan::where('is_active', true)->get();

        return view('integrator.charging-points.create', compact(
            'operators', 
            'businessProfiles', 
            'groups', 
            'pricingPlans'
        ));
    }

    /**
     * Crée une nouvelle borne de recharge
     */
    public function store(Request $request)
    {
        Gate::authorize('create_charging_points');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'power_output' => 'required|numeric|min:0.1',
            'operator_id' => 'required|exists:users,id',
            'business_profile_id' => 'required|exists:business_profiles,id',
            'group_id' => 'nullable|exists:groups,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'status' => 'required|in:online,offline,maintenance',
            'description' => 'nullable|string|max:1000',
            'connectors' => 'required|array|min:1',
            'connectors.*.type' => 'required|string|in:Type1,Type2,CCS,CHAdeMO',
            'connectors.*.power' => 'required|numeric|min:0.1',
            'connectors.*.status' => 'required|in:available,occupied,out_of_order',
        ]);

        try {
            DB::beginTransaction();

            // Vérifier que l'opérateur appartient à l'intégrateur
            $operator = User::where('id', $request->operator_id)
                ->where('integrator_id', $integrator->id)
                ->whereHas('roles', function($query) {
                    $query->where('name', 'operator');
                })
                ->firstOrFail();

            // Vérifier que le business profile existe et est actif
            $businessProfile = BusinessProfile::where('id', $request->business_profile_id)
                ->where('is_active', true)
                ->firstOrFail();

            // Vérifier le groupe si fourni
            if ($request->group_id) {
                $group = Group::where('id', $request->group_id)
                    ->where('integrator_id', $integrator->id)
                    ->firstOrFail();
            }

            // Créer la borne de recharge
            $chargingPointData = [
                'name' => $request->name,
                'address' => $request->address,
                'city' => $request->city,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'power_output' => $request->power_output,
                'user_id' => $operator->id,
                'partner_id' => $operator->partner_id,
                'integrator_id' => $integrator->id,
                'business_profile_id' => $businessProfile->id,
                'group_id' => $request->group_id,
                'pricing_plan_id' => $request->pricing_plan_id,
                'status' => $request->status,
                'description' => $request->description,
            ];

            $chargingPoint = ChargingPoint::create($chargingPointData);

            // Créer les connecteurs
            foreach ($request->connectors as $connectorData) {
                $chargingPoint->connectors()->create([
                    'type' => $connectorData['type'],
                    'power' => $connectorData['power'],
                    'status' => $connectorData['status'],
                ]);
            }

            DB::commit();

            // Notifier les admins
            User::role('admin')->each(function($admin) use ($chargingPoint) {
                $admin->notify(new \App\Notifications\NewChargingPointNotification($chargingPoint));
            });

            return redirect()->route('integrator.charging-points.index')
                ->with('success', 'Borne de recharge créée avec succès pour l\'opérateur "' . $operator->name . '".');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création borne intégrateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la borne de recharge.')
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'une borne
     */
    public function show(ChargingPoint $chargingPoint)
    {
        Gate::authorize('view_charging_points');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator || $chargingPoint->integrator_id !== $integrator->id) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $chargingPoint->load(['user', 'partner', 'businessProfile', 'group', 'connectors', 'pricingPlan']);

        return view('integrator.charging-points.show', compact('chargingPoint'));
    }

    /**
     * Affiche le formulaire d'édition d'une borne
     */
    public function edit(ChargingPoint $chargingPoint)
    {
        Gate::authorize('edit_charging_points');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator || $chargingPoint->integrator_id !== $integrator->id) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Récupérer les opérateurs de l'intégrateur
        $operators = User::where('integrator_id', $integrator->id)
            ->whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->with('partner.businessProfile')
            ->get();

        // Récupérer tous les business profiles actifs (créés par les admins)
        $businessProfiles = BusinessProfile::where('is_active', true)
            ->whereNull('integrator_id') // Business profiles créés par les admins
            ->get();

        // Récupérer les groupes de l'intégrateur
        $groups = Group::where('integrator_id', $integrator->id)
            ->where('is_active', true)
            ->get();

        // Récupérer les plans tarifaires
        $pricingPlans = PricingPlan::where('is_active', true)->get();

        $chargingPoint->load(['connectors']);

        return view('integrator.charging-points.edit', compact(
            'chargingPoint',
            'operators', 
            'businessProfiles', 
            'groups', 
            'pricingPlans'
        ));
    }

    /**
     * Met à jour une borne de recharge
     */
    public function update(Request $request, ChargingPoint $chargingPoint)
    {
        Gate::authorize('edit_charging_points');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator || $chargingPoint->integrator_id !== $integrator->id) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'power_output' => 'required|numeric|min:0.1',
            'operator_id' => 'required|exists:users,id',
            'business_profile_id' => 'required|exists:business_profiles,id',
            'group_id' => 'nullable|exists:groups,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'status' => 'required|in:online,offline,maintenance',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Vérifier que l'opérateur appartient à l'intégrateur
            $operator = User::where('id', $request->operator_id)
                ->where('integrator_id', $integrator->id)
                ->whereHas('roles', function($query) {
                    $query->where('name', 'operator');
                })
                ->firstOrFail();

            // Vérifier que le business profile existe et est actif
            $businessProfile = BusinessProfile::where('id', $request->business_profile_id)
                ->where('is_active', true)
                ->firstOrFail();

            // Mettre à jour la borne
            $chargingPoint->update([
                'name' => $request->name,
                'address' => $request->address,
                'city' => $request->city,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'power_output' => $request->power_output,
                'user_id' => $operator->id,
                'partner_id' => $operator->partner_id,
                'business_profile_id' => $businessProfile->id,
                'group_id' => $request->group_id,
                'pricing_plan_id' => $request->pricing_plan_id,
                'status' => $request->status,
                'description' => $request->description,
            ]);

            DB::commit();

            return redirect()->route('integrator.charging-points.index')
                ->with('success', 'Borne de recharge mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour borne intégrateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la borne de recharge.')
                ->withInput();
        }
    }

    /**
     * Supprime une borne de recharge
     */
    public function destroy(ChargingPoint $chargingPoint)
    {
        Gate::authorize('delete_charging_points');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator || $chargingPoint->integrator_id !== $integrator->id) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        try {
            $chargingPoint->delete();
            
            return redirect()->route('integrator.charging-points.index')
                ->with('success', 'Borne de recharge supprimée avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur suppression borne intégrateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de la borne de recharge.');
        }
    }
}
