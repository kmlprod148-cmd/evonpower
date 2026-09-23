@extends('layouts.public')

@section('title', 'Recharge – ' . $chargingPoint->name)

@push('styles')
<style>
    :root {
        --ev-blue: #3b82f6;
        --ev-blue-dark: #1d4ed8;
        --ev-green: #10b981;
        --ev-green-dark: #059669;
    }

    /* ── Card base ── */
    .ev-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 16px rgba(0,0,0,.06);
        overflow: hidden;
    }
    .ev-card-header {
        background: linear-gradient(135deg, var(--ev-blue), var(--ev-blue-dark));
        color: #fff;
        padding: 1.25rem 1.5rem;
    }

    /* ── Step indicator ── */
    .step-indicator {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1.75rem;
    }
    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .3rem;
    }
    .step-dot {
        width: 32px; height: 32px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .8rem;
        transition: all .3s ease;
        flex-shrink: 0;
    }
    .step-dot.done   { background: var(--ev-green); color: #fff; }
    .step-dot.active { background: var(--ev-blue); color: #fff; box-shadow: 0 0 0 4px rgba(59,130,246,.2); }
    .step-dot.todo   { background: #e5e7eb; color: #9ca3af; }
    .step-label {
        font-size: .7rem; color: #6b7280;
        text-align: center; white-space: nowrap;
        transition: color .3s;
    }
    .step-label.active { color: var(--ev-blue); font-weight: 600; }
    .step-label.done   { color: var(--ev-green); }
    .step-connector {
        flex: 1; height: 2px; background: #e5e7eb;
        margin-top: 15px;
        transition: background .3s ease;
    }
    .step-connector.done { background: var(--ev-green); }

    /* ── Type selection cards ── */
    .type-card {
        border: 2px solid #e5e7eb;
        border-radius: 14px;
        padding: 1rem 1.25rem;
        cursor: pointer;
        transition: all .25s ease;
        background: #fff;
        display: flex;
        align-items: flex-start;
        gap: .875rem;
        min-height: 80px;
        position: relative;
        overflow: hidden;
        text-align: left;
        width: 100%;
    }
    .type-card::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        opacity: 0;
        transition: opacity .25s ease;
    }
    .type-card:hover {
        border-color: var(--ev-blue);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59,130,246,.12);
    }
    .type-card.active { border-color: var(--ev-blue); }
    .type-card.active::before { opacity: 1; }
    .type-card .icon-wrap {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: #f1f5f9;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: background .25s ease;
        position: relative; z-index: 1;
    }
    .type-card.active .icon-wrap { background: var(--ev-blue); }
    .type-card.active .icon-wrap svg { color: #fff; }
    .type-card .card-content { position: relative; z-index: 1; }
    .type-card .card-title { font-weight: 600; font-size: .9rem; color: #1f2937; }
    .type-card .card-desc  { font-size: .78rem; color: #6b7280; margin-top: .15rem; line-height: 1.35; }
    .type-card.active .card-title { color: var(--ev-blue-dark); }
    .type-card .check-badge {
        position: absolute; top: .5rem; right: .5rem;
        width: 18px; height: 18px; border-radius: 50%;
        background: var(--ev-blue);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transform: scale(0);
        transition: all .2s cubic-bezier(0.68,-0.55,0.265,1.55);
        z-index: 2;
    }
    .type-card.active .check-badge { opacity: 1; transform: scale(1); }

    /* ── Value preset buttons ── */
    .value-btn {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: .6rem .85rem;
        cursor: pointer;
        transition: all .2s ease;
        background: #fff;
        font-weight: 600;
        font-size: .85rem;
        min-height: 48px;
        display: flex; align-items: center; justify-content: center; gap: .2rem;
    }
    .value-btn:hover { border-color: var(--ev-blue); background: #f0f7ff; }
    .value-btn.active {
        border-color: var(--ev-blue);
        background: #eff6ff;
        color: var(--ev-blue-dark);
        box-shadow: 0 2px 8px rgba(59,130,246,.15);
    }
    .value-btn .unit { font-size: .7rem; color: #9ca3af; }
    .value-btn.active .unit { color: var(--ev-blue); }

    /* ── Cost preview (animated slide-down) ── */
    #cost-preview {
        max-height: 0;
        overflow: hidden;
        transition: max-height .4s ease, opacity .3s ease;
        opacity: 0;
    }
    #cost-preview.visible { max-height: 200px; opacity: 1; }
    .cost-inner {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: .875rem 1rem;
        margin-bottom: .75rem;
    }
    .summary-row {
        display: flex; justify-content: space-between;
        padding: .28rem 0; font-size: .875rem;
    }
    .summary-row.total {
        font-weight: 700; font-size: 1.05rem;
        border-top: 1px solid #bfdbfe;
        margin-top: .4rem; padding-top: .6rem;
    }

    /* ── Section transitions ── */
    .section-wrap { position: relative; }
    .section-panel { transition: opacity .35s ease, transform .35s ease; }
    .section-panel.hidden {
        opacity: 0; transform: translateY(10px);
        pointer-events: none;
        position: absolute; top: 0; left: 0; right: 0;
        visibility: hidden;
    }
    .section-panel.visible { opacity: 1; transform: translateY(0); }

    /* ── Stripe loading skeleton ── */
    .stripe-skeleton { display: flex; flex-direction: column; gap: .75rem; padding: .25rem 0; }
    .skel-line {
        height: 13px;
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skel-shimmer 1.4s infinite;
        border-radius: 6px;
    }
    .skel-input {
        height: 48px;
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skel-shimmer 1.4s infinite;
        border-radius: 8px;
    }
    @keyframes skel-shimmer {
        0%   { background-position: -200% 0; }
        100% { background-position:  200% 0; }
    }

    /* ── Toast notifications ── */
    #toast-container {
        position: fixed; top: 1rem; right: 1rem;
        z-index: 9999; display: flex; flex-direction: column; gap: .5rem;
        pointer-events: none;
    }
    .toast {
        display: flex; align-items: center; gap: .6rem;
        padding: .8rem 1.1rem;
        border-radius: 12px; font-size: .875rem; font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        pointer-events: all;
        transform: translateX(120%); opacity: 0;
        transition: transform .3s cubic-bezier(0.175,.885,.32,1.275), opacity .3s ease;
        max-width: 320px;
    }
    .toast.show { transform: translateX(0); opacity: 1; }
    .toast-error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .toast-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #14532d; }
    .toast-info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

    /* ── Button states ── */
    .btn-loading { opacity: .85; cursor: wait; }
    .btn-loading .btn-spinner { display: inline-flex !important; }
    .btn-loading .btn-label   { display: none !important; }
    .btn-spinner { display: none; align-items: center; gap: .4rem; }

    /* ── Helper text ── */
    #btn-helper {
        font-size: .78rem; color: #9ca3af;
        text-align: center; margin-top: .5rem;
        min-height: 1.2em; transition: color .2s;
    }
    #btn-helper.warn { color: #f59e0b; }

    /* ── Security badge row ── */
    .security-row {
        display: flex; align-items: center; justify-content: center;
        gap: .4rem; font-size: .72rem; color: #9ca3af; margin-top: .75rem;
    }

    /* ── Connector availability badge ── */
    .conn-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .7rem; border-radius: 999px;
        font-size: .75rem; font-weight: 600;
    }
    .conn-badge-available { background: rgba(16,185,129,.25); border: 1px solid rgba(16,185,129,.4); }
    .conn-badge-occupied  { background: rgba(245,158,11,.25);  border: 1px solid rgba(245,158,11,.4);  }

    /* ── Payment amount hero ── */
    .amount-hero { text-align: center; padding: .75rem 0 .5rem; }
    .amount-hero .lbl { font-size: .8rem; color: #6b7280; }
    .amount-hero .val { font-size: 2rem; font-weight: 800; color: #1f2937; line-height: 1.1; }
    .amount-hero .cur { font-size: 1rem; color: #6b7280; }

    /* ── Payment error message ── */
    #payment-message {
        display: none;
        align-items: center; gap: .5rem;
        background: #fef2f2; border: 1px solid #fecaca;
        border-radius: 10px; padding: .75rem 1rem;
        color: #991b1b; font-size: .875rem; margin-bottom: .75rem;
    }

    @media (max-width: 480px) {
        .type-card { padding: .85rem 1rem; }
        .value-btn { min-height: 52px; }
    }
</style>
@endpush

@section('content')

{{-- Toast container --}}
<div id="toast-container"></div>

<div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-lg mx-auto">

        {{-- Server-side flash messages --}}
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                {{ session('info') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Step indicator --}}
        <div class="step-indicator">
            <div class="step-item">
                <div class="step-dot done" id="step1-dot">✓</div>
                <span class="step-label done" id="step1-lbl">Inscrit</span>
            </div>
            <div class="step-connector done" id="conn1"></div>
            <div class="step-item">
                <div class="step-dot active" id="step2-dot">2</div>
                <span class="step-label active" id="step2-lbl">Configurer</span>
            </div>
            <div class="step-connector" id="conn2"></div>
            <div class="step-item">
                <div class="step-dot todo" id="step3-dot">3</div>
                <span class="step-label" id="step3-lbl">Payer</span>
            </div>
            <div class="step-connector" id="conn3"></div>
            <div class="step-item">
                <div class="step-dot todo" id="step4-dot">4</div>
                <span class="step-label" id="step4-lbl">Recharge</span>
            </div>
        </div>

        {{-- Station info card --}}
        @php
            $availableCount  = $chargingPoint->connectors->where('status', 'Available')->count();
            $totalConnectors = $chargingPoint->connectors->count();
            $stationPower    = $chargingPoint->connectors->first()?->power
                            ?? $chargingPoint->power_output
                            ?? 22;

            $hasKwh   = ($pricingPlan->price_per_kwh   ?? 0) > 0;
            $hasMin   = ($pricingPlan->price_per_minute ?? 0) > 0;
            $autoType = (!$hasKwh && $hasMin) ? 'duration' : ((!$hasMin && $hasKwh) ? 'energy' : null);
        @endphp

        <div class="ev-card mb-5">
            <div class="ev-card-header">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <svg class="w-7 h-7 opacity-90 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <div>
                            <h1 class="font-bold text-lg leading-tight">{{ $chargingPoint->name }}</h1>
                            @if($chargingPoint->address ?? $chargingPoint->location ?? null)
                                <p class="text-blue-100 text-sm mt-0.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $chargingPoint->address ?? $chargingPoint->location }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Connector availability badge --}}
                    @if($totalConnectors > 0)
                        @if($availableCount > 0)
                            <span class="conn-badge conn-badge-available flex-shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-300 animate-pulse inline-block"></span>
                                {{ $availableCount }} disponible{{ $availableCount > 1 ? 's' : '' }}
                            </span>
                        @else
                            <span class="conn-badge conn-badge-occupied flex-shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-300 inline-block"></span>
                                En cours
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            <div class="px-5 py-4 grid grid-cols-3 gap-3 text-center text-sm">
                <div class="bg-gray-50 rounded-xl py-3 px-2">
                    <p class="text-gray-400 text-xs mb-1">Puissance</p>
                    <p class="font-semibold text-gray-800">{{ $stationPower }} kW</p>
                </div>
                <div class="bg-gray-50 rounded-xl py-3 px-2">
                    <p class="text-gray-400 text-xs mb-1">Tarif</p>
                    <p class="font-semibold text-gray-800 text-xs leading-snug">
                        @if($hasKwh && $hasMin)
                            Mixte
                        @elseif($hasKwh)
                            {{ number_format($pricingPlan->price_per_kwh, 2) }} {{ $currency }}/kWh
                        @elseif($hasMin)
                            {{ number_format($pricingPlan->price_per_minute, 2) }} {{ $currency }}/min
                        @else
                            Forfait
                        @endif
                    </p>
                </div>
                <div class="bg-gray-50 rounded-xl py-3 px-2">
                    <p class="text-gray-400 text-xs mb-1">Bonjour</p>
                    <p class="font-semibold text-gray-800 truncate text-xs">{{ $client->name }}</p>
                </div>
            </div>
        </div>

        {{-- ================================================================
             Section wrapper – form ↔ Stripe swap
             ================================================================ --}}
        <div class="section-wrap">

            {{-- STEP 2 – Configure charging --}}
            <div class="section-panel visible" id="charging-form-section">
                <div class="ev-card mb-4">
                    <div class="px-5 pt-5 pb-1">
                        <h2 class="font-semibold text-gray-800 text-base mb-1">Configurez votre recharge</h2>
                        <p class="text-sm text-gray-500 mb-4">Choisissez votre mode et la quantité souhaitée</p>
                    </div>

                    {{-- ── Charging type cards ── --}}
                    <div class="px-5 mb-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Mode de recharge</p>
                        <div class="flex flex-col gap-3">
                            @if($hasKwh)
                            <button type="button" class="type-card {{ $autoType === 'energy' ? 'active' : '' }}" data-type="energy">
                                <div class="icon-wrap">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div class="card-content flex-1">
                                    <p class="card-title">Par énergie (kWh)</p>
                                    <p class="card-desc">Rechargez un nombre précis de kWh — idéal si vous connaissez l'autonomie souhaitée</p>
                                </div>
                                <div class="check-badge">
                                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                            </button>
                            @endif

                            @if($hasMin)
                            <button type="button" class="type-card {{ $autoType === 'duration' ? 'active' : '' }}" data-type="duration">
                                <div class="icon-wrap">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="card-content flex-1">
                                    <p class="card-title">Par durée (minutes)</p>
                                    <p class="card-desc">Choisissez combien de temps vous souhaitez charger votre véhicule</p>
                                </div>
                                <div class="check-badge">
                                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- ── Value selection ── --}}
                    <div id="value-section" class="px-5 mb-5" style="display:none">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3" id="value-label">Quantité</p>

                        <div id="energy-presets" class="flex flex-wrap gap-2" style="display:none!important">
                            @foreach([5, 10, 20, 30, 40, 50] as $kwh)
                                <button type="button" class="value-btn" data-value="{{ $kwh }}">
                                    <span>{{ $kwh }}</span><span class="unit">kWh</span>
                                </button>
                            @endforeach
                        </div>

                        <div id="duration-presets" class="flex flex-wrap gap-2" style="display:none!important">
                            @foreach([15, 30, 45, 60, 90, 120] as $min)
                                <button type="button" class="value-btn" data-value="{{ $min }}">
                                    <span>{{ $min }}</span><span class="unit">min</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <label class="text-xs text-gray-500">Ou saisissez une valeur personnalisée</label>
                            <div class="relative mt-1">
                                <input type="number" id="custom-value" min="1"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 pr-16 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition"
                                       placeholder="Ex: 25">
                                <span id="custom-unit" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium pointer-events-none">kWh</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1" id="custom-hint">Entrez la quantité d'énergie souhaitée (1–200 kWh)</p>
                        </div>
                    </div>

                    {{-- ── Cost preview (animated) ── --}}
                    <div id="cost-preview" class="px-5">
                        <div class="cost-inner">
                            <div class="summary-row">
                                <span class="text-gray-500">Montant HT</span>
                                <span id="cost-ht" class="font-medium text-gray-700">–</span>
                            </div>
                            <div class="summary-row">
                                <span class="text-gray-500">TVA ({{ $pricingPlan->vatRate?->rate ?? $pricingPlan->vat_rate ?? 0 }}%)</span>
                                <span id="cost-vat" class="font-medium text-gray-700">–</span>
                            </div>
                            <div class="summary-row total">
                                <span class="text-gray-800">Total TTC</span>
                                <span id="cost-ttc" class="text-blue-700">–</span>
                            </div>
                        </div>
                    </div>

                    {{-- ── Proceed button ── --}}
                    <div class="px-5 pb-5">
                        <button type="button" id="btn-proceed-payment"
                                class="w-full py-3.5 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                disabled>
                            <span class="btn-label flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                                Procéder au paiement
                            </span>
                            <span class="btn-spinner">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Initialisation…
                            </span>
                        </button>

                        <p id="btn-helper">Sélectionnez un mode de recharge pour continuer</p>

                        <div class="security-row">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                            <span>Paiement sécurisé</span>
                            <span>·</span>
                            <span>SSL 256-bit</span>
                            <span>·</span>
                            <span>Stripe</span>
                        </div>
                    </div>
                </div>
            </div>{{-- /charging-form-section --}}

            {{-- STEP 3 – Stripe payment --}}
            <div class="section-panel hidden" id="stripe-payment-section">
                <div class="ev-card mb-4">
                    <div class="px-5 pt-5 pb-5">

                        {{-- Amount hero --}}
                        <div class="amount-hero mb-4 pb-4 border-b border-gray-100">
                            <p class="lbl mb-1">Montant à régler</p>
                            <p class="val"><span id="hero-amount">–</span> <span class="cur">{{ $currency }}</span></p>
                            <p class="text-xs text-gray-400 mt-1" id="hero-summary">–</p>
                        </div>

                        <h2 class="font-semibold text-gray-800 mb-1">Paiement sécurisé</h2>
                        <p class="text-sm text-gray-500 mb-4">Vos coordonnées bancaires sont chiffrées et ne transitent jamais par nos serveurs</p>

                        {{-- Stripe skeleton --}}
                        <div id="stripe-skeleton" class="stripe-skeleton mb-4">
                            <div class="skel-line" style="width:40%"></div>
                            <div class="skel-input"></div>
                            <div class="flex gap-3">
                                <div class="skel-input flex-1"></div>
                                <div class="skel-input flex-1"></div>
                            </div>
                        </div>

                        <form id="payment-form">
                            @csrf
                            <div id="payment-element" class="mb-4" style="display:none"></div>

                            <div id="payment-message">
                                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                <span id="payment-message-text"></span>
                            </div>

                            <button type="submit" id="submit-payment"
                                    class="w-full py-3.5 rounded-xl font-semibold text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 transition flex items-center justify-center gap-2">
                                <span class="btn-label flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                    Payer <span id="btn-amount"></span> {{ $currency }}
                                </span>
                                <span class="btn-spinner">
                                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Traitement en cours…
                                </span>
                            </button>
                        </form>

                        <button type="button" id="btn-back-offer"
                                class="mt-3 w-full py-3 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-xl transition flex items-center justify-center gap-1.5 border border-gray-200 min-h-[48px]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Modifier ma sélection
                        </button>

                        <div class="security-row">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                            <span>Stripe · PCI DSS · Données jamais stockées</span>
                        </div>
                    </div>
                </div>
            </div>{{-- /stripe-payment-section --}}

        </div>{{-- /section-wrap --}}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
(function () {
    'use strict';

    // ─────────────────────────────────────────────────────────────────────────
    // Server-injected configuration
    // ─────────────────────────────────────────────────────────────────────────
    const STRIPE_KEY = @json($stripePublicKey ?? '');
    const CSRF       = '{{ csrf_token() }}';
    const PAY_URL    = '{{ route('client.charging.pay', ['id' => $chargingPoint->id]) }}';
    const PRICING    = {
        activationFee : {{ (float)($pricingPlan->activation_fee ?? 0) }},
        baseRate      : {{ (float)($pricingPlan->base_rate ?? 0) }},
        pricePerKwh   : {{ (float)($pricingPlan->price_per_kwh ?? 0) }},
        pricePerMin   : {{ (float)($pricingPlan->price_per_minute ?? 0) }},
        vatRate       : {{ (float)($pricingPlan->vatRate?->rate ?? $pricingPlan->vat_rate ?? 0) }},
        currency      : '{{ $currency }}',
    };
    const AUTO_TYPE  = @json($autoType ?? null); // 'energy' | 'duration' | null

    // ─────────────────────────────────────────────────────────────────────────
    // State
    // ─────────────────────────────────────────────────────────────────────────
    let selectedType  = null;
    let selectedValue = null;
    let estimatedCost = 0;
    let stripe        = null;
    let elements      = null;
    let paymentElement = null;
    let reservationId = null;
    let confirmUrl    = null;
    let costAnimFrame = null;
    let debounceTimer = null;

    // ─────────────────────────────────────────────────────────────────────────
    // DOM references
    // ─────────────────────────────────────────────────────────────────────────
    const $typeCards        = document.querySelectorAll('.type-card');
    const $valueSection     = document.getElementById('value-section');
    const $energyPresets    = document.getElementById('energy-presets');
    const $durationPresets  = document.getElementById('duration-presets');
    const $customValue      = document.getElementById('custom-value');
    const $customUnit       = document.getElementById('custom-unit');
    const $customHint       = document.getElementById('custom-hint');
    const $valueLabel       = document.getElementById('value-label');
    const $costPreview      = document.getElementById('cost-preview');
    const $costHT           = document.getElementById('cost-ht');
    const $costVat          = document.getElementById('cost-vat');
    const $costTTC          = document.getElementById('cost-ttc');
    const $btnProceed       = document.getElementById('btn-proceed-payment');
    const $btnHelper        = document.getElementById('btn-helper');
    const $formSection      = document.getElementById('charging-form-section');
    const $stripeSection    = document.getElementById('stripe-payment-section');
    const $paymentForm      = document.getElementById('payment-form');
    const $paymentMessage   = document.getElementById('payment-message');
    const $paymentMsgText   = document.getElementById('payment-message-text');
    const $submitPayment    = document.getElementById('submit-payment');
    const $btnAmount        = document.getElementById('btn-amount');
    const $heroAmount       = document.getElementById('hero-amount');
    const $heroSummary      = document.getElementById('hero-summary');
    const $btnBack          = document.getElementById('btn-back-offer');
    const $stripeSkeleton   = document.getElementById('stripe-skeleton');
    const $paymentEl        = document.getElementById('payment-element');
    const $toastContainer   = document.getElementById('toast-container');

    // Step indicator elements (1-indexed)
    const $dots  = [null, ...Array.from({length:4}, (_, i) => document.getElementById(`step${i+1}-dot`))];
    const $lbls  = [null, ...Array.from({length:4}, (_, i) => document.getElementById(`step${i+1}-lbl`))];
    const $conns = [null, ...Array.from({length:3}, (_, i) => document.getElementById(`conn${i+1}`))];

    // ─────────────────────────────────────────────────────────────────────────
    // Toast system – replaces all alert() calls
    // ─────────────────────────────────────────────────────────────────────────
    const TOAST_ICONS = {
        error  : '<svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
        success: '<svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
        info   : '<svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>',
    };

    function showToast(message, type = 'error', duration = 4500) {
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.innerHTML = (TOAST_ICONS[type] || '') + `<span>${message}</span>`;
        $toastContainer.appendChild(t);
        // Double rAF ensures transition plays after element is in the DOM
        requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
        setTimeout(() => {
            t.classList.remove('show');
            setTimeout(() => t.remove(), 350);
        }, duration);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────
    function fmt(n) { return parseFloat(n).toFixed(2); }
    function round2(n) { return Math.round(n * 100) / 100; }

    function computeCost(type, value) {
        let ht = PRICING.activationFee + PRICING.baseRate;
        if (type === 'energy')   ht += value * PRICING.pricePerKwh;
        if (type === 'duration') ht += value * PRICING.pricePerMin;
        const vat   = ht * (PRICING.vatRate / 100);
        return { ht: round2(ht), vat: round2(vat), total: round2(ht + vat) };
    }

    // Smooth number counter animation on the TTC field
    function animateCounter(el, from, to, duration = 550) {
        if (costAnimFrame) cancelAnimationFrame(costAnimFrame);
        const start = performance.now();
        const range = to - from;
        function step(now) {
            const t = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - t, 3); // ease-out cubic
            el.textContent = fmt(from + range * eased) + ' ' + PRICING.currency;
            if (t < 1) costAnimFrame = requestAnimationFrame(step);
        }
        costAnimFrame = requestAnimationFrame(step);
    }

    function setHelper(text, warn = false) {
        $btnHelper.textContent = text;
        $btnHelper.classList.toggle('warn', warn);
    }

    function setProceedLoading(loading) {
        $btnProceed.classList.toggle('btn-loading', loading);
        $btnProceed.disabled = loading;
    }

    function setPayLoading(loading) {
        $submitPayment.classList.toggle('btn-loading', loading);
        $submitPayment.disabled = loading;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step indicator
    // ─────────────────────────────────────────────────────────────────────────
    function activateStep(step) {
        for (let i = 1; i <= 4; i++) {
            const dot = $dots[i], lbl = $lbls[i];
            if (!dot) continue;
            dot.classList.remove('done', 'active', 'todo');
            lbl.classList.remove('done', 'active');
            if (i < step) {
                dot.classList.add('done'); dot.textContent = '✓'; lbl.classList.add('done');
            } else if (i === step) {
                dot.classList.add('active'); dot.textContent = i; lbl.classList.add('active');
            } else {
                dot.classList.add('todo'); dot.textContent = i;
            }
        }
        for (let i = 1; i <= 3; i++) {
            if ($conns[i]) $conns[i].classList.toggle('done', i < step);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Section swap (fade + slide)
    // ─────────────────────────────────────────────────────────────────────────
    function showSection(showEl, hideEl) {
        hideEl.classList.replace('visible', 'hidden');
        showEl.classList.replace('hidden', 'visible');
        showEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Type selection
    // ─────────────────────────────────────────────────────────────────────────
    function selectType(type) {
        $typeCards.forEach(c => c.classList.toggle('active', c.dataset.type === type));
        selectedType  = type;
        selectedValue = null;
        document.querySelectorAll('.value-btn').forEach(b => b.classList.remove('active'));
        $customValue.value = '';
        $valueSection.style.display = 'block';

        if (type === 'energy') {
            $energyPresets.style.setProperty('display', 'flex', 'important');
            $durationPresets.style.setProperty('display', 'none', 'important');
            $valueLabel.textContent = 'Quantité d\'énergie';
            $customUnit.textContent = 'kWh';
            $customHint.textContent = 'Entrez la quantité souhaitée (1–200 kWh)';
        } else {
            $energyPresets.style.setProperty('display', 'none', 'important');
            $durationPresets.style.setProperty('display', 'flex', 'important');
            $valueLabel.textContent = 'Durée de charge';
            $customUnit.textContent = 'min';
            $customHint.textContent = 'Entrez la durée souhaitée (1–480 min)';
        }

        $costPreview.classList.remove('visible');
        $btnProceed.disabled = true;
        setHelper('Sélectionnez une valeur pour continuer');
    }

    $typeCards.forEach(card => card.addEventListener('click', () => selectType(card.dataset.type)));

    // ─────────────────────────────────────────────────────────────────────────
    // Value selection
    // ─────────────────────────────────────────────────────────────────────────
    document.querySelectorAll('.value-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.value-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            $customValue.value = '';
            selectedValue = parseFloat(btn.dataset.value);
            updateCostPreview();
        });
    });

    $customValue.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const v = parseFloat($customValue.value);
            if (v > 0) {
                document.querySelectorAll('.value-btn').forEach(b => b.classList.remove('active'));
                selectedValue = v;
                updateCostPreview();
            } else {
                selectedValue = null;
                $costPreview.classList.remove('visible');
                $btnProceed.disabled = true;
                setHelper('Saisissez une valeur valide pour continuer');
            }
        }, 250);
    });

    function updateCostPreview() {
        if (!selectedType || !selectedValue) return;
        const c = computeCost(selectedType, selectedValue);
        const prev = estimatedCost;
        estimatedCost = c.total;

        $costHT.textContent  = fmt(c.ht)  + ' ' + PRICING.currency;
        $costVat.textContent = fmt(c.vat) + ' ' + PRICING.currency;
        animateCounter($costTTC, prev, c.total);
        $costPreview.classList.add('visible');

        if (c.total < 0.50) {
            $btnProceed.disabled = true;
            setHelper('Montant minimum : 0,50 ' + PRICING.currency, true);
        } else {
            $btnProceed.disabled = false;
            setHelper('');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // "Proceed to payment" – create Reservation + Stripe PaymentIntent
    // ─────────────────────────────────────────────────────────────────────────
    $btnProceed.addEventListener('click', async () => {
        if (!selectedType || !selectedValue || estimatedCost < 0.50) return;

        setProceedLoading(true);

        try {
            const res  = await fetch(PAY_URL, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body   : JSON.stringify({ type: selectedType, value: selectedValue }),
            });
            const data = await res.json();

            if (!data.success) {
                setProceedLoading(false);
                showToast(data.error || 'Une erreur est survenue. Veuillez réessayer.', 'error');
                return;
            }

            reservationId = data.reservation_id;
            confirmUrl    = data.confirm_url;

            // Update payment hero
            $heroAmount.textContent = parseFloat(data.amount).toFixed(2);
            $btnAmount.textContent  = parseFloat(data.amount).toFixed(2);
            const typeLabel = selectedType === 'energy'
                ? `${selectedValue} kWh · Énergie`
                : `${selectedValue} min · Durée`;
            $heroSummary.textContent = typeLabel;

            // Advance step indicator then swap to Stripe section
            activateStep(3);
            showSection($stripeSection, $formSection);

            await mountStripeElements(data.client_secret);

        } catch (err) {
            console.error(err);
            setProceedLoading(false);
            showToast('Erreur réseau. Vérifiez votre connexion et réessayez.', 'error');
        }
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Mount Stripe Elements with skeleton
    // ─────────────────────────────────────────────────────────────────────────
    async function mountStripeElements(clientSecret) {
        if (!STRIPE_KEY) {
            showToast('Configuration Stripe manquante. Contactez le support.', 'error');
            $stripeSkeleton.style.display = 'none';
            $submitPayment.disabled = true;
            return;
        }

        $stripeSkeleton.style.display = 'flex';
        $paymentEl.style.display = 'none';

        stripe   = Stripe(STRIPE_KEY);
        elements = stripe.elements({ clientSecret });
        paymentElement = elements.create('payment', { layout: 'tabs' });

        // Hide skeleton + reveal element once Stripe iframe is ready
        paymentElement.on('ready', () => {
            $stripeSkeleton.style.display = 'none';
            $paymentEl.style.display = 'block';
            setProceedLoading(false); // re-enable (not needed again, but clean)
        });

        paymentElement.mount('#payment-element');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stripe payment form submit
    // ─────────────────────────────────────────────────────────────────────────
    $paymentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!stripe || !elements) return;

        setPayLoading(true);
        $paymentMessage.style.display = 'none';

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: { return_url: confirmUrl },
        });

        // confirmPayment only resolves without redirect when there's an error
        if (error) {
            $paymentMsgText.textContent = error.message ?? 'Une erreur est survenue lors du paiement.';
            $paymentMessage.style.display = 'flex';
            if (error.type !== 'validation_error') {
                showToast(error.message ?? 'Échec du paiement.', 'error');
            }
        }

        setPayLoading(false);
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Back button – destroy Stripe instance cleanly, restore form step
    // ─────────────────────────────────────────────────────────────────────────
    $btnBack.addEventListener('click', () => {
        if (paymentElement) {
            paymentElement.destroy();
            paymentElement = null;
            stripe   = null;
            elements = null;
        }
        $paymentEl.style.display      = 'none';
        $stripeSkeleton.style.display = 'none';
        $paymentMessage.style.display = 'none';

        activateStep(2);
        showSection($formSection, $stripeSection);
        $btnProceed.disabled = (estimatedCost < 0.50);
        if (estimatedCost >= 0.50) setHelper('');
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Init – auto-select type when only one pricing mode exists
    // ─────────────────────────────────────────────────────────────────────────
    if (AUTO_TYPE) {
        selectType(AUTO_TYPE);
    } else {
        setHelper('Sélectionnez un mode de recharge pour continuer');
    }

})();
</script>
@endpush
