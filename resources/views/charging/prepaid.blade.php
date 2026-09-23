@extends('layouts.app')

@section('title', 'Recharge Prépayée – ' . $chargingPoint->name)

@push('styles')
<style>
:root {
    --ev-green:      #10b981;
    --ev-green-dark: #059669;
    --ev-blue:       #3b82f6;
    --ev-blue-dark:  #1d4ed8;
    --ev-yellow:     #f59e0b;
}

/* ─── Card ─────────────────────────────────────────────── */
.ev-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 20px rgba(0,0,0,.06);
    overflow: hidden;
}
.ev-card-header {
    background: linear-gradient(135deg, var(--ev-green), var(--ev-green-dark));
    color: #fff;
    padding: 1.25rem 1.5rem;
}

/* ─── Mode Toggle ───────────────────────────────────────── */
.mode-toggle { display: flex; gap: .5rem; background: #f3f4f6; border-radius: 10px; padding: 4px; }
.mode-btn {
    flex: 1; padding: .5rem 1rem; border-radius: 8px; border: none;
    font-size: .85rem; font-weight: 600; cursor: pointer; transition: all .25s;
    color: #6b7280; background: transparent;
}
.mode-btn.active { background: #fff; color: var(--ev-blue); box-shadow: 0 1px 4px rgba(0,0,0,.12); }

/* ─── Value selector ────────────────────────────────────── */
.value-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
.chip {
    padding: .4rem .9rem; border-radius: 8px; border: 2px solid #e5e7eb;
    background: #f9fafb; font-size: .9rem; font-weight: 600; cursor: pointer;
    transition: all .2s; color: #374151;
}
.chip:hover { border-color: var(--ev-green); color: var(--ev-green); }
.chip.selected { border-color: var(--ev-green); background: #ecfdf5; color: var(--ev-green-dark); }

/* ─── Cost display ──────────────────────────────────────── */
.cost-display {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border: 2px solid var(--ev-green);
    border-radius: 12px; padding: 1rem 1.25rem; text-align: center;
}
.cost-display .cost-value { font-size: 2rem; font-weight: 800; color: var(--ev-green-dark); }
.cost-display .cost-label { font-size: .8rem; color: #6b7280; margin-top: .1rem; }

/* ─── Wallet badge ──────────────────────────────────────── */
.wallet-badge {
    display: flex; align-items: center; gap: .6rem;
    background: #eff6ff; border: 1px solid #bfdbfe;
    border-radius: 10px; padding: .65rem 1rem;
}
.wallet-badge.insufficient { background: #fef2f2; border-color: #fecaca; }
.wallet-balance { font-size: 1.15rem; font-weight: 700; color: var(--ev-blue-dark); }
.wallet-badge.insufficient .wallet-balance { color: #dc2626; }

/* ─── Submit button ─────────────────────────────────────── */
.btn-start {
    width: 100%; padding: .9rem; border-radius: 12px; border: none;
    font-size: 1rem; font-weight: 700; cursor: pointer;
    background: linear-gradient(135deg, var(--ev-green), var(--ev-green-dark));
    color: #fff; transition: all .25s;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.btn-start:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16,185,129,.35); }
.btn-start:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }

/* ─── Pricing table ─────────────────────────────────────── */
.price-row { display: flex; justify-content: space-between; padding: .4rem 0; border-bottom: 1px solid #f3f4f6; font-size: .88rem; }
.price-row:last-child { border-bottom: none; }
.price-key { color: #6b7280; }
.price-val { font-weight: 600; color: #111827; }
</style>
@endpush

@section('content')
<div class="max-w-xl mx-auto px-4 py-8">

    {{-- ── Fil d'Ariane ─────────────────────────────────────── --}}
    <nav class="text-sm text-gray-500 mb-5 flex items-center gap-2">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Tableau de bord</a>
        <span>/</span>
        <a href="{{ route('charging-points.show', $chargingPoint) }}" class="hover:text-gray-800 transition">{{ $chargingPoint->name }}</a>
        <span>/</span>
        <span class="text-gray-800 font-medium">Recharge Prépayée</span>
    </nav>

    {{-- ── Alertes session ──────────────────────────────────── --}}
    @if(session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm font-medium">
            ❌ {{ session('error') }}
        </div>
    @endif

    {{-- ── Carte principale ────────────────────────────────── --}}
    <div class="ev-card">
        <div class="ev-card-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-xl">⚡</div>
                <div>
                    <div class="font-bold text-lg">{{ $chargingPoint->name }}</div>
                    <div class="text-white/80 text-sm">{{ $chargingPoint->address ?? $chargingPoint->city ?? 'Borne de recharge' }}</div>
                </div>
                <span class="ml-auto px-2 py-1 rounded-full text-xs font-semibold
                    {{ $chargingPoint->status === 'online' ? 'bg-white/30 text-white' : 'bg-red-400/80 text-white' }}">
                    {{ $chargingPoint->status === 'online' ? '● En ligne' : '● Hors ligne' }}
                </span>
            </div>
        </div>

        <div class="p-5 space-y-5">

            {{-- Wallet ─────────────────────────────────────── --}}
            <div class="wallet-badge {{ $walletBalance < 1 ? 'insufficient' : '' }}" id="walletBadge">
                <div class="text-2xl">👛</div>
                <div>
                    <div class="text-xs text-gray-500 font-medium">Votre solde wallet</div>
                    <div class="wallet-balance" id="walletBalance">{{ number_format($walletBalance, 2) }} {{ $currency }}</div>
                </div>
                @if($walletBalance < 1)
                    <a href="{{ route('credit-recharge.index') }}"
                       class="ml-auto text-xs text-blue-600 font-semibold underline">
                       Recharger
                    </a>
                @endif
            </div>

            {{-- Mode (énergie / durée) ───────────────────── --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Mode de recharge</label>
                <div class="mode-toggle">
                    <button type="button" class="mode-btn active" id="btnEnergy" onclick="setMode('energy')">
                        ⚡ Par énergie (kWh)
                    </button>
                    <button type="button" class="mode-btn" id="btnDuration" onclick="setMode('duration')">
                        ⏱ Par durée (min)
                    </button>
                </div>
            </div>

            {{-- Sélection rapide ─────────────────────────── --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2" id="chipLabel">
                    Quantité d'énergie
                </label>
                <div class="value-chips" id="chipGroup">
                    {{-- Energy chips --}}
                    <div id="energyChips" class="value-chips w-full">
                        <button type="button" class="chip" onclick="selectChip(5,  'energy')">5 kWh</button>
                        <button type="button" class="chip" onclick="selectChip(10, 'energy')">10 kWh</button>
                        <button type="button" class="chip" onclick="selectChip(20, 'energy')">20 kWh</button>
                        <button type="button" class="chip" onclick="selectChip(40, 'energy')">40 kWh</button>
                        <button type="button" class="chip" onclick="selectChip(60, 'energy')">60 kWh</button>
                    </div>
                    {{-- Duration chips (hidden) --}}
                    <div id="durationChips" class="value-chips w-full hidden">
                        <button type="button" class="chip" onclick="selectChip(15,  'duration')">15 min</button>
                        <button type="button" class="chip" onclick="selectChip(30,  'duration')">30 min</button>
                        <button type="button" class="chip" onclick="selectChip(60,  'duration')">1 h</button>
                        <button type="button" class="chip" onclick="selectChip(120, 'duration')">2 h</button>
                        <button type="button" class="chip" onclick="selectChip(240, 'duration')">4 h</button>
                    </div>
                </div>

                {{-- Saisie manuelle --}}
                <div class="mt-3 flex items-center gap-2">
                    <input type="number"
                           id="manualValue"
                           min="0.1" step="0.1"
                           placeholder="Ou saisissez une valeur…"
                           class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-green-400"
                           oninput="onManualInput(this.value)" />
                    <span class="text-sm text-gray-500 w-10" id="unitLabel">kWh</span>
                </div>
            </div>

            {{-- Coût estimé ──────────────────────────────── --}}
            <div class="cost-display">
                <div class="cost-value" id="costDisplay">0.00 {{ $currency }}</div>
                <div class="cost-label">Coût estimé (TVA incluse)</div>
            </div>

            {{-- Tarification ─────────────────────────────── --}}
            @if($pricingPlan)
            <details class="text-sm">
                <summary class="cursor-pointer text-gray-500 hover:text-gray-800 font-medium">
                    Détail de la tarification
                </summary>
                <div class="mt-2 space-y-0">
                    @if($pricingPlan->activation_fee > 0)
                    <div class="price-row">
                        <span class="price-key">Frais d'activation</span>
                        <span class="price-val">{{ number_format($pricingPlan->activation_fee, 2) }} {{ $currency }}</span>
                    </div>
                    @endif
                    @if($pricingPlan->price_per_kwh > 0)
                    <div class="price-row">
                        <span class="price-key">Prix par kWh</span>
                        <span class="price-val">{{ number_format($pricingPlan->price_per_kwh, 4) }} {{ $currency }}</span>
                    </div>
                    @endif
                    @if($pricingPlan->price_per_minute > 0)
                    <div class="price-row">
                        <span class="price-key">Prix par minute</span>
                        <span class="price-val">{{ number_format($pricingPlan->price_per_minute, 4) }} {{ $currency }}</span>
                    </div>
                    @endif
                    @if(($pricingPlan->vatRate?->rate ?? $pricingPlan->vat_rate ?? 0) > 0)
                    <div class="price-row">
                        <span class="price-key">TVA</span>
                        <span class="price-val">{{ $pricingPlan->vatRate?->rate ?? $pricingPlan->vat_rate }}%</span>
                    </div>
                    @endif
                </div>
            </details>
            @endif

            {{-- Formulaire de démarrage ──────────────────── --}}
            <form method="POST"
                  action="{{ route('charging.prepaid.start', $chargingPoint) }}"
                  id="prepaidForm">
                @csrf
                <input type="hidden" name="type"  id="hiddenType"  value="energy" />
                <input type="hidden" name="value" id="hiddenValue" value="" />

                <button type="submit"
                        class="btn-start"
                        id="btnSubmit"
                        disabled>
                    <span id="btnIcon">⚡</span>
                    <span id="btnText">Sélectionnez une quantité</span>
                </button>
            </form>

            <p class="text-center text-xs text-gray-400">
                Le montant sera débité immédiatement de votre wallet.
                Tout excédent vous sera recrédité à la fin de la session.
            </p>

        </div>{{-- /p-5 --}}
    </div>{{-- /ev-card --}}

    {{-- Lien vers postpayé ───────────────────────────────── --}}
    <div class="mt-4 text-center">
        <p class="text-sm text-gray-500">
            Vous préférez payer après la recharge ?
            <a href="{{ route('charging.postpaid.show', $chargingPoint) }}"
               class="text-blue-600 font-semibold hover:underline">
                Mode Postpayé →
            </a>
        </p>
    </div>

</div>
@endsection

@push('scripts')
<script>
const PRICING = {
    activationFee:  {{ (float)($pricingPlan->activation_fee ?? 0) }},
    baseRate:       {{ (float)($pricingPlan->base_rate ?? 0) }},
    pricePerKwh:    {{ (float)($pricingPlan->price_per_kwh ?? 0) }},
    pricePerMinute: {{ (float)($pricingPlan->price_per_minute ?? 0) }},
    vatRate:        {{ (float)($pricingPlan->vatRate?->rate ?? $pricingPlan->vat_rate ?? 0) }},
};
const WALLET_BALANCE = {{ $walletBalance }};
const CURRENCY = '{{ $currency }}';

let currentMode  = 'energy';
let currentValue = 0;

// ─── Mode toggle ──────────────────────────────────────────
function setMode(mode) {
    currentMode  = mode;
    currentValue = 0;
    document.getElementById('hiddenType').value  = mode;
    document.getElementById('hiddenValue').value = '';
    document.getElementById('manualValue').value = '';

    document.getElementById('btnEnergy').classList.toggle('active', mode === 'energy');
    document.getElementById('btnDuration').classList.toggle('active', mode === 'duration');
    document.getElementById('energyChips').classList.toggle('hidden', mode !== 'energy');
    document.getElementById('durationChips').classList.toggle('hidden', mode !== 'duration');
    document.getElementById('chipLabel').textContent = mode === 'energy' ? "Quantité d'énergie" : "Durée de recharge";
    document.getElementById('unitLabel').textContent  = mode === 'energy' ? 'kWh' : 'min';

    // reset chip selection
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('selected'));
    updateCost();
}

// ─── Chip selection ────────────────────────────────────────
function selectChip(value, mode) {
    if (mode !== currentMode) setMode(mode);
    currentValue = value;
    document.getElementById('manualValue').value  = '';
    document.getElementById('hiddenValue').value  = value;

    document.querySelectorAll(`#${mode}Chips .chip`).forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    updateCost();
}

// ─── Manual input ──────────────────────────────────────────
function onManualInput(val) {
    currentValue = parseFloat(val) || 0;
    document.getElementById('hiddenValue').value = currentValue > 0 ? currentValue : '';
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('selected'));
    updateCost();
}

// ─── Cost calculation ──────────────────────────────────────
function calcCost(type, value) {
    let cost = PRICING.activationFee + PRICING.baseRate;
    if (type === 'energy')   cost += value * PRICING.pricePerKwh;
    if (type === 'duration') cost += value * PRICING.pricePerMinute;
    if (PRICING.vatRate > 0) cost *= 1 + (PRICING.vatRate / 100);
    return Math.round(cost * 100) / 100;
}

function updateCost() {
    const cost = currentValue > 0 ? calcCost(currentMode, currentValue) : 0;
    const fmt  = cost.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('costDisplay').textContent = `${fmt} ${CURRENCY}`;

    const sufficient = WALLET_BALANCE >= cost && cost > 0;
    const hasValue   = currentValue > 0;

    const btn = document.getElementById('btnSubmit');
    btn.disabled = !hasValue || !sufficient;

    if (!hasValue) {
        document.getElementById('btnText').textContent = 'Sélectionnez une quantité';
    } else if (!sufficient) {
        document.getElementById('btnText').textContent = `Solde insuffisant (${WALLET_BALANCE.toFixed(2)} ${CURRENCY} disponible)`;
    } else {
        document.getElementById('btnText').textContent = `Payer ${fmt} ${CURRENCY} et démarrer`;
    }

    // Wallet badge
    document.getElementById('walletBadge').classList.toggle('insufficient', !sufficient && hasValue);
}

// ─── Form submit guard ──────────────────────────────────────
document.getElementById('prepaidForm').addEventListener('submit', function(e) {
    const v = parseFloat(document.getElementById('hiddenValue').value);
    if (!v || v <= 0) {
        e.preventDefault();
        alert('Veuillez sélectionner ou saisir une quantité.');
        return;
    }
    document.getElementById('btnText').textContent = 'Traitement en cours…';
    document.getElementById('btnSubmit').disabled  = true;
});

// init
updateCost();
</script>
@endpush
