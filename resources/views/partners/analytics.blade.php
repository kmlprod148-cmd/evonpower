@extends('layouts.app')

@section('title', 'Analytiques — ' . $partner->name)

@push('styles')
<style>
/* ============================================================
   PARTNER ANALYTICS — PREMIUM DESIGN SYSTEM
   ============================================================ */
:root {
    --pa-primary:     #6366f1;
    --pa-primary-dk:  #4f46e5;
    --pa-success:     #10b981;
    --pa-warning:     #f59e0b;
    --pa-danger:      #ef4444;
    --pa-info:        #06b6d4;
    --pa-surface:     #ffffff;
    --pa-bg:          #f1f5f9;
    --pa-border:      #e2e8f0;
    --pa-text:        #1e293b;
    --pa-muted:       #64748b;
    --pa-radius:      14px;
    --pa-shadow:      0 4px 24px rgba(99,102,241,.08);
    --pa-shadow-lg:   0 8px 48px rgba(99,102,241,.16);
}

.pa-page { background: var(--pa-bg); min-height: 100vh; padding: 1.5rem; }

/* ── HEADER ── */
.pa-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem; margin-bottom: 1.75rem;
}
.pa-header-left { display: flex; align-items: center; gap: 1rem; }
.pa-avatar {
    width: 52px; height: 52px; border-radius: 12px;
    background: linear-gradient(135deg, var(--pa-primary), var(--pa-info));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.4rem; font-weight: 700; flex-shrink: 0;
}
.pa-title { font-size: 1.4rem; font-weight: 700; color: var(--pa-text); line-height: 1.2; }
.pa-subtitle { font-size: .85rem; color: var(--pa-muted); margin-top: .2rem; }
.pa-header-actions { display: flex; gap: .75rem; flex-wrap: wrap; }
.pa-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1.1rem; border-radius: 10px; font-size: .85rem;
    font-weight: 600; cursor: pointer; transition: all .18s; text-decoration: none;
    border: none;
}
.pa-btn-primary  { background: var(--pa-primary); color: #fff; }
.pa-btn-primary:hover { background: var(--pa-primary-dk); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(99,102,241,.4); }
.pa-btn-outline  { background: #fff; color: var(--pa-text); border: 1.5px solid var(--pa-border); }
.pa-btn-outline:hover { border-color: var(--pa-primary); color: var(--pa-primary); }
.pa-btn-success  { background: var(--pa-success); color: #fff; }
.pa-btn-danger   { background: var(--pa-danger);  color: #fff; }
.pa-btn-sm { padding: .35rem .8rem; font-size: .78rem; }

/* ── PERIOD SELECTOR ── */
.pa-period-bar {
    display: flex; align-items: center; gap: .5rem;
    background: #fff; border-radius: 10px; padding: .35rem .5rem;
    border: 1.5px solid var(--pa-border); flex-wrap: wrap;
}
.pa-period-btn {
    padding: .3rem .75rem; border-radius: 7px; font-size: .8rem; font-weight: 600;
    color: var(--pa-muted); cursor: pointer; transition: all .15s;
    background: transparent; border: none;
}
.pa-period-btn.active, .pa-period-btn:hover {
    background: var(--pa-primary); color: #fff;
}

/* ── KPI CARDS ── */
.pa-kpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.pa-kpi {
    background: var(--pa-surface); border-radius: var(--pa-radius);
    padding: 1.3rem 1.4rem; box-shadow: var(--pa-shadow);
    border: 1px solid var(--pa-border); position: relative; overflow: hidden;
    transition: transform .18s, box-shadow .18s;
}
.pa-kpi:hover { transform: translateY(-2px); box-shadow: var(--pa-shadow-lg); }
.pa-kpi-icon {
    width: 44px; height: 44px; border-radius: 10px; margin-bottom: .9rem;
    display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
}
.pa-kpi-value { font-size: 1.7rem; font-weight: 800; color: var(--pa-text); line-height: 1; }
.pa-kpi-label { font-size: .78rem; color: var(--pa-muted); margin-top: .35rem; font-weight: 500; }
.pa-kpi-change {
    display: inline-flex; align-items: center; gap: .2rem;
    font-size: .72rem; font-weight: 700; padding: .15rem .45rem;
    border-radius: 999px; margin-top: .5rem;
}
.pa-kpi-change.up   { background: #d1fae5; color: #059669; }
.pa-kpi-change.down { background: #fee2e2; color: #dc2626; }
.pa-kpi-bg {
    position: absolute; right: -1rem; top: -1rem;
    font-size: 5rem; opacity: .05; user-select: none;
}

/* ── CHART GRID ── */
.pa-charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
@media (max-width: 900px) { .pa-charts-grid { grid-template-columns: 1fr; } }

.pa-card {
    background: var(--pa-surface); border-radius: var(--pa-radius);
    box-shadow: var(--pa-shadow); border: 1px solid var(--pa-border); overflow: hidden;
}
.pa-card-header {
    padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--pa-border);
    display: flex; align-items: center; justify-content: space-between;
}
.pa-card-title { font-size: .95rem; font-weight: 700; color: var(--pa-text); display: flex; align-items: center; gap: .5rem; }
.pa-card-body { padding: 1.1rem 1.4rem; }
.pa-chart-wrap { position: relative; height: 260px; }
.pa-chart-wrap-sm { position: relative; height: 220px; }

/* ── CP DISTRIBUTION ── */
.pa-cp-bar { display: flex; align-items: center; gap: .7rem; margin-bottom: .7rem; }
.pa-cp-bar-label { font-size: .78rem; color: var(--pa-muted); width: 110px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex-shrink: 0; }
.pa-cp-bar-track { flex: 1; background: var(--pa-bg); border-radius: 999px; height: 8px; overflow: hidden; }
.pa-cp-bar-fill  { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--pa-primary), var(--pa-info)); transition: width .5s; }
.pa-cp-bar-value { font-size: .78rem; font-weight: 700; color: var(--pa-text); width: 55px; text-align: right; flex-shrink: 0; }

/* ── TRANSACTIONS TABLE ── */
.pa-table-wrap { overflow-x: auto; }
.pa-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.pa-table th {
    padding: .7rem 1rem; text-align: left; font-size: .72rem; font-weight: 700;
    color: var(--pa-muted); text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 1.5px solid var(--pa-border); white-space: nowrap;
}
.pa-table td { padding: .85rem 1rem; border-bottom: 1px solid var(--pa-border); vertical-align: middle; }
.pa-table tr:last-child td { border-bottom: none; }
.pa-table tr:hover td { background: #f8faff; }

/* ── STATUS BADGES ── */
.pa-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700;
}
.pa-badge-success { background: #d1fae5; color: #059669; }
.pa-badge-warning { background: #fef3c7; color: #d97706; }
.pa-badge-danger  { background: #fee2e2; color: #dc2626; }
.pa-badge-info    { background: #cffafe; color: #0891b2; }
.pa-badge-gray    { background: #f1f5f9; color: #64748b; }

/* ── PAYMENT REQUESTS ── */
.pa-payment-req {
    padding: 1rem 1.4rem; border-bottom: 1px solid var(--pa-border);
    display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}
.pa-payment-req:last-child { border-bottom: none; }
.pa-payment-req-info { flex: 1; min-width: 200px; }
.pa-payment-req-user { font-size: .85rem; font-weight: 700; color: var(--pa-text); }
.pa-payment-req-meta { font-size: .75rem; color: var(--pa-muted); margin-top: .15rem; }
.pa-payment-req-amount { font-size: 1.1rem; font-weight: 800; color: var(--pa-text); white-space: nowrap; }
.pa-payment-req-actions { display: flex; gap: .5rem; }

/* ── ALERT BANNER ── */
.pa-alert-pending {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1.5px solid #f59e0b; border-radius: 12px;
    padding: 1rem 1.4rem; margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
}
.pa-alert-pending-icon { font-size: 1.5rem; flex-shrink: 0; }
.pa-alert-pending-text { flex: 1; font-size: .875rem; color: #92400e; font-weight: 600; }

/* ── EMPTY STATE ── */
.pa-empty { text-align: center; padding: 2.5rem; }
.pa-empty-icon { font-size: 3rem; margin-bottom: .75rem; }
.pa-empty-text { color: var(--pa-muted); font-size: .875rem; }

/* ── PAGINATION ── */
.pa-pagination { padding: .75rem 1.4rem; border-top: 1px solid var(--pa-border); }

/* ── MODAL VALIDATION ── */
.pa-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000;
    display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.pa-modal {
    background: #fff; border-radius: var(--pa-radius);
    padding: 1.75rem; max-width: 460px; width: 100%; box-shadow: var(--pa-shadow-lg);
}
.pa-modal-title { font-size: 1.1rem; font-weight: 700; color: var(--pa-text); margin-bottom: 1rem; }
.pa-modal-actions { display: flex; gap: .75rem; margin-top: 1.25rem; justify-content: flex-end; }
.pa-form-group { margin-bottom: 1rem; }
.pa-label { display: block; font-size: .8rem; font-weight: 600; color: var(--pa-text); margin-bottom: .4rem; }
.pa-textarea {
    width: 100%; padding: .6rem .9rem; border-radius: 9px; border: 1.5px solid var(--pa-border);
    font-size: .84rem; resize: none; font-family: inherit; outline: none; transition: border-color .15s;
}
.pa-textarea:focus { border-color: var(--pa-primary); }

/* ── PROGRESS CIRCLE ── */
.pa-progress-ring { display: flex; align-items: center; justify-content: center; padding: 1rem; }
.pa-progress-center { text-align: center; }
.pa-progress-value { font-size: 2rem; font-weight: 800; color: var(--pa-text); }
.pa-progress-label { font-size: .75rem; color: var(--pa-muted); margin-top: .25rem; }

/* ── LOADING SKELETON ── */
@keyframes skeleton-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .45; }
}
.pa-skeleton { background: #e2e8f0; border-radius: 6px; animation: skeleton-pulse 1.5s infinite; }

/* ── RESPONSIVE ── */
@media (max-width: 640px) {
    .pa-kpis { grid-template-columns: repeat(2, 1fr); }
    .pa-header { flex-direction: column; align-items: flex-start; }
}
</style>
@endpush

@section('content')
<div class="pa-page" x-data="partnerAnalytics()" x-init="init()">

    {{-- ════════════════════════════════════════════════
         HEADER
    ════════════════════════════════════════════════ --}}
    <div class="pa-header">
        <div class="pa-header-left">
            <div class="pa-avatar">
                {{ mb_strtoupper(mb_substr($partner->name, 0, 1)) }}
            </div>
            <div>
                <div class="pa-title">{{ $partner->name }}</div>
                <div class="pa-subtitle">Tableau de bord analytique · Partenaire</div>
            </div>
        </div>

        <div class="pa-header-actions">
            {{-- Sélecteur de période --}}
            <div class="pa-period-bar">
                @foreach([7 => '7j', 30 => '30j', 90 => '3m', 180 => '6m', 365 => '1an'] as $days => $label)
                <button class="pa-period-btn {{ $period == $days ? 'active' : '' }}"
                        onclick="changePeriod({{ $days }})">{{ $label }}</button>
                @endforeach
            </div>

            <a href="{{ route('partners.analytics.export', ['partner' => $partner->id, 'period' => $period]) }}"
               class="pa-btn pa-btn-outline">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exporter CSV
            </a>

            <a href="{{ route('partners.analytics.payment-requests', $partner) }}" class="pa-btn pa-btn-primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Paiements
                @if($stats['pending_payments_count'] > 0)
                <span style="background:#ef4444;color:#fff;border-radius:999px;padding:0 .5rem;font-size:.7rem;">
                    {{ $stats['pending_payments_count'] }}
                </span>
                @endif
            </a>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         ALERT — PAIEMENTS EN ATTENTE
    ════════════════════════════════════════════════ --}}
    @if($stats['pending_payments_count'] > 0)
    <div class="pa-alert-pending">
        <div class="pa-alert-pending-icon">⚠️</div>
        <div class="pa-alert-pending-text">
            <strong>{{ $stats['pending_payments_count'] }} demande(s) de paiement</strong> en attente de validation
            — Montant total : <strong>{{ number_format($stats['pending_payments_amount'], 2, ',', ' ') }} €</strong>
        </div>
        <a href="{{ route('partners.analytics.payment-requests', $partner) }}" class="pa-btn pa-btn-primary pa-btn-sm">
            Valider maintenant →
        </a>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════
         KPI CARDS
    ════════════════════════════════════════════════ --}}
    <div class="pa-kpis">
        {{-- Revenu Total --}}
        <div class="pa-kpi">
            <div class="pa-kpi-bg">💶</div>
            <div class="pa-kpi-icon" style="background:#ede9fe;">💰</div>
            <div class="pa-kpi-value">{{ number_format($stats['total_revenue'], 2, ',', ' ') }} €</div>
            <div class="pa-kpi-label">Revenus ({{ $period }}j)</div>
            @if(!empty($kpis['revenue_change']))
            <div class="pa-kpi-change {{ $kpis['revenue_change']['positive'] ? 'up' : 'down' }}">
                {{ $kpis['revenue_change']['positive'] ? '↑' : '↓' }}
                {{ $kpis['revenue_change']['value'] }}%
            </div>
            @endif
        </div>

        {{-- Transactions Complétées --}}
        <div class="pa-kpi">
            <div class="pa-kpi-bg">✅</div>
            <div class="pa-kpi-icon" style="background:#d1fae5;">⚡</div>
            <div class="pa-kpi-value">{{ number_format($stats['completed_transactions']) }}</div>
            <div class="pa-kpi-label">Transactions réussies</div>
            @if(!empty($kpis['transaction_change']))
            <div class="pa-kpi-change {{ $kpis['transaction_change']['positive'] ? 'up' : 'down' }}">
                {{ $kpis['transaction_change']['positive'] ? '↑' : '↓' }}
                {{ $kpis['transaction_change']['value'] }}%
            </div>
            @endif
        </div>

        {{-- Taux de Succès --}}
        <div class="pa-kpi">
            <div class="pa-kpi-bg">📊</div>
            <div class="pa-kpi-icon" style="background:#cffafe;">📈</div>
            <div class="pa-kpi-value">{{ $stats['success_rate'] }}%</div>
            <div class="pa-kpi-label">Taux de succès</div>
        </div>

        {{-- Énergie Délivrée --}}
        <div class="pa-kpi">
            <div class="pa-kpi-bg">🔋</div>
            <div class="pa-kpi-icon" style="background:#fef3c7;">🔌</div>
            <div class="pa-kpi-value">{{ number_format($stats['total_energy_kwh'], 1, ',', ' ') }}</div>
            <div class="pa-kpi-label">kWh délivrés</div>
        </div>

        {{-- Valeur Moyenne --}}
        <div class="pa-kpi">
            <div class="pa-kpi-bg">💳</div>
            <div class="pa-kpi-icon" style="background:#fce7f3;">💵</div>
            <div class="pa-kpi-value">{{ number_format($stats['avg_transaction_value'], 2, ',', ' ') }} €</div>
            <div class="pa-kpi-label">Valeur moyenne/transaction</div>
        </div>

        {{-- Bornes Actives --}}
        <div class="pa-kpi">
            <div class="pa-kpi-bg">⚡</div>
            <div class="pa-kpi-icon" style="background:#e0e7ff;">🏭</div>
            <div class="pa-kpi-value">{{ $stats['active_charging_points'] }}/{{ $stats['total_charging_points'] }}</div>
            <div class="pa-kpi-label">Bornes actives</div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         GRAPHIQUES REVENUS + RÉPARTITION
    ════════════════════════════════════════════════ --}}
    <div class="pa-charts-grid">
        {{-- Graphique revenus journalier --}}
        <div class="pa-card">
            <div class="pa-card-header">
                <span class="pa-card-title">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    Revenus sur {{ $period }} jours
                </span>
                <div style="display:flex;gap:.4rem;">
                    <span class="pa-badge pa-badge-success">● Complété</span>
                </div>
            </div>
            <div class="pa-card-body">
                <div class="pa-chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Répartition statuts --}}
        <div class="pa-card">
            <div class="pa-card-header">
                <span class="pa-card-title">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                    Statuts transactions
                </span>
            </div>
            <div class="pa-card-body">
                <div class="pa-chart-wrap-sm">
                    <canvas id="statusChart"></canvas>
                </div>
                <div style="display:flex;gap:1rem;justify-content:center;margin-top:.75rem;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:.3rem;font-size:.75rem;">
                        <span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:block;"></span>
                        Complétées
                    </div>
                    <div style="display:flex;align-items:center;gap:.3rem;font-size:.75rem;">
                        <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:block;"></span>
                        En attente
                    </div>
                    <div style="display:flex;align-items:center;gap:.3rem;font-size:.75rem;">
                        <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:block;"></span>
                        Échouées
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         DEUXIÈME RANGÉE : TOP BORNES + PAIEMENTS EN ATTENTE
    ════════════════════════════════════════════════ --}}
    <div class="pa-charts-grid" style="margin-bottom:1.5rem;">

        {{-- Distribution par borne --}}
        <div class="pa-card">
            <div class="pa-card-header">
                <span class="pa-card-title">
                    🏆 Top bornes par revenus
                </span>
            </div>
            <div class="pa-card-body">
                @php $maxRevenue = collect($chargingPointStats)->max('revenue') ?: 1; @endphp
                @forelse($chargingPointStats as $cp)
                <div class="pa-cp-bar">
                    <div class="pa-cp-bar-label" title="{{ $cp['name'] }}">{{ $cp['name'] }}</div>
                    <div class="pa-cp-bar-track">
                        <div class="pa-cp-bar-fill" style="width: {{ round(($cp['revenue'] / $maxRevenue) * 100) }}%"></div>
                    </div>
                    <div class="pa-cp-bar-value">{{ number_format($cp['revenue'], 2, ',', ' ') }} €</div>
                </div>
                @empty
                <div class="pa-empty">
                    <div class="pa-empty-icon">📊</div>
                    <div class="pa-empty-text">Aucune donnée disponible pour cette période.</div>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Demandes de paiement en attente --}}
        <div class="pa-card">
            <div class="pa-card-header">
                <span class="pa-card-title">
                    💳 Paiements à valider
                    @if($stats['pending_payments_count'] > 0)
                    <span class="pa-badge pa-badge-danger">{{ $stats['pending_payments_count'] }}</span>
                    @endif
                </span>
                <a href="{{ route('partners.analytics.payment-requests', $partner) }}" class="pa-btn pa-btn-outline pa-btn-sm">
                    Voir tout
                </a>
            </div>
            <div style="max-height: 320px; overflow-y: auto;">
                @forelse($pendingPayments as $reservation)
                <div class="pa-payment-req">
                    <div class="pa-payment-req-info">
                        <div class="pa-payment-req-user">
                            {{ $reservation->user?->name ?? $reservation->guest_email ?? 'Client inconnu' }}
                        </div>
                        <div class="pa-payment-req-meta">
                            {{ $reservation->chargingPoint?->name ?? 'Borne inconnue' }} ·
                            {{ $reservation->created_at?->diffForHumans() }}
                        </div>
                    </div>
                    <div class="pa-payment-req-amount">
                        {{ number_format($reservation->estimated_cost ?? 0, 2, ',', ' ') }} €
                    </div>
                    <div class="pa-payment-req-actions">
                        <button class="pa-btn pa-btn-success pa-btn-sm"
                                onclick="openValidationModal({{ $reservation->id }}, 'approve', '{{ number_format($reservation->estimated_cost ?? 0, 2) }}', '{{ $reservation->user?->name ?? 'Client' }}')">
                            ✓
                        </button>
                        <button class="pa-btn pa-btn-danger pa-btn-sm"
                                onclick="openValidationModal({{ $reservation->id }}, 'reject', '{{ number_format($reservation->estimated_cost ?? 0, 2) }}', '{{ $reservation->user?->name ?? 'Client' }}')">
                            ✗
                        </button>
                    </div>
                </div>
                @empty
                <div class="pa-empty">
                    <div class="pa-empty-icon">✅</div>
                    <div class="pa-empty-text">Aucun paiement en attente de validation.</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         TABLEAU DES TRANSACTIONS RÉCENTES
    ════════════════════════════════════════════════ --}}
    <div class="pa-card" style="margin-bottom: 1.5rem;">
        <div class="pa-card-header">
            <span class="pa-card-title">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Transactions récentes
            </span>
            <div style="font-size:.78rem;color:var(--pa-muted);">
                {{ $transactions->total() }} transaction(s) au total
            </div>
        </div>
        <div class="pa-table-wrap">
            <table class="pa-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Borne</th>
                        <th>Énergie</th>
                        <th>Durée</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                    <tr>
                        <td style="font-weight:600;color:var(--pa-primary);">#{{ $transaction->id }}</td>
                        <td style="color:var(--pa-muted);font-size:.78rem;">
                            {{ $transaction->created_at?->format('d/m/Y') }}<br>
                            <span style="font-size:.7rem;">{{ $transaction->created_at?->format('H:i') }}</span>
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:.84rem;">{{ $transaction->user?->name ?? 'Anonyme' }}</div>
                            <div style="font-size:.72rem;color:var(--pa-muted);">{{ $transaction->user?->email }}</div>
                        </td>
                        <td style="font-size:.84rem;">{{ $transaction->chargingPoint?->name ?? '—' }}</td>
                        <td>
                            @if($transaction->energy_consumed_wh)
                                <span style="font-weight:600;">{{ number_format($transaction->energy_consumed_wh / 1000, 2, ',', ' ') }}</span>
                                <span style="font-size:.72rem;color:var(--pa-muted);">kWh</span>
                            @else
                                <span style="color:var(--pa-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            @if($transaction->duration_minutes)
                                <span style="font-weight:600;">{{ $transaction->duration_minutes }}</span>
                                <span style="font-size:.72rem;color:var(--pa-muted);">min</span>
                            @else
                                <span style="color:var(--pa-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-weight:800;font-size:.95rem;">
                                {{ number_format($transaction->amount ?? 0, 2, ',', ' ') }} €
                            </span>
                        </td>
                        <td>
                            @php
                                $statusMap = [
                                    'completed'  => ['class' => 'pa-badge-success', 'label' => 'Complétée'],
                                    'confirmed'  => ['class' => 'pa-badge-success', 'label' => 'Confirmée'],
                                    'pending'    => ['class' => 'pa-badge-warning', 'label' => 'En attente'],
                                    'failed'     => ['class' => 'pa-badge-danger',  'label' => 'Échouée'],
                                    'refunded'   => ['class' => 'pa-badge-info',    'label' => 'Remboursée'],
                                ];
                                $statusInfo = $statusMap[$transaction->status] ?? ['class' => 'pa-badge-gray', 'label' => ucfirst($transaction->status ?? '—')];
                            @endphp
                            <span class="pa-badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                        </td>
                        <td>
                            <a href="{{ route('partners.analytics.transaction', ['partner' => $partner->id, 'transaction' => $transaction->id]) }}"
                               class="pa-btn pa-btn-outline pa-btn-sm">
                                Détails
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="pa-empty">
                                <div class="pa-empty-icon">💸</div>
                                <div class="pa-empty-text">Aucune transaction sur cette période.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="pa-pagination">
            {{ $transactions->appends(['period' => $period])->links('pagination::simple-tailwind') }}
        </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════
         MODAL — VALIDATION PAIEMENT
    ════════════════════════════════════════════════ --}}
    <div id="pa-validation-modal" class="pa-modal-overlay" style="display:none;" x-cloak>
        <div class="pa-modal">
            <div class="pa-modal-title" id="pa-modal-title">Validation du paiement</div>
            <p id="pa-modal-desc" style="font-size:.875rem;color:var(--pa-muted);margin-bottom:1rem;"></p>

            <form id="pa-validation-form" method="POST">
                @csrf
                <input type="hidden" name="action" id="pa-modal-action">
                <div class="pa-form-group">
                    <label class="pa-label" for="pa-modal-notes">Notes (optionnel)</label>
                    <textarea class="pa-textarea" id="pa-modal-notes" name="notes" rows="3"
                              placeholder="Commentaire sur cette décision..."></textarea>
                </div>
                <div class="pa-modal-actions">
                    <button type="button" class="pa-btn pa-btn-outline" onclick="closeValidationModal()">
                        Annuler
                    </button>
                    <button type="submit" id="pa-modal-submit" class="pa-btn pa-btn-primary">
                        Confirmer
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>{{-- .pa-page --}}
@endsection

