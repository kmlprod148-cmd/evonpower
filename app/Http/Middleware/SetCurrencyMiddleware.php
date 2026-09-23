<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CurrencyService;
use Symfony\Component\HttpFoundation\Response;

class SetCurrencyMiddleware
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if currency is being set via request parameter
        if ($request->has('currency')) {
            $requestCurrency = strtoupper($request->input('currency'));
            if ($this->currencyService->isValidCurrency($requestCurrency)) {
                $this->currencyService->setCurrentCurrency($requestCurrency);
            }
        }

        // If no currency in session, set the default
        if (!session()->has('currency')) {
            $defaultCurrency = $this->currencyService->getDefaultCurrency();
            session(['currency' => $defaultCurrency]);
        }

        // Share currency data with all views
        $currentCurrency = $this->currencyService->getCurrentCurrency();
        $currencyConfig = $this->currencyService->getCurrencyConfig($currentCurrency);
        
        view()->share('currentCurrency', $currentCurrency);
        view()->share('currencySymbol', $currencyConfig['symbol'] ?? $currentCurrency);
        view()->share('currencyConfig', $currencyConfig);
        view()->share('availableCurrencies', $this->currencyService->getEnabledCurrencies());

        return $next($request);
    }
}
