<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class GeneralSettingsController extends Controller
{
    /**
     * Middleware pour vérifier que seul admin@evon.com peut accéder
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->email !== 'admin@evon.com') {
                abort(403, 'Accès non autorisé. Seul l\'administrateur principal peut accéder aux paramètres généraux.');
            }
            return $next($request);
        });
    }

    /**
     * Afficher les paramètres généraux
     */
    public function index(): View
    {
        $settings = $this->getSettings();
        
        return view('admin.general-settings', compact('settings'));
    }

    /**
     * Mettre à jour les paramètres généraux
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'currency' => 'required|string|in:EUR,USD,MAD',
            'currency_symbol' => 'required|string|max:5',
            'decimal_places' => 'required|integer|min:0|max:4',
            'thousands_separator' => 'required|string|max:1',
            'decimal_separator' => 'required|string|max:1',
            'app_name' => 'required|string|max:100',
            'app_description' => 'nullable|string|max:500',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'time_format' => 'required|string'
        ]);

        $settings = [
            'currency' => $request->currency,
            'currency_symbol' => $request->currency_symbol,
            'decimal_places' => $request->decimal_places,
            'thousands_separator' => $request->thousands_separator,
            'decimal_separator' => $request->decimal_separator,
            'app_name' => $request->app_name,
            'app_description' => $request->app_description,
            'timezone' => $request->timezone,
            'date_format' => $request->date_format,
            'time_format' => $request->time_format,
            'updated_by' => Auth::user()->email,
            'updated_at' => now()->toISOString()
        ];

        $this->saveSettings($settings);

        return redirect()->route('admin.general-settings')
            ->with('success', 'Paramètres généraux mis à jour avec succès !');
    }

    /**
     * Réinitialiser les paramètres par défaut
     */
    public function reset(): RedirectResponse
    {
        $defaultSettings = [
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'decimal_places' => 2,
            'thousands_separator' => ' ',
            'decimal_separator' => ',',
            'app_name' => 'EVON',
            'app_description' => 'Système de gestion des bornes de recharge',
            'timezone' => 'Europe/Paris',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'updated_by' => Auth::user()->email,
            'updated_at' => now()->toISOString()
        ];

        $this->saveSettings($defaultSettings);

        return redirect()->route('admin.general-settings')
            ->with('success', 'Paramètres réinitialisés aux valeurs par défaut !');
    }

    /**
     * Obtenir les paramètres actuels
     */
    private function getSettings(): array
    {
        $defaultSettings = [
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'decimal_places' => 2,
            'thousands_separator' => ' ',
            'decimal_separator' => ',',
            'app_name' => 'EVON',
            'app_description' => 'Système de gestion des bornes de recharge',
            'timezone' => 'Europe/Paris',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        ];

        if (Storage::exists('general-settings.json')) {
            $savedSettings = json_decode(Storage::get('general-settings.json'), true);
            return array_merge($defaultSettings, $savedSettings);
        }

        return $defaultSettings;
    }

    /**
     * Sauvegarder les paramètres
     */
    private function saveSettings(array $settings): void
    {
        Storage::put('general-settings.json', json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Obtenir les devises disponibles
     */
    public function getAvailableCurrencies(): array
    {
        return [
            'EUR' => [
                'name' => 'Euro',
                'symbol' => '€',
                'code' => 'EUR',
                'decimal_separator' => ',',
                'thousands_separator' => ' '
            ],
            'USD' => [
                'name' => 'Dollar américain',
                'symbol' => '$',
                'code' => 'USD',
                'decimal_separator' => '.',
                'thousands_separator' => ','
            ],
            'MAD' => [
                'name' => 'Dirham marocain',
                'symbol' => 'MAD',
                'code' => 'MAD',
                'decimal_separator' => ',',
                'thousands_separator' => ' '
            ]
        ];
    }

    /**
     * Obtenir les fuseaux horaires disponibles
     */
    public function getAvailableTimezones(): array
    {
        return [
            'Europe/Paris' => 'Europe/Paris (France)',
            'Europe/London' => 'Europe/London (Royaume-Uni)',
            'America/New_York' => 'America/New_York (États-Unis)',
            'Africa/Casablanca' => 'Africa/Casablanca (Maroc)',
            'UTC' => 'UTC (Temps universel)'
        ];
    }
}
