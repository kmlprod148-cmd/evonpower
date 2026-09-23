@extends('layouts.app')

@section('title', 'Demandes de paiement — ' . $partner->name)

@push('styles')
<style>
:root {
    --pa-primary: #6366f1; --pa-success: #10b981; --pa-warning: #f59e0b;
    --pa-danger: #ef4444;  --pa-surface: #fff;    --pa-bg: #f1f5f9;
    --pa-border: #e2e8f0;  --pa-text: #1e293b;    --pa-muted: #64748b;
    --pa-radius: 14px;     --pa-shadow: 0 4px 24px rgba(99,102,241,.08);
}

.pr-page { background: var(--pa-bg); min-height: 100vh; padding: 1.5rem; }

.pr-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem; margin-bottom: 1.75rem;
}
.pr-title { font-size: 1.3rem; font-weight: 700; color: var(--pa-text); }
.pr-subtitle { font-size: .83rem; color: var(--pa-muted); margin-top: .2rem; }

.pr-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1.1rem; border-radius: 10px; font-size: .85rem;
    font-weight: 600; cursor: pointer; transition: all .18s; text-decoration: none; border: none;
}
.pr-btn-primary { background: var(--pa-primary); color: #fff; }
.pr-btn-outline { background: #fff; color: var(--pa-text); border: 1.5px solid var(--pa-border); }
.pr-btn-success { background: var(--pa-success); color: #fff; }
.pr-btn-danger  { background: var(--pa-danger);  color: #fff; }
.pr-btn-sm      { padding: .3rem .75rem; font-size: .78rem; }
.pr-btn-warning { background: var(--pa-warning); color: #fff; }

/* Summary Cards */
.pr-summary { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.pr-sum-card {
    background: var(--pa-surface); border-radius: var(--pa-radius);
    padding: 1.2rem; box-shadow: var(--pa-shadow); border: 1px solid var(--pa-border);
}
.pr-sum-value { font-size: 1.6rem; font-weight: 800; color: var(--pa-text); }
.pr-sum-label { font-size: .75rem; color: var(--pa-muted); margin-top: .3rem; font-weight: 500; }
.pr-sum-icon  { font-size: 1.4rem; margin-bottom: .6rem; }

/* Filter Bar */
.pr-filters {
    background: var(--pa-surface); border-radius: var(--pa-radius);
    padding: .85rem 1.2rem; margin-bottom: 1.25rem;
    border: 1px solid var(--pa-border); box-shadow: var(--pa-shadow);
    display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
}
.pr-filter-label { font-size: .8rem; font-weight: 600; color: var(--pa-muted); }
.pr-filter-btn {
    padding: .3rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 600;
    cursor: pointer; border: 1.5px solid var(--pa-border); background: transparent;
    color: var(--pa-muted); transition: all .15s;
}
.pr-filter-btn.active { background: var(--pa-primary); color: #fff; border-color: var(--pa-primary); }

/* Table */
.pr-card { background: var(--pa-surface); border-radius: var(--pa-radius); box-shadow: var(--pa-shadow); border: 1px solid var(--pa-border); overflow: hidden; }
.pr-card-header { padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--pa-border); display: flex; align-items: center; justify-content: space-between; }
.pr-card-title  { font-size: .95rem; font-weight: 700; color: var(--pa-text); }

.pr-table-wrap { overflow-x: auto; }
.pr-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.pr-table th {
    padding: .75rem 1.1rem; text-align: left; font-size: .72rem; font-weight: 700;
    color: var(--pa-muted); text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 1.5px solid var(--pa-border); white-space: nowrap; background: #f8faff;
}
.pr-table td { padding: .9rem 1.1rem; border-bottom: 1px solid var(--pa-border); vertical-align: middle; }
.pr-table tr:last-child td { border-bottom: none; }
.pr-table tr:hover td { background: #f8faff; }

.pr-badge {
    display: inline-flex; align-items: center; padding: .2rem .65rem;
    border-radius: 999px; font-size: .72rem; font-weight: 700;
}
.pr-badge-pending  { background: #fef3c7; color: #d97706; }
.pr-badge-paid     { background: #d1fae5; color: #059669; }
.pr-badge-failed   { background: #fee2e2; color: #dc2626; }
.pr-badge-canceled { background: #f1f5f9; color: #64748b; }
.pr-badge-confirmed { background: #dbeafe; color: #2563eb; }

.pr-actions { display: flex; gap: .4rem; }

/* Payment Detail Expand */
.pr-detail-row td { background: #f8faff; padding: .75rem 1.1rem; }
.pr-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .75rem; }
.pr-detail-item { }
.pr-detail-key { font-size: .7rem; color: var(--pa-muted); font-weight: 600; text-transform: uppercase; }
.pr-detail-val { font-size: .84rem; font-weight: 700; color: var(--pa-text); margin-top: .1rem; }

/* Modal */
.pr-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.pr-modal { background: #fff; border-radius: var(--pa-radius); padding: 2rem; max-width: 460px; width: 100%; box-shadow: 0 16px 64px rgba(0,0,0,.25); }
.pr-modal-title { font-size: 1.1rem; font-weight: 700; color: var(--pa-text); margin-bottom: 1rem; }
.pr-modal-actions { display: flex; gap: .75rem; margin-top: 1.25rem; justify-content: flex-end; }
.pr-form-group { margin-bottom: 1rem; }
.pr-label { display: block; font-size: .8rem; font-weight: 600; color: var(--pa-text); margin-bottom: .4rem; }
.pr-textarea { width: 100%; padding: .65rem .9rem; border-radius: 9px; border: 1.5px solid var(--pa-border); font-size: .84rem; resize: none; font-family: inherit; outline: none; transition: border-color .15s; }
.pr-textarea:focus { border-color: var(--pa-primary); }

.pr-empty { text-align: center; padding: 3rem; color: var(--pa-muted); }
.pr-empty-icon { font-size: 3rem; margin-bottom: .75rem; }
.pr-pagination { padding: .75rem 1.4rem; border-top: 1px solid var(--pa-border); }

@media (max-width: 640px) {
    .pr-summary { grid-template-columns: repeat(2, 1fr); }
}
</style>
@endpush

@section('content')
<div class="pr-page">

    {{-- HEADER --}}
    <div class="pr-header">
        <div>
            <a href="{{ route('partners.analytics.index', $partner) }}" style="display:inline-flex;align-items:center;gap:.4rem;color:var(--pa-muted);font-size:.83rem;text-decoration:none;margin-bottom:.5rem;">
                ← Retour aux analytiques
            </a>
            <div class="pr-title">💳 Demandes de paiement</div>
            <div class="pr-subtitle">{{ $partner->name }} — Gestion et validation des paiements</div>
        </div>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <a href="{{ route('partners.analytics.index', $partner) }}" class="pr-btn pr-btn-outline">
                📊 Dashboard
            </a>
        </div>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="pr-summary">
        <div class="pr-sum-card">
            <div class="pr-sum-icon">⏳</div>
            <div class="pr-sum-value">{{ $summary['count_pending'] }}</div>
            <div class="pr-sum-label">En attente</div>
        </div>
        <div class="pr-sum-card">
            <div class="pr-sum-icon">💰</div>
            <div class="pr-sum-value">{{ number_format($summary['total_pending'], 2, ',', ' ') }} €</div>
            <div class="pr-sum-label">Montant en attente</div>
        </div>
        <div class="pr-sum-card">
            <div class="pr-sum-icon">✅</div>
            <div class="pr-sum-value">{{ $summary['count_paid'] }}</div>
            <div class="pr-sum-label">Payés</div>
        </div>
        <div class="pr-sum-card">
            <div class="pr-sum-icon">💵</div>
            <div class="pr-sum-value">{{ number_format($summary['total_paid'], 2, ',', ' ') }} €</div>
            <div class="pr-sum-label">Montant encaissé</div>
        </div>
        <div class="pr-sum-card">
            <div class="pr-sum-icon">❌</div>
            <div class="pr-sum-value">{{ number_format($summary['total_failed'], 2, ',', ' ') }} €</div>
            <div class="pr-sum-label">Montant échoué</div>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="pr-filters">
        <span class="pr-filter-label">Statut :</span>
        @foreach(['all' => 'Tous', 'pending' => 'En attente', 'paid' => 'Payés', 'failed' => 'Échoués'] as $val => $lbl)
        <button class="pr-filter-btn {{ $status === $val ? 'active' : '' }}"
                onclick="setFilter('status', '{{ $val }}')">{{ $lbl }}</button>
        @endforeach

        <span class="pr-filter-label" style="margin-left:.5rem;">Période :</span>
        @foreach([30 => '30j', 90 => '3m', 180 => '6m', 365 => '1an'] as $days => $lbl)
        <button class="pr-filter-btn {{ $period == $days ? 'active' : '' }}"
                onclick="setFilter('period', '{{ $days }}')">{{ $lbl }}</button>
        @endforeach
    </div>

    {{-- PAYMENTS TABLE --}}
    <div class="pr-card">
        <div class="pr-card-header">
            <span class="pr-card-title">
                Demandes de paiement
                <span style="background:#e0e7ff;color:#6366f1;padding:.1rem .55rem;border-radius:999px;font-size:.75rem;margin-left:.4rem;">
                    {{ $paymentRequests->total() }}
                </span>
            </span>
        </div>
        <div class="pr-table-wrap">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Borne</th>
                        <th>Date réservation</th>
                        <th>Montant estimé</th>
                        <th>Montant réel</th>
                        <th>Mode paiement</th>
                        <th>Statut réservation</th>
                        <th>Statut paiement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentRequests as $reservation)
                    <tr id="row-{{ $reservation->id }}">
                        <td style="font-weight:600;color:var(--pa-primary);">#{{ $reservation->id }}</td>
                        <td>
                            <div style="font-weight:600;">
                                {{ $reservation->user?->name ?? $reservation->guest_email ?? 'Client inconnu' }}
                            </div>
                            <div style="font-size:.72rem;color:var(--pa-muted);">
                                {{ $reservation->user?->email ?? $reservation->guest_phone ?? '' }}
                            </div>
                        </td>
                        <td style="font-size:.84rem;">
                            {{ $reservation->chargingPoint?->name ?? '—' }}
                        </td>
                        <td style="font-size:.8rem;color:var(--pa-muted);">
                            {{ $reservation->created_at?->format('d/m/Y H:i') }}<br>
                            <span style="font-size:.7rem;">{{ $reservation->created_at?->diffForHumans() }}</span>
                        </td>
                        <td>
                            <span style="font-weight:800;">
                                {{ number_format($reservation->estimated_cost ?? 0, 2, ',', ' ') }} €
                            </span>
                        </td>
                        <td>
                            @if($reservation->actual_cost)
                                <span style="font-weight:700;color:var(--pa-success);">
                                    {{ number_format($reservation->actual_cost, 2, ',', ' ') }} €
                                </span>
                            @else
                                <span style="color:var(--pa-muted);">—</span>
                            @endif
                        </td>
                        <td style="font-size:.8rem;">
                            @php
                                $methodLabels = [
                                    'prepaid_credit'  => '💳 Crédit prépayé',
                                    'postpaid_credit' => '💳 Crédit postpayé',
                                    'cmi'             => '🏦 CMI',
                                    'stripe'          => '💳 Stripe',
                                    'credit'          => '💰 Crédit',
                                    'cash'            => '💵 Espèces',
                                ];
                            @endphp
                            {{ $methodLabels[$reservation->payment_method] ?? ($reservation->payment_method ? ucfirst($reservation->payment_method) : '—') }}
                        </td>
                        <td>
                            @php
                                $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus ? $reservation->status->value : (string) $reservation->status;
                                $statusClass = match($statusValue) {
                                    'confirmed', 'active' => 'pr-badge-confirmed',
                                    'completed'           => 'pr-badge-paid',
                                    'canceled'            => 'pr-badge-canceled',
                                    default               => 'pr-badge-pending',
                                };
                                $statusLabel = match($statusValue) {
                                    'pending'              => 'En attente',
                                    'pending_confirmation' => 'En validation',
                                    'confirmed'            => 'Confirmée',
                                    'active'               => 'Active',
                                    'completed'            => 'Terminée',
                                    'canceled'             => 'Annulée',
                                    default                => ucfirst($statusValue),
                                };
                            @endphp
                            <span class="pr-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td>
                            @php
                                $payStatus = strtoupper($reservation->payment_status ?? 'PENDING');
                                $payClass  = match($payStatus) {
                                    'PAID', 'PAYE'  => 'pr-badge-paid',
                                    'FAILED'        => 'pr-badge-failed',
                                    'REFUNDED'      => 'pr-badge-canceled',
                                    default         => 'pr-badge-pending',
                                };
                                $payLabel  = match($payStatus) {
                                    'PAID', 'PAYE'  => '✅ Payé',
                                    'FAILED'        => '❌ Échoué',
                                    'REFUNDED'      => '↩ Remboursé',
                                    default         => '⏳ En attente',
                                };
                            @endphp
                            <span class="pr-badge {{ $payClass }}">{{ $payLabel }}</span>
                        </td>
                        <td>
                            <div class="pr-actions">
                                @if(in_array($payStatus, ['PENDING', '']) || $payStatus === null || !in_array($payStatus, ['PAID', 'PAYE', 'REFUNDED']))
                                <button class="pr-btn pr-btn-success pr-btn-sm"
                                        onclick="openValidationModal({{ $reservation->id }}, 'approve', '{{ number_format($reservation->estimated_cost ?? 0, 2) }}', '{{ addslashes($reservation->user?->name ?? 'Client') }}')"
                                        title="Valider le paiement">
                                    ✓ Valider
                                </button>
                                <button class="pr-btn pr-btn-danger pr-btn-sm"
                                        onclick="openValidationModal({{ $reservation->id }}, 'reject', '{{ number_format($reservation->estimated_cost ?? 0, 2) }}', '{{ addslashes($reservation->user?->name ?? 'Client') }}')"
                                        title="Rejeter le paiement">
                                    ✗
                                </button>
                                @else
                                <span style="font-size:.75rem;color:var(--pa-muted);">Traité</span>
                                @endif
                                <button class="pr-btn pr-btn-outline pr-btn-sm"
                                        onclick="toggleDetail({{ $reservation->id }})"
                                        title="Voir les détails">
                                    👁
                                </button>
                            </div>
                        </td>
                    </tr>
                    {{-- Ligne de détail expandable --}}
                    <tr id="detail-{{ $reservation->id }}" style="display:none;" class="pr-detail-row">
                        <td colspan="10">
                            <div class="pr-detail-grid">
                                <div class="pr-detail-item">
                                    <div class="pr-detail-key">Heure début</div>
                                    <div class="pr-detail-val">{{ $reservation->start_time?->format('d/m/Y H:i') ?? '—' }}</div>
                                </div>
                                <div class="pr-detail-item">
                                    <div class="pr-detail-key">Heure fin</div>
                                    <div class="pr-detail-val">{{ $reservation->end_time?->format('d/m/Y H:i') ?? '—' }}</div>
                                </div>
                                <div class="pr-detail-item">
                                    <div class="pr-detail-key">Énergie estimée</div>
                                    <div class="pr-detail-val">{{ $reservation->estimated_energy ? number_format($reservation->estimated_energy, 2, ',', ' ') . ' kWh' : '—' }}</div>
                                </div>
                                <div class="pr-detail-item">
                                    <div class="pr-detail-key">Durée estimée</div>
                                    <div class="pr-detail-val">{{ $reservation->estimated_duration ? $reservation->estimated_duration . ' min' : '—' }}</div>
                                </div>
                                <div class="pr-detail-item">
                                    <div class="pr-detail-key">Mode de paiement</div>
                                    <div class="pr-detail-val">{{ $reservation->payment_mode ?? '—' }}</div>
                                </div>
                                <div class="pr-detail-item">
                                    <div class="pr-detail-key">Transaction ID</div>
                                    <div class="pr-detail-val">
                                        @if($reservation->transaction)
                                            #{{ $reservation->transaction->id }}
                                        @else
                                            Aucune transaction
                                        @endif
                                    </div>
                                </div>
                                @if($reservation->notes)
                                <div class="pr-detail-item" style="grid-column:1/-1;">
                                    <div class="pr-detail-key">Notes</div>
                                    <div class="pr-detail-val">{{ $reservation->notes }}</div>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10">
                            <div class="pr-empty">
                                <div class="pr-empty-icon">✅</div>
                                <div style="font-weight:600;color:var(--pa-text);margin-bottom:.3rem;">
                                    Aucune demande de paiement trouvée
                                </div>
                                <div style="font-size:.83rem;">
                                    Aucune réservation ne correspond aux filtres sélectionnés.
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paymentRequests->hasPages())
        <div class="pr-pagination">
            {{ $paymentRequests->appends(['status' => $status, 'period' => $period])->links('pagination::simple-tailwind') }}
        </div>
        @endif
    </div>

    {{-- MODAL VALIDATION --}}
    <div id="pr-modal" class="pr-modal-overlay" style="display:none;">
        <div class="pr-modal">
            <div class="pr-modal-title" id="pr-modal-title">Validation du paiement</div>
            <p id="pr-modal-desc" style="font-size:.875rem;color:var(--pa-muted);margin-bottom:1rem;"></p>

            <form id="pr-validation-form" method="POST">
                @csrf
                <input type="hidden" name="action" id="pr-modal-action">
                <div class="pr-form-group">
                    <label class="pr-label" for="pr-modal-notes">Notes (optionnel)</label>
                    <textarea class="pr-textarea" id="pr-modal-notes" name="notes" rows="3"
                              placeholder="Raisonnement ou commentaire..."></textarea>
                </div>
                <div class="pr-modal-actions">
                    <button type="button" class="pr-btn pr-btn-outline" onclick="closeModal()">Annuler</button>
                    <button type="submit" id="pr-modal-submit" class="pr-btn pr-btn-success">Confirmer</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const VALIDATE_BASE = "{{ url('/partners') }}/{{ $partner->id }}/analytics/payment-requests/";

function setFilter(key, val) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, val);
    window.location.href = url.toString();
}

function toggleDetail(id) {
    const row = document.getElementById('detail-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

function openValidationModal(resId, action, amount, clientName) {
    const modal  = document.getElementById('pr-modal');
    const title  = document.getElementById('pr-modal-title');
    const desc   = document.getElementById('pr-modal-desc');
    const submit = document.getElementById('pr-modal-submit');
    const form   = document.getElementById('pr-validation-form');

    document.getElementById('pr-modal-action').value = action;
    form.action = VALIDATE_BASE + resId + '/validate';

    if (action === 'approve') {
        title.textContent  = '✅ Valider le paiement';
        desc.innerHTML     = `Approuver le paiement de <strong>${clientName}</strong> — <strong>${amount} €</strong>`;
        submit.className   = 'pr-btn pr-btn-success';
        submit.textContent = 'Approuver';
    } else {
        title.textContent  = '❌ Rejeter le paiement';
        desc.innerHTML     = `Rejeter la demande de <strong>${clientName}</strong> (${amount} €). Cette action est irréversible.`;
        submit.className   = 'pr-btn pr-btn-danger';
        submit.textContent = 'Rejeter';
    }

    modal.style.display = 'flex';
    document.getElementById('pr-modal-notes').focus();
}

function closeModal() {
    document.getElementById('pr-modal').style.display = 'none';
    document.getElementById('pr-modal-notes').value = '';
}

document.getElementById('pr-modal')?.addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

document.getElementById('pr-validation-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const submit = document.getElementById('pr-modal-submit');
    const orig = submit.textContent;
    submit.textContent = 'Traitement…'; submit.disabled = true;

    try {
        const res = await fetch(this.action, {
            method: 'POST', body: new FormData(this),
            headers: { 'X-Requested-With':'XMLHttpRequest', Accept:'application/json' }
        });
        const data = await res.json();

        closeModal();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showToast(data.message || 'Erreur.', 'danger');
        }
    } catch(err) { showToast('Erreur réseau.', 'danger'); }
    finally { submit.textContent = orig; submit.disabled = false; }
});

function showToast(msg, type) {
    const colors = { success:'#10b981', danger:'#ef4444' };
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;top:1.5rem;right:1.5rem;z-index:9999;background:${colors[type]||'#6366f1'};color:#fff;padding:.75rem 1.25rem;border-radius:12px;font-size:.875rem;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,.2);max-width:340px;animation:slideIn .3s ease;`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.animation='slideOut .3s ease'; setTimeout(()=>t.remove(),300); }, 3500);
}
const s = document.createElement('style');
s.textContent = '@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}} @keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(100%);opacity:0}}';
document.head.appendChild(s);
</script>
@endpush
