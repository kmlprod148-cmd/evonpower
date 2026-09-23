<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Sms\PhoneOtpService;
use App\Services\WalletService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    private const DEFAULT_COUNTRY = 'MA';
    private const PENDING_SESSION_KEY = 'pending_registration';

    public function __construct(private readonly PhoneOtpService $otp) {}

    /** GET /register — show the simplified name + phone form. */
    public function create(): View
    {
        // Defensive: kill any stale guard session before the new flow starts.
        // Without this, an admin/staff cookie from a previous dev session keeps
        // the user "authenticated" on the `web` guard, and `guest` middleware
        // on /register/resend kicks them back into the prior account.
        $this->logoutAllGuards();

        return view('auth.register');
    }

    /**
     * Forget every guard, invalidate the session, and rotate the CSRF token.
     * Safe to call from the registration entry point because the new flow
     * stores its own state in a fresh session after this point.
     */
    private function logoutAllGuards(): void
    {
        $request = request();
        foreach (array_keys(config('auth.guards', [])) as $guardName) {
            try {
                $guard = auth($guardName);
                if (method_exists($guard, 'check') && $guard->check()
                    && method_exists($guard, 'logout')) {
                    $guard->logout();
                }
            } catch (\Throwable $e) {
                // Some non-session guards (e.g. token) have no logout — ignore.
            }
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * POST /register — validate name + phone, stash a pending registration
     * in the session, dispatch the OTP, and redirect to the verification screen.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'max:30',
                'regex:/^\+[1-9]\d{6,14}$/',
                'unique:users,phone',
            ],
        ], [
            'phone.regex'  => 'Le numéro doit être au format international : +212612345678',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
        ]);

        // Per-IP throttle on OTP requests (5 requests / 10 min).
        $throttleKey = 'register-otp:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'phone' => "Trop de demandes. Réessayez dans {$seconds} secondes.",
            ]);
        }
        RateLimiter::hit($throttleKey, 600);

        $result = $this->otp->issue($validated['phone'], 'registration', $request->ip());

        if (!$result['issued'] && $result['retry_after'] !== null) {
            throw ValidationException::withMessages([
                'phone' => "Veuillez patienter {$result['retry_after']}s avant de redemander un code.",
            ]);
        }

        session()->put(self::PENDING_SESSION_KEY, [
            'name'      => $validated['name'],
            'phone'     => $validated['phone'],
            'country'   => self::DEFAULT_COUNTRY,
            'issued_at' => now()->timestamp,
        ]);

        $redirect = redirect()->route('register.verify.show')
            ->with('info', 'Un code de vérification a été envoyé au ' . $validated['phone']);

        // Surface the code in dev mode so the developer can complete the flow without SMS.
        if (!empty($result['code'])) {
            $redirect->with('dev_otp_code', $result['code']);
        }

        return $redirect;
    }

    /** GET /register/verify — show OTP entry. Redirects home if no pending registration. */
    public function showOtp(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING_SESSION_KEY);
        if (!$pending) {
            return redirect()->route('register');
        }

        return view('auth.register-otp', [
            'phone' => $pending['phone'],
            'name'  => $pending['name'],
        ]);
    }

    /**
     * POST /register/verify — validate OTP, create the user, log them in, and
     * resume any pending charging-offer flow.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING_SESSION_KEY);
        if (!$pending) {
            return redirect()->route('register')
                ->with('info', 'Votre session a expiré. Veuillez recommencer.');
        }

        $request->validate([
            'code' => ['required', 'digits:' . PhoneOtpService::CODE_LENGTH],
        ], [
            'code.required' => 'Veuillez saisir le code reçu par SMS.',
            'code.digits'   => 'Le code doit contenir ' . PhoneOtpService::CODE_LENGTH . ' chiffres.',
        ]);

        $status = $this->otp->verify($pending['phone'], $request->input('code'), 'registration');

        $message = match ($status) {
            'invalid'           => 'Code incorrect. Veuillez réessayer.',
            'expired'           => 'Le code a expiré. Demandez-en un nouveau.',
            'too_many_attempts' => 'Trop de tentatives. Demandez un nouveau code.',
            'not_found'         => 'Aucun code en cours. Demandez-en un nouveau.',
            default             => null,
        };
        if ($message !== null) {
            throw ValidationException::withMessages(['code' => $message]);
        }

        $user = DB::transaction(function () use ($pending) {
            $user = User::create([
                'name'              => $pending['name'],
                'phone'             => $pending['phone'],
                'phone_verified_at' => now(),
                'country'           => $pending['country'] ?? self::DEFAULT_COUNTRY,
                'email'             => null,
                'password'          => null,
                'is_active'         => true,
                'currency'          => 'EUR',
                'language'          => 'fr',
                'timezone'          => 'Africa/Casablanca',
            ]);

            try {
                $user->assignRole(Role::firstOrCreate(['name' => 'user']));
            } catch (\Throwable $e) {
                Log::error('Failed to assign role during OTP registration', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            try {
                WalletService::createWallet($user, [
                    'name'        => $user->name . "'s Wallet",
                    'description' => 'Portefeuille principal de ' . $user->name,
                    'currency'    => 'EUR',
                    'balance'     => 0,
                    'is_active'   => true,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to create wallet during OTP registration', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->forget(self::PENDING_SESSION_KEY);
        $request->session()->regenerate();

        Log::info('Phone-verified registration completed', [
            'user_id' => $user->id,
            'phone'   => $user->phone,
        ]);

        $pendingChargingPointId = session()->pull('pending_charging_point_id');
        if ($pendingChargingPointId) {
            return redirect()->route('client.charging.offer', ['id' => $pendingChargingPointId])
                ->with('success', 'Compte créé avec succès. Complétez votre réservation ci-dessous.');
        }

        return redirect()->route('reservations.index')
            ->with('success', 'Votre numéro a été vérifié et votre compte est créé.');
    }

    /** POST /register/resend — re-issue the OTP, honouring the cooldown. */
    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING_SESSION_KEY);
        if (!$pending) {
            return redirect()->route('register');
        }

        $throttleKey = 'register-otp-resend:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors(['code' => "Trop de demandes. Réessayez dans {$seconds} secondes."]);
        }
        RateLimiter::hit($throttleKey, 600);

        $result = $this->otp->issue($pending['phone'], 'registration', $request->ip());

        if (!$result['issued'] && $result['retry_after'] !== null) {
            return back()->withErrors([
                'code' => "Veuillez patienter {$result['retry_after']}s avant de redemander un code.",
            ]);
        }

        $back = back()->with('info', 'Un nouveau code a été envoyé.');
        if (!empty($result['code'])) {
            $back->with('dev_otp_code', $result['code']);
        }
        return $back;
    }
}
