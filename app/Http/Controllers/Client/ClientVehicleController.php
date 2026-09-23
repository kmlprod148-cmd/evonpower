<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientVehicleController extends Controller
{
    /**
     * Show the client's vehicle management page.
     */
    public function index()
    {
        $user = $this->resolveClient();
        $vehicles = $user->vehicles()->orderByDesc('is_primary')->orderBy('created_at')->get();

        return view('client.account.vehicles', compact('user', 'vehicles'));
    }

    /**
     * Store a new vehicle for the client.
     */
    public function store(Request $request)
    {
        $user = $this->resolveClient();

        $validated = $request->validate([
            'make'             => ['required', 'string', 'max:100'],
            'model'            => ['required', 'string', 'max:100'],
            'registration'     => ['nullable', 'string', 'max:20', 'unique:vehicles,registration'],
            'year'             => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'color'            => ['nullable', 'string', 'max:50'],
            'battery_capacity' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'connector_type'   => ['nullable', 'string', 'in:' . implode(',', array_keys(Vehicle::connectorTypes()))],
            'vin'              => ['nullable', 'string', 'max:17'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'is_primary'       => ['boolean'],
        ]);

        // If setting as primary, unset all others first
        if (!empty($validated['is_primary'])) {
            $user->vehicles()->update(['is_primary' => false]);
        }

        // First vehicle is automatically primary
        if ($user->vehicles()->count() === 0) {
            $validated['is_primary'] = true;
        }

        $validated['client_user_id'] = $user->id;
        $validated['is_active'] = true;

        Vehicle::create($validated);

        return redirect()->route('client.account.vehicles')
            ->with('success', __('messages.vehicle_added'));
    }

    /**
     * Update an existing vehicle.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $user = $this->resolveClient();
        $this->authorizeVehicle($vehicle, $user);

        $validated = $request->validate([
            'make'             => ['required', 'string', 'max:100'],
            'model'            => ['required', 'string', 'max:100'],
            'registration'     => ['nullable', 'string', 'max:20', 'unique:vehicles,registration,' . $vehicle->id],
            'year'             => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'color'            => ['nullable', 'string', 'max:50'],
            'battery_capacity' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'connector_type'   => ['nullable', 'string', 'in:' . implode(',', array_keys(Vehicle::connectorTypes()))],
            'vin'              => ['nullable', 'string', 'max:17'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $vehicle->update($validated);

        return redirect()->route('client.account.vehicles')
            ->with('success', __('messages.vehicle_updated'));
    }

    /**
     * Delete a vehicle.
     */
    public function destroy(Vehicle $vehicle)
    {
        $user = $this->resolveClient();
        $this->authorizeVehicle($vehicle, $user);

        $wasPrimary = $vehicle->is_primary;
        $vehicle->delete();

        // Assign another vehicle as primary if the deleted one was primary
        if ($wasPrimary) {
            $user->vehicles()->first()?->update(['is_primary' => true]);
        }

        return redirect()->route('client.account.vehicles')
            ->with('success', __('messages.vehicle_deleted'));
    }

    /**
     * Set a vehicle as the primary vehicle.
     */
    public function setPrimary(Vehicle $vehicle)
    {
        $user = $this->resolveClient();
        $this->authorizeVehicle($vehicle, $user);

        $user->vehicles()->update(['is_primary' => false]);
        $vehicle->update(['is_primary' => true]);

        return redirect()->route('client.account.vehicles')
            ->with('success', __('messages.vehicle_set_primary'));
    }

    /**
     * Resolve the authenticated client (supports both web and client guards).
     */
    private function resolveClient()
    {
        return Auth::guard('client')->user() ?? Auth::guard('web')->user();
    }

    /**
     * Abort if the vehicle does not belong to the authenticated client.
     */
    private function authorizeVehicle(Vehicle $vehicle, $user): void
    {
        if ($vehicle->client_user_id !== $user->id) {
            abort(403);
        }
    }
}