@push('scripts')
{{-- Chart.js via CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
/* =============================================================
   PARTNER ANALYTICS — JavaScript
============================================================= */

// ── Données injectées depuis Blade ──
const CHART_DATA = {
    revenue: {
        labels:   @json($revenueChartData['labels']),
        revenues: @json($revenueChartData['revenues']),
        counts:   @json($revenueChartData['counts']),
    },
    status: {
        labels: ['Complétées', 'En attente', 'Échouées'],
        data:   [
            {{ $stats['completed_transactions'] }},
            {{ $stats['pending_transactions'] }},
            {{ $stats['total_transactions'] - $stats['completed_transactions'] - $stats['pending_transactions'] }}
        ],
    }
};

const PARTNER_ID  = {{ $partner->id }};
const VALIDATE_URL_BASE = "{{ url('/partners') }}/" + PARTNER_ID + "/analytics/payment-requests/";

// ── Changement de période ──
function changePeriod(days) {
    const url = new URL(window.location.href);
    url.searchParams.set('period', days);
    window.location.href = url.toString();
}

// ── Init Alpine component ──
function partnerAnalytics() {
    return {
        init() {
            this.$nextTick(() => {
                initRevenueChart();
                initStatusChart();
            });
        }
    };
}

// ── Revenue Chart (Line) ──
let revenueChartInstance = null;
function initRevenueChart() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');

    revenueChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: CHART_DATA.revenue.labels,
            datasets: [{
                label: 'Revenus (€)',
                data: CHART_DATA.revenue.revenues,
                borderColor: '#6366f1',
                backgroundColor: gradient,
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#6366f1',
                pointRadius: 3,
                pointHoverRadius: 6,
            }, {
                label: 'Transactions',
                data: CHART_DATA.revenue.counts,
                borderColor: '#10b981',
                backgroundColor: 'transparent',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointBackgroundColor: '#10b981',
                pointRadius: 3,
                pointHoverRadius: 6,
                yAxisID: 'y2',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.datasetIndex === 0
                            ? ` ${ctx.parsed.y.toFixed(2)} €`
                            : ` ${ctx.parsed.y} transactions`
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: {
                    position: 'left',
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: { font: { size: 10 }, callback: v => v.toFixed(0) + ' €' }
                },
                y2: {
                    position: 'right',
                    grid: { display: false },
                    ticks: { font: { size: 10 }, stepSize: 1 }
                }
            }
        }
    });
}

