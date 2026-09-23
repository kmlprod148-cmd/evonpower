<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\PasswordUpdateRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ToggleTwoFactorRequest;
use App\Http\Requests\GeneralSettingsUpdateRequest;
use Illuminate\Support\Facades\Storage; // Added Storage facade

class SettingsController extends Controller
{
    /**
     * Constructor with authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Prepare settings data
            $settingsData = [
                // User profile settings
                'profile' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'timezone' => $user->timezone ?? config('app.timezone'),
                ],

                // Application settings
                'app_settings' => [
                    'language' => config('app.locale', 'en'),
                    'timezone' => config('app.timezone', 'UTC'),
                ],

                // Notification preferences
                'notifications' => [
                    'email_notifications' => $user->email_notifications ?? true,
                    'sms_notifications' => $user->sms_notifications ?? false,
                ],

                // Security settings
                'security' => [
                    'two_factor_auth' => $user->two_factor_enabled ?? false,
                    'last_password_change' => $user->password_changed_at ?? null,
                ],

                // Available options
                'options' => [
                    'languages' => [
                        'en' => 'English',
                        'fr' => 'Français',
                        'es' => 'Español'
                    ],
                    'themes' => [
                        'light' => 'Light Mode',
                        'dark' => 'Dark Mode',
                        'system' => 'System Default'
                    ],
                    'timezones' => $this->getTimezones(),
                ]
            ];

            // Return the settings view
            return view('settings.index', $settingsData);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Settings page load error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to load settings. Please try again.');
        }
    }

    /**
     * Display the profile settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        // Get the authenticated user
        $user = auth()->user();

        // Return the profile view with the user data
        return view('settings.profile', compact('user'));
    }

    /**
     * Update the user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            // Add other fields as needed
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            // Add any other fields your form might have
        ]);

        // Update the user's information
        $user = auth()->user();
        $user->update($validated);

        // Redirect back with a success message
        return redirect()->route('settings.profile')->with('success', 'Profile updated successfully!');
    }

    /**
     * Update user theme preference
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark,system',
        ]);

        $user = auth()->user();
        $user->update(['theme' => $validated['theme']]);

        // Clear cache
        Cache::forget("theme:user:{$user->id}");

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thème mis à jour avec succès',
                'theme' => $validated['theme'],
            ]);
        }

        return redirect()->back()->with('success', 'Thème mis à jour avec succès!');
    }

    /**
     * Update user settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProfileUpdateRequest $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            $validatedData = $request->validated();

            // Begin database transaction
            DB::beginTransaction();

            // Update user profile
            $user->fill([
                'name' => $validatedData['name'] ?? $user->name,
                'email' => $validatedData['email'] ?? $user->email,
            ]);

            // Update preferences
            if (isset($validatedData['language'])) {
                $user->language = $validatedData['language'];
            }

            if (isset($validatedData['timezone'])) {
                $user->timezone = $validatedData['timezone'];
            }

            // Update notification preferences
            if (isset($validatedData['email_notifications'])) {
                $user->email_notifications = $validatedData['email_notifications'];
            }

            if (isset($validatedData['sms_notifications'])) {
                $user->sms_notifications = $validatedData['sms_notifications'];
            }

            // Update security settings
            if (isset($validatedData['two_factor_auth'])) {
                $user->two_factor_enabled = $validatedData['two_factor_auth'];
            }

            // Save user
            $user->save();

            // Commit transaction
            DB::commit();

            // Clear user settings cache
            Cache::forget('user_settings_' . $user->id);

            // Log the update
            Log::info('User settings updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validatedData)
            ]);

            // Redirect with success message
            return redirect()->route('settings.index')
                ->with('success', 'Settings updated successfully.');

        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();

            // Log the error
            Log::error('Settings update error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to update settings.')
                ->withInput();
        }
    }

    /**
     * Reset user settings to default
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Begin transaction
            DB::beginTransaction();

            // Reset specific settings
            $user->name = $user->getOriginal('name');
            $user->email = $user->getOriginal('email');
            $user->language = config('app.locale');
            $user->timezone = config('app.timezone');
            $user->email_notifications = true;
            $user->sms_notifications = false;
            $user->two_factor_enabled = false;

            // Save user
            $user->save();

            // Commit transaction
            DB::commit();

            // Clear user settings cache
            Cache::forget('user_settings_' . $user->id);

            // Log the reset
            Log::info('User settings reset', [
                'user_id' => $user->id
            ]);

            // Redirect with success message
            return redirect()->route('settings.index')
                ->with('success', 'All settings have been reset to default.');

        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();

            // Log the error
            Log::error('Settings reset error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to reset settings.');
        }
    }

    /**
     * Get list of timezones
     *
     * @return array
     */
    private function getTimezones()
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Europe/Berlin' => 'Berlin',
            'Asia/Tokyo' => 'Tokyo',
            'Australia/Sydney' => 'Sydney',
        ];
    }
    /**
     * Show security settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function security()
    {
        try {
            $user = Auth::user();

            // Prepare security-related data
            $securityData = [
                'two_factor_enabled' => $user->two_factor_enabled ?? false,
                'last_password_change' => $user->password_changed_at ?? null,
                'active_sessions' => $this->getActiveSessions($user),
                'security_options' => [
                    'two_factor_methods' => [
                        'app' => 'Authenticator App',
                        'sms' => 'SMS',
                        'email' => 'Email'
                    ]
                ]
            ];

            return view('settings.security', $securityData);

        } catch (\Exception $e) {
            Log::error('Error loading security settings: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to load security settings.');
        }
    }

    /**
     * Update password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(PasswordUpdateRequest $request)
    {
        $user = Auth::user();

        try {
            // Update password
            $user->password = Hash::make($request->input('password'));
            $user->password_changed_at = now();
            $user->save();

            // Log security event
            Log::info('User password updated', [
                'user_id' => $user->id,
                'ip_address' => $request->ip()
            ]);

            // Terminate other sessions
            $this->terminateOtherSessions($user);

            return redirect()->route('settings.security')
                ->with('success', 'Password updated successfully.');

        } catch (\Exception $e) {
            Log::error('Password update error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to update password.');
        }
    }

    /**
     * Toggle two-factor authentication
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleTwoFactor(ToggleTwoFactorRequest $request)
    {
        $user = Auth::user();

        try {
            // Toggle two-factor authentication
            $user->two_factor_enabled = !$user->two_factor_enabled;
            $user->two_factor_method = $user->two_factor_enabled
                ? $request->input('two_factor_method')
                : null;
            $user->save();

            // Log security event
            Log::info('Two-factor authentication status changed', [
                'user_id' => $user->id,
                'status' => $user->two_factor_enabled ? 'Enabled' : 'Disabled',
                'method' => $user->two_factor_method
            ]);

            return redirect()->route('settings.security')
                ->with('success', $user->two_factor_enabled
                    ? 'Two-factor authentication enabled.'
                    : 'Two-factor authentication disabled.'
                );

        } catch (\Exception $e) {
            Log::error('Two-factor toggle error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to update two-factor authentication.');
        }
    }

    /**
     * Terminate other active sessions
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function terminateSessions(Request $request)
    {
        $user = Auth::user();

        try {
            // Terminate other sessions
            $this->terminateOtherSessions($user);

            // Log security event
            Log::info('User terminated other sessions', [
                'user_id' => $user->id,
                'ip_address' => $request->ip()
            ]);

            return redirect()->route('settings.security')
                ->with('success', 'Other sessions have been terminated.');

        } catch (\Exception $e) {
            Log::error('Session termination error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to terminate other sessions.');
        }
    }

    /**
     * Get active user sessions
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getActiveSessions($user)
    {
        // This is a placeholder. In a real application,
        // you would retrieve actual active sessions
        return [
            [
                'ip_address' => request()->ip(),
                'device' => request()->userAgent(),
                'last_activity' => now(),
                'current_session' => true
            ]
        ];
    }

    /**
     * Terminate other user sessions
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    private function terminateOtherSessions($user)
    {
        // Placeholder for session termination logic
        // In a real application, you would:
        // 1. Invalidate other session tokens
        // 2. Remove other active sessions from storage
    }

    /**
     * Show general settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function general()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Prepare general settings data
            $generalSettings = [
                // User profile information
                'profile' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $this->getUserAvatar($user),
                ],

                // Preferences
                'preferences' => [
                    'language' => $user->language ?? config('app.locale', 'en'),
                    'timezone' => $user->timezone ?? config('app.timezone', 'UTC'),
                    'theme' => $user->theme ?? 'light',
                ],

                // Notification settings
                'notifications' => [
                    'email_notifications' => $user->email_notifications ?? true,
                    'sms_notifications' => $user->sms_notifications ?? false,
                    'push_notifications' => $user->push_notifications ?? false,
                ],

                // Available options
                'options' => [
                    // Language options
                    'languages' => [
                        'en' => 'English',
                        'fr' => 'Français',
                        'es' => 'Español',
                        'de' => 'Deutsch',
                    ],

                    // Theme options
                    'themes' => [
                        'light' => 'Light Mode',
                        'dark' => 'Dark Mode',
                        'system' => 'System Default',
                    ],

                    // Timezone options
                    'timezones' => $this->getTimezoneList(),
                ],
            ];

            // Return the general settings view
            return view('settings.general', $generalSettings);

        } catch (\Exception $e) {
            // Log the error
            Log::error('General settings page load error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to load general settings. Please try again.');
        }
    }

    /**
     * Update general user settings
     *
     * @param  \App\Http\Requests\GeneralSettingsUpdateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGeneral(GeneralSettingsUpdateRequest $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        $validatedData = $request->validated();

        try {
            // Begin database transaction
            DB::beginTransaction();

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                $this->deleteOldAvatar($user);

                // Store new avatar
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validatedData['avatar'] = $avatarPath;
            }

            // Update user profile
            $user->fill([
                'name' => $validatedData['name'] ?? $user->name,
                'email' => $validatedData['email'] ?? $user->email,
                'language' => $validatedData['language'] ?? $user->language,
                'timezone' => $validatedData['timezone'] ?? $user->timezone,
                'theme' => $validatedData['theme'] ?? $user->theme,
                'email_notifications' => $validatedData['email_notifications'] ?? $user->email_notifications,
                'sms_notifications' => $validatedData['sms_notifications'] ?? $user->sms_notifications,
                'push_notifications' => $validatedData['push_notifications'] ?? $user->push_notifications,
            ]);

            // Save user
            $user->save();

            // Commit transaction
            DB::commit();

            // Clear user settings cache
            Cache::forget('user_settings_' . $user->id);

            // Log the update
            Log::info('User general settings updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validatedData)
            ]);

            // Redirect with success message
            return redirect()->route('settings.general')
                ->with('success', 'General settings updated successfully.');

        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();

            // Log the error
            Log::error('General settings update error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to update general settings.')
                ->withInput();
        }
    }

    /**
     * Get list of timezones
     *
     * @return array
     */
    private function getTimezoneList()
    {
        return [
            'UTC' => 'UTC',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Europe/Berlin' => 'Berlin',
            'Asia/Tokyo' => 'Tokyo',
            'Australia/Sydney' => 'Sydney',
        ];
    }

    /**
     * Get user avatar
     *
     * @param  \App\Models\User  $user
     * @return string
     */
    private function getUserAvatar($user)
    {
        // Return avatar path or default avatar
        return $user->avatar
            ? Storage::url($user->avatar)
            : asset('images/default-avatar.png');
    }

    /**
     * Delete old avatar
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    private function deleteOldAvatar($user)
    {
        // Delete existing avatar if it's not the default
        if ($user->avatar && $user->avatar !== 'default-avatar.png') {
            Storage::disk('public')->delete($user->avatar);
        }
    }

    /**
     * Display the notifications settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function notifications()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Prepare notifications data based on the view requirements
            $notificationsData = [
                'user' => $user,
                'email_security_alerts' => $user->email_security_alerts ?? true,
                'email_account_updates' => $user->email_account_updates ?? true,
                'email_transactions' => $user->email_transactions ?? true,
                'email_stations' => $user->email_stations ?? true,
                'email_marketing' => $user->email_marketing ?? false,
                'app_security_alerts' => $user->app_security_alerts ?? true,
                'app_account_updates' => $user->app_account_updates ?? true,
                'app_transactions' => $user->app_transactions ?? true,
                'app_stations' => $user->app_stations ?? true,
                'email_frequency' => $user->email_frequency ?? 'daily',
            ];

            // Return the notifications view
            return view('settings.notifications', compact('notificationsData'));

        } catch (\Exception $e) {
            // Log the error
            Log::error('Notifications settings page load error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to load notifications settings. Please try again.');
        }
    }

    /**
     * Update notification settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateNotifications(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Validate the request
            $validated = $request->validate([
                'email_security_alerts' => 'boolean',
                'email_account_updates' => 'boolean',
                'email_transactions' => 'boolean',
                'email_stations' => 'boolean',
                'email_marketing' => 'boolean',
                'app_security_alerts' => 'boolean',
                'app_account_updates' => 'boolean',
                'app_transactions' => 'boolean',
                'app_stations' => 'boolean',
                'email_frequency' => 'in:realtime,daily,weekly,never',
            ]);

            // Update user notification preferences
            $user->update($validated);

            // Log the update
            Log::info('User notification settings updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validated)
            ]);

            // Redirect with success message
            return redirect()->route('settings.notifications')
                ->with('success', 'Notification settings updated successfully.');

        } catch (\Exception $e) {
            // Log the error
            Log::error('Notification settings update error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to update notification settings.')
                ->withInput();
        }
    }

    /**
     * Display the API settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function apiIndex()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if user is admin
            if (!$user->hasRole(['admin', 'super_admin'])) {
                return redirect()->route('settings.index')
                    ->with('error', 'Accès non autorisé. Seuls les administrateurs peuvent gérer les paramètres API.');
            }

            // Get Stripe and CMI credentials from AdminSetting
            $stripeSettings = $this->getStripeSettings();
            $cmiSettings = $this->getCmiSettings();
            $steveSettings = $this->getSteveSettings();
            $websocketSettings = $this->getWebSocketSettings();

            // Prepare API settings data
            $apiData = [
                'user' => $user,
                'stripe' => $stripeSettings,
                'cmi' => $cmiSettings,
                'steve' => $steveSettings,
                'websocket' => $websocketSettings,
                'api_keys' => $this->getUserApiKeys($user),
                'api_usage' => $this->getApiUsage($user),
                'rate_limits' => $this->getRateLimits(),
                'available_endpoints' => $this->getAvailableEndpoints(),
            ];

            // Return the API settings view
            return view('settings.api', $apiData);

        } catch (\Exception $e) {
            // Log the error
            Log::error('API settings page load error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to load API settings. Please try again.');
        }
    }

    /**
     * Get user API keys
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getUserApiKeys($user)
    {
        // Placeholder for API keys retrieval
        // In a real application, you would retrieve actual API keys from the database
        return [
            [
                'id' => 1,
                'name' => 'Production API Key',
                'key' => 'sk_' . str_repeat('*', 32),
                'last_used' => now()->subDays(2),
                'created_at' => now()->subMonths(3),
                'permissions' => ['read', 'write'],
            ],
            [
                'id' => 2,
                'name' => 'Development API Key',
                'key' => 'sk_' . str_repeat('*', 32),
                'last_used' => now()->subHours(5),
                'created_at' => now()->subWeeks(2),
                'permissions' => ['read'],
            ],
        ];
    }

    /**
     * Get API usage statistics
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getApiUsage($user)
    {
        // Placeholder for API usage statistics
        return [
            'requests_today' => 1250,
            'requests_this_month' => 45600,
            'rate_limit_remaining' => 8750,
            'rate_limit_reset' => now()->addHours(24),
        ];
    }

    /**
     * Get rate limits information
     *
     * @return array
     */
    private function getRateLimits()
    {
        return [
            'requests_per_minute' => 100,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000,
        ];
    }

    /**
     * Get available API endpoints
     *
     * @return array
     */
    private function getAvailableEndpoints()
    {
        return [
            'GET /api/v1/stations' => 'List charging stations',
            'GET /api/v1/stations/{id}' => 'Get station details',
            'POST /api/v1/transactions' => 'Create transaction',
            'GET /api/v1/transactions' => 'List transactions',
            'GET /api/v1/user/profile' => 'Get user profile',
            'PUT /api/v1/user/profile' => 'Update user profile',
        ];
    }

    /**
     * Get Stripe settings from AdminSetting
     *
     * @return array
     */
    private function getStripeSettings(): array
    {
        $settings = \App\Models\AdminSetting::where('category', 'external_apis')
            ->whereIn('key', [
                'stripe_test_publishable_key',
                'stripe_test_secret_key',
                'stripe_test_webhook_secret',
                'stripe_prod_publishable_key',
                'stripe_prod_secret_key',
                'stripe_prod_webhook_secret',
                'stripe_environment',
            ])
            ->get()
            ->keyBy('key');

        $sensitiveKeys = [
            'stripe_test_secret_key',
            'stripe_test_webhook_secret',
            'stripe_prod_secret_key',
            'stripe_prod_webhook_secret',
        ];

        $result = [];
        foreach ([
            'stripe_test_publishable_key',
            'stripe_test_secret_key',
            'stripe_test_webhook_secret',
            'stripe_prod_publishable_key',
            'stripe_prod_secret_key',
            'stripe_prod_webhook_secret',
            'stripe_environment',
        ] as $key) {
            $setting = $settings->get($key);
            $value = $setting ? $setting->value : '';
            
            if (in_array($key, $sensitiveKeys) && $value) {
                try {
                    $decrypted = Crypt::decryptString($value);
                    $result[$key] = [
                        'value' => '••••••••••••••••',
                        'raw_value' => $decrypted,
                        'has_value' => true,
                    ];
                } catch (\Exception $e) {
                    $result[$key] = [
                        'value' => $value ? '••••••••••••••••' : '',
                        'raw_value' => $value,
                        'has_value' => !empty($value),
                    ];
                }
            } else {
                $result[$key] = [
                    'value' => $value,
                    'raw_value' => $value,
                    'has_value' => !empty($value),
                ];
            }
        }

        return $result;
    }

    /**
     * Get CMI settings from AdminSetting
     *
     * @return array
     */
    private function getCmiSettings(): array
    {
        $settings = \App\Models\AdminSetting::where('category', 'external_apis')
            ->whereIn('key', [
                'cmi_test_api_key',
                'cmi_test_merchant_id',
                'cmi_test_api_url',
                'cmi_test_callback_url',
                'cmi_prod_api_key',
                'cmi_prod_merchant_id',
                'cmi_prod_api_url',
                'cmi_prod_callback_url',
                'cmi_environment',
            ])
            ->get()
            ->keyBy('key');

        $sensitiveKeys = [
            'cmi_test_api_key',
            'cmi_prod_api_key',
        ];

        $result = [];
        foreach ([
            'cmi_test_api_key',
            'cmi_test_merchant_id',
            'cmi_test_api_url',
            'cmi_test_callback_url',
            'cmi_prod_api_key',
            'cmi_prod_merchant_id',
            'cmi_prod_api_url',
            'cmi_prod_callback_url',
            'cmi_environment',
        ] as $key) {
            $setting = $settings->get($key);
            $value = $setting ? $setting->value : '';
            
            // Valeurs par défaut pour les URLs si non définies
            if (empty($value)) {
                if ($key === 'cmi_test_api_url') {
                    $value = 'https://testpayment.cmi.co.ma/fim/est3Dgate';
                } elseif ($key === 'cmi_prod_api_url') {
                    $value = 'https://payment.cmi.co.ma/fim/est3Dgate';
                } elseif (str_contains($key, 'callback_url')) {
                    $value = config('app.url');
                }
            }
            
            if (in_array($key, $sensitiveKeys) && $value) {
                try {
                    $decrypted = Crypt::decryptString($value);
                    $result[$key] = [
                        'value' => '••••••••••••••••',
                        'raw_value' => $decrypted,
                        'has_value' => true,
                    ];
                } catch (\Exception $e) {
                    $result[$key] = [
                        'value' => $value ? '••••••••••••••••' : '',
                        'raw_value' => $value,
                        'has_value' => !empty($value),
                    ];
                }
            } else {
                $result[$key] = [
                    'value' => $value,
                    'raw_value' => $value,
                    'has_value' => !empty($value),
                ];
            }
        }

        return $result;
    }

    /**
     * Update Stripe settings
     *
     * @param array $data
     * @return void
     */
    private function updateStripeSettings(array $data): void
    {
        $encryptedKeys = [
            'stripe_test_secret_key',
            'stripe_test_webhook_secret',
            'stripe_prod_secret_key',
            'stripe_prod_webhook_secret',
        ];

        foreach ([
            'stripe_test_publishable_key',
            'stripe_test_secret_key',
            'stripe_test_webhook_secret',
            'stripe_prod_publishable_key',
            'stripe_prod_secret_key',
            'stripe_prod_webhook_secret',
            'stripe_environment',
        ] as $key) {
            if (!isset($data[$key])) {
                continue;
            }

            $value = $data[$key];
            
            // Ignorer les valeurs masquées
            if ($value === '••••••••••••••••' || (is_string($value) && strpos($value, '•••') === 0)) {
                continue;
            }

            // Ne pas mettre à jour si la valeur est vide et que c'est une clé sensible
            if (in_array($key, $encryptedKeys) && empty($value)) {
                continue;
            }

            $setting = \App\Models\AdminSetting::updateOrCreate(
                [
                    'category' => 'external_apis',
                    'key' => $key
                ],
                [
                    'type' => in_array($key, $encryptedKeys) ? 'password' : 'text',
                    'is_active' => true,
                    'description' => $this->getStripeFieldDescription($key)
                ]
            );

            // Crypter les clés sensibles
            if (in_array($key, $encryptedKeys) && !empty($value)) {
                $setting->value = Crypt::encryptString($value);
            } else {
                $setting->value = (string) $value;
            }

            $setting->save();
        }
    }

    /**
     * Update CMI settings
     *
     * @param array $data
     * @return void
     */
    private function updateCmiSettings(array $data): void
    {
        $encryptedKeys = [
            'cmi_test_api_key',
            'cmi_prod_api_key',
        ];

        foreach ([
            'cmi_test_api_key',
            'cmi_test_merchant_id',
            'cmi_test_api_url',
            'cmi_test_callback_url',
            'cmi_prod_api_key',
            'cmi_prod_merchant_id',
            'cmi_prod_api_url',
            'cmi_prod_callback_url',
            'cmi_environment',
        ] as $key) {
            if (!isset($data[$key])) {
                continue;
            }

            $value = $data[$key];
            
            // Ignorer les valeurs masquées
            if ($value === '••••••••••••••••' || (is_string($value) && strpos($value, '•••') === 0)) {
                continue;
            }

            // Ne pas mettre à jour si la valeur est vide et que c'est une clé sensible
            if (in_array($key, $encryptedKeys) && empty($value)) {
                continue;
            }

            $setting = \App\Models\AdminSetting::updateOrCreate(
                [
                    'category' => 'external_apis',
                    'key' => $key
                ],
                [
                    'type' => in_array($key, $encryptedKeys) ? 'password' : (str_contains($key, 'url') ? 'url' : 'text'),
                    'is_active' => true,
                    'description' => $this->getCmiFieldDescription($key)
                ]
            );

            // Crypter les clés sensibles
            if (in_array($key, $encryptedKeys) && !empty($value)) {
                $setting->value = Crypt::encryptString($value);
            } else {
                $setting->value = (string) $value;
            }

            $setting->save();
        }
    }

    /**
     * Get Stripe field description
     *
     * @param string $key
     * @return string
     */
    private function getStripeFieldDescription(string $key): string
    {
        $descriptions = [
            'stripe_test_publishable_key' => 'Clé publique Stripe pour l\'environnement de test',
            'stripe_test_secret_key' => 'Clé secrète Stripe pour l\'environnement de test',
            'stripe_test_webhook_secret' => 'Secret webhook Stripe pour l\'environnement de test',
            'stripe_prod_publishable_key' => 'Clé publique Stripe pour l\'environnement de production',
            'stripe_prod_secret_key' => 'Clé secrète Stripe pour l\'environnement de production',
            'stripe_prod_webhook_secret' => 'Secret webhook Stripe pour l\'environnement de production',
            'stripe_environment' => 'Environnement actif Stripe (test ou prod)',
        ];

        return $descriptions[$key] ?? '';
    }

    /**
     * Get CMI field description
     *
     * @param string $key
     * @return string
     */
    private function getCmiFieldDescription(string $key): string
    {
        $descriptions = [
            'cmi_test_api_key' => 'Clé API CMI (Store Key) pour l\'environnement de test',
            'cmi_test_merchant_id' => 'ID marchand CMI (Client ID) pour l\'environnement de test',
            'cmi_test_api_url' => 'URL de l\'API CMI pour l\'environnement de test',
            'cmi_test_callback_url' => 'URL de callback CMI pour l\'environnement de test (où CMI redirige après paiement)',
            'cmi_prod_api_key' => 'Clé API CMI (Store Key) pour l\'environnement de production',
            'cmi_prod_merchant_id' => 'ID marchand CMI (Client ID) pour l\'environnement de production',
            'cmi_prod_api_url' => 'URL de l\'API CMI pour l\'environnement de production',
            'cmi_prod_callback_url' => 'URL de callback CMI pour l\'environnement de production (où CMI redirige après paiement)',
            'cmi_environment' => 'Environnement actif CMI (test ou prod)',
        ];

        return $descriptions[$key] ?? '';
    }

    /**
     * Get SteVe settings from AdminSetting
     *
     * @return array
     */
    private function getSteveSettings(): array
    {
        $settings = AdminSetting::where('category', 'api')
            ->whereIn('key', [
                'steve_api_key',
                'steve_api_user',
                'steve_api_pass',
            ])
            ->get()
            ->keyBy('key');

        $sensitiveKeys = [
            'steve_api_key',
            'steve_api_user',
            'steve_api_pass',
        ];

        $result = [];
        foreach ($sensitiveKeys as $key) {
            $setting = $settings->get($key);
            $value = $setting ? $setting->value : '';
            
            if ($value) {
                try {
                    $decrypted = Crypt::decryptString($value);
                    $result[$key] = [
                        'value' => '••••••••••••••••',
                        'raw_value' => $decrypted,
                        'has_value' => true,
                    ];
                } catch (\Exception $e) {
                    $result[$key] = [
                        'value' => $value ? '••••••••••••••••' : '',
                        'raw_value' => $value,
                        'has_value' => !empty($value),
                    ];
                }
            } else {
                // Valeurs par défaut depuis config
                $defaultValue = '';
                if ($key === 'steve_api_user') {
                    $defaultValue = config('services.steve.user', config('steve.username', ''));
                } elseif ($key === 'steve_api_pass') {
                    $defaultValue = config('services.steve.pass', config('steve.password', ''));
                } elseif ($key === 'steve_api_key') {
                    $defaultValue = config('services.steve.key', config('steve.api_key', ''));
                }
                
                $result[$key] = [
                    'value' => $defaultValue,
                    'raw_value' => $defaultValue,
                    'has_value' => !empty($defaultValue),
                ];
            }
        }

        return $result;
    }

    /**
     * Get WebSocket settings from AdminSetting
     *
     * @return array
     */
    private function getWebSocketSettings(): array
    {
        $settings = AdminSetting::where('category', 'websocket')
            ->whereIn('key', [
                'steve_websocket_base_url',
                'steve_api_url',
                'websocket_timeout',
            ])
            ->get()
            ->keyBy('key');

        $defaultSettings = [
            'steve_websocket_base_url' => config('steve.websocket_url', 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/'),
            'steve_api_url' => config('steve.api_url', 'http://158.69.27.239:8080'),
            'websocket_timeout' => config('steve.websocket_timeout', 30),
        ];

        $result = [];
        foreach ($defaultSettings as $key => $defaultValue) {
            $setting = $settings->get($key);
            $value = $setting ? $setting->value : $defaultValue;
            
            $result[$key] = [
                'value' => $value,
                'raw_value' => $value,
                'has_value' => !empty($value),
            ];
        }

        return $result;
    }

    /**
     * Update SteVe settings
     *
     * @param array $data
     * @return void
     */
    private function updateSteveSettings(array $data): void
    {
        $encryptedKeys = [
            'steve_api_key',
            'steve_api_user',
            'steve_api_pass',
        ];

        foreach ($encryptedKeys as $key) {
            if (!isset($data[$key])) {
                continue;
            }

            $value = $data[$key];
            
            // Ignorer les valeurs masquées
            if ($value === '••••••••••••••••' || (is_string($value) && strpos($value, '•••') === 0)) {
                continue;
            }

            // Ne pas mettre à jour si la valeur est vide
            if (empty($value)) {
                continue;
            }

            $setting = AdminSetting::updateOrCreate(
                [
                    'category' => 'api',
                    'key' => $key
                ],
                [
                    'type' => 'password',
                    'is_active' => true,
                    'description' => $this->getSteveFieldDescription($key)
                ]
            );

            // Crypter les clés sensibles
            $setting->value = Crypt::encryptString($value);
            $setting->save();
        }
    }

    /**
     * Update WebSocket settings
     *
     * @param array $data
     * @return void
     */
    private function updateWebSocketSettings(array $data): void
    {
        $allowedKeys = [
            'steve_websocket_base_url',
            'steve_api_url',
            'websocket_timeout',
        ];

        foreach ($allowedKeys as $key) {
            if (!isset($data[$key])) {
                continue;
            }

            $value = $data[$key];
            
            if (empty($value) && $key !== 'websocket_timeout') {
                continue;
            }

            $setting = AdminSetting::updateOrCreate(
                [
                    'category' => 'websocket',
                    'key' => $key
                ],
                [
                    'type' => $key === 'websocket_timeout' ? 'number' : 'url',
                    'is_active' => true,
                    'description' => $this->getWebSocketFieldDescription($key)
                ]
            );

            $setting->value = (string) $value;
            $setting->save();
        }
    }

    /**
     * Get SteVe field description
     *
     * @param string $key
     * @return string
     */
    private function getSteveFieldDescription(string $key): string
    {
        $descriptions = [
            'steve_api_key' => 'Clé API SteVe',
            'steve_api_user' => 'Nom d\'utilisateur API SteVe',
            'steve_api_pass' => 'Mot de passe API SteVe',
        ];

        return $descriptions[$key] ?? '';
    }

    /**
     * Get WebSocket field description
     *
     * @param string $key
     * @return string
     */
    private function getWebSocketFieldDescription(string $key): string
    {
        $descriptions = [
            'steve_websocket_base_url' => 'URL de base WebSocket SteVe',
            'steve_api_url' => 'URL de l\'API SteVe',
            'websocket_timeout' => 'Timeout WebSocket (secondes)',
        ];

        return $descriptions[$key] ?? '';
    }

    /**
     * Update payment gateway credentials (Stripe and CMI)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function apiUpdate(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if user is admin
            if (!$user->hasRole(['admin', 'super_admin'])) {
                return redirect()->route('settings.index')
                    ->with('error', 'Accès non autorisé. Seuls les administrateurs peuvent gérer les paramètres API.');
            }

            // Validate the request
            $validated = $request->validate([
                // Stripe validation
                'stripe_test_publishable_key' => 'nullable|string|min:20',
                'stripe_test_secret_key' => 'nullable|string|min:20',
                'stripe_test_webhook_secret' => 'nullable|string|min:20',
                'stripe_prod_publishable_key' => 'nullable|string|min:20',
                'stripe_prod_secret_key' => 'nullable|string|min:20',
                'stripe_prod_webhook_secret' => 'nullable|string|min:20',
                'stripe_environment' => 'nullable|in:test,prod',
                // CMI validation
                'cmi_test_api_key' => 'nullable|string|min:10',
                'cmi_test_merchant_id' => 'nullable|string|min:3',
                'cmi_test_api_url' => 'nullable|url',
                'cmi_test_callback_url' => 'nullable|url',
                'cmi_prod_api_key' => 'nullable|string|min:10',
                'cmi_prod_merchant_id' => 'nullable|string|min:3',
                'cmi_prod_api_url' => 'nullable|url',
                'cmi_prod_callback_url' => 'nullable|url',
                'cmi_environment' => 'nullable|in:test,prod',
                // SteVe validation
                'steve_api_key' => 'nullable|string|min:1',
                'steve_api_user' => 'nullable|string|min:1',
                'steve_api_pass' => 'nullable|string|min:1',
                // WebSocket validation
                'steve_websocket_base_url' => 'nullable|string|min:1',
                'steve_api_url' => 'nullable|url',
                'websocket_timeout' => 'nullable|integer|min:1|max:300',
            ]);

            // Update Stripe settings
            $this->updateStripeSettings($validated);

            // Update CMI settings
            $this->updateCmiSettings($validated);

            // Update SteVe settings
            $this->updateSteveSettings($validated);

            // Update WebSocket settings
            $this->updateWebSocketSettings($validated);

            // Synchronize active keys with .env file
            $this->syncActiveKeysToEnvironment($validated);

            // Clear cache
            Cache::forget('active_stripe_keys');
            Cache::forget('active_cmi_keys');
            Cache::forget('admin_settings_external_apis');
            
            // Clear PaymentKeysService cache
            if (class_exists(\App\Services\PaymentKeysService::class)) {
                app(\App\Services\PaymentKeysService::class)->clearCache();
            }

            // Log the update
            Log::info('Payment gateway credentials updated', [
                'user_id' => $user->id,
                'updated_at' => now()
            ]);

            // Redirect with success message
            return redirect()->route('settings.api.index')
                ->with('success', 'Identifiants API mis à jour avec succès !');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // Log the error
            Log::error('API credentials update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Store a new API key
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function apiStore(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'permissions' => 'array',
                'permissions.*' => 'in:read,write,admin',
            ]);

            // Get the authenticated user
            $user = Auth::user();

            // Generate API key
            $apiKey = 'sk_' . bin2hex(random_bytes(32));

            // In a real application, you would store this in the database
            // For now, we'll just log it
            Log::info('New API key created', [
                'user_id' => $user->id,
                'name' => $validated['name'],
                'permissions' => $validated['permissions'] ?? ['read'],
                'api_key' => $apiKey,
            ]);

            // Redirect with success message
            return redirect()->route('settings.api.index')
                ->with('success', 'API key created successfully. Please copy and store it securely as it will not be shown again.');

        } catch (\Exception $e) {
            // Log the error
            Log::error('API key creation error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to create API key. Please try again.')
                ->withInput();
        }
    }

    /**
     * Destroy an API key
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function apiDestroy($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // In a real application, you would delete the API key from the database
            // For now, we'll just log it
            Log::info('API key deleted', [
                'user_id' => $user->id,
                'api_key_id' => $id,
            ]);

            // Redirect with success message
            return redirect()->route('settings.api.index')
                ->with('success', 'API key deleted successfully.');

        } catch (\Exception $e) {
            // Log the error
            Log::error('API key deletion error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to delete API key. Please try again.');
        }
    }

    /**
     * Display the billing settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function billing()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login')
                    ->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }

            // Get subscription data with error handling
            $subscription = null;
            try {
                $subscription = $this->getUserSubscription($user);
            } catch (\Exception $e) {
                Log::warning('Error getting subscription: ' . $e->getMessage());
                // Use default subscription
                $subscription = (object) [
                    'plan' => (object) [
                        'name' => 'Formule standard',
                        'description' => 'Accès à toutes les fonctionnalités de base.',
                        'price' => '299,99',
                    ],
                    'next_billing_date' => now()->addMonth()->format('d/m/Y'),
                    'status' => 'active',
                ];
            }
            
            // Get billing information with error handling
            $billingInfo = null;
            try {
                $billingInfo = $this->getUserBillingInfo($user);
            } catch (\Exception $e) {
                Log::warning('Error getting billing info: ' . $e->getMessage());
                // Use default billing info
                $billingInfo = (object) [
                    'company_name' => $user->company_name ?? null,
                    'address_line1' => $user->address ?? null,
                    'address_line2' => null,
                    'postal_code' => $user->postal_code ?? null,
                    'city' => $user->city ?? null,
                    'country' => $user->country ?? 'Maroc',
                    'tax_id' => $user->tax_id ?? null,
                ];
            }

            // Get payment methods and invoices with error handling
            $paymentMethods = [];
            $invoices = [];
            try {
                $paymentMethods = $this->getUserPaymentMethods($user);
            } catch (\Exception $e) {
                Log::warning('Error getting payment methods: ' . $e->getMessage());
            }
            
            try {
                $invoices = $this->getUserInvoices($user);
            } catch (\Exception $e) {
                Log::warning('Error getting invoices: ' . $e->getMessage());
            }

            // Prepare billing data for the view
            $billingData = [
                'user' => $user,
                'subscription' => $subscription,
                'billingInfo' => $billingInfo,
                'payment_methods' => $paymentMethods,
                'invoices' => $invoices,
            ];

            // Return the billing view
            return view('settings.billing', $billingData);

        } catch (\Exception $e) {
            // Log the full error with stack trace
            Log::error('Billing settings page load error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Redirect with error message
            return redirect()->route('settings.index')
                ->with('error', 'Unable to load billing settings. Please try again.');
        }
    }

    /**
     * Set default payment method
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $method
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDefaultPaymentMethod(Request $request, $method)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Validate the payment method
            $validMethods = ['card', 'bank_account', 'paypal'];
            if (!in_array($method, $validMethods)) {
                return redirect()->back()
                    ->with('error', 'Invalid payment method.');
            }

            // Update user's default payment method
            $user->default_payment_method = $method;
            $user->save();

            // Log the update
            Log::info('Default payment method updated', [
                'user_id' => $user->id,
                'method' => $method,
            ]);

            // Redirect with success message
            return redirect()->route('settings.billing')
                ->with('success', 'Default payment method updated successfully.');

        } catch (\Exception $e) {
            // Log the error
            Log::error('Payment method update error: ' . $e->getMessage());

            // Redirect with error message
            return redirect()->back()
                ->with('error', 'Unable to update payment method. Please try again.');
        }
    }

    /**
     * Get user payment methods
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getUserPaymentMethods($user)
    {
        // Placeholder for payment methods retrieval
        // In a real application, you would retrieve actual payment methods from the database
        return [];
    }

    /**
     * Get user billing address
     *
     * @param  \App\Models\User  $user
     * @return array|null
     */
    private function getUserBillingAddress($user)
    {
        // Placeholder for billing address retrieval
        // In a real application, you would retrieve actual billing address from the database
        return [
            'street' => $user->billing_street ?? '',
            'city' => $user->billing_city ?? '',
            'state' => $user->billing_state ?? '',
            'postal_code' => $user->billing_postal_code ?? '',
            'country' => $user->billing_country ?? '',
        ];
    }

    /**
     * Get user invoices
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getUserInvoices($user)
    {
        // Placeholder for invoices retrieval
        // In a real application, you would retrieve actual invoices from the database
        return [];
    }

    /**
     * Get user subscription
     *
     * @param  \App\Models\User  $user
     * @return object
     */
    private function getUserSubscription($user)
    {
        try {
            // Try to get subscription from database if Subscription model exists
            if (class_exists(\App\Models\Subscription::class)) {
                $subscription = \App\Models\Subscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();
                
                if ($subscription) {
                    // Try to load plan relationship safely
                    try {
                        if (method_exists($subscription, 'plan') && $subscription->plan) {
                            // Plan exists, format it properly
                            $plan = $subscription->plan;
                            $subscription->plan = (object) [
                                'name' => $plan->name ?? 'Formule standard',
                                'description' => $plan->description ?? 'Accès à toutes les fonctionnalités de base.',
                                'price' => $plan->price ?? $plan->amount ?? '299,99',
                            ];
                        } else {
                            // Plan doesn't exist, create default
                            $subscription->plan = (object) [
                                'name' => 'Formule standard',
                                'description' => 'Accès à toutes les fonctionnalités de base.',
                                'price' => $subscription->amount ?? '299,99',
                            ];
                        }
                    } catch (\Exception $e) {
                        // If plan loading fails, use subscription amount
                        $subscription->plan = (object) [
                            'name' => 'Formule standard',
                            'description' => 'Accès à toutes les fonctionnalités de base.',
                            'price' => $subscription->amount ?? '299,99',
                        ];
                    }
                    
                    // Ensure next_billing_date exists
                    if (!isset($subscription->next_billing_date) || empty($subscription->next_billing_date)) {
                        $subscription->next_billing_date = $subscription->end_date 
                            ? $subscription->end_date->format('d/m/Y')
                            : now()->addMonth()->format('d/m/Y');
                    } else {
                        // If it's a string, keep it; if it's a date, format it
                        if ($subscription->next_billing_date instanceof \DateTime || $subscription->next_billing_date instanceof \Carbon\Carbon) {
                            $subscription->next_billing_date = $subscription->next_billing_date->format('d/m/Y');
                        }
                    }
                    
                    return $subscription;
                }
            }
        } catch (\Exception $e) {
            // Log but don't fail - return default subscription
            Log::warning('Error loading subscription: ' . $e->getMessage());
        }

        // Return default subscription object structure
        return (object) [
            'plan' => (object) [
                'name' => 'Formule standard',
                'description' => 'Accès à toutes les fonctionnalités de base.',
                'price' => '299,99',
            ],
            'next_billing_date' => now()->addMonth()->format('d/m/Y'),
            'status' => 'active',
        ];
    }

    /**
     * Get user billing information
     *
     * @param  \App\Models\User  $user
     * @return object
     */
    private function getUserBillingInfo($user)
    {
        try {
            // Try to get billing info from database if BillingInfo model exists
            if (class_exists(\App\Models\BillingInfo::class)) {
                $billingInfo = \App\Models\BillingInfo::where('user_id', $user->id)->first();
                
                if ($billingInfo) {
                    return $billingInfo;
                }
            }
        } catch (\Exception $e) {
            // Log but don't fail - return default billing info
            Log::warning('Error loading billing info: ' . $e->getMessage());
        }

        // Return default billing info object structure
        return (object) [
            'company_name' => $user->company_name ?? null,
            'address_line1' => $user->billing_street ?? $user->address ?? null,
            'address_line2' => $user->billing_address_line2 ?? null,
            'postal_code' => $user->billing_postal_code ?? $user->postal_code ?? null,
            'city' => $user->billing_city ?? $user->city ?? null,
            'country' => $user->billing_country ?? $user->country ?? 'Maroc',
            'tax_id' => $user->tax_id ?? $user->vat_number ?? null,
        ];
    }

    /**
     * Get user subscriptions
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getUserSubscriptions($user)
    {
        // Placeholder for subscriptions retrieval
        // In a real application, you would retrieve actual subscriptions from the database
        return [];
    }

    /**
     * Display commission plans page
     *
     * @return \Illuminate\Http\Response
     */
    public function commissionPlans()
    {
        try {
            $user = Auth::user();

            // Return a simple view or redirect
            return redirect()->route('settings.index')
                ->with('info', 'Commission plans feature coming soon.');

        } catch (\Exception $e) {
            Log::error('Commission plans page load error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to load commission plans. Please try again.');
        }
    }

    /**
     * Display commission dashboard page
     *
     * @return \Illuminate\Http\Response
     */
    public function commissionDashboard()
    {
        try {
            $user = Auth::user();

            // Return a simple view or redirect
            return redirect()->route('settings.index')
                ->with('info', 'Commission dashboard feature coming soon.');

        } catch (\Exception $e) {
            Log::error('Commission dashboard page load error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Unable to load commission dashboard. Please try again.');
        }
    }

    /**
     * Synchronise les clés actives avec les variables d'environnement
     *
     * @param array $data
     * @return void
     */
    private function syncActiveKeysToEnvironment(array $data): void
    {
        try {
            $envPath = base_path('.env');
            
            if (!\Illuminate\Support\Facades\File::exists($envPath)) {
                Log::warning('Fichier .env non trouvé, impossible de synchroniser les clés');
                return;
            }

            $envContent = \Illuminate\Support\Facades\File::get($envPath);
            $updated = false;

            // Récupérer l'environnement actif pour Stripe
            $stripeEnv = $data['stripe_environment'] ?? 
                AdminSetting::where('category', 'external_apis')
                    ->where('key', 'stripe_environment')
                    ->value('value') ?? 'test';
            
            // Synchroniser les clés Stripe selon l'environnement
            if ($stripeEnv === 'prod') {
                $stripePublishableKey = $data['stripe_prod_publishable_key'] ?? '';
                $stripeSecretKey = $data['stripe_prod_secret_key'] ?? '';
                $stripeWebhookSecret = $data['stripe_prod_webhook_secret'] ?? '';
            } else {
                $stripePublishableKey = $data['stripe_test_publishable_key'] ?? '';
                $stripeSecretKey = $data['stripe_test_secret_key'] ?? '';
                $stripeWebhookSecret = $data['stripe_test_webhook_secret'] ?? '';
            }

            // Mettre à jour STRIPE_PUBLISHABLE_KEY
            if (!empty($stripePublishableKey) && $stripePublishableKey !== '••••••••••••••••') {
                $envContent = $this->updateEnvVariable($envContent, 'STRIPE_PUBLISHABLE_KEY', $stripePublishableKey);
                $updated = true;
            }

            // Mettre à jour STRIPE_SECRET_KEY
            if (!empty($stripeSecretKey) && $stripeSecretKey !== '••••••••••••••••') {
                $envContent = $this->updateEnvVariable($envContent, 'STRIPE_SECRET_KEY', $stripeSecretKey);
                $updated = true;
            }

            // Mettre à jour STRIPE_WEBHOOK_SECRET
            if (!empty($stripeWebhookSecret) && $stripeWebhookSecret !== '••••••••••••••••') {
                $envContent = $this->updateEnvVariable($envContent, 'STRIPE_WEBHOOK_SECRET', $stripeWebhookSecret);
                $updated = true;
            }

            // Récupérer l'environnement actif pour CMI
            $cmiEnv = $data['cmi_environment'] ?? 
                AdminSetting::where('category', 'external_apis')
                    ->where('key', 'cmi_environment')
                    ->value('value') ?? 'test';
            
            // Synchroniser les clés CMI selon l'environnement
            if ($cmiEnv === 'prod') {
                $cmiApiKey = $data['cmi_prod_api_key'] ?? '';
                $cmiMerchantId = $data['cmi_prod_merchant_id'] ?? '';
            } else {
                $cmiApiKey = $data['cmi_test_api_key'] ?? '';
                $cmiMerchantId = $data['cmi_test_merchant_id'] ?? '';
            }

            // Mettre à jour CMI_STOREKEY
            if (!empty($cmiApiKey) && $cmiApiKey !== '••••••••••••••••') {
                $envContent = $this->updateEnvVariable($envContent, 'CMI_STOREKEY', $cmiApiKey);
                $updated = true;
            }

            // Mettre à jour CMI_CLIENTID
            if (!empty($cmiMerchantId)) {
                $envContent = $this->updateEnvVariable($envContent, 'CMI_CLIENTID', $cmiMerchantId);
                $updated = true;
            }

            // Synchroniser les paramètres SteVe
            if (isset($data['steve_api_user']) && !empty($data['steve_api_user']) && $data['steve_api_user'] !== '••••••••••••••••') {
                $envContent = $this->updateEnvVariable($envContent, 'STEVE_API_USER', $data['steve_api_user']);
                $envContent = $this->updateEnvVariable($envContent, 'STEVE_USERNAME', $data['steve_api_user']);
                $updated = true;
            }

            if (isset($data['steve_api_pass']) && !empty($data['steve_api_pass']) && $data['steve_api_pass'] !== '••••••••••••••••') {
                $envContent = $this->updateEnvVariable($envContent, 'STEVE_API_PASS', $data['steve_api_pass']);
                $envContent = $this->updateEnvVariable($envContent, 'STEVE_PASSWORD', $data['steve_api_pass']);
                $updated = true;
            }

            if (isset($data['steve_api_key']) && !empty($data['steve_api_key']) && $data['steve_api_key'] !== '••••••••••••••••') {
                $envContent = $this->updateEnvVariable($envContent, 'STEVE_API_KEY', $data['steve_api_key']);
                $updated = true;
            }

            // Synchroniser les paramètres WebSocket
            if (isset($data['steve_websocket_base_url']) && !empty($data['steve_websocket_base_url'])) {
                $envContent = $this->updateEnvVariable($envContent, 'STEVE_WEBSOCKET_URL', $data['steve_websocket_base_url']);
                $updated = true;
            }

            if (isset($data['steve_api_url']) && !empty($data['steve_api_url'])) {
                $envContent = $this->updateEnvVariable($envContent, 'STEVE_API_URL', $data['steve_api_url']);
                $updated = true;
            }

            if (isset($data['websocket_timeout']) && !empty($data['websocket_timeout'])) {
                $envContent = $this->updateEnvVariable($envContent, 'STEVE_WEBSOCKET_TIMEOUT', $data['websocket_timeout']);
                $updated = true;
            }

            if ($updated) {
                \Illuminate\Support\Facades\File::put($envPath, $envContent);
                Log::info('Variables d\'environnement mises à jour avec les clés API actives', [
                    'stripe_env' => $stripeEnv,
                    'cmi_env' => $cmiEnv
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation des clés vers l\'environnement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Met à jour une variable d'environnement dans le contenu .env
     *
     * @param string $envContent
     * @param string $key
     * @param string $value
     * @return string
     */
    private function updateEnvVariable(string $envContent, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*$/m";
        $replacement = "{$key}={$value}";
        
        if (preg_match($pattern, $envContent)) {
            // Mettre à jour la variable existante
            return preg_replace($pattern, $replacement, $envContent);
        } else {
            // Ajouter la nouvelle variable
            return $envContent . "\n{$replacement}";
        }
    }
}