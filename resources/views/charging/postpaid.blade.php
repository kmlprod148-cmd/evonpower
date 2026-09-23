@extends('layouts.app')

@section('title', 'Recharge Postpayée – ' . $chargingPoint->name)

@push('styles')
<style>
:root {
    --ev-blue:       #3b82f6;
    --ev-blue-dark:  #1d4ed8;
    --ev-green:      #10b981;
    --ev-green-dark: #059669;
    --ev-purple:     #8b5cf6;
    --ev-purple-dark:#6d28d9;
}

.ev-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 20px rgba(0,0,0,.06);
    overflow: hidden;
}
.ev-card-header {
    background: linear-gradient(135deg, var(--ev-blue), var(--ev-blue-dark));
    color: #fff;
    padding: 1.25rem 1.5rem;
}

/* ─── Info box ──────────────────────────────────────────── */
.info-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 1rem;
}
.info-box.warning {
    background: #fffbeb;
    border-color: #fde68a;
}

/* ─── Wallet badge ──────────────────────────────────────── */
.wallet-badge {
    display: flex; align-items: center; gap: .6rem;
    background: #eff6ff; border: 1px solid #bfdbfe;
    border-radius: 10px; padding: .65rem 1rem;
}
.wallet-badge.insufficient { background: #fef2f2; border-color: #fecaca; }
.wallet-balance { font-size: 1.15rem; font-weight: 700; color: var(--ev-blue-dark); }
.wallet-badge.insufficient .wallet-balance { color: #dc2626; }

/* ─── Limit input ───────────────────────────────────────── */
.limit-card {
    border: 2px solid #e5e7eb; border-radius: 12px;
    padding: 1rem; transition: border-color .2s;
}
.limit-card:focus-within { border-color: var(--ev-blue); }
.limit-label { font-size: .8rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.limit-input {
    width: 100%; border: none; outline: none;
    font-size: 1.3rem; font-weight: 700; color: #111827;
    background: transparent; padding: .2rem 0;
}
.limit-unit { font-size: .85rem; color: #9ca3af; }

/* ─── Estimation ────────────────────────────────────────── */
.estimation-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: .45rem .75rem; border-radius: 8px; background: #f9fafb;
    font-size: .88rem;
}
.estimation-row .label { color: #6b7280; }
.estimation-row .value { font-weight: 700; color: #111827; }

/* ─── Flow steps ────────────────────────────────────────── */
.flow-step {
    display: flex; gap: .75rem; align-items: flex-start;
    padding: .5rem 0;
}
.flow-step-num {
    width: 26px; height: 26px; border-radius: 50%;
    background: var(--ev-blue); color: #fff;
    font-size: .75rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px;
}
.flow-step-text { font-size: .88rem; color: #374151; line-height: 1.4; }

/* ─── Submit ────────────────────────────────────────────── */
.btn-start {
    width: 100%; padding: .9rem; border-radius: 12px; border: none;
    font-size: 1rem; font-weight: 700; cursor: pointer;
    background: linear-gradient(135deg, var(--ev-blue), var(--ev-blue-dark));
    color: #fff; transition: all .25s;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.btn-start:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(59,130,246,.35);
}
.btn-start:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }

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
        <span class="text-gray-800 font-medium">Recharge Postpayée</span>
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
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-xl">🔋</div>
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

            {{-- Mode info ─────────────────────────────────── --}}
            <div class="info-box">
                <div class="font-semibold text-blue-800 mb-1 flex items-center gap-2">
                    <span>ℹ️</span> Mode Postpayé
                </div>
                <p class="text-blue-700 text-sm">
                    Démarrez immédiatement. Le coût réel sera débité de votre wallet
                    <strong>à la fin de la session</strong>, selon votre consommation effective.
                </p>
            </div>

            {{-- Wallet ─────────────────────────────────────── --}}
            <div class="wallet-badge {{ $walletBalance < 1 ? 'insufficient' : '' }}">
                <div class="text-2xl">👛</div>
                <div>
                    <div class="text-xs text-gray-500 font-medium">Votre solde wallet</div>
                    <div class="wallet-balance">{{ number_format($walletBalance, 2) }} {{ $currency }}</div>
                </div>
                @if($walletBalance < 1)
                    <a href="{{ route('credit-recharge.index') }}"
                       class="ml-auto text-xs text-blue-600 font-semibold underline">
                        Recharger
                    </a>
                @endif
            </div>

            {{-- Limites optionnelles ─────────────────────── --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Limites de session <span class="text-gray-400 font-normal">(optionnel)</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <div class="limit-card">
                        <div class="limit-label">Énergie max</div>
                        <div class="flex items-baseline gap-1">
                            <input type="number"
                                   id="inputKwh"
                                   min="0.1" max="500" step="0.5"
                                   placeholder="—"
                                   class="limit-input"
                                   oninput="updateEstimation()" />
                            <span class="limit-unit">kWh</span>
                        </div>
                    </div>
                    <div class="limit-card">
                        <div class="limit-label">Durée max</div>
                        <div class="flex items-baseline gap-1">
                            <input type="number"
                                   id="inputMin"
                                   min="1" max="1440" step="5"
                                   placeholder="—"
                                   class="limit-input"
                                   oninput="updateEstimation()" />
                            <span class="limit-unit">min</span>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    La session s'arrête automatiquement à la première limite atteinte.
                </p>
            </div>

            {{-- Estimation de coût ───────────────────────── --}}
            <div class="space-y-2">
                <div class="text-sm font-semibold text-gray-700">Estimation de coût</div>
                <div class="estimation-row">
                    <span class="label">Tarif / kWh</span>
                    <span class="value">{{ $pricingPlan ? number_format($pricingPlan->price_per_kwh ?? 0, 4) . ' ' . $currency : '—' }}</span>
                </div>
                <div class="estimation-row">
                    <span class="label">Tarif / minute</span>
                    <span class="value">{{ $pricingPlan ? number_format($pricingPlan->price_per_minute ?? 0, 4) . ' ' . $currency : '—' }}</span>
                </div>
                <div class="estimation-row bg-blue-50 border border-blue-100">
                    <span class="label text-blue-700 font-semibold">Estimation maximale</span>
                    <span class="value text-blue-700 text-base" id="maxEstimation">
                        {{ $estimatedHourCost > 0 ? number_format($estimatedHourCost, 2) . ' ' . $currency . ' / heure' : '—' }}
                    </span>
                </div>
            </div>

            {{-- Tarification détaillée ───────────────────── --}}
            @if($pricingPlan)
            <details class="text-sm">
                <summary class="cursor-pointer text-gray-500 hover:text-gray-800 font-medium">
                    Détail de la tarification
                </summary>
                <div class="mt-2">
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

            {{-- Flux postpayé ────────────────────────────── --}}
            <div class="bg-gray-50 rounded-xl p-4">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Comment ça marche</div>
                <div class="flow-step">
                    <div class="flow-step-num">1</div>
                    <div class="flow-step-text">
                        <strong>Démarrage immédiat</strong> — La borne reçoit la commande RemoteStart via OCPP.
                    </div>
                </div>
                <div class="flow-step">
                    <div class="flow-step-num">2</div>
                    <div class="flow-step-text">
                        <strong>Suivi en temps réel</strong> — Consultez l'énergie consommée et la durée en direct.
                    </div>
                </div>
                <div class="flow-step">
                    <div class="flow-step-num">3</div>
                    <div class="flow-step-text">
                        <strong>Arrêt & paiement</strong> — À l'arrêt, le coût réel est débité de votre wallet automatiquement.
                    </div>
                </div>
            </div>

            {{-- Formulaire ───────────────────────────────── --}}
            <form method="POST"
                  action="{{ route('charging.postpaid.start', $chargingPoint) }}"
                  id="postpaidForm">
                @csrf
                <input type="hidden" name="max_kwh"     id="hiddenKwh" value="" />
                <input type="hidden" name="max_minutes" id="hiddenMin" value="" />

                @if($walletBalance < 1)
                <div class="info-box warning mb-4">
                    <p class="text-amber-800 text-sm font-medium">
                        ⚠️ Solde wallet insuffisant pour démarrer une session postpayée.
                        <a href="{{ route('credit-recharge.index') }}" class="underline">Recharger votre wallet</a>
                    </p>
                </div>
                @endif

                <button type="submit"
                        class="btn-start"
                        id="btnSubmit"
                        {{ $walletBalance < 1 ? 'disabled' : '' }}>
                    🔋 Démarrer la recharge maintenant
                </button>
            </form>

            <p class="text-center text-xs text-gray-400">
                Le paiement n'est débité qu'à la fin de la session. Minimum {{ number_format($walletBalance < 1 ? 0 : 1, 2) }} {{ $currency }} requis dans le wallet.
            </p>

        </div>{{-- /p-5 --}}
    </div>{{-- /ev-card --}}

    {{-- Lien vers prépayé ───────────────────────────────── --}}
    <div class="mt-4 text-center">
        <p class="text-sm text-gray-500">
            Vous préférez payer avant la recharge ?
            <a href="{{ route('charging.prepaid.show', $chargingPoint) }}"
               class="text-green-600 font-semibold hover:underline">
                Mode Prépayé →
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
const CURRENCY = '{{ $currency }}';

function calcCost(kwh, minutes) {
    let cost = PRICING.activationFee + PRICING.baseRate;
    if (kwh > 0)     cost += kwh     * PRICING.pricePerKwh;
    if (minutes > 0) cost += minutes * PRICING.pricePerMinute;
    if (PRICING.vatRate > 0) cost *= 1 + (PRICING.vatRate / 100);
    return Math.round(cost * 100) / 100;
}

function updateEstimation() {
    const kwh = parseFloat(document.getElementById('inputKwh').value) || 0;
    const min = parseFloat(document.getElementById('inputMin').value) || 0;

    document.getElementById('hiddenKwh').value = kwh > 0 ? kwh : '';
    document.getElementById('hiddenMin').value = min > 0 ? min : '';

    const cost = calcCost(kwh, min);
    const elMax = document.getElementById('maxEstimation');

    if (cost > 0) {
        elMax.textContent = `≤ ${cost.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${CURRENCY}`;
    } else {
        const hourCost = {{ $estimatedHourCost > 0 ? $estimatedHourCost : 0 }};
        elMax.textContent = hourCost > 0
            ? `${hourCost.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${CURRENCY} / heure`
            : '—';
    }
}

document.getElementById('postpaidForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '⏳ Démarrage en cours…';
});
</script>
@endpush