// ── Status Donut Chart ──
let statusChartInstance = null;
function initStatusChart() {
    const ctx = document.getElementById('statusChart');
    if (!ctx) return;

    statusChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: CHART_DATA.status.labels,
            datasets: [{
                data: CHART_DATA.status.data,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed} (${ctx.label})`
                    }
                }
            }
        }
    });
}

// ── Modal Validation ──
let currentReservationId = null;
let currentAction = null;

function openValidationModal(reservationId, action, amount, clientName) {
    currentReservationId = reservationId;
    currentAction = action;

    const modal = document.getElementById('pa-validation-modal');
    const title = document.getElementById('pa-modal-title');
    const desc  = document.getElementById('pa-modal-desc');
    const submit = document.getElementById('pa-modal-submit');
    const actionInput = document.getElementById('pa-modal-action');
    const form = document.getElementById('pa-validation-form');

    actionInput.value = action;

    if (action === 'approve') {
        title.textContent  = '✅ Valider le paiement';
        desc.innerHTML     = `Vous allez <strong>approuver</strong> le paiement de <strong>${clientName}</strong> d'un montant de <strong>${amount} €</strong>.`;
        submit.className   = 'pa-btn pa-btn-success';
        submit.textContent = 'Approuver';
    } else {
        title.textContent  = '❌ Rejeter le paiement';
        desc.innerHTML     = `Vous allez <strong>rejeter</strong> la demande de paiement de <strong>${clientName}</strong> (${amount} €). Cette action est irréversible.`;
        submit.className   = 'pa-btn pa-btn-danger';
        submit.textContent = 'Rejeter';
    }

    const actionUrl = VALIDATE_URL_BASE + reservationId + '/validate';
    form.action = actionUrl;

    modal.style.display = 'flex';
    document.getElementById('pa-modal-notes').focus();
}

