<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ClientUser;
use App\Models\User;
use App\Services\Sms\PhoneOtpService;
use App\Support\LoginRolePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const CUSTOMER_LOGIN_SESSION_KEY = 'pending_customer_login';
    private const CUSTOMER_LOGIN_PURPOSE = 'login';

    public function __construct(private readonly PhoneOtpService $otp) {}

    /**
     * Display the login view.
     *
     * Two views are served from the same `/login` path:
     *  - `/login`          → customer phone-OTP form  (default)
     *  - `/login?staff=1`  → staff email/password form (auth.login-staff)
     *
     * The path stays unified for SEO and bookmark simplicity; the marker is
     * a single query param so the URL is trivially shareable and the link
     * from the customer view never crosses hosts. The legacy
     * /{admin.path}/login URL still exists and serves the staff view too
     * (via showStaffLoginForm), so any old bookmarks keep working.
     */
    public function create(): View
    {
        if (request()->boolean('staff')) {
            return view('auth.login-staff');
        }

        // CRITIQUE : Nettoyer url.intended si elle contient une URL CMI ou une URL vraiment externe
        // Permettre les redirections vers les routes internes de l'application
        $intendedUrl = session()->get('url.intended');
        
        // Vérifier si l'URL intended est une URL CMI ou une URL externe dangereuse
        $shouldClear = false;
        if ($intendedUrl) {
            // Vérifier les URLs CMI - toujours bloquer
            if (strpos($intendedUrl, 'testpayment.cmi.co.ma') !== false || 
                strpos($intendedUrl, 'payment.cmi.co.ma') !== false ||
                strpos($intendedUrl, 'est3Dgate') !== false ||
                strpos($intendedUrl, 'cmi.co.ma') !== false) {
                $shouldClear = true;
                \Illuminate\Support\Facades\Log::warning('Login: Clearing CMI URL from intended URL', [
                    'intended_url' => $intendedUrl,
                    'session_id' => session()->getId(),
                ]);
            } else {
                // Pour les autres URLs, vérifier si c'est vraiment externe
                $appUrl = config('app.url');
                if ($appUrl && !empty($intendedUrl)) {
                    $intendedUrlParsed = parse_url($intendedUrl);
                    $appUrlParsed = parse_url($appUrl);
                    
                    // Si les domaines sont différents, vérifier si c'est une URL interne de l'application
                    if (isset($intendedUrlParsed['host']) && isset($appUrlParsed['host'])) {
                        $intendedHost = preg_replace('/^www\./', '', strtolower($intendedUrlParsed['host']));
                        $appHost = preg_replace('/^www\./', '', strtolower($appUrlParsed['host']));
                        $path = $intendedUrlParsed['path'] ?? '';
                        
                        if ($intendedHost !== $appHost) {
                            // Vérifier si c'est une URL interne (commence par /)
                            $path = $intendedUrlParsed['path'] ?? '';
                            $allowedInternalRoutes = [
                                '/credits',
                                '/credit-recharge',
                                '/dashboard',
                                '/profile',
                                '/balances',
                                '/charging-points',
                                '/transactions',
                                '/admin',
                                '/integrator',
                                '/operator',
                                '/partner'
                            ];
                            
                            $isInternalRoute = false;
                            foreach ($allowedInternalRoutes as $route) {
                                if (strpos($path, $route) === 0) {
                                    $isInternalRoute = true;
                                    \Illuminate\Support\Facades\Log::info('Login: Allowing internal route redirect', [
                                        'intended_url' => $intendedUrl,
                                        'path' => $path,
                                        'route' => $route,
                                        'session_id' => session()->getId(),
                                    ]);
                                    break;
                                }
                            }
                            
                            if (!$isInternalRoute) {
                                $shouldClear = true;
                                \Illuminate\Support\Facades\Log::warning('Login: Clearing external URL from intended URL', [
                                    'intended_url' => $intendedUrl,
                                    'app_url' => $appUrl,
                                    'intended_host' => $intendedHost,
                                    'app_host' => $appHost,
                                    'path' => $path,
                                    'allowed_routes' => $allowedInternalRoutes,
                                    'session_id' => session()->getId(),
                                ]);
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::info('Login: Allowing internal route redirect', [
                                'intended_url' => $intendedUrl,
                                'path' => $path,
                                'session_id' => session()->getId(),
                            ]);
                        }
                    }
                }
            }
        }
        
        if ($shouldClear) {
            \Illuminate\Support\Facades\Log::warning('Login: Clearing CMI or external URL from intended URL', [
                'intended_url' => $intendedUrl,
                'session_id' => session()->getId(),
            ]);
            session()->forget('url.intended');
        }
        
        // Vérification finale AVANT de retourner la vue - double sécurité
        $finalCheck = session()->get('url.intended');
        if ($finalCheck) {
            // Vérifier une dernière fois si l'URL est CMI
            if (stripos($finalCheck, 'testpayment.cmi.co.ma') !== false || 
                stripos($finalCheck, 'payment.cmi.co.ma') !== false ||
                stripos($finalCheck, 'est3Dgate') !== false ||
                stripos($finalCheck, 'cmi.co.ma') !== false) {
                \Illuminate\Support\Facades\Log::error('Login: CRITICAL - CMI URL still in session after cleanup!', [
                    'intended_url' => $finalCheck,
                    'session_id' => session()->getId(),
                ]);
                session()->forget('url.intended');
            } else {
                // Vérifier si c'est externe mais autorisé (routes internes)
                $appUrl = config('app.url');
                if ($appUrl) {
                    $finalCheckParsed = parse_url($finalCheck);
                    $appUrlParsed = parse_url($appUrl);
                    
                    if (isset($finalCheckParsed['host']) && isset($appUrlParsed['host'])) {
                        $finalHost = preg_replace('/^www\./', '', strtolower($finalCheckParsed['host']));
                        $appHost = preg_replace('/^www\./', '', strtolower($appUrlParsed['host']));
                        
                        if ($finalHost !== $appHost) {
                            // Vérifier si c'est une URL interne autorisée
                            $path = $finalCheckParsed['path'] ?? '';
                            $allowedInternalRoutes = [
                                '/credits',
                                '/credit-recharge',
                                '/dashboard',
                                '/profile',
                                '/balances',
                                '/charging-points',
                                '/transactions',
                                '/admin',
                                '/integrator',
                                '/operator',
                                '/partner'
                            ];
                            
                            $isInternalRoute = false;
                            foreach ($allowedInternalRoutes as $route) {
                                if (strpos($path, $route) === 0) {
                                    $isInternalRoute = true;
                                    \Illuminate\Support\Facades\Log::info('Login: Allowing internal route in final check', [
                                        'intended_url' => $finalCheck,
                                        'path' => $path,
                                        'route' => $route,
                                        'session_id' => session()->getId(),
                                    ]);
                                    break;
                                }
                            }
                            
                            if (!$isInternalRoute) {
                                \Illuminate\Support\Facades\Log::error('Login: CRITICAL - External URL still in session after cleanup!', [
                                    'intended_url' => $finalCheck,
                                    'intended_host' => $finalHost,
                                    'app_host' => $appHost,
                                    'path' => $path,
                                    'allowed_routes' => $allowedInternalRoutes,
                                    'session_id' => session()->getId(),
                                ]);
                                session()->forget('url.intended');
                            }
                        }
                    }
                }
            }
        }
        
        // Log pour diagnostiquer les problèmes de redirection
        \Illuminate\Support\Facades\Log::info('Login page accessed', [
            'url' => request()->url(),
            'route_name' => request()->route()?->getName(),
            'session_id' => session()->getId(),
            'intended_url' => session()->get('url.intended'),
            'ip' => request()->ip(),
        ]);
        
        return view('auth.login');
    }

    /**
     * Display the back-office (admin / operator / integrator / partner) login view.
     *
     * Renders a separate Blade template with its own visual theme so the
     * staff login experience is visually distinct from the customer one.
     * The form posts to the existing `login.admin` POST endpoint, so the
     * authentication path (LoginRequest -> authenticateAdmin + LoginRolePolicy
     * check) is unchanged.
     */
    public function showStaffLoginForm(): View
    {
        return view('auth.login-staff');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if ($request->loginFlow() === LoginRequest::FLOW_CUSTOMER) {
            return $this->sendCustomerLoginCode($request);
        }

        $request->authenticateAdmin();

        $request->session()->regenerate();

        // CRITIQUE : Vérifier que l'URL intended n'est pas une URL CMI ou externe avant de rediriger
        $intendedUrl = $request->session()->get('url.intended');
        
        $shouldBlock = false;
        if ($intendedUrl) {
            // Vérifier les URLs CMI
            if (strpos($intendedUrl, 'testpayment.cmi.co.ma') !== false || 
                strpos($intendedUrl, 'payment.cmi.co.ma') !== false ||
                strpos($intendedUrl, 'est3Dgate') !== false) {
                $shouldBlock = true;
            }
            
            // Vérifier si c'est une URL externe (ne commence pas par l'URL de l'application)
            $appUrl = config('app.url');
            if ($appUrl && !empty($intendedUrl)) {
                $intendedUrlParsed = parse_url($intendedUrl);
                $appUrlParsed = parse_url($appUrl);
                
                // Si les domaines sont différents, c'est une URL externe
                if (isset($intendedUrlParsed['host']) && isset($appUrlParsed['host'])) {
                    $intendedHost = preg_replace('/^www\./i', '', strtolower($intendedUrlParsed['host']));
                    $appHost      = preg_replace('/^www\./i', '', strtolower($appUrlParsed['host']));
                    if ($intendedHost !== $appHost) {
                        $shouldBlock = true;
                        \Illuminate\Support\Facades\Log::warning('Login: Blocked redirect to external URL', [
                            'intended_url'  => $intendedUrl,
                            'app_url'       => $appUrl,
                            'intended_host' => $intendedHost,
                            'app_host'      => $appHost,
                            'user_id'       => Auth::id(),
                        ]);
                    }
                }
            }
        }
        
        if ($shouldBlock) {
            \Illuminate\Support\Facades\Log::warning('Login: Blocked redirect to CMI or external URL', [
                'intended_url' => $intendedUrl,
                'user_id' => Auth::id(),
            ]);
            $request->session()->forget('url.intended');
            
            // Rediriger vers le dashboard approprié
            if (Auth::guard('client')->check() && !Auth::guard('web')->check()) {
                return redirect()->route('dashboard.client');
            }
            return redirect()->route('dashboard');
        }

        // Utiliser redirect()->intended() mais avec une protection supplémentaire
        // Vérifier à nouveau avant de rediriger (double sécurité)
        $finalIntendedUrl = $request->session()->get('url.intended');
        if ($finalIntendedUrl && (
            strpos($finalIntendedUrl, 'testpayment.cmi.co.ma') !== false || 
            strpos($finalIntendedUrl, 'payment.cmi.co.ma') !== false ||
            strpos($finalIntendedUrl, 'est3Dgate') !== false
        )) {
            $request->session()->forget('url.intended');
            if (Auth::guard('client')->check() && !Auth::guard('web')->check()) {
                return redirect()->route('dashboard.client');
            }
            return redirect()->route('dashboard');
        }

        // If the client came from a checkout page, send them back so payment auto-starts
        if ($checkoutReturnUrl = session()->pull('checkout.return_url')) {
            return redirect($checkoutReturnUrl);
        }

        // Redirect clients to their dedicated dashboard
        if (Auth::guard('client')->check() && !Auth::guard('web')->check()) {
            return redirect()->intended(route('dashboard.client', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function sendCustomerLoginCode(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        [$guard, $account, $failureReason] = $this->findCustomerAccountByPhone($request->input('phone'));

        if (! $account) {
            RateLimiter::hit($request->throttleKey());

            $message = $failureReason === 'admin_phone'
                ? 'Ce numero appartient a un compte administratif. Utilisez la connexion par e-mail.'
                : trans('auth.failed');

            throw ValidationException::withMessages(['phone' => $message]);
        }

        if ($failureReason === 'inactive' || (isset($account->is_active) && $account->is_active === false)) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'phone' => 'Ce compte client est desactive.',
            ]);
        }

        $phone = $this->normalizePhone($account->phone ?? $request->input('phone'));

        $result = $this->otp->issue($phone, self::CUSTOMER_LOGIN_PURPOSE, $request->ip());

        if (! $result['issued']) {
            if ($result['retry_after'] !== null) {
                throw ValidationException::withMessages([
                    'phone' => "Veuillez patienter {$result['retry_after']}s avant de redemander un code.",
                ]);
            }

            Log::error('Customer login OTP could not be sent', [
                'guard' => $guard,
                'account_id' => $account->getKey(),
                'phone' => $phone,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'phone' => 'Impossible d envoyer le code SMS pour le moment.',
            ]);
        }

        $request->session()->put(self::CUSTOMER_LOGIN_SESSION_KEY, [
            'guard' => $guard,
            'account_id' => $account->getKey(),
            'phone' => $phone,
            'remember' => $request->boolean('remember'),
            'issued_at' => now()->timestamp,
        ]);

        RateLimiter::clear($request->throttleKey());

        Log::info('Customer login OTP issued', [
            'guard' => $guard,
            'account_id' => $account->getKey(),
            'phone' => $phone,
            'ip' => $request->ip(),
        ]);

        $redirect = redirect()->route('login.customer.verify.show')
            ->with('info', 'Un code de connexion a ete envoye au ' . $phone);

        if (! empty($result['code'])) {
            $redirect->with('dev_otp_code', $result['code']);
        }

        return $redirect;
    }

    public function showCustomerOtp(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(self::CUSTOMER_LOGIN_SESSION_KEY);

        if (! $pending) {
            return redirect()->route('login')
                ->with('status', 'Veuillez saisir votre numero de telephone pour recevoir un code de connexion.');
        }

        return view('auth.login-otp', [
            'phone' => $pending['phone'],
        ]);
    }

    public function verifyCustomerOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::CUSTOMER_LOGIN_SESSION_KEY);

        if (! $pending) {
            return redirect()->route('login')
                ->with('status', 'Votre session de connexion a expire. Demandez un nouveau code.');
        }

        $request->validate([
            'code' => ['required', 'digits:' . PhoneOtpService::CODE_LENGTH],
        ]);

        $status = $this->otp->verify($pending['phone'], $request->input('code'), self::CUSTOMER_LOGIN_PURPOSE);

        $message = match ($status) {
            'invalid' => 'Code incorrect. Veuillez reessayer.',
            'expired' => 'Le code a expire. Demandez-en un nouveau.',
            'too_many_attempts' => 'Trop de tentatives. Demandez un nouveau code.',
            'not_found' => 'Aucun code en cours. Demandez-en un nouveau.',
            default => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['code' => $message]);
        }

        $account = $this->resolvePendingCustomerAccount($pending);

        if (! $account) {
            $request->session()->forget(self::CUSTOMER_LOGIN_SESSION_KEY);

            throw ValidationException::withMessages([
                'code' => 'Compte client introuvable. Veuillez recommencer.',
            ]);
        }

        $guard = $pending['guard'];
        Auth::guard($guard)->login($account, (bool) ($pending['remember'] ?? false));

        if ($account instanceof User && empty($account->phone_verified_at)) {
            $account->forceFill(['phone_verified_at' => now()])->save();
        }

        if ($account instanceof ClientUser) {
            $account->recordLogin();
        }

        $request->session()->forget(self::CUSTOMER_LOGIN_SESSION_KEY);
        $request->session()->regenerate();

        Log::info('Customer phone login completed', [
            'guard' => $guard,
            'account_id' => $account->getKey(),
        ]);

        if ($checkoutReturnUrl = session()->pull('checkout.return_url')) {
            return redirect($checkoutReturnUrl);
        }

        if ($guard === 'client') {
            return redirect()->intended(route('dashboard.client', absolute: false));
        }

        return redirect()->intended(route('reservations.index', absolute: false));
    }

    public function resendCustomerOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::CUSTOMER_LOGIN_SESSION_KEY);

        if (! $pending) {
            return redirect()->route('login');
        }

        $result = $this->otp->issue($pending['phone'], self::CUSTOMER_LOGIN_PURPOSE, $request->ip());

        if (! $result['issued'] && $result['retry_after'] !== null) {
            return back()->withErrors([
                'code' => "Veuillez patienter {$result['retry_after']}s avant de redemander un code.",
            ]);
        }

        if (! $result['issued']) {
            return back()->withErrors([
                'code' => 'Impossible d envoyer un nouveau code pour le moment.',
            ]);
        }

        $back = back()->with('info', 'Un nouveau code de connexion a ete envoye.');

        if (! empty($result['code'])) {
            $back->with('dev_otp_code', $result['code']);
        }

        return $back;
    }

    /**
     * @return array{0: string|null, 1: \App\Models\User|\App\Models\ClientUser|null, 2: string|null}
     */
    private function findCustomerAccountByPhone(string $phone): array
    {
        $phones = $this->phoneLookupCandidates($phone);
        $adminPhoneMatched = false;

        try {
            $users = User::whereIn('phone', $phones)->get();

            foreach ($users as $user) {
                if (LoginRolePolicy::usesAdministrativeEmailLogin($user)) {
                    $adminPhoneMatched = true;
                    continue;
                }

                return ['web', $user, null];
            }
        } catch (\Throwable $e) {
            Log::warning('Customer phone login user lookup failed', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $clientUser = ClientUser::whereIn('phone', $phones)->first();

            if ($clientUser) {
                return ['client', $clientUser, $clientUser->isActive() ? null : 'inactive'];
            }
        } catch (\Throwable $e) {
            Log::warning('Customer phone login client guard lookup failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return [null, null, $adminPhoneMatched ? 'admin_phone' : 'not_found'];
    }

    private function resolvePendingCustomerAccount(array $pending): User|ClientUser|null
    {
        return match ($pending['guard'] ?? null) {
            'web' => User::find($pending['account_id'] ?? null),
            'client' => ClientUser::find($pending['account_id'] ?? null),
            default => null,
        };
    }

    private function phoneLookupCandidates(string $phone): array
    {
        $raw = trim($phone);
        $normalized = $this->normalizePhone($raw);
        $digits = preg_replace('/\D+/', '', $raw) ?: '';

        $candidates = [$raw, $normalized, $digits];

        if (str_starts_with($digits, '212')) {
            $candidates[] = '0' . substr($digits, 3);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function normalizePhone(string $phone): string
    {
        $raw = trim($phone);
        $digits = preg_replace('/\D+/', '', $raw) ?: '';

        if ($raw !== '' && str_starts_with($raw, '+')) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        if (str_starts_with($digits, '212')) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+212' . substr($digits, 1);
        }

        if ($digits !== '') {
            return '+212' . $digits;
        }

        return $raw;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        \Illuminate\Support\Facades\Log::info('Logout initiated. User authenticated: ' . Auth::guard('web')->check());
        Auth::guard('web')->logout();

        // Only fully invalidate the session when no other guard is still active.
        // A client authenticated via the 'client' guard must not be signed out
        // just because a web (admin/staff) guard session is being terminated.
        if (auth('client')->check()) {
            $request->session()->regenerate();
        } else {
            $request->session()->invalidate();
        }

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Handle demo account auto-login.
     * Permet de se connecter automatiquement avec un compte de démo.
     */
    public function autoLogin(Request $request, string $role): RedirectResponse
    {
        // Définir les comptes de démo disponibles
        $demoAccounts = [
            'admin' => [
                'email' => 'admin@demo.com',
                'password' => 'demo123',
            ],
            'integrateur' => [
                'email' => 'integrateur@demo.com',
                'password' => 'demo123',
            ],
            'operateur' => [
                'email' => 'operateur@demo.com',
                'password' => 'demo123',
            ],
        ];

        // Normaliser le rôle (gérer les variations)
        $role = strtolower($role);
        if ($role === 'integrator' || $role === 'intégrateur') {
            $role = 'integrateur';
        }
        if ($role === 'operator' || $role === 'opérateur') {
            $role = 'operateur';
        }

        // Vérifier que le rôle existe
        if (!isset($demoAccounts[$role])) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Compte de démo non trouvé pour ce rôle.']);
        }

        $credentials = $demoAccounts[$role];

        // Tenter la connexion
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            \Illuminate\Support\Facades\Log::info('Auto-login réussi pour le rôle: ' . $role);

            // CRITIQUE : Vérifier que l'URL intended n'est pas une URL CMI ou externe avant de rediriger
            $intendedUrl = $request->session()->get('url.intended');
            
            $shouldBlock = false;
            if ($intendedUrl) {
                // Vérifier les URLs CMI
                if (strpos($intendedUrl, 'testpayment.cmi.co.ma') !== false || 
                    strpos($intendedUrl, 'payment.cmi.co.ma') !== false ||
                    strpos($intendedUrl, 'est3Dgate') !== false) {
                    $shouldBlock = true;
                }
                
                // Vérifier si c'est une URL externe
                $appUrl = config('app.url');
                if ($appUrl && !empty($intendedUrl)) {
                    $intendedUrlParsed = parse_url($intendedUrl);
                    $appUrlParsed = parse_url($appUrl);
                    
                    if (isset($intendedUrlParsed['host']) && isset($appUrlParsed['host'])) {
                        $intendedHost = preg_replace('/^www\./i', '', strtolower($intendedUrlParsed['host']));
                        $appHost      = preg_replace('/^www\./i', '', strtolower($appUrlParsed['host']));
                        if ($intendedHost !== $appHost) {
                            $shouldBlock = true;
                        }
                    }
                }
            }
            
            if ($shouldBlock) {
                \Illuminate\Support\Facades\Log::warning('Auto-login: Blocked redirect to CMI or external URL', [
                    'intended_url' => $intendedUrl,
                    'role' => $role,
                ]);
                $request->session()->forget('url.intended');
                // Rediriger vers le dashboard au lieu de l'URL externe
                return redirect()->route('dashboard');
            }

            // Vérification finale avant redirect()->intended()
            $finalIntendedUrl = $request->session()->get('url.intended');
            if ($finalIntendedUrl && (
                strpos($finalIntendedUrl, 'testpayment.cmi.co.ma') !== false || 
                strpos($finalIntendedUrl, 'payment.cmi.co.ma') !== false ||
                strpos($finalIntendedUrl, 'est3Dgate') !== false
            )) {
                $request->session()->forget('url.intended');
                return redirect()->route('dashboard');
            }

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->route('login')
            ->withErrors(['email' => 'Échec de la connexion automatique. Veuillez vérifier que les comptes de démo sont créés.']);
    }
}
