<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CurrencySettingsController extends Controller
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Display the currency settings page
     */
    public function index()
    {
        $availableCurrencies = $this->currencyService->getAvailableCurrencies();
        $defaultCurrency = $this->currencyService->getDefaultCurrency();
        
        // Get enabled currencies from settings
        $enabledCurrencies = AdminSetting::getSetting('currency', 'enabled_currencies');
        if (!is_array($enabledCurrencies)) {
            $enabledCurrencies = ['MAD', 'EUR', 'USD'];
        }

        // Get exchange rates from database
        $exchangeRates = DB::table('exchange_rates')
            ->orderBy('from_currency')
            ->orderBy('to_currency')
            ->get();

        // Get API settings
        $apiProvider = AdminSetting::getSetting('currency', 'exchange_rate_api_provider') ?? 'exchangerate';
        $apiKey = AdminSetting::getSetting('currency', 'exchange_rate_api_key') ?? '';
        $autoConvert = AdminSetting::getSetting('currency', 'auto_convert_display') ?? true;

        return view('admin.settings.currency', compact(
            'availableCurrencies',
            'defaultCurrency',
            'enabledCurrencies',
            'exchangeRates',
            'apiProvider',
            'apiKey',
            'autoConvert'
        ));
    }

    /**
     * Update currency settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'default_currency' => 'required|string|size:3',
            'enabled_currencies' => 'required|array|min:1',
            'enabled_currencies.*' => 'string|size:3',
            'api_provider' => 'nullable|string|in:exchangerate,fixer,currencylayer',
            'api_key' => 'nullable|string|max:255',
            'auto_convert_display' => 'boolean',
        ]);

        try {
            // Update default currency
            $this->currencyService->setDefaultCurrency($request->default_currency);

            // Update enabled currencies
            AdminSetting::updateSetting(
                'currency',
                'enabled_currencies',
                $request->enabled_currencies,
                'json'
            );

            // Update API settings
            if ($request->filled('api_provider')) {
                AdminSetting::updateSetting(
                    'currency',
                    'exchange_rate_api_provider',
                    $request->api_provider,
                    'select'
                );
            }

            if ($request->has('api_key')) {
                AdminSetting::updateSetting(
                    'currency',
                    'exchange_rate_api_key',
                    $request->api_key,
                    'password'
                );
            }

            AdminSetting::updateSetting(
                'currency',
                'auto_convert_display',
                $request->boolean('auto_convert_display'),
                'boolean'
            );

            // Clear currency caches
            $this->currencyService->clearCache();

            Log::info('Currency settings updated', [
                'admin_id' => auth()->id(),
                'default_currency' => $request->default_currency,
            ]);

            return redirect()
                ->route('admin.settings.currency')
                ->with('success', __('Paramètres de devise mis à jour avec succès.'));

        } catch (\Exception $e) {
            Log::error('Failed to update currency settings', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', __('Erreur lors de la mise à jour des paramètres.'))
                ->withInput();
        }
    }

    /**
     * Update exchange rates manually
     */
    public function updateRates(Request $request)
    {
        $request->validate([
            'rates' => 'required|array',
            'rates.*.from' => 'required|string|size:3',
            'rates.*.to' => 'required|string|size:3',
            'rates.*.rate' => 'required|numeric|min:0.000001',
        ]);

        try {
            foreach ($request->rates as $rateData) {
                DB::table('exchange_rates')->updateOrInsert(
                    [
                        'from_currency' => $rateData['from'],
                        'to_currency' => $rateData['to'],
                    ],
                    [
                        'rate' => $rateData['rate'],
                        'source' => 'manual',
                        'fetched_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Clear exchange rate caches
            $this->currencyService->clearCache();

            Log::info('Exchange rates manually updated', [
                'admin_id' => auth()->id(),
                'count' => count($request->rates),
            ]);

            return redirect()
                ->route('admin.settings.currency')
                ->with('success', __('Taux de change mis à jour avec succès.'));

        } catch (\Exception $e) {
            Log::error('Failed to update exchange rates', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', __('Erreur lors de la mise à jour des taux.'))
                ->withInput();
        }
    }

    /**
     * Refresh exchange rates from API
     */
    public function refreshRates()
    {
        try {
            $success = $this->currencyService->refreshExchangeRates();

            if ($success) {
                return redirect()
                    ->route('admin.settings.currency')
                    ->with('success', __('Taux de change actualisés depuis l\'API.'));
            }

            return redirect()
                ->route('admin.settings.currency')
                ->with('warning', __('Impossible d\'actualiser les taux. Vérifiez la configuration API.'));

        } catch (\Exception $e) {
            Log::error('Failed to refresh exchange rates from API', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', __('Erreur lors de l\'actualisation des taux.'));
        }
    }

    /**
     * API endpoint to switch user currency
     */
    public function switchCurrency(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        $currency = strtoupper($request->currency);

        if (!$this->currencyService->isValidCurrency($currency)) {
            return response()->json([
                'success' => false,
                'message' => __('Devise invalide.'),
            ], 400);
        }

        $this->currencyService->setCurrentCurrency($currency);

        return response()->json([
            'success' => true,
            'currency' => $currency,
            'symbol' => $this->currencyService->getSymbol($currency),
            'message' => __('Devise changée avec succès.'),
        ]);
    }
}
