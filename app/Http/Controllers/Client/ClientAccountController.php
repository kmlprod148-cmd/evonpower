<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ClientAccountController extends Controller
{
    /**
     * Show the client's account profile page.
     */
    public function profile()
    {
        $user = $this->resolveClient();
        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();

        return view('client.account.profile', compact('user', 'wallet'));
    }

    /**
     * Update the client's personal information.
     */
    public function updateProfile(Request $request)
    {
        $user = $this->resolveClient();

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:255', 'unique:client_users,email,' . $user->id],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
            'city'       => ['nullable', 'string', 'max:100'],
            'postal_code'=> ['nullable', 'string', 'max:20'],
            'country'    => ['nullable', 'string', 'max:100'],
            'language'   => ['nullable', 'in:fr,en,ar'],
        ]);

        // If email changed, clear verification
        if ($validated['email'] !== $user->email) {
            $validated['email_verified_at'] = null;
        }

        $user->update($validated);

        // Persist chosen language in session immediately
        if (!empty($validated['language'])) {
            session(['locale' => $validated['language']]);
            app()->setLocale($validated['language']);
        }

        return redirect()->route('client.account.profile')
            ->with('success', __('messages.profile_updated'));
    }

    /**
     * Change the client's password.
     */
    public function changePassword(Request $request)
    {
        $user = $this->resolveClient();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => __('messages.current_password_incorrect')])
                ->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('client.account.profile')
            ->with('success', __('messages.password_changed'));
    }

    /**
     * Resolve the authenticated client (supports both web and client guards).
     *
     * @return \App\Models\ClientUser|\App\Models\User
     */
    private function resolveClient()
    {
        return Auth::guard('client')->user() ?? Auth::guard('web')->user();
    }
}
