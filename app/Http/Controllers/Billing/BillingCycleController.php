<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BillingCycleController extends Controller
{
    /**
     * Afficher la liste des cycles de facturation
     */
    public function index(): View
    {
        $cycles = BillingCycle::with(['creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('billing.cycles.index', compact('cycles'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(): View
    {
        return view('billing.cycles.create');
    }

    /**
     * Créer un nouveau cycle de facturation
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval' => 'required|integer|min:1',
            'is_active' => 'boolean'
        ]);

        $cycle = BillingCycle::create([
            'name' => $request->name,
            'description' => $request->description,
            'frequency' => $request->frequency,
            'interval' => $request->interval,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cycle de facturation créé avec succès',
            'data' => $cycle
        ]);
    }

    /**
     * Afficher un cycle de facturation
     */
    public function show(BillingCycle $billingCycle): View
    {
        $billingCycle->load(['creator', 'updater', 'billingPlans', 'invoices']);
        
        return view('billing.cycles.show', compact('billingCycle'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(BillingCycle $billingCycle): View
    {
        return view('billing.cycles.edit', compact('billingCycle'));
    }

    /**
     * Mettre à jour un cycle de facturation
     */
    public function update(Request $request, BillingCycle $billingCycle): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval' => 'required|integer|min:1',
            'is_active' => 'boolean'
        ]);

        $billingCycle->update([
            'name' => $request->name,
            'description' => $request->description,
            'frequency' => $request->frequency,
            'interval' => $request->interval,
            'is_active' => $request->boolean('is_active'),
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cycle de facturation mis à jour avec succès',
            'data' => $billingCycle
        ]);
    }

    /**
     * Supprimer un cycle de facturation
     */
    public function destroy(BillingCycle $billingCycle): JsonResponse
    {
        // Vérifier s'il y a des plans ou factures associés
        if ($billingCycle->billingPlans()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce cycle car il est utilisé par des plans de facturation'
            ], 422);
        }

        if ($billingCycle->invoices()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce cycle car il est utilisé par des factures'
            ], 422);
        }

        $billingCycle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cycle de facturation supprimé avec succès'
        ]);
    }

    /**
     * Activer/Désactiver un cycle
     */
    public function toggle(BillingCycle $billingCycle): JsonResponse
    {
        $billingCycle->update([
            'is_active' => !$billingCycle->is_active,
            'updated_by' => auth()->id()
        ]);

        $status = $billingCycle->is_active ? 'activé' : 'désactivé';

        return response()->json([
            'success' => true,
            'message' => "Cycle de facturation {$status} avec succès",
            'data' => $billingCycle
        ]);
    }
}
