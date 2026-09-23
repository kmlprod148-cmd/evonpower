<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\PricingPlan;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = Group::with(['partner', 'integrator', 'chargingPoints', 'pricingPlans']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('integrator_id')) {
            $query->where('integrator_id', $request->integrator_id);
        }

        $groups = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'   => Group::count(),
            'private' => Group::where('type', 'private')->count(),
            'business'=> Group::where('type', 'business')->count(),
            'total_points' => ChargingPoint::whereNotNull('group_id')->count(),
        ];

        $partners    = Partner::orderBy('name')->get();
        $integrators = Integrator::orderBy('name')->get();

        return view('admin.groups.index', compact('groups', 'stats', 'partners', 'integrators'));
    }

    public function create()
    {
        $partners     = Partner::active()->orderBy('name')->get();
        $integrators  = Integrator::active()->orderBy('name')->get();
        
        // Filter pricing plans based on current user's role
        $currentUser = auth()->user();
        $userRoles = $currentUser->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isPartner = in_array('partner', $userRoles) && $currentUser->partner_id;
        $isAdmin = in_array('admin', $userRoles) || in_array('super_admin', $userRoles);
        
        $pricingPlansQuery = PricingPlan::where('is_active', true)->orderBy('name');
        
        if ($isPartner) {
            // Partners only see their own pricing plans
            $pricingPlansQuery->where(function($q) use ($currentUser) {
                // Plans created by the partner user
                $q->where('created_by', $currentUser->id)
                  // Or plans assigned to their partner
                  ->orWhereHas('partners', function($subQ) use ($currentUser) {
                      $subQ->where('partner_id', $currentUser->partner_id);
                  })
                  // Or public plans assigned to their partner
                  ->orWhere(function($subQ) use ($currentUser) {
                      $subQ->where('is_public', true)
                           ->whereHas('partners', function($subSubQ) use ($currentUser) {
                               $subSubQ->where('partner_id', $currentUser->partner_id);
                           });
                  });
            });
        }
        // Admins see all active pricing plans
        
        $pricingPlans = $pricingPlansQuery->get();
        
        // Auto-assign partner from current user's profile
        $userPartner = $currentUser->partner_id ? $currentUser->partner_id : ($currentUser->partner ? $currentUser->partner->id : null);

        return view('admin.groups.create', compact('partners', 'integrators', 'pricingPlans', 'userPartner'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:private,business',
            'consumption_mode'=> 'nullable|in:prepaid,postpaid',
            'city'            => 'nullable|string|max:100',
            'description'     => 'nullable|string|max:2000',
            'partner_id'      => 'nullable|exists:partners,id',
            'integrator_id'   => 'nullable|exists:integrators,id',
            'pricing_plan_ids'=> 'nullable|array',
            'pricing_plan_ids.*' => 'exists:pricing_plans,id',
        ]);

        try {
            $currentUser = auth()->user();
            
            // If user is a partner, use their partner_id; otherwise use the provided one or null
            $partnerId = null;
            if ($currentUser->partner_id) {
                $partnerId = $currentUser->partner_id;
            } elseif ($currentUser->partner) {
                $partnerId = $currentUser->partner->id;
            } else {
                // For admins, use the selected partner
                $partnerId = $validated['partner_id'] ?? null;
            }
            
            $group = Group::create([
                'name'             => $validated['name'],
                'type'             => $validated['type'],
                'consumption_mode' => $validated['consumption_mode'] ?? 'prepaid',
                'city'             => $validated['city'] ?? null,
                'description'      => $validated['description'] ?? null,
                'partner_id'       => $partnerId,
                'integrator_id'    => $validated['integrator_id'] ?? null,
                'user_id'          => auth()->id(),
                'created_by'       => auth()->id(),
            ]);

            if (!empty($validated['pricing_plan_ids'])) {
                $group->pricingPlans()->sync($validated['pricing_plan_ids']);
            }

            return redirect()
                ->route('admin.groups.show', $group->id)
                ->with('success', "Groupe '{$group->name}' créé avec succès.");
        } catch (\Exception $e) {
            Log::error('AdminGroupController@store: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function show(Group $group)
    {
        $group->load([
            'partner',
            'integrator',
            'chargingPoints',
            'pricingPlans',
        ]);

        $stats = [
            'charging_points'  => $group->chargingPoints()->count(),
            'active_points'    => $group->chargingPoints()->where('is_active', true)->count(),
            'pricing_plans'    => $group->pricingPlans()->count(),
        ];

        return view('admin.groups.show', compact('group', 'stats'));
    }

    public function edit(Group $group)
    {
        $group->load('pricingPlans');
        $partners     = Partner::orderBy('name')->get();
        $integrators  = Integrator::orderBy('name')->get();
        
        // Filter pricing plans based on current user's role
        $currentUser = auth()->user();
        $userRoles = $currentUser->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isPartner = in_array('partner', $userRoles) && $currentUser->partner_id;
        $isAdmin = in_array('admin', $userRoles) || in_array('super_admin', $userRoles);
        
        $pricingPlansQuery = PricingPlan::where('is_active', true)->orderBy('name');
        
        if ($isPartner) {
            // Partners only see their own pricing plans
            $pricingPlansQuery->where(function($q) use ($currentUser) {
                // Plans created by the partner user
                $q->where('created_by', $currentUser->id)
                  // Or plans assigned to their partner
                  ->orWhereHas('partners', function($subQ) use ($currentUser) {
                      $subQ->where('partner_id', $currentUser->partner_id);
                  })
                  // Or public plans assigned to their partner
                  ->orWhere(function($subQ) use ($currentUser) {
                      $subQ->where('is_public', true)
                           ->whereHas('partners', function($subSubQ) use ($currentUser) {
                               $subSubQ->where('partner_id', $currentUser->partner_id);
                           });
                  });
            });
        }
        // Admins see all active pricing plans
        
        $pricingPlans = $pricingPlansQuery->get();
        $selectedPlans = $group->pricingPlans->pluck('id')->toArray();
        
        // Auto-assign partner from current user's profile
        $userPartner = $currentUser->partner_id ? $currentUser->partner_id : ($currentUser->partner ? $currentUser->partner->id : null);

        return view('admin.groups.edit', compact('group', 'partners', 'integrators', 'pricingPlans', 'selectedPlans', 'userPartner'));
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:private,business',
            'consumption_mode'=> 'nullable|in:prepaid,postpaid',
            'city'            => 'nullable|string|max:100',
            'description'     => 'nullable|string|max:2000',
            'partner_id'      => 'nullable|exists:partners,id',
            'integrator_id'   => 'nullable|exists:integrators,id',
            'pricing_plan_ids'=> 'nullable|array',
            'pricing_plan_ids.*' => 'exists:pricing_plans,id',
        ]);

        $currentUser = auth()->user();
        
        // If user is a partner, use their partner_id; otherwise use the provided one or existing one
        $partnerId = $group->partner_id; // Keep existing partner by default
        if ($currentUser->partner_id) {
            $partnerId = $currentUser->partner_id;
        } elseif ($currentUser->partner) {
            $partnerId = $currentUser->partner->id;
        } elseif (!$currentUser->hasRole('admin')) {
            // For non-admins non-partners, keep the existing partner
            $partnerId = $group->partner_id;
        } else {
            // For admins, use the selected partner or keep existing
            $partnerId = $validated['partner_id'] ?? $group->partner_id;
        }
        
        $group->update([
            'name'             => $validated['name'],
            'type'             => $validated['type'],
            'consumption_mode' => $validated['consumption_mode'] ?? $group->consumption_mode,
            'city'             => $validated['city'] ?? null,
            'description'      => $validated['description'] ?? null,
            'partner_id'       => $partnerId,
            'integrator_id'    => $validated['integrator_id'] ?? null,
        ]);

        $group->pricingPlans()->sync($validated['pricing_plan_ids'] ?? []);

        return redirect()
            ->route('admin.groups.show', $group->id)
            ->with('success', "Groupe '{$group->name}' mis à jour.");
    }

    public function destroy(Group $group)
    {
        $name = $group->name;
        $group->pricingPlans()->detach();
        $group->delete();

        return redirect()
            ->route('admin.groups.index')
            ->with('success', "Groupe '{$name}' supprimé.");
    }
}