function closeValidationModal() {
    document.getElementById('pa-validation-modal').style.display = 'none';
    document.getElementById('pa-modal-notes').value = '';
}

// Fermer modal en cliquant l'overlay
document.getElementById('pa-validation-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeValidationModal();
});

// Soumission AJAX du formulaire
document.getElementById('pa-validation-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const submitBtn = document.getElementById('pa-modal-submit');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Traitement…';
    submitBtn.disabled = true;

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        const result = await response.json();

        if (result.success) {
            closeValidationModal();
            // Notification succès
            showToast(result.message, 'success');
            // Recharger la page après 1.2s pour mettre à jour les compteurs
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showToast(result.message || 'Une erreur est survenue.', 'danger');
        }
    } catch (err) {
        showToast('Erreur réseau. Veuillez réessayer.', 'danger');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// ── Toast Notification ──
function showToast(message, type = 'success') {
    const colors = { success: '#10b981', danger: '#ef4444', warning: '#f59e0b' };
    const toast = document.createElement('div');
    toast.style.cssText = `
        position:fixed;top:1.5rem;right:1.5rem;z-index:9999;
        background:${colors[type] || '#6366f1'};color:#fff;
        padding:.75rem 1.25rem;border-radius:12px;font-size:.875rem;font-weight:600;
        box-shadow:0 8px 32px rgba(0,0,0,.2);max-width:340px;
        animation:slideInRight .3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'slideOutRight .3s ease'; setTimeout(() => toast.remove(), 300); }, 3500);
}

// Animations CSS inline
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight  { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
    @keyframes slideOutRight { from{transform:translateX(0);opacity:1} to{transform:translateX(100%);opacity:0} }
`;
document.head.appendChild(style);
</script>
@endpush
