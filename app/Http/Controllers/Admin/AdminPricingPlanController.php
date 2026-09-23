<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use App\Models\VatRate;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminPricingPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = PricingPlan::with(['vatRate', 'groups', 'chargingPoints']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->filled('rate_type')) {
            $query->where('rate_type', $request->rate_type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        $plans = $query->orderBy('priority')->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total'    => PricingPlan::count(),
            'active'   => PricingPlan::where('is_active', true)->count(),
            'by_minute'=> PricingPlan::whereIn('rate_type', ['time', 'minute'])->count(),
            'by_kwh'   => PricingPlan::whereIn('rate_type', ['energy', 'kwh'])->count(),
        ];

        return view('admin.pricing-plans.index', compact('plans', 'stats'));
    }

    public function create()
    {
        $vatRates = VatRate::getActiveRates();
        $groups   = Group::orderBy('name')->get();

        return view('admin.pricing-plans.create', compact('vatRates', 'groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'rate_type'        => 'required|in:time,minute,energy,kwh,fixed',
            'price_per_minute' => 'nullable|numeric|min:0',
            'price_per_kwh'    => 'nullable|numeric|min:0',
            'fixed_price'      => 'nullable|numeric|min:0',
            'activation_fee'   => 'nullable|numeric|min:0',
            'vat_rate_id'      => 'nullable|exists:vat_rates,id',
            'priority'         => 'integer|min:0|max:100',
            'max_duration'     => 'nullable|integer|min:0',
            'currency'         => 'nullable|string|max:10',
            'is_active'        => 'boolean',
            'is_public'        => 'boolean',
            'valid_from'       => 'nullable|date',
            'valid_until'      => 'nullable|date|after_or_equal:valid_from',
            'group_ids'        => 'nullable|array',
            'group_ids.*'      => 'exists:groups,id',
        ]);

        try {
            $plan = PricingPlan::create([
                'name'             => $validated['name'],
                'description'      => $validated['description'] ?? null,
                'rate_type'        => $validated['rate_type'],
                'price_per_minute' => $validated['price_per_minute'] ?? null,
                'price_per_kwh'    => $validated['price_per_kwh'] ?? null,
                'fixed_price'      => $validated['fixed_price'] ?? null,
                'activation_fee'   => $validated['activation_fee'] ?? null,
                'vat_rate_id'      => $validated['vat_rate_id'] ?? null,
                'priority'         => $validated['priority'] ?? 0,
                'max_duration'     => $validated['max_duration'] ?? null,
                'currency'         => $validated['currency'] ?? 'MAD',
                'is_active'        => $request->boolean('is_active', true),
                'is_public'        => $request->boolean('is_public', false),
                'valid_from'       => $validated['valid_from'] ?? null,
                'valid_until'      => $validated['valid_until'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            if (!empty($validated['group_ids'])) {
                $plan->groups()->sync($validated['group_ids']);
            }

            return redirect()
                ->route('admin.pricing-plans.show', $plan->id)
                ->with('success', "Plan tarifaire '{$plan->name}' créé avec succès.");
        } catch (\Exception $e) {
            Log::error('AdminPricingPlanController@store: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function show(PricingPlan $pricingPlan)
    {
        $pricingPlan->load(['vatRate', 'groups.partner', 'chargingPoints', 'additionalRates']);

        return view('admin.pricing-plans.show', compact('pricingPlan'));
    }

    public function edit(PricingPlan $pricingPlan)
    {
        $pricingPlan->load('groups');
        $vatRates    = VatRate::getActiveRates();
        $groups      = Group::orderBy('name')->get();
        $selectedGroups = $pricingPlan->groups->pluck('id')->toArray();

        return view('admin.pricing-plans.edit', compact('pricingPlan', 'vatRates', 'groups', 'selectedGroups'));
    }

    public function update(Request $request, PricingPlan $pricingPlan)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'rate_type'        => 'required|in:time,minute,energy,kwh,fixed',
            'price_per_minute' => 'nullable|numeric|min:0',
            'price_per_kwh'    => 'nullable|numeric|min:0',
            'fixed_price'      => 'nullable|numeric|min:0',
            'activation_fee'   => 'nullable|numeric|min:0',
            'vat_rate_id'      => 'nullable|exists:vat_rates,id',
            'priority'         => 'integer|min:0|max:100',
            'max_duration'     => 'nullable|integer|min:0',
            'currency'         => 'nullable|string|max:10',
            'is_active'        => 'boolean',
            'is_public'        => 'boolean',
            'valid_from'       => 'nullable|date',
            'valid_until'      => 'nullable|date|after_or_equal:valid_from',
            'group_ids'        => 'nullable|array',
            'group_ids.*'      => 'exists:groups,id',
        ]);

        $pricingPlan->update([
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'rate_type'        => $validated['rate_type'],
            'price_per_minute' => $validated['price_per_minute'] ?? null,
            'price_per_kwh'    => $validated['price_per_kwh'] ?? null,
            'fixed_price'      => $validated['fixed_price'] ?? null,
            'activation_fee'   => $validated['activation_fee'] ?? null,
            'vat_rate_id'      => $validated['vat_rate_id'] ?? null,
            'priority'         => $validated['priority'] ?? 0,
            'max_duration'     => $validated['max_duration'] ?? null,
            'currency'         => $validated['currency'] ?? $pricingPlan->currency,
            'is_active'        => $request->boolean('is_active'),
            'is_public'        => $request->boolean('is_public'),
            'valid_from'       => $validated['valid_from'] ?? null,
            'valid_until'      => $validated['valid_until'] ?? null,
        ]);

        $pricingPlan->groups()->sync($validated['group_ids'] ?? []);

        return redirect()
            ->route('admin.pricing-plans.show', $pricingPlan->id)
            ->with('success', "Plan tarifaire '{$pricingPlan->name}' mis à jour.");
    }

    public function destroy(PricingPlan $pricingPlan)
    {
        $name = $pricingPlan->name;
        $pricingPlan->groups()->detach();
        $pricingPlan->delete();

        return redirect()
            ->route('admin.pricing-plans.index')
            ->with('success', "Plan tarifaire '{$name}' supprimé.");
    }

    public function toggleActive(PricingPlan $pricingPlan)
    {
        $pricingPlan->update(['is_active' => !$pricingPlan->is_active]);
        $status = $pricingPlan->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Plan '{$pricingPlan->name}' {$status}.");
    }
}
