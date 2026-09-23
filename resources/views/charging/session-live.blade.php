@extends('layouts.app')

@section('title', 'Session en cours – ' . $chargingPoint->name)

@push('styles')
<style>
:root {
    --ev-green:      #10b981;
    --ev-green-dark: #059669;
    --ev-blue:       #3b82f6;
    --ev-blue-dark:  #1d4ed8;
    --ev-yellow:     #f59e0b;
    --ev-red:        #ef4444;
    --ev-orange:     #f97316;
}

/* ─── Card ──────────────────────────────────────────────── */
.ev-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 20px rgba(0,0,0,.06);
    overflow: hidden;
}
.ev-card-header {
    padding: 1.25rem 1.5rem;
    color: #fff;
}
.ev-card-header.prepaid  { background: linear-gradient(135deg, var(--ev-green), var(--ev-green-dark)); }
.ev-card-header.postpaid { background: linear-gradient(135deg, var(--ev-blue), var(--ev-blue-dark)); }

/* ─── Status Banner ─────────────────────────────────────── */
.status-banner {
    display: flex; align-items: center; gap: 1rem;
    padding: 1rem 1.25rem; border-radius: 12px;
    font-weight: 600;
}
.status-banner.pending   { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.status-banner.starting  { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.status-banner.charging  { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.status-banner.completed { background: #f0fdf4; border: 1px solid #bbf7d0; color: #14532d; }
.status-banner.failed    { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

/* ─── Pulse animation ───────────────────────────────────── */
@keyframes pulse-ring {
    0%   { transform: scale(1);    opacity: 1; }
    100% { transform: scale(2.5);  opacity: 0; }
}
.pulse-dot {
    position: relative; display: inline-flex;
    width: 16px; height: 16px; flex-shrink: 0;
}
.pulse-dot span {
    display: block; width: 100%; height: 100%;
    border-radius: 50%; background: currentColor;
}
.pulse-dot::before {
    content: ''; position: absolute; inset: 0;
    border-radius: 50%; background: currentColor;
    animation: pulse-ring 1.5s ease-out infinite;
}

/* ─── Metrics grid ──────────────────────────────────────── */
.metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
@media (max-width: 420px) { .metrics-grid { grid-template-columns: repeat(2, 1fr); } }
.metric-card {
    background: #f9fafb; border-radius: 12px;
    padding: .85rem; text-align: center;
    border: 1px solid #f3f4f6;
}
.metric-icon  { font-size: 1.4rem; }
.metric-value { font-size: 1.35rem; font-weight: 800; color: #111827; margin-top: .2rem; }
.metric-label { font-size: .72rem; color: #9ca3af; font-weight: 500; margin-top: .1rem; }

/* ─── Timer ─────────────────────────────────────────────── */
.timer-display {
    font-size: 2.5rem; font-weight: 900;
    color: var(--ev-green-dark); letter-spacing: .05em;
    font-variant-numeric: tabular-nums;
}

/* ─── Cost live ─────────────────────────────────────────── */
.cost-live {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border: 2px solid var(--ev-green); border-radius: 12px;
    padding: .75rem 1.25rem; text-align: center;
}
.cost-live .cost-val { font-size: 1.6rem; font-weight: 800; color: var(--ev-green-dark); }
.cost-live .cost-sub { font-size: .75rem; color: #6b7280; }

/* ─── Stop button ───────────────────────────────────────── */
.btn-stop {
    width: 100%; padding: .9rem; border-radius: 12px; border: none;
    font-size: 1rem; font-weight: 700; cursor: pointer;
    background: linear-gradient(135deg, var(--ev-red), #dc2626);
    color: #fff; transition: all .25s;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
}
.btn-stop:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(239,68,68,.35);
}
.btn-stop:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }

/* ─── Summary box (completed) ───────────────────────────── */
.summary-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: .5rem .75rem; border-radius: 8px; background: #f9fafb;
    font-size: .9rem; margin-bottom: .5rem;
}
.summary-row .skey { color: #6b7280; }
.summary-row .sval { font-weight: 700; color: #111827; }

/* ─── Step bar ──────────────────────────────────────────── */
.step-bar { display: flex; align-items: center; margin-bottom: 1.5rem; }
.step-bar-item { display: flex; flex-direction: column; align-items: center; gap: .2rem; }
.step-bar-dot {
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 700; flex-shrink: 0;
}
.step-bar-dot.done    { background: var(--ev-green); color: #fff; }
.step-bar-dot.active  { background: var(--ev-blue); color: #fff; box-shadow: 0 0 0 4px rgba(59,130,246,.2); }
.step-bar-dot.pending { background: #e5e7eb; color: #9ca3af; }
.step-bar-line { flex: 1; height: 2px; background: #e5e7eb; }
.step-bar-line.done   { background: var(--ev-green); }
.step-bar-label { font-size: .65rem; color: #9ca3af; white-space: nowrap; }
.step-bar-label.active { color: var(--ev-blue); font-weight: 600; }
.step-bar-label.done   { color: var(--ev-green); }
</style>
@endpush

@section('content')
<div class="max-w-xl mx-auto px-4 py-8" id="pageRoot">

    {{-- ── Fil d'Ariane ─────────────────────────────────────── --}}
    <nav class="text-sm text-gray-500 mb-5 flex items-center gap-2">
        <a href="{{ route('dashboard') }}" class="hover:text-gray-800 transition">Tableau de bord</a>
        <span>/</span>
        <span class="text-gray-800 font-medium">Session de recharge</span>
    </nav>

    {{-- ── Carte session ────────────────────────────────────── --}}
    <div class="ev-card" id="sessionCard">
        {{-- Header --}}
        <div class="ev-card-header {{ $reservation->payment_mode === 'prepaid' ? 'prepaid' : 'postpaid' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-xl">
                    {{ $reservation->payment_mode === 'prepaid' ? '⚡' : '🔋' }}
                </div>
                <div>
                    <div class="font-bold text-lg">{{ $chargingPoint->name }}</div>
                    <div class="text-white/80 text-sm">
                        {{ $reservation->payment_mode === 'prepaid' ? 'Mode Prépayé' : 'Mode Postpayé' }}
                        &nbsp;·&nbsp; Réservation #{{ $reservation->id }}
                    </div>
                </div>
            </div>
        </div>

        <div class="p-5 space-y-5">

            {{-- Alerte flash --}}
            @if(session('success'))
            <div class="p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm font-medium">
                ✅ {{ session('success') }}
            </div>
            @endif

            {{-- ── Barre d'étapes ─────────────────────────── --}}
            <div class="step-bar" id="stepBar">
                <div class="step-bar-item">
                    <div class="step-bar-dot done" id="step1dot">✓</div>
                    <div class="step-bar-label done">Payé</div>
                </div>
                <div class="step-bar-line done"></div>
                <div class="step-bar-item">
                    <div class="step-bar-dot" id="step2dot">2</div>
                    <div class="step-bar-label" id="step2label">Démarrage</div>
                </div>
                <div class="step-bar-line" id="step3line"></div>
                <div class="step-bar-item">
                    <div class="step-bar-dot" id="step3dot">3</div>
                    <div class="step-bar-label" id="step3label">En charge</div>
                </div>
                <div class="step-bar-line" id="step4line"></div>
                <div class="step-bar-item">
                    <div class="step-bar-dot" id="step4dot">4</div>
                    <div class="step-bar-label" id="step4label">Terminé</div>
                </div>
            </div>

            {{-- ── Status banner ──────────────────────────── --}}
            <div class="status-banner pending" id="statusBanner">
                <div class="pulse-dot"><span></span></div>
                <div>
                    <div id="statusTitle">Initialisation de la session…</div>
                    <div class="text-xs font-normal opacity-80" id="statusSub">Connexion à la borne en cours</div>
                </div>
            </div>

            {{-- ── Métriques live ──────────────────────────── --}}
            <div class="metrics-grid" id="metricsGrid">
                <div class="metric-card">
                    <div class="metric-icon">⏱</div>
                    <div class="metric-value" id="mDuration">--:--</div>
                    <div class="metric-label">Durée</div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon">⚡</div>
                    <div class="metric-value" id="mEnergy">0 kWh</div>
                    <div class="metric-label">Énergie</div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon">💶</div>
                    <div class="metric-value" id="mCost">—</div>
                    <div class="metric-label">Coût estimé</div>
                </div>
            </div>

            {{-- ── Coût en direct ──────────────────────────── --}}
            <div class="cost-live hidden" id="costLiveBox">
                <div class="cost-val" id="costLiveVal">0.00 EUR</div>
                <div class="cost-sub" id="costLiveSub">Coût estimé en temps réel</div>
            </div>

            {{-- ── Résumé final (hidden until completed) ───── --}}
            <div class="hidden" id="summaryBox">
                <div class="text-sm font-semibold text-gray-700 mb-3">📄 Résumé de session</div>
                <div class="summary-row">
                    <span class="skey">Durée totale</span>
                    <span class="sval" id="sumDuration">—</span>
                </div>
                <div class="summary-row">
                    <span class="skey">Énergie consommée</span>
                    <span class="sval" id="sumEnergy">—</span>
                </div>
                <div class="summary-row">
                    <span class="skey">Coût final</span>
                    <span class="sval text-green-700" id="sumCost">—</span>
                </div>
                @if($reservation->payment_mode === 'prepaid')
                <div class="summary-row hidden" id="sumRefundRow">
                    <span class="skey">Remboursement wallet</span>
                    <span class="sval text-blue-700" id="sumRefund">—</span>
                </div>
                @endif
                <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm font-medium text-center">
                    ✅ Session terminée avec succès
                </div>
            </div>

            {{-- ── Bouton d'arrêt ──────────────────────────── --}}
            <div id="stopSection">
                <form method="POST"
                      action="{{ route('charging.session.stop', $reservation) }}"
                      id="stopForm">
                    @csrf
                    <button type="button"
                            class="btn-stop"
                            id="btnStop"
                            disabled
                            onclick="confirmStop()">
                        🛑 Arrêter la recharge
                    </button>
                </form>
            </div>

            {{-- Info solde wallet ───────────────────────── --}}
            <div class="flex items-center justify-between text-xs text-gray-400 pt-1 border-t border-gray-100">
                <span>Solde wallet actuel</span>
                <span class="font-semibold text-gray-600" id="walletBalanceLive">
                    {{ number_format(auth()->user()->getWalletBalance(), 2) }} {{ $reservation->pricingPlan?->currency ?? 'EUR' }}
                </span>
            </div>

        </div>{{-- /p-5 --}}
    </div>{{-- /ev-card --}}

    {{-- Lien retour ─────────────────────────────────────── --}}
    <div class="mt-4 text-center">
        <a href="{{ route('charging-points.show', $chargingPoint) }}"
           class="text-sm text-gray-400 hover:text-gray-600 transition">
            ← Retour à la borne
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
const RESERVATION_ID   = {{ $reservation->id }};
const STATUS_URL       = '{{ route("charging.session.status", $reservation) }}';
const STOP_URL         = '{{ route("charging.session.stop", $reservation) }}';
const PAYMENT_MODE     = '{{ $reservation->payment_mode }}';
const CURRENCY         = '{{ $reservation->pricingPlan?->currency ?? "EUR" }}';
const CSRF_TOKEN       = document.querySelector('meta[name="csrf-token"]')?.content || '';

let pollInterval   = null;
let elapsedSeconds = 0;
let timerInterval  = null;
let sessionStarted = false;
let isStopping     = false;

// ─── UI helpers ───────────────────────────────────────────
const $ = id => document.getElementById(id);

function setStepState(step, state) {
    // state: 'pending' | 'active' | 'done'
    const dot   = $(`step${step}dot`);
    const label = $(`step${step}label`);
    if (!dot) return;
    dot.className   = `step-bar-dot ${state}`;
    dot.textContent = state === 'done' ? '✓' : step;
    if (label) {
        label.className = `step-bar-label ${state}`;
    }
    // line before this step
    const line = $(`step${step}line`);
    if (line) line.className = `step-bar-line ${state === 'done' || state === 'active' ? 'done' : ''}`;
}

function updateBanner(state, title, sub) {
    const banner = $('statusBanner');
    banner.className = `status-banner ${state}`;
    $('statusTitle').textContent = title;
    $('statusSub').textContent   = sub;
}

function formatDuration(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) return `${h}h ${String(m).padStart(2,'0')}m`;
    return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

function fmt(n, digits = 2) {
    return parseFloat(n || 0).toLocaleString('fr-FR', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits
    });
}

// ─── Timer (client-side, until session data arrives) ──────
function startTimer(secondsElapsed = 0) {
    if (timerInterval) return;
    elapsedSeconds = secondsElapsed;
    timerInterval = setInterval(() => {
        elapsedSeconds++;
        $('mDuration').textContent = formatDuration(elapsedSeconds);
    }, 1000);
}

// ─── Render data from status endpoint ─────────────────────
function renderStatus(data) {
    const state   = data.state;
    const session = data.session;

    // ── Step bar
    if (state === 'starting') {
        setStepState(2, 'active');
        setStepState(3, 'pending');
        setStepState(4, 'pending');
    } else if (state === 'charging') {
        setStepState(2, 'done');
        setStepState(3, 'active');
        setStepState(4, 'pending');
    } else if (state === 'completed') {
        setStepState(2, 'done');
        setStepState(3, 'done');
        setStepState(4, 'done');
    } else if (state === 'failed') {
        setStepState(2, 'done');
        setStepState(3, 'pending');
        setStepState(4, 'pending');
    }

    // ── Banner
    const bannerMap = {
        pending:   ['pending',   '⏳ Paiement validé…',             'Connexion à la borne OCPP en cours'],
        starting:  ['starting',  '🔌 Démarrage en cours…',          'Commande RemoteStart envoyée à la borne'],
        charging:  ['charging',  '⚡ Recharge en cours',             'Votre véhicule est en charge'],
        completed: ['completed', '✅ Session terminée',              'Merci d\'avoir utilisé EVON'],
        failed:    ['failed',    '❌ Démarrage échoué',              data.last_error || 'Contactez le support'],
    };
    const [cls, title, sub] = bannerMap[state] || bannerMap.pending;
    updateBanner(cls, title, sub);

    // ── Session metrics
    if (session) {
        if (!sessionStarted && state === 'charging') {
            sessionStarted = true;
            const startedAt = session.started_at ? new Date(session.started_at) : new Date();
            const elapsed   = Math.floor((Date.now() - startedAt.getTime()) / 1000);
            startTimer(Math.max(0, elapsed));
        }

        const energy = fmt(session.energy_kwh, 3);
        const cost   = fmt(session.current_cost ?? session.actual_cost, 2);

        $('mEnergy').textContent = `${energy} kWh`;
        $('mCost').textContent   = `${cost} ${CURRENCY}`;

        // Cost live box
        if (state === 'charging') {
            $('costLiveBox').classList.remove('hidden');
            $('costLiveVal').textContent = `${cost} ${CURRENCY}`;
            $('costLiveSub').textContent = PAYMENT_MODE === 'postpaid'
                ? 'Sera débité de votre wallet à l\'arrêt'
                : 'Estimé en temps réel';
        }
    }

    // ── Wallet balance
    if (data.wallet_balance !== undefined) {
        $('walletBalanceLive').textContent = `${fmt(data.wallet_balance)} ${CURRENCY}`;
    }

    // ── Stop button
    const btnStop = $('btnStop');
    if (state === 'charging') {
        btnStop.disabled = false;
    } else if (state === 'completed' || state === 'failed') {
        btnStop.disabled = true;
    }

    // ── Completed summary
    if (state === 'completed') {
        stopPolling();
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }

        $('summaryBox').classList.remove('hidden');
        $('stopSection').classList.add('hidden');
        $('costLiveBox').classList.add('hidden');

        if (session) {
            $('sumDuration').textContent = formatDuration((session.duration_minutes ?? 0) * 60);
            $('sumEnergy').textContent   = `${fmt(session.energy_kwh, 3)} kWh`;
            const finalCost = session.actual_cost ?? session.current_cost ?? 0;
            $('sumCost').textContent = `${fmt(finalCost)} ${CURRENCY}`;

            if (PAYMENT_MODE === 'prepaid' && session.prepaid_amount) {
                const refund = Math.max(0, parseFloat(session.prepaid_amount) - parseFloat(finalCost));
                if (refund > 0.01) {
                    $('sumRefundRow')?.classList.remove('hidden');
                    $('sumRefund').textContent = `+${fmt(refund)} ${CURRENCY}`;
                }
            }
        }
    }

    // ── Failed state
    if (state === 'failed') {
        stopPolling();
    }
}

// ─── Polling ───────────────────────────────────────────────
function poll() {
    fetch(STATUS_URL, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            renderStatus(data);

            // Slow down once charging
            if (data.state === 'charging' && pollInterval) {
                clearInterval(pollInterval);
                pollInterval = setInterval(poll, 5000); // 5 s
            }
        })
        .catch(err => console.error('Status poll error:', err));
}

function stopPolling() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
}

// ─── Stop confirmation ─────────────────────────────────────
function confirmStop() {
    if (isStopping) return;
    if (!confirm('Êtes-vous sûr de vouloir arrêter la recharge maintenant ?')) return;

    isStopping = true;
    const btn = $('btnStop');
    btn.disabled = true;
    btn.innerHTML = '⏳ Arrêt en cours…';

    fetch(STOP_URL, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: JSON.stringify({}),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateBanner('starting', '🛑 Arrêt de la session en cours…', 'La borne reçoit la commande d\'arrêt');
        } else {
            isStopping = false;
            btn.disabled = false;
            btn.innerHTML = '🛑 Arrêter la recharge';
            alert(data.error || 'Erreur lors de l\'arrêt. Réessayez.');
        }
    })
    .catch(() => {
        isStopping = false;
        btn.disabled = false;
        btn.innerHTML = '🛑 Arrêter la recharge';
        alert('Erreur réseau. Réessayez.');
    });
}

// ─── Init ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Immediate poll
    poll();
    // Then every 2 s (slows to 5 s once charging, via renderStatus)
    pollInterval = setInterval(poll, 2000);

    // If session was already charging on page load
    @if($session && in_array($session->status, ['active', 'in_progress']))
        sessionStarted = true;
        const startedAt = new Date('{{ $session->started_at?->toIso8601String() }}');
        const elapsed   = Math.floor((Date.now() - startedAt.getTime()) / 1000);
        startTimer(Math.max(0, elapsed));
    @endif
});
</script>
@endpush
