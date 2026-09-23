@extends('layouts.public')

@section('title', 'Recharge démarrée ✓')

@push('styles')
<style>
    /* ── Animations ──────────────────────────────────────────────────────── */
    @keyframes pulse-ring {
        0%   { transform: scale(1);   opacity: .8; }
        70%  { transform: scale(1.4); opacity: 0; }
        100% { transform: scale(1.4); opacity: 0; }
    }
    @keyframes bounce-in {
        0%   { transform: scale(.4); opacity: 0; }
        60%  { transform: scale(1.1); }
        80%  { transform: scale(.95); }
        100% { transform: scale(1); opacity: 1; }
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Header icon ─────────────────────────────────────────────────────── */
    .pulse-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .pulse-ring {
        position: absolute;
        inset: -10px;
        border-radius: 50%;
        border: 3px solid #10b981;
        animation: pulse-ring 2s ease-out infinite;
    }
    .check-circle {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: #10b981;
        display: flex; align-items: center; justify-content: center;
        animation: bounce-in .7s ease both;
    }

    /* ── Status badges ───────────────────────────────────────────────────── */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .35rem .9rem;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 600;
    }
    .badge-active   { background: #d1fae5; color: #065f46; }
    .badge-pending  { background: #fef3c7; color: #92400e; }
    .badge-starting { background: #dbeafe; color: #1e40af; }
    .badge-failed   { background: #fee2e2; color: #991b1b; }

    /* ── Card ────────────────────────────────────────────────────────────── */
    .ev-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 12px rgba(0,0,0,.06);
        overflow: hidden;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .55rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .info-row:last-child { border-bottom: none; }

    /* ── Step indicator ──────────────────────────────────────────────────── */
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
        width: 30px; height: 30px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .75rem;
        background: #10b981; color: #fff;
    }
    .step-label {
        font-size: .68rem; color: #10b981;
        font-weight: 600; text-align: center; white-space: nowrap;
    }
    .step-connector {
        flex: 1; height: 2px;
        background: #10b981;
        margin-top: 14px;
    }

    /* ── Live status panel ───────────────────────────────────────────────── */
    #live-status {
        transition: all .3s ease;
    }
    .spinner {
        display: inline-block;
        width: 14px; height: 14px;
        border: 2px solid currentColor;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin .7s linear infinite;
        vertical-align: middle;
        margin-right: 4px;
    }
    .progress-step {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: .6rem 0;
        border-bottom: 1px solid #f3f4f6;
        animation: fade-in .3s ease;
    }
    .progress-step:last-child { border-bottom: none; }

    .step-icon {
        flex-shrink: 0;
        width: 28px; height: 28px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 700;
    }
    .step-done    { background: #d1fae5; color: #059669; }
    .step-active  { background: #dbeafe; color: #2563eb; }
    .step-waiting { background: #f3f4f6; color: #9ca3af; }
    .step-error   { background: #fee2e2; color: #dc2626; }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 py-10 px-4">
    <div class="max-w-md mx-auto">

        {{-- ── All-done step indicator ──────────────────────────────── --}}
        <div class="step-indicator">
            @foreach(['Inscrit','Configuré','Payé','Recharge'] as $label)
                @if(!$loop->first)
                    <div class="step-connector"></div>
                @endif
                <div class="step-item">
                    <div class="step-dot">✓</div>
                    <span class="step-label">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        {{-- ── Success header ───────────────────────────────────────── --}}
        <div class="text-center mb-8">
            <div class="pulse-wrapper mb-5">
                <div class="pulse-ring"></div>
                <div class="check-circle">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor"
                         stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-1">Paiement confirmé !</h1>
            <p class="text-gray-500 text-sm">
                Votre demande de recharge a été transmise à la borne.
            </p>

            {{-- Live badge updated by JS ──────────────────────────── --}}
            <div class="mt-3" id="live-badge">
                <span class="status-badge badge-starting">
                    <span class="spinner"></span> Démarrage en cours…
                </span>
            </div>
        </div>

        {{-- ── Flash message ────────────────────────────────────────── --}}
        @if(session('info'))
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-800 text-sm">
                ℹ {{ session('info') }}
            </div>
        @endif

        {{-- ── Reservation summary ──────────────────────────────────── --}}
        <div class="ev-card mb-5">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Récapitulatif de la réservation</h2>
            </div>
            <div class="px-5 py-4 space-y-1 text-sm">
                <div class="info-row">
                    <span class="text-gray-500">Borne</span>
                    <span class="font-medium text-gray-800">{{ $chargingPoint?->name ?? '–' }}</span>
                </div>
                <div class="info-row">
                    <span class="text-gray-500">Réservation #</span>
                    <span class="font-medium text-gray-800">{{ $reservation->id }}</span>
                </div>
                <div class="info-row">
                    <span class="text-gray-500">Type</span>
                    <span class="font-medium text-gray-800">
                        {{ $reservation->reservation_type === 'kwh' ? 'Énergie' : 'Durée' }}
                        – {{ $reservation->reservation_value }}
                        {{ $reservation->reservation_type === 'kwh' ? 'kWh' : 'min' }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="text-gray-500">Montant payé</span>
                    <span class="font-semibold text-green-700">
                        {{ number_format($reservation->amount, 2) }}
                        {{ strtoupper($reservation->chargingPoint?->pricingPlan?->currency ?? 'EUR') }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="text-gray-500">Paiement</span>
                    <span class="font-medium text-gray-800">Carte bancaire (Stripe)</span>
                </div>
                <div class="info-row">
                    <span class="text-gray-500">Date</span>
                    <span class="font-medium text-gray-800">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- ── Live remote-start progress ───────────────────────────── --}}
        <div class="ev-card mb-5" id="live-status">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Progression</h2>
                <span id="live-dot"
                      class="w-2 h-2 rounded-full bg-blue-400 animate-pulse inline-block"></span>
            </div>
            <div class="px-5 py-4 space-y-0 text-sm" id="progress-steps">

                {{-- Step 1 – Payment ──────────────────────────────────── --}}
                <div class="progress-step">
                    <div class="step-icon step-done">✓</div>
                    <div>
                        <p class="font-medium text-gray-800">Paiement confirmé</p>
                        <p class="text-gray-500">Votre carte a été débitée avec succès.</p>
                    </div>
                </div>

                {{-- Step 2 – RemoteStart (updated by JS) ──────────────── --}}
                <div class="progress-step" id="step-remotestart">
                    <div class="step-icon step-active" id="step-remotestart-icon">
                        <span class="spinner" style="width:10px;height:10px;border-width:1.5px;"></span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800" id="step-remotestart-title">
                            Envoi commande RemoteStart
                        </p>
                        <p class="text-gray-500" id="step-remotestart-desc">
                            La borne reçoit l'ordre de démarrage via le protocole OCPP…
                        </p>
                    </div>
                </div>

                {{-- Step 3 – Plug cable ───────────────────────────────── --}}
                <div class="progress-step" id="step-plug">
                    <div class="step-icon step-waiting" id="step-plug-icon">3</div>
                    <div>
                        <p class="font-medium text-gray-800">Branchez votre véhicule</p>
                        <p class="text-gray-500" id="step-plug-desc">
                            La borne autorisera la charge dès la connexion du câble.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Error panel (hidden until a failure is detected) ────── --}}
        <div class="hidden ev-card mb-5 border-red-200" id="error-panel">
            <div class="px-5 py-4 border-b border-red-100 bg-red-50">
                <h2 class="font-semibold text-red-700">Problème de démarrage</h2>
            </div>
            <div class="px-5 py-4 text-sm text-red-600" id="error-message">
                Le démarrage automatique a échoué. Veuillez contacter le support.
            </div>
            <div class="px-5 pb-4 text-sm text-gray-500">
                Votre paiement a bien été enregistré. Un remboursement sera traité
                si la recharge ne démarre pas.
            </div>
        </div>

        {{-- ── Actions ──────────────────────────────────────────────── --}}
        <div class="space-y-3">
            <a href="{{ route('client.charging.offer', $chargingPoint?->id ?? 0) }}"
               class="block w-full py-3 rounded-xl text-center font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                ⚡ Nouvelle recharge
            </a>
            <a href="{{ route('home') }}"
               class="block w-full py-3 rounded-xl text-center font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition">
                Accueil
            </a>
        </div>

        <p class="mt-6 text-center text-xs text-gray-400">
            Un email de confirmation vous a été envoyé à {{ $client?->email }}.
        </p>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const POLL_URL     = @json(route('client.charging.status', ['id' => $chargingPoint?->id ?? 0, 'reservationId' => $reservation->id]));
    const POLL_INTERVAL_MS  = 3000;   // every 3 s
    const MAX_POLLS         = 40;     // give up after ~2 min
    const TERMINAL_STATES   = ['charging', 'failed'];

    let pollCount  = 0;
    let pollTimer  = null;

    // DOM refs
    const liveBadge     = document.getElementById('live-badge');
    const liveDot       = document.getElementById('live-dot');
    const rsIcon        = document.getElementById('step-remotestart-icon');
    const rsTitle       = document.getElementById('step-remotestart-title');
    const rsDesc        = document.getElementById('step-remotestart-desc');
    const plugIcon      = document.getElementById('step-plug-icon');
    const plugDesc      = document.getElementById('step-plug-desc');
    const errorPanel    = document.getElementById('error-panel');
    const errorMessage  = document.getElementById('error-message');

    function setBadge(state) {
        const map = {
            paid:     { cls: 'badge-starting', html: '<span class="spinner"></span> Paiement confirmé…' },
            starting: { cls: 'badge-starting', html: '<span class="spinner"></span> Démarrage en cours…' },
            charging: { cls: 'badge-active',   html: '<span class="w-2 h-2 rounded-full bg-green-500 inline-block animate-pulse"></span> Recharge en cours' },
            failed:   { cls: 'badge-failed',   html: '⚠ Échec du démarrage' },
        };
        const cfg = map[state] ?? map.starting;
        liveBadge.innerHTML = `<span class="status-badge ${cfg.cls}">${cfg.html}</span>`;
    }

    function setStepDone(iconEl, titleEl, descEl, descText) {
        iconEl.className    = 'step-icon step-done';
        iconEl.textContent  = '✓';
        if (titleEl)  titleEl.className = 'font-medium text-gray-800';
        if (descEl && descText) descEl.textContent = descText;
    }

    function setStepFailed(iconEl, titleEl) {
        iconEl.className   = 'step-icon step-error';
        iconEl.textContent = '✕';
        if (titleEl) titleEl.className = 'font-medium text-red-700';
    }

    function handleState(data) {
        const state = data.state;

        // ── Update live header badge
        setBadge(state);

        if (state === 'charging') {
            // ── Remote start succeeded
            setStepDone(rsIcon, rsTitle, rsDesc, 'Commande RemoteStart acceptée par la borne.');
            plugIcon.className    = 'step-icon step-active';
            plugIcon.textContent  = '⚡';
            plugDesc.textContent  = 'Branchez le câble — la recharge démarrera automatiquement.';
            liveDot.className     = 'w-2 h-2 rounded-full bg-green-500 inline-block animate-pulse';
            stopPolling();
            return;
        }

        if (state === 'failed') {
            // ── Remote start failed
            setStepFailed(rsIcon, rsTitle);
            rsTitle.textContent = 'Échec RemoteStart';
            rsDesc.textContent  = data.last_error ?? 'La borne n\'a pas répondu à la commande OCPP.';
            liveDot.className   = 'w-2 h-2 rounded-full bg-red-500 inline-block';

            errorPanel.classList.remove('hidden');
            if (data.last_error) {
                errorMessage.textContent = data.last_error;
            }
            stopPolling();
            return;
        }

        // Still in-progress — keep polling
        if (state === 'starting') {
            rsDesc.textContent = 'En attente de confirmation de la borne…';
        }
    }

    async function poll() {
        pollCount++;

        try {
            const resp = await fetch(POLL_URL, {
                headers: {
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!resp.ok) {
                console.warn('[EvonStatus] poll returned', resp.status);
                return;
            }

            const data = await resp.json();
            handleState(data);

            if (TERMINAL_STATES.includes(data.state)) {
                return; // stopPolling already called inside handleState
            }

        } catch (err) {
            console.warn('[EvonStatus] poll error', err);
        }

        if (pollCount >= MAX_POLLS) {
            // Timed out — show neutral message
            rsDesc.textContent = 'La borne met du temps à répondre. Vérifiez l\'état sur votre tableau de bord.';
            stopPolling();
            return;
        }

        pollTimer = setTimeout(poll, POLL_INTERVAL_MS);
    }

    function stopPolling() {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
        liveDot.classList.remove('animate-pulse');
    }

    // Kick off first poll after a short delay to let the job queue breathe
    pollTimer = setTimeout(poll, 2000);
})();
</script>
@endpush
