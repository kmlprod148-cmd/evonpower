<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integrator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminIntegratorController extends Controller
{
    public function index(Request $request)
    {
        $query = Integrator::with(['user', 'partners', 'chargingPoints', 'wallet']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        $integrators = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'  => Integrator::count(),
            'active' => Integrator::where('is_active', true)->count(),
            'total_partners' => \App\Models\Partner::count(),
            'total_charging_points' => \App\Models\ChargingPoint::count(),
        ];

        $cities = Integrator::whereNotNull('city')->distinct()->pluck('city')->sort()->values();

        return view('admin.integrators.index', compact('integrators', 'stats', 'cities'));
    }

    public function create()
    {
        return view('admin.integrators.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'contact_name'   => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string|max:500',
            'city'           => 'nullable|string|max:100',
            'country'        => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'website'        => 'nullable|url|max:255',
            'description'    => 'nullable|string|max:2000',
            'collection_mode'=> 'nullable|in:admin,integrator,partner',
            'password'       => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // Create the user account
            $user = User::create([
                'name'     => $validated['contact_name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone'    => $validated['phone'] ?? null,
                'is_active'=> true,
            ]);

            // Create the integrator record
            $integrator = Integrator::create([
                'user_id'         => $user->id,
                'name'            => $validated['name'],
                'contact_name'    => $validated['contact_name'],
                'email'           => $validated['email'],
                'phone'           => $validated['phone'] ?? null,
                'address'         => $validated['address'] ?? null,
                'city'            => $validated['city'] ?? null,
                'country'         => $validated['country'] ?? null,
                'postal_code'     => $validated['postal_code'] ?? null,
                'website'         => $validated['website'] ?? null,
                'description'     => $validated['description'] ?? null,
                'collection_mode' => $validated['collection_mode'] ?? 'admin',
                'is_active'       => true,
                'created_by'      => auth()->id(),
            ]);

            // Link user to integrator
            $user->update(['integrator_id' => $integrator->id]);

            DB::commit();

            return redirect()
                ->route('admin.integrators.show', $integrator->id)
                ->with('success', "Intégrateur '{$integrator->name}' créé avec succès.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminIntegratorController@store: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function show(Integrator $integrator)
    {
        $integrator->load([
            'user',
            'partners.chargingPoints',
            'chargingPoints',
            'wallet',
        ]);

        $stats = [
            'partners_count'       => $integrator->partners()->count(),
            'active_partners'      => $integrator->partners()->where('is_active', true)->count(),
            'charging_points'      => $integrator->chargingPoints()->count(),
            'active_charging_points' => $integrator->chargingPoints()->where('is_active', true)->count(),
            'wallet_balance'       => optional($integrator->wallet)->balance ?? 0,
        ];

        return view('admin.integrators.show', compact('integrator', 'stats'));
    }

    public function edit(Integrator $integrator)
    {
        return view('admin.integrators.edit', compact('integrator'));
    }

    public function update(Request $request, Integrator $integrator)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'contact_name'    => 'required|string|max:255',
            'email'           => 'required|email',
            'phone'           => 'nullable|string|max:30',
            'address'         => 'nullable|string|max:500',
            'city'            => 'nullable|string|max:100',
            'country'         => 'nullable|string|max:100',
            'postal_code'     => 'nullable|string|max:20',
            'website'         => 'nullable|url|max:255',
            'description'     => 'nullable|string|max:2000',
            'collection_mode' => 'nullable|in:admin,integrator,partner',
            'is_active'       => 'boolean',
        ]);

        $integrator->update($validated);

        return redirect()
            ->route('admin.integrators.show', $integrator->id)
            ->with('success', "Intégrateur '{$integrator->name}' mis à jour avec succès.");
    }

    public function destroy(Integrator $integrator)
    {
        $name = $integrator->name;
        $integrator->delete();

        return redirect()
            ->route('admin.integrators.index')
            ->with('success', "Intégrateur '{$name}' supprimé.");
    }

    public function toggleActive(Integrator $integrator)
    {
        $integrator->update(['is_active' => !$integrator->is_active]);
        $status = $integrator->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Intégrateur '{$integrator->name}' {$status}.");
    }
}
