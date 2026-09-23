<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientUser;
use App\Models\Vehicle;
use App\Models\OcppTag;
use App\Models\OcppTagHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientUserController extends Controller
{
    /**
     * Display a listing of client users.
     */
    public function index(Request $request)
    {
        $query = ClientUser::query()->with(['user', 'vehicles', 'ocppTags']);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        $clients = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new client - Step 1 (Personal Info)
     */
    public function createStep1(Request $request)
    {
        // Get persisted data from step 1 if exists
        $step1Data = $request->session()->get('client_create_step1', []);

        return view('admin.clients.create-step1', compact('step1Data'));
    }

    /**
     * Store Step 1 - Personal Information
     */
    public function storeStep1(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:client_users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        // Generate temporary password if not provided
        if (empty($validated['password'])) {
            $validated['password'] = Str::random(12);
        }

        // Hash password
        $validated['password'] = Hash::make($validated['password']);

        // Persist step 1 data in session
        $request->session()->put('client_create_step1', $validated);

        return redirect()->route('admin.clients.create-step2')
            ->with('success', 'Informations personnelles enregistrées. Veuillez compléter les informations du véhicule.');
    }

    /**
     * Show the form for creating a new client - Step 2 (Vehicle Info)
     */
    public function createStep2(Request $request)
    {
        $step1Data = $request->session()->get('client_create_step1');

        if (!$step1Data) {
            return redirect()->route('admin.clients.create-step1')
                ->with('error', 'Veuillez d\'abord compléter les informations personnelles.');
        }

        $step2Data = $request->session()->get('client_create_step2', []);

        return view('admin.clients.create-step2', compact('step1Data', 'step2Data'));
    }

    /**
     * Store Step 2 - Vehicle Information and create client
     */
    public function storeStep2(Request $request)
    {
        $step1Data = $request->session()->get('client_create_step1');

        if (!$step1Data) {
            return redirect()->route('admin.clients.create-step1')
                ->with('error', 'Veuillez d\'abord compléter les informations personnelles.');
        }

        $validated = $request->validate([
            // Vehicle information
            'vehicle_make' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_registration' => 'required|string|max:50|unique:vehicles,registration',
            'vehicle_battery_capacity' => 'nullable|string|max:20',
            'vehicle_connector_type' => 'nullable|string|max:50',
            'vehicle_year' => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'vehicle_color' => 'nullable|string|max:50',
        ], [
            'vehicle_make.required' => 'La marque du véhicule est obligatoire.',
            'vehicle_model.required' => 'Le modèle du véhicule est obligatoire.',
            'vehicle_registration.required' => 'L\'immatriculation est obligatoire.',
            'vehicle_registration.unique' => 'Cette immatriculation existe déjà.',
        ]);

        try {
            // Get the authenticated user (admin)
            $adminUser = auth()->user();

            // Create the client user
            $clientUser = ClientUser::create([
                'name' => $step1Data['name'],
                'first_name' => $step1Data['first_name'] ?? null,
                'email' => $step1Data['email'],
                'password' => $step1Data['password'],
                'phone' => $step1Data['phone'] ?? null,
                'address' => $step1Data['address'] ?? null,
                'city' => $step1Data['city'] ?? null,
                'postal_code' => $step1Data['postal_code'] ?? null,
                'country' => $step1Data['country'] ?? null,
                'user_id' => $adminUser->id,
                'is_active' => true,
                'language' => 'fr',
                'currency' => 'EUR',
            ]);

            // Create the vehicle
            $vehicle = Vehicle::create([
                'client_user_id' => $clientUser->id,
                'make' => $validated['vehicle_make'],
                'model' => $validated['vehicle_model'],
                'registration' => strtoupper($validated['vehicle_registration']),
                'battery_capacity' => $validated['vehicle_battery_capacity'] ?? null,
                'connector_type' => $validated['vehicle_connector_type'] ?? null,
                'year' => $validated['vehicle_year'] ?? null,
                'color' => $validated['vehicle_color'] ?? null,
                'is_primary' => true,
                'is_active' => true,
            ]);

            // Generate temporary password for email
            $tempPassword = Str::random(10);

            // Send welcome email with temporary password
            try {
                Mail::send('emails.client-welcome', [
                    'client' => $clientUser,
                    'tempPassword' => $tempPassword,
                    'vehicle' => $vehicle,
                ], function ($message) use ($clientUser) {
                    $message->to($clientUser->email)
                        ->subject('Bienvenue - Création de votre compte');
                });
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email: ' . $e->getMessage());
            }

            // Clear session data
            $request->session()->forget(['client_create_step1', 'client_create_step2']);

            return redirect()->route('admin.clients.index')
                ->with('success', 'Client créé avec succès. Un email de bienvenue a été envoyé.');

        } catch (\Exception $e) {
            Log::error('Error creating client: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la création du client: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified client.
     */
    public function show(ClientUser $client)
    {
        $client->load(['user', 'vehicles', 'ocppTags', 'reservations', 'transactions']);

        return view('admin.clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(ClientUser $client)
    {
        $client->load(['vehicles', 'ocppTags']);

        return view('admin.clients.edit', compact('client'));
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, ClientUser $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'email' => ['required', 'email', Rule::unique('client_users')->ignore($client->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $client->update($validated);

        return redirect()->route('admin.clients.show', $client)
            ->with('success', 'Client mis à jour avec succès.');
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(ClientUser $client)
    {
        // Check if client has transactions
        if ($client->transactions()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer ce client car il a des transactions associées.');
        }

        $client->delete();

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client supprimé avec succès.');
    }

    /**
     * Verify client email
     */
    public function verifyEmail(ClientUser $client)
    {
        $client->markEmailAsVerified();

        return back()->with('success', 'Email vérifié avec succès.');
    }

    /**
     * Toggle client active status
     */
    public function toggleActive(Request $request, ClientUser $client)
    {
        $client->update(['is_active' => !$client->is_active]);

        $status = $client->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Compte {$status} avec succès.");
    }

    /**
     * Cancel the creation process and clear session
     */
    public function cancelCreate(Request $request)
    {
        $request->session()->forget(['client_create_step1', 'client_create_step2']);

        return redirect()->route('admin.clients.index');
    }
}
