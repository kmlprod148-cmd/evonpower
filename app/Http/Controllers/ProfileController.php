<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     * ClientUser (auth:client guard) is redirected to the dedicated client account page.
     */
    public function edit(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::guard('client')->user() ?? $request->user();

        // Redirect ClientUser to the dedicated account profile page
        if ($user instanceof \App\Models\ClientUser) {
            return Redirect::route('client.account.profile');
        }

        return view('profile.edit', ['user' => $user]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's default payment method.
     */
    public function updatePaymentMethod(Request $request): RedirectResponse
    {
        $request->validate([
            'default_payment_method' => ['nullable', 'string', 'in:cmi,stripe,prepaid_credit,postpaid_credit,offline'],
        ]);

        $user = $request->user();
        $user->default_payment_method = $request->default_payment_method;
        $user->save();

        return Redirect::route('profile.edit')
            ->with('status', 'payment-method-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
