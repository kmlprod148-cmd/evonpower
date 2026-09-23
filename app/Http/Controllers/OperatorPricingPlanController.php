<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperatorPricingPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:operator|partner');
    }

    /**
     * Display a listing of the operator's pricing plans
     */
    public function index()
    {
        $operator = Auth::user();
        
        // Get pricing plans created by this operator
        $pricingPlans = PricingPlan::where('created_by', $operator->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('operator.pricing-plans.index', compact('pricingPlans'));
    }

    /**
     * Show the form for creating a new pricing plan
     */
    public function create()
    {
        return view('operator.pricing-plans.create');
    }

    /**
     * Store a newly created pricing plan
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'price_per_kwh' => 'required|numeric|min:0',
            'price_per_minute' => 'nullable|numeric|min:0',
            'connection_fee' => 'nullable|numeric|min:0',
            'minimum_fee' => 'nullable|numeric|min:0',
            'maximum_fee' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $operator = Auth::user();
            
            // Create the pricing plan
            $pricingPlan = PricingPlan::create([
                'name' => $request->name,
                'description' => $request->description,
                'base_price' => $request->base_price,
                'price_per_kwh' => $request->price_per_kwh,
                'price_per_minute' => $request->price_per_minute,
                'connection_fee' => $request->connection_fee,
                'minimum_fee' => $request->minimum_fee,
                'maximum_fee' => $request->maximum_fee,
                'created_by' => $operator->id,
                'is_active' => $request->boolean('is_active', true),
                'is_public' => $request->boolean('is_public', false),
            ]);

            Log::info('Operator created pricing plan', [
                'operator_id' => $operator->id,
                'pricing_plan_id' => $pricingPlan->id,
                'pricing_plan_name' => $pricingPlan->name
            ]);

            DB::commit();

            return redirect()
                ->route('operator.pricing-plans.show', $pricingPlan)
                ->with('success', 'Plan de tarification créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating pricing plan', [
                'operator_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la création du plan de tarification: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified pricing plan
     */
    public function show(PricingPlan $pricingPlan)
    {
        // Check if operator can view this pricing plan
        if ($pricingPlan->created_by !== Auth::id()) {
            return redirect()->route('operator.pricing-plans.index')
                ->with('error', 'Vous n\'avez pas accès à ce plan de tarification.');
        }

        // Get charging points using this pricing plan
        $chargingPoints = ChargingPoint::where('pricing_plan_id', $pricingPlan->id)
            ->where('user_id', Auth::id())
            ->with(['station', 'group'])
            ->get();

        return view('operator.pricing-plans.show', compact('pricingPlan', 'chargingPoints'));
    }

    /**
     * Show the form for editing the specified pricing plan
     */
    public function edit(PricingPlan $pricingPlan)
    {
        // Check if operator can edit this pricing plan
        if ($pricingPlan->created_by !== Auth::id()) {
            return redirect()->route('operator.pricing-plans.index')
                ->with('error', 'Vous n\'avez pas la permission de modifier ce plan de tarification.');
        }

        return view('operator.pricing-plans.edit', compact('pricingPlan'));
    }

    /**
     * Update the specified pricing plan
     */
    public function update(Request $request, PricingPlan $pricingPlan)
    {
        // Check if operator can update this pricing plan
        if ($pricingPlan->created_by !== Auth::id()) {
            return redirect()->route('operator.pricing-plans.index')
                ->with('error', 'Vous n\'avez pas la permission de modifier ce plan de tarification.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'price_per_kwh' => 'required|numeric|min:0',
            'price_per_minute' => 'nullable|numeric|min:0',
            'connection_fee' => 'nullable|numeric|min:0',
            'minimum_fee' => 'nullable|numeric|min:0',
            'maximum_fee' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ]);

        try {
            $pricingPlan->update([
                'name' => $request->name,
                'description' => $request->description,
                'base_price' => $request->base_price,
                'price_per_kwh' => $request->price_per_kwh,
                'price_per_minute' => $request->price_per_minute,
                'connection_fee' => $request->connection_fee,
                'minimum_fee' => $request->minimum_fee,
                'maximum_fee' => $request->maximum_fee,
                'is_active' => $request->boolean('is_active'),
                'is_public' => $request->boolean('is_public'),
            ]);

            Log::info('Operator updated pricing plan', [
                'operator_id' => Auth::id(),
                'pricing_plan_id' => $pricingPlan->id,
                'pricing_plan_name' => $pricingPlan->name
            ]);

            return redirect()
                ->route('operator.pricing-plans.show', $pricingPlan)
                ->with('success', 'Plan de tarification mis à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Error updating pricing plan', [
                'operator_id' => Auth::id(),
                'pricing_plan_id' => $pricingPlan->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour du plan de tarification: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified pricing plan
     */
    public function destroy(PricingPlan $pricingPlan)
    {
        // Check if operator can delete this pricing plan
        if ($pricingPlan->created_by !== Auth::id()) {
            return redirect()->route('operator.pricing-plans.index')
                ->with('error', 'Vous n\'avez pas la permission de supprimer ce plan de tarification.');
        }

        // Check if pricing plan is being used by charging points
        $chargingPointsCount = ChargingPoint::where('pricing_plan_id', $pricingPlan->id)
            ->where('user_id', Auth::id())
            ->count();

        if ($chargingPointsCount > 0) {
            return redirect()
                ->back()
                ->with('error', 'Ce plan de tarification est utilisé par ' . $chargingPointsCount . ' charging point(s). Vous ne pouvez pas le supprimer.');
        }

        try {
            $pricingPlanName = $pricingPlan->name;
            $pricingPlan->delete();

            Log::info('Operator deleted pricing plan', [
                'operator_id' => Auth::id(),
                'pricing_plan_name' => $pricingPlanName
            ]);

            return redirect()
                ->route('operator.pricing-plans.index')
                ->with('success', 'Plan de tarification supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error deleting pricing plan', [
                'operator_id' => Auth::id(),
                'pricing_plan_id' => $pricingPlan->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du plan de tarification: ' . $e->getMessage());
        }
    }

    /**
     * Toggle pricing plan status
     */
    public function toggleStatus(PricingPlan $pricingPlan)
    {
        // Check if operator can manage this pricing plan
        if ($pricingPlan->created_by !== Auth::id()) {
            return redirect()->route('operator.pricing-plans.index')
                ->with('error', 'Vous n\'avez pas la permission de gérer ce plan de tarification.');
        }

        try {
            $pricingPlan->is_active = !$pricingPlan->is_active;
            $pricingPlan->save();

            $status = $pricingPlan->is_active ? 'activé' : 'désactivé';
            
            Log::info('Operator toggled pricing plan status', [
                'operator_id' => Auth::id(),
                'pricing_plan_id' => $pricingPlan->id,
                'new_status' => $pricingPlan->is_active
            ]);

            return redirect()
                ->back()
                ->with('success', "Plan de tarification {$status} avec succès.");

        } catch (\Exception $e) {
            Log::error('Error toggling pricing plan status', [
                'operator_id' => Auth::id(),
                'pricing_plan_id' => $pricingPlan->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
        }
    }

    /**
     * Get pricing plans for AJAX requests
     */
    public function getPricingPlans()
    {
        $operator = Auth::user();
        
        $pricingPlans = PricingPlan::where('created_by', $operator->id)
            ->where('is_active', true)
            ->select('id', 'name', 'base_rate as base_price', 'price_per_kwh')
            ->get();

        return response()->json($pricingPlans);
    }
}
