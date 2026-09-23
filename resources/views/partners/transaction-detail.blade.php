@extends('layouts.app')

@section('title', 'Transaction #' . $transaction->id . ' — ' . $partner->name)

@push('styles')
<style>
:root {
    --pa-primary:#6366f1;--pa-success:#10b981;--pa-warning:#f59e0b;
    --pa-danger:#ef4444;--pa-surface:#fff;--pa-bg:#f1f5f9;
    --pa-border:#e2e8f0;--pa-text:#1e293b;--pa-muted:#64748b;
    --pa-radius:14px;--pa-shadow:0 4px 24px rgba(99,102,241,.08);
}
.td-page { background:var(--pa-bg); min-height:100vh; padding:1.5rem; max-width:1000px; margin:0 auto; }
.td-back { display:inline-flex;align-items:center;gap:.4rem;color:var(--pa-muted);font-size:.83rem;text-decoration:none;margin-bottom:1.25rem; }
.td-header { display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap; }
.td-badge {
    display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .75rem;
    border-radius:999px;font-size:.78rem;font-weight:700;
}
.td-badge-success {background:#d1fae5;color:#059669;}
.td-badge-warning {background:#fef3c7;color:#d97706;}
.td-badge-danger  {background:#fee2e2;color:#dc2626;}
.td-badge-gray    {background:#f1f5f9;color:#64748b;}

.td-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem; }
.td-card { background:var(--pa-surface);border-radius:var(--pa-radius);box-shadow:var(--pa-shadow);border:1px solid var(--pa-border);overflow:hidden; }
.td-card-header { padding:1rem 1.3rem;border-bottom:1px solid var(--pa-border);font-size:.9rem;font-weight:700;color:var(--pa-text);display:flex;align-items:center;gap:.5rem; }
.td-card-body { padding:1.1rem 1.3rem; }
.td-row { display:flex;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid var(--pa-bg); }
.td-row:last-child { border-bottom:none; }
.td-key { font-size:.78rem;color:var(--pa-muted);font-weight:500; }
.td-val { font-size:.84rem;font-weight:600;color:var(--pa-text);text-align:right; }
.td-amount-hero { font-size:2.5rem;font-weight:900;color:var(--pa-text);margin:.5rem 0; }
.td-section-title { font-size:1.1rem;font-weight:700;color:var(--pa-text);margin:1.5rem 0 .75rem; }
.td-repartion { display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.75rem;margin-top:.5rem; }
.td-rep-item { background:var(--pa-bg);border-radius:10px;padding:.9rem;text-align:center; }
.td-rep-label { font-size:.7rem;color:var(--pa-muted);font-weight:600;text-transform:uppercase; }
.td-rep-value { font-size:1.2rem;font-weight:800;color:var(--pa-text);margin-top:.25rem; }
.td-rep-pct   { font-size:.72rem;color:var(--pa-muted);margin-top:.1rem; }
</style>
@endpush

@section('content')
<div class="td-page">
    <a href="{{ route('partners.analytics.index', $partner) }}" class="td-back">← Retour aux analytiques</a>

    {{-- HEADER --}}
    <div class="td-header">
        <div style="flex:1;">
            <div style="font-size:.8rem;color:var(--pa-muted);margin-bottom:.25rem;">Transaction</div>
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--pa-text);margin:0;">#{{ $transaction->id }}</h1>
        </div>
        @php
            $statusMap = ['completed'=>['td-badge-success','✅ Complétée'],'confirmed'=>['td-badge-success','✅ Confirmée'],'pending'=>['td-badge-warning','⏳ En attente'],'failed'=>['td-badge-danger','❌ Échouée']];
            $si = $statusMap[$transaction->status] ?? ['td-badge-gray', ucfirst($transaction->status??'—')];
        @endphp
        <span class="td-badge {{ $si[0] }}">{{ $si[1] }}</span>
        <div style="font-size:.8rem;color:var(--pa-muted);">
            {{ $transaction->created_at?->format('d/m/Y à H:i') }}
        </div>
    </div>

    {{-- MONTANT HERO --}}
    <div class="td-card" style="margin-bottom:1.25rem;padding:1.5rem 1.75rem;">
        <div style="font-size:.8rem;color:var(--pa-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Montant total</div>
        <div class="td-amount-hero">{{ number_format($transaction->amount ?? 0, 2, ',', ' ') }} €</div>
        <div style="display:flex;gap:1.25rem;flex-wrap:wrap;">
            @if($transaction->energy_consumed_wh)
            <div>
                <span style="font-size:.75rem;color:var(--pa-muted);">Énergie</span>
                <div style="font-weight:700;">{{ number_format($transaction->energy_consumed_wh / 1000, 2, ',', ' ') }} kWh</div>
            </div>
            @endif
            @if($transaction->duration_minutes)
            <div>
                <span style="font-size:.75rem;color:var(--pa-muted);">Durée</span>
                <div style="font-weight:700;">{{ $transaction->duration_minutes }} min</div>
            </div>
            @endif
            @if($transaction->currency)
            <div>
                <span style="font-size:.75rem;color:var(--pa-muted);">Devise</span>
                <div style="font-weight:700;">{{ $transaction->currency }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- CARDS GRILLE --}}
    <div class="td-grid">
        {{-- CLIENT --}}
        <div class="td-card">
            <div class="td-card-header">👤 Client</div>
            <div class="td-card-body">
                @if($transaction->user)
                <div class="td-row"><span class="td-key">Nom</span><span class="td-val">{{ $transaction->user->name }}</span></div>
                <div class="td-row"><span class="td-key">Email</span><span class="td-val">{{ $transaction->user->email }}</span></div>
                <div class="td-row"><span class="td-key">Téléphone</span><span class="td-val">{{ $transaction->user->phone ?? '—' }}</span></div>
                @else
                <p style="color:var(--pa-muted);font-size:.84rem;">Aucun utilisateur associé.</p>
                @endif
            </div>
        </div>

        {{-- BORNE --}}
        <div class="td-card">
            <div class="td-card-header">⚡ Borne de recharge</div>
            <div class="td-card-body">
                @if($transaction->chargingPoint)
                <div class="td-row"><span class="td-key">Nom</span><span class="td-val">{{ $transaction->chargingPoint->name }}</span></div>
                <div class="td-row"><span class="td-key">Adresse</span><span class="td-val">{{ $transaction->chargingPoint->address ?? '—' }}</span></div>
                <div class="td-row"><span class="td-key">Statut</span><span class="td-val">{{ ucfirst($transaction->chargingPoint->status ?? '—') }}</span></div>
                @else
                <p style="color:var(--pa-muted);font-size:.84rem;">Borne non trouvée.</p>
                @endif
            </div>
        </div>

        {{-- DÉTAILS TECHNIQUE --}}
        <div class="td-card">
            <div class="td-card-header">🔧 Détails techniques</div>
            <div class="td-card-body">
                @if($transaction->steve_transaction_id)
                <div class="td-row"><span class="td-key">ID SteVe</span><span class="td-val">{{ $transaction->steve_transaction_id }}</span></div>
                @endif
                @if($transaction->ocpp_id_tag)
                <div class="td-row"><span class="td-key">Tag OCPP</span><span class="td-val">{{ $transaction->ocpp_id_tag }}</span></div>
                @endif
                @if($transaction->connector_id)
                <div class="td-row"><span class="td-key">Connecteur</span><span class="td-val">#{{ $transaction->connector_id }}</span></div>
                @endif
                @if($transaction->start_timestamp)
                <div class="td-row"><span class="td-key">Début</span><span class="td-val">{{ $transaction->start_timestamp?->format('H:i') }}</span></div>
                @endif
                @if($transaction->stop_timestamp)
                <div class="td-row"><span class="td-key">Fin</span><span class="td-val">{{ $transaction->stop_timestamp?->format('H:i') }}</span></div>
                @endif
                @if($transaction->stop_reason)
                <div class="td-row"><span class="td-key">Raison arrêt</span><span class="td-val">{{ $transaction->stop_reason }}</span></div>
                @endif
                @if($transaction->processed_at)
                <div class="td-row"><span class="td-key">Traité le</span><span class="td-val">{{ $transaction->processed_at?->format('d/m/Y H:i') }}</span></div>
                @endif
            </div>
        </div>
    </div>

    {{-- RÉPARTITION DES PARTS --}}
    @if($transaction->repartition || $transaction->transactionDetail)
    <div class="td-section-title">💰 Répartition des revenus</div>
    <div class="td-card" style="margin-bottom:1.25rem;">
        <div class="td-card-body">
            <div class="td-repartion">
                @php $total = $transaction->amount ?: 1; @endphp

                @if($adminFee = $transaction->getAdminFee())
                <div class="td-rep-item">
                    <div class="td-rep-label">Admin</div>
                    <div class="td-rep-value">{{ number_format($adminFee, 2, ',', ' ') }} €</div>
                    <div class="td-rep-pct">{{ number_format(($adminFee / $total) * 100, 1) }}%</div>
                </div>
                @endif

                @if($integratorFee = $transaction->getIntegratorFee())
                <div class="td-rep-item">
                    <div class="td-rep-label">Intégrateur</div>
                    <div class="td-rep-value">{{ number_format($integratorFee, 2, ',', ' ') }} €</div>
                    <div class="td-rep-pct">{{ number_format(($integratorFee / $total) * 100, 1) }}%</div>
                </div>
                @endif

                @if($operatorShare = $transaction->getOperatorShare())
                <div class="td-rep-item">
                    <div class="td-rep-label">Opérateur</div>
                    <div class="td-rep-value">{{ number_format($operatorShare, 2, ',', ' ') }} €</div>
                    <div class="td-rep-pct">{{ number_format(($operatorShare / $total) * 100, 1) }}%</div>
                </div>
                @endif

                @if($transaction->partner_commission)
                <div class="td-rep-item" style="border:2px solid var(--pa-primary);">
                    <div class="td-rep-label" style="color:var(--pa-primary);">Partenaire</div>
                    <div class="td-rep-value" style="color:var(--pa-primary);">{{ number_format($transaction->partner_commission, 2, ',', ' ') }} €</div>
                    <div class="td-rep-pct">{{ number_format(($transaction->partner_commission / $total) * 100, 1) }}%</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- RETOUR --}}
    <div style="display:flex;gap:.75rem;margin-top:1rem;">
        <a href="{{ route('partners.analytics.index', $partner) }}" class="td-card" style="padding:.7rem 1.3rem;text-decoration:none;color:var(--pa-muted);font-size:.85rem;font-weight:600;display:inline-flex;align-items:center;gap:.4rem;">
            ← Retour au dashboard
        </a>
        <a href="{{ route('partners.analytics.payment-requests', $partner) }}" class="td-card" style="padding:.7rem 1.3rem;text-decoration:none;color:var(--pa-primary);font-size:.85rem;font-weight:600;display:inline-flex;align-items:center;gap:.4rem;">
            💳 Paiements à valider
        </a>
    </div>
</div>
@endsection
