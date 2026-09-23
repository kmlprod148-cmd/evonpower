<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = Partner::with(['integrator', 'chargingPoints', 'groups', 'wallet']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('contact_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('integrator_id')) {
            $query->where('integrator_id', $request->integrator_id);
        }

        $partners = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'   => Partner::count(),
            'active'  => Partner::where('is_active', true)->count(),
            'with_integrator' => Partner::whereNotNull('integrator_id')->count(),
            'standalone'      => Partner::whereNull('integrator_id')->count(),
        ];

        $integrators = Integrator::active()->orderBy('name')->get();

        return view('admin.partners.index', compact('partners', 'stats', 'integrators'));
    }

    public function create()
    {
        $integrators = Integrator::active()->orderBy('name')->get();
        return view('admin.partners.create', compact('integrators'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'contact_name'    => 'required|string|max:255',
            'type'            => 'required|in:partner,proprietaire,exploitant',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'nullable|string|max:30',
            'address'         => 'nullable|string|max:500',
            'city'            => 'nullable|string|max:100',
            'country'         => 'nullable|string|max:100',
            'postal_code'     => 'nullable|string|max:20',
            'integrator_id'   => 'nullable|exists:integrators,id',
            'collection_mode' => 'nullable|in:admin,integrator,partner',
            'currency'        => 'nullable|string|max:10',
            'description'     => 'nullable|string|max:2000',
            'password'        => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'     => $validated['contact_name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone'    => $validated['phone'] ?? null,
                'is_active'=> true,
            ]);

            $partner = Partner::create([
                'name'            => $validated['name'],
                'contact_name'    => $validated['contact_name'],
                'type'            => $validated['type'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'] ?? null,
                'address'         => $validated['address'] ?? null,
                'city'            => $validated['city'] ?? null,
                'country'         => $validated['country'] ?? null,
                'postal_code'     => $validated['postal_code'] ?? null,
                'integrator_id'   => $validated['integrator_id'] ?? null,
                'collection_mode' => $validated['collection_mode'] ?? 'admin',
                'currency'        => $validated['currency'] ?? 'MAD',
                'description'     => $validated['description'] ?? null,
                'is_active'       => true,
                'created_by'      => auth()->id(),
            ]);

            $user->assignRole('partner');
            $user->update([
                'partner_id'     => $partner->id,
                'integrator_id'  => $partner->integrator_id,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.partners.show', $partner->id)
                ->with('success', "Partenaire '{$partner->name}' créé avec succès.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminPartnerController@store: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function show(Partner $partner)
    {
        $partner->load(['integrator', 'groups.chargingPoints', 'chargingPoints', 'wallet']);

        $stats = [
            'groups_count'       => $partner->groups()->count(),
            'charging_points'    => $partner->chargingPoints()->count(),
            'active_points'      => $partner->chargingPoints()->where('is_active', true)->count(),
            'wallet_balance'     => optional($partner->wallet)->balance ?? 0,
        ];

        return view('admin.partners.show', compact('partner', 'stats'));
    }

    public function edit(Partner $partner)
    {
        $integrators = Integrator::orderBy('name')->get();
        return view('admin.partners.edit', compact('partner', 'integrators'));
    }

    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'contact_name'    => 'required|string|max:255',
            'type'            => 'required|in:partner,proprietaire,exploitant',
            'email'           => 'required|email',
            'phone'           => 'nullable|string|max:30',
            'address'         => 'nullable|string|max:500',
            'city'            => 'nullable|string|max:100',
            'country'         => 'nullable|string|max:100',
            'postal_code'     => 'nullable|string|max:20',
            'integrator_id'   => 'nullable|exists:integrators,id',
            'collection_mode' => 'nullable|in:admin,integrator,partner',
            'description'     => 'nullable|string|max:2000',
            'is_active'       => 'boolean',
        ]);

        $partner->update($validated);

        return redirect()
            ->route('admin.partners.show', $partner->id)
            ->with('success', "Partenaire '{$partner->name}' mis à jour.");
    }

    public function destroy(Partner $partner)
    {
        $name = $partner->name;
        $partner->delete();

        return redirect()
            ->route('admin.partners.index')
            ->with('success', "Partenaire '{$name}' supprimé.");
    }

    public function toggleActive(Partner $partner)
    {
        $partner->update(['is_active' => !$partner->is_active]);
        $status = $partner->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Partenaire '{$partner->name}' {$status}.");
    }
}
