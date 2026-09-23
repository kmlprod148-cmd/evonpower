<?php

namespace App\Http\Controllers\Public;

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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClientRegistrationController extends Controller
{
    /**
     * Show the registration form
     */
    public function showRegistrationForm(Request $request)
    {
        // Persist charging point context arriving via query string into the session
        if ($request->has('cp')) {
            session(['pending_charging_point_id' => $request->query('cp')]);
        }

        return view('public.register', [
            'pending_charging_point_id' => session('pending_charging_point_id'),
        ]);
    }

    /**
     * Handle the public registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Personal Information
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:client_users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'password' => 'required|string|min:8|confirmed',
            
            // Vehicle Information (optional)
            'vehicle_make' => 'nullable|string|max:100',
            'vehicle_model' => 'nullable|string|max:100',
            'vehicle_registration' => 'nullable|string|max:50|unique:vehicles,registration',
            'vehicle_battery_capacity' => 'nullable|string|max:20',
            'vehicle_connector_type' => 'nullable|string|max:50',
            'vehicle_year' => 'nullable|integer|min:2000|max:' . (date('Y') + 1),
            'vehicle_color' => 'nullable|string|max:50',
            
            // Existing OCPP Tag (optional)
            'existing_ocpp_tag' => 'nullable|string|max:50',
        ], [
            // Custom French messages
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'vehicle_registration.unique' => 'Cette immatriculation existe déjà.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        try {
            // Create the client user
            $clientUser = ClientUser::create([
                'name' => $validated['name'],
                'first_name' => $validated['first_name'] ?? null,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'] ?? 'France',
                'is_active' => true,
                'language' => 'fr',
                'currency' => 'EUR',
                // No user_id - this is self-registration
            ]);

            // Create vehicle if provided
            if (!empty($validated['vehicle_make']) && !empty($validated['vehicle_model'])) {
                Vehicle::create([
                    'client_user_id' => $clientUser->id,
                    'make' => $validated['vehicle_make'],
                    'model' => $validated['vehicle_model'],
                    'registration' => strtoupper($validated['vehicle_registration'] ?? ''),
                    'battery_capacity' => $validated['vehicle_battery_capacity'] ?? null,
                    'connector_type' => $validated['vehicle_connector_type'] ?? null,
                    'year' => $validated['vehicle_year'] ?? null,
                    'color' => $validated['vehicle_color'] ?? null,
                    'is_primary' => true,
                    'is_active' => true,
                ]);
            }

            // Link existing OCPP tag if provided
            if (!empty($validated['existing_ocpp_tag'])) {
                $existingTag = OcppTag::where('ocpp_tag', $validated['existing_ocpp_tag'])->first();
                
                if ($existingTag && !$existingTag->user_id) {
                    // Tag exists and is not linked - link it
                    $existingTag->update(['user_id' => $clientUser->id]);
                    
                    // Record history
                    OcppTagHistory::record(
                        $existingTag,
                        $clientUser,
                        'associated',
                        null,
                        'active',
                        'Association via inscription publique'
                    );
                } elseif ($existingTag && $existingTag->user_id) {
                    // Tag is already linked to another user
                    Log::warning('OCPP tag already linked during registration', [
                        'tag' => $validated['existing_ocpp_tag'],
                        'existing_user_id' => $existingTag->user_id,
                    ]);
                }
            }

            // Create wallet for the client
            $clientUser->getOrCreateWallet();

            // Send confirmation email
            try {
                Mail::send('emails.client-registration-confirmation', [
                    'client' => $clientUser,
                ], function ($message) use ($clientUser) {
                    $message->to($clientUser->email)
                        ->subject('Confirmation de votre inscription - EVON');
                });
            } catch (\Exception $e) {
                Log::error('Failed to send registration confirmation email: ' . $e->getMessage());
            }

            // Auto-login the newly registered client via the 'client' guard
            auth('client')->login($clientUser);

            // If the client came from a checkout page, send them back so payment auto-starts
            if ($checkoutReturnUrl = session()->pull('checkout.return_url')) {
                // checkout.data stays in session for userInfo() to read
                session()->forget(['checkout.slug']);
                return redirect($checkoutReturnUrl)
                    ->with('success', __('Account created! Proceeding to payment.'));
            }

            // If the user arrived from a charging offer page, redirect them back
            $pendingChargingPointId = session()->pull('pending_charging_point_id');
            if ($pendingChargingPointId) {
                return redirect()->route('client.charging.offer', ['id' => $pendingChargingPointId])
                    ->with('success', 'Compte créé avec succès ! Complétez votre réservation ci-dessous.');
            }

            return redirect()->route('dashboard.client')
                ->with('success', 'Votre compte a été créé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error during client registration: ' . $e->getMessage());
            return back()
                ->with('error', 'Erreur lors de l\'inscription: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Verify email and activate account
     */
    public function verifyEmail(Request $request, $token)
    {
        $client = ClientUser::where('verification_token', $token)->first();

        if (!$client) {
            return redirect()->route('login')
                ->with('error', 'Lien de vérification invalide.');
        }

        $client->markEmailAsVerified();
        $client->verification_token = null;
        $client->save();

        return redirect()->route('login')
            ->with('success', 'Votre email a été vérifié. Vous pouvez maintenant vous connecter.');
    }

    /**
     * Check if email is available (AJAX)
     */
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        
        if (ClientUser::where('email', $email)->exists()) {
            return response()->json([
                'available' => false,
                'message' => 'Cette adresse email est déjà utilisée.'
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Adresse email disponible.'
        ]);
    }

    /**
     * Check if vehicle registration is available (AJAX)
     */
    public function checkVehicleRegistration(Request $request)
    {
        $registration = $request->input('registration');
        
        if (Vehicle::where('registration', strtoupper($registration))->exists()) {
            return response()->json([
                'available' => false,
                'message' => 'Cette immatriculation existe déjà.'
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Immatriculation disponible.'
        ]);
    }

    /**
     * Check if OCPP tag exists and is available (AJAX)
     */
    public function checkOcppTag(Request $request)
    {
        $tag = $request->input('ocpp_tag');
        
        $existingTag = OcppTag::where('ocpp_tag', $tag)->first();

        if (!$existingTag) {
            return response()->json([
                'available' => true,
                'exists' => false,
                'message' => 'Ce tag n\'existe pas dans notre système. Vous pourrez commander un nouveau tag.'
            ]);
        }

        if ($existingTag->user_id) {
            return response()->json([
                'available' => false,
                'exists' => true,
                'message' => 'Ce tag est déjà associé à un autre compte.'
            ]);
        }

        return response()->json([
            'available' => true,
            'exists' => true,
            'message' => 'Ce tag est disponible et sera associé à votre compte.'
        ]);
    }
}
