<?php

namespace App\Http\Requests\Auth;

use App\Support\LoginRolePolicy;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public const FLOW_ADMIN = 'admin';
    public const FLOW_CUSTOMER = 'customer';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('login.customer')) {
            $flow = self::FLOW_CUSTOMER;
        } elseif ($this->routeIs('login.admin')) {
            $flow = self::FLOW_ADMIN;
        } else {
            $flow = $this->input('login_method') === self::FLOW_CUSTOMER
                ? self::FLOW_CUSTOMER
                : self::FLOW_ADMIN;
        }

        $this->merge([
            'login_method' => $flow,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->loginFlow() === self::FLOW_CUSTOMER) {
            return [
                'login_method' => ['required', Rule::in([self::FLOW_ADMIN, self::FLOW_CUSTOMER])],
                'phone' => ['required', 'string', 'max:30'],
                'remember' => ['nullable', 'boolean'],
            ];
        }

        return [
            'login_method' => ['required', Rule::in([self::FLOW_ADMIN, self::FLOW_CUSTOMER])],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function loginFlow(): string
    {
        return $this->input('login_method') === self::FLOW_CUSTOMER
            ? self::FLOW_CUSTOMER
            : self::FLOW_ADMIN;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->authenticateAdmin();
    }

    /**
     * Attempt to authenticate administrative email/password credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticateAdmin(): void
    {
        $this->ensureIsNotRateLimited();

        if (Auth::attempt([
            'email' => Str::lower((string) $this->input('email')),
            'password' => $this->input('password'),
        ], $this->boolean('remember'))) {
            $user = Auth::user();

            if (! LoginRolePolicy::usesAdministrativeEmailLogin($user)) {
                Auth::guard('web')->logout();
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => 'Ce formulaire est reserve aux comptes administratifs. Les clients doivent utiliser la connexion par telephone.',
                ]);
            }

            if (isset($user->is_active) && $user->is_active === false) {
                Auth::guard('web')->logout();
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => 'Ce compte administratif est desactive.',
                ]);
            }

            RateLimiter::clear($this->throttleKey());
            return;
        }

        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            $this->rateLimitField() => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $identifier = $this->loginFlow() === self::FLOW_CUSTOMER
            ? $this->string('phone')
            : $this->string('email');

        return Str::transliterate(Str::lower($identifier).'|'.$this->ip());
    }

    private function rateLimitField(): string
    {
        return $this->loginFlow() === self::FLOW_CUSTOMER ? 'phone' : 'email';
    }
}
