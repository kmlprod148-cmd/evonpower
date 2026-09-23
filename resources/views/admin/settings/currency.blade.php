@extends('layouts.app')

@section('title', __('Paramètres de Devise'))

@push('styles')
<style>
    .currency-settings {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.5rem;
    }
    
    .currency-header {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        border-radius: 1rem;
        padding: 1.5rem 2rem;
        color: white;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .currency-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    
    .dark .currency-card {
        background: #1f2937;
    }
    
    .currency-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .dark .currency-card-header {
        border-color: #374151;
    }
    
    .currency-card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .dark .currency-card-title {
        color: #f9fafb;
    }
    
    .currency-card-title svg {
        width: 1.25rem;
        height: 1.25rem;
        color: #059669;
    }
    
    .currency-card-body {
        padding: 1.5rem;
    }
    
    .currency-option {
        display: flex;
        align-items: center;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        margin-bottom: 0.75rem;
    }
    
    .dark .currency-option {
        border-color: #374151;
    }
    
    .currency-option:hover {
        border-color: #059669;
    }
    
    .currency-option.selected {
        border-color: #059669;
        background: #f0fdf4;
    }
    
    .dark .currency-option.selected {
        background: rgba(5, 150, 105, 0.1);
    }
    
    .currency-option input[type="radio"],
    .currency-option input[type="checkbox"] {
        display: none;
    }
    
    .currency-flag {
        font-size: 1.5rem;
        margin-right: 1rem;
    }
    
    .currency-info {
        flex: 1;
    }
    
    .currency-name {
        font-weight: 600;
        color: #111827;
    }
    
    .dark .currency-name {
        color: #f9fafb;
    }
    
    .currency-code {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .currency-symbol {
        font-size: 1.25rem;
        font-weight: 700;
        color: #059669;
        margin-left: auto;
        padding-left: 1rem;
    }
    
    .rate-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }
    
    .rate-item {
        background: #f9fafb;
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .dark .rate-item {
        background: #374151;
    }
    
    .rate-pair {
        font-weight: 500;
        color: #374151;
        min-width: 100px;
    }
    
    .dark .rate-pair {
        color: #d1d5db;
    }
    
    .rate-input {
        flex: 1;
        padding: 0.5rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.9375rem;
    }
    
    .dark .rate-input {
        background: #1f2937;
        border-color: #4b5563;
        color: #f9fafb;
    }
    
    .rate-input:focus {
        outline: none;
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
    }
    
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    }
    
    .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: #f3f4f6;
        color: #374151;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .dark .btn-secondary {
        background: #374151;
        color: #f9fafb;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .dark .btn-secondary:hover {
        background: #4b5563;
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .dark .form-label {
        color: #d1d5db;
    }
    
    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.9375rem;
    }
    
    .dark .form-input {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
    }
    
    .alert {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    
    .dark .alert-success {
        background: rgba(5, 150, 105, 0.2);
        border-color: rgba(5, 150, 105, 0.3);
        color: #34d399;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    
    .alert-warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    
    .toggle-switch {
        position: relative;
        width: 48px;
        height: 26px;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: 0.3s;
        border-radius: 26px;
    }
    
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }
    
    input:checked + .toggle-slider {
        background-color: #059669;
    }
    
    input:checked + .toggle-slider:before {
        transform: translateX(22px);
    }

    .actions-bar {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
        margin-top: 1.5rem;
    }
    
    .dark .actions-bar {
        border-color: #374151;
    }
</style>
@endpush

@section('content')
<div class="currency-settings">
    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-error">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif
    
    @if(session('warning'))
        <div class="alert alert-warning">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ session('warning') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="currency-header">
        <div>
            <h1 class="text-2xl font-bold">{{ __('messages.currency_settings') }}</h1>
            <p class="text-white/80 mt-1">{{ __('messages.configure_currency') }}</p>
        </div>
        <form action="{{ route('admin.settings.currency.refresh-rates') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="btn-secondary" style="background: rgba(255,255,255,0.2); color: white;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ __('messages.refresh_rates') }}
            </button>
        </form>
    </div>

    <form action="{{ route('admin.settings.currency.update') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Default Currency --}}
        <div class="currency-card">
            <div class="currency-card-header">
                <h2 class="currency-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('messages.default_currency') }}
                </h2>
            </div>
            <div class="currency-card-body">
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('messages.select_main_currency') }}
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($availableCurrencies as $code => $currency)
                    <label class="currency-option {{ $defaultCurrency === $code ? 'selected' : '' }}" data-currency="{{ $code }}">
                        <input type="radio" name="default_currency" value="{{ $code }}" {{ $defaultCurrency === $code ? 'checked' : '' }}>
                        <span class="currency-flag">{{ $currency['flag'] }}</span>
                        <div class="currency-info">
                            <div class="currency-name">{{ $currency['name'] }}</div>
                            <div class="currency-code">{{ $code }}</div>
                        </div>
                        <span class="currency-symbol">{{ $currency['symbol'] }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Enabled Currencies --}}
        <div class="currency-card">
            <div class="currency-card-header">
                <h2 class="currency-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('messages.enabled_currencies') }}
                </h2>
            </div>
            <div class="currency-card-body">
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('messages.select_available_currencies') }}
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($availableCurrencies as $code => $currency)
                    <label class="currency-option {{ in_array($code, $enabledCurrencies) ? 'selected' : '' }}" data-currency-checkbox="{{ $code }}">
                        <input type="checkbox" name="enabled_currencies[]" value="{{ $code }}" {{ in_array($code, $enabledCurrencies) ? 'checked' : '' }}>
                        <span class="currency-flag">{{ $currency['flag'] }}</span>
                        <div class="currency-info">
                            <div class="currency-name">{{ $currency['name'] }}</div>
                            <div class="currency-code">{{ $code }}</div>
                        </div>
                        <span class="currency-symbol">{{ $currency['symbol'] }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- API Settings --}}
        <div class="currency-card">
            <div class="currency-card-header">
                <h2 class="currency-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ __('messages.api_configuration') }}
                </h2>
            </div>
            <div class="currency-card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">{{ __('messages.api_provider') }}</label>
                        <select name="api_provider" class="form-input">
                            <option value="exchangerate" {{ $apiProvider === 'exchangerate' ? 'selected' : '' }}>ExchangeRate-API</option>
                            <option value="fixer" {{ $apiProvider === 'fixer' ? 'selected' : '' }}>Fixer.io</option>
                            <option value="currencylayer" {{ $apiProvider === 'currencylayer' ? 'selected' : '' }}>CurrencyLayer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">{{ __('messages.api_key') }}</label>
                        <input type="password" name="api_key" class="form-input" value="{{ $apiKey }}" placeholder="{{ __('messages.enter_api_key') }}">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1rem;">
                    <div class="flex items-center gap-3">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_convert_display" value="1" {{ $autoConvert ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                        <div>
                            <span class="form-label" style="margin-bottom: 0;">{{ __('messages.auto_conversion') }}</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.auto_convert_display_prices') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="actions-bar">
            <a href="{{ route('admin.settings.index') }}" class="btn-secondary">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ __('messages.save_settings') }}
            </button>
        </div>
    </form>

    {{-- Exchange Rates --}}
    <div class="currency-card">
        <div class="currency-card-header">
            <h2 class="currency-card-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                {{ __('messages.exchange_rates') }}
            </h2>
        </div>
        <div class="currency-card-body">
            <form action="{{ route('admin.settings.currency.update-rates') }}" method="POST">
                @csrf
                
                <div class="rate-grid">
                    @foreach($exchangeRates as $index => $rate)
                    <div class="rate-item">
                        <input type="hidden" name="rates[{{ $index }}][from]" value="{{ $rate->from_currency }}">
                        <input type="hidden" name="rates[{{ $index }}][to]" value="{{ $rate->to_currency }}">
                        <span class="rate-pair">{{ $rate->from_currency }} → {{ $rate->to_currency }}</span>
                        <input type="number" name="rates[{{ $index }}][rate]" class="rate-input" value="{{ $rate->rate }}" step="0.000001" min="0">
                    </div>
                    @endforeach
                </div>
                
                <div class="actions-bar">
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('messages.update_rates') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Radio button selection
        document.querySelectorAll('[data-currency]').forEach(function(el) {
            el.addEventListener('click', function() {
                document.querySelectorAll('[data-currency]').forEach(function(item) {
                    item.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Checkbox selection
        document.querySelectorAll('[data-currency-checkbox]').forEach(function(el) {
            el.addEventListener('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                }
                this.classList.toggle('selected', this.querySelector('input[type="checkbox"]').checked);
            });
        });
    });
</script>
@endpush
