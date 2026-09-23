<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport Financier — EVON</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1f2937;
            background: #fff;
        }

        /* ── Header ─────────────────────────────────── */
        .header {
            background: #3bb86b;
            color: #fff;
            padding: 18px 24px;
            margin-bottom: 20px;
        }
        .header-top {
            display: block;
            overflow: hidden;
            margin-bottom: 6px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: -0.3px;
        }
        .header .subtitle {
            font-size: 11px;
            opacity: 0.85;
            margin-top: 2px;
        }
        .header .meta {
            font-size: 10px;
            opacity: 0.8;
            margin-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 8px;
        }

        /* ── Summary Boxes ───────────────────────────── */
        .summary-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .summary-grid td {
            width: 25%;
            padding: 0 6px;
            vertical-align: top;
            border: none;
        }
        .summary-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
        }
        .summary-box .label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .summary-box .value {
            font-size: 15px;
            font-weight: bold;
            color: #1f2937;
        }
        .summary-box.green  { border-color: #3bb86b; background: #f0fdf4; }
        .summary-box.green .value { color: #3bb86b; }
        .summary-box.red    { border-color: #ef4444; background: #fef2f2; }
        .summary-box.red .value   { color: #ef4444; }
        .summary-box.blue   { border-color: #3b82f6; background: #eff6ff; }
        .summary-box.blue .value  { color: #3b82f6; }
        .summary-box.amber  { border-color: #f59e0b; background: #fffbeb; }
        .summary-box.amber .value { color: #d97706; }

        /* ── Section titles ──────────────────────────── */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1f2937;
            padding: 8px 12px;
            background: #f3f4f6;
            border-left: 3px solid #3bb86b;
            margin-bottom: 8px;
            margin-top: 20px;
        }
        .section-title span {
            font-size: 10px;
            font-weight: normal;
            color: #6b7280;
            margin-left: 6px;
        }

        /* ── Tables ──────────────────────────────────── */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10px;
        }
        table.data-table th {
            background: #f9fafb;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            color: #374151;
            padding: 7px 8px;
            border: 1px solid #e5e7eb;
            white-space: nowrap;
        }
        table.data-table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background: #fafafa;
        }
        table.data-table .amount {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }
        table.data-table .center {
            text-align: center;
        }
        table.data-table .muted {
            color: #6b7280;
        }

        /* ── Status badges ───────────────────────────── */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-success  { background: #dcfce7; color: #166534; }
        .badge-warning  { background: #fef9c3; color: #854d0e; }
        .badge-danger   { background: #fee2e2; color: #991b1b; }
        .badge-info     { background: #dbeafe; color: #1e40af; }
        .badge-gray     { background: #f3f4f6; color: #374151; }

        /* ── Financial breakdown table ───────────────── */
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .breakdown-table td {
            padding: 7px 12px;
            border: 1px solid #e5e7eb;
            font-size: 11px;
        }
        .breakdown-table .row-header td {
            background: #f3f4f6;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #374151;
        }
        .breakdown-table .row-total td {
            background: #ecfdf5;
            font-weight: bold;
            font-size: 12px;
            color: #166534;
            border-top: 2px solid #3bb86b;
        }
        .breakdown-table .row-total.negative td {
            background: #fef2f2;
            color: #991b1b;
            border-top-color: #ef4444;
        }
        .breakdown-table td.right { text-align: right; font-weight: bold; }

        /* ── Empty state ─────────────────────────────── */
        .empty-row td {
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            padding: 12px;
        }

        /* ── Footer ──────────────────────────────────── */
        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }

        /* ── Page break hints ────────────────────────── */
        .page-break { page-break-before: always; }
        .no-break   { page-break-inside: avoid; }
    </style>
</head>
<body>

{{-- ── HEADER ───────────────────────────────────────────────────── --}}
<div class="header">
    <div class="header-top">
        <h1>Rapport Financier Global</h1>
        <div class="subtitle">EVON Power — Gestion des paiements et recharges EV</div>
    </div>
    <div class="meta">
        Généré le {{ $generatedAt->format('d/m/Y à H:i') }}
        @if(!empty($filters['date_from']) || !empty($filters['date_to']))
            &nbsp;·&nbsp;
            Période :
            {{ !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : '–' }}
            →
            {{ !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'aujourd\'hui' }}
        @else
            &nbsp;·&nbsp; Toutes les périodes
        @endif
        &nbsp;·&nbsp;
        {{ $transactions->count() }} transactions &nbsp;·&nbsp;
        {{ $withdrawals->count() }} retraits &nbsp;·&nbsp;
        {{ $sessions->count() }} sessions
    </div>
</div>

{{-- ── FINANCIAL SUMMARY BOXES ──────────────────────────────────── --}}
<table class="summary-grid">
    <tr>
        <td style="padding-left:0">
            <div class="summary-box green">
                <div class="label">Revenus transactions</div>
                <div class="value">{{ number_format($summary['revenus']['transactions'] ?? 0, 2, ',', ' ') }} €</div>
            </div>
        </td>
        <td>
            <div class="summary-box blue">
                <div class="label">Revenus sessions</div>
                <div class="value">{{ number_format($summary['revenus']['sessions'] ?? 0, 2, ',', ' ') }} €</div>
            </div>
        </td>
        <td>
            <div class="summary-box red">
                <div class="label">Retraits complétés</div>
                <div class="value">{{ number_format($summary['depenses']['retraits'] ?? 0, 2, ',', ' ') }} €</div>
            </div>
        </td>
        <td style="padding-right:0">
            @php $soldeNet = $summary['totals']['solde_net'] ?? 0; @endphp
            <div class="summary-box {{ $soldeNet >= 0 ? 'green' : 'red' }}">
                <div class="label">Solde net</div>
                <div class="value">{{ number_format($soldeNet, 2, ',', ' ') }} €</div>
            </div>
        </td>
    </tr>
</table>

{{-- ── FINANCIAL BREAKDOWN ───────────────────────────────────────── --}}
<div class="no-break">
    <div class="section-title">Synthèse financière</div>
    <table class="breakdown-table">
        <tr class="row-header"><td colspan="2">Revenus</td></tr>
        <tr>
            <td>Transactions (paiements clients)</td>
            <td class="right">{{ number_format($summary['revenus']['transactions'] ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        <tr>
            <td>Sessions de recharge (coût réel)</td>
            <td class="right">{{ number_format($summary['revenus']['sessions'] ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        @php $totalRevenus = ($summary['revenus']['transactions'] ?? 0) + ($summary['revenus']['sessions'] ?? 0); @endphp
        <tr>
            <td style="font-weight:bold;color:#374151">Total revenus</td>
            <td class="right" style="font-weight:bold">{{ number_format($totalRevenus, 2, ',', ' ') }} €</td>
        </tr>

        <tr class="row-header"><td colspan="2">Dépenses</td></tr>
        <tr>
            <td>Retraits complétés (montant net)</td>
            <td class="right">{{ number_format($summary['depenses']['retraits'] ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        <tr>
            <td>Frais sur retraits</td>
            <td class="right">{{ number_format($summary['depenses']['frais_retraits'] ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        @php $totalDepenses = ($summary['depenses']['retraits'] ?? 0) + ($summary['depenses']['frais_retraits'] ?? 0); @endphp
        <tr>
            <td style="font-weight:bold;color:#374151">Total dépenses</td>
            <td class="right" style="font-weight:bold">{{ number_format($totalDepenses, 2, ',', ' ') }} €</td>
        </tr>

        <tr class="row-header"><td colspan="2">Portefeuilles</td></tr>
        <tr>
            <td>Total crédits wallets</td>
            <td class="right">{{ number_format($summary['wallets']['total_credits'] ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        <tr>
            <td>Total débits wallets</td>
            <td class="right">{{ number_format($summary['wallets']['total_debits'] ?? 0, 2, ',', ' ') }} €</td>
        </tr>

        <tr class="row-total {{ $soldeNet < 0 ? 'negative' : '' }}">
            <td>SOLDE NET (Revenus − Retraits)</td>
            <td class="right">{{ number_format($soldeNet, 2, ',', ' ') }} €</td>
        </tr>
    </table>
</div>

{{-- ── TRANSACTIONS ──────────────────────────────────────────────── --}}
<div class="section-title">
    Transactions
    <span>({{ $transactions->count() }} enregistrement(s))</span>
</div>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Client</th>
            <th>Borne</th>
            <th>Type</th>
            <th>Statut</th>
            <th class="amount">Montant</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $tx)
        <tr>
            <td class="muted">{{ $tx->id }}</td>
            <td class="muted">{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
            <td>{{ $tx->user?->name ?? '—' }}</td>
            <td class="muted">{{ $tx->chargingPoint?->name ?? ($tx->chargingPoint?->charge_point_id ?? '—') }}</td>
            <td class="center">
                @php
                    $typeMap = ['charge' => 'badge-blue', 'credit' => 'badge-success', 'debit' => 'badge-danger', 'refund' => 'badge-warning'];
                    $typeCls = $typeMap[$tx->type ?? ''] ?? 'badge-gray';
                @endphp
                <span class="badge {{ $typeCls }}">{{ $tx->type ?? '—' }}</span>
            </td>
            <td class="center">
                @php
                    $stCls = match($tx->status ?? '') { 'completed','success' => 'badge-success', 'pending' => 'badge-warning', 'failed','cancelled' => 'badge-danger', default => 'badge-gray' };
                @endphp
                <span class="badge {{ $stCls }}">{{ $tx->status ?? '—' }}</span>
            </td>
            <td class="amount">{{ number_format($tx->amount ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="7">Aucune transaction pour cette période</td></tr>
        @endforelse
    </tbody>
    @if($transactions->count() > 0)
    <tfoot>
        <tr>
            <td colspan="6" style="text-align:right;font-weight:bold;background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">Total</td>
            <td class="amount" style="background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">
                {{ number_format($transactions->sum('amount'), 2, ',', ' ') }} €
            </td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── WITHDRAWAL REQUESTS ───────────────────────────────────────── --}}
<div class="page-break"></div>

<div class="section-title">
    Demandes de retrait
    <span>({{ $withdrawals->count() }} enregistrement(s))</span>
</div>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Propriétaire</th>
            <th>Statut</th>
            <th class="amount">Montant brut</th>
            <th class="amount">Frais</th>
            <th class="amount">Montant net</th>
        </tr>
    </thead>
    <tbody>
        @forelse($withdrawals as $wd)
        <tr>
            <td class="muted">{{ $wd->id }}</td>
            <td class="muted">{{ $wd->created_at?->format('d/m/Y H:i') }}</td>
            <td>{{ $wd->owner?->name ?? '—' }}</td>
            <td class="center">
                @php
                    $wCls = match($wd->status ?? '') { 'completed' => 'badge-success', 'pending','processing' => 'badge-warning', 'rejected','cancelled' => 'badge-danger', default => 'badge-gray' };
                    $wLabel = match($wd->status ?? '') { 'completed' => 'Complété', 'pending' => 'En attente', 'processing' => 'En cours', 'rejected' => 'Rejeté', 'cancelled' => 'Annulé', default => $wd->status ?? '—' };
                @endphp
                <span class="badge {{ $wCls }}">{{ $wLabel }}</span>
            </td>
            <td class="amount">{{ number_format($wd->amount ?? 0, 2, ',', ' ') }} €</td>
            <td class="amount muted">{{ number_format($wd->fee ?? 0, 2, ',', ' ') }} €</td>
            <td class="amount">{{ number_format($wd->net_amount ?? (($wd->amount ?? 0) - ($wd->fee ?? 0)), 2, ',', ' ') }} €</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="7">Aucun retrait pour cette période</td></tr>
        @endforelse
    </tbody>
    @if($withdrawals->count() > 0)
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right;font-weight:bold;background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">Total</td>
            <td class="amount" style="background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">
                {{ number_format($withdrawals->sum('amount'), 2, ',', ' ') }} €
            </td>
            <td class="amount" style="background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">
                {{ number_format($withdrawals->sum('fee'), 2, ',', ' ') }} €
            </td>
            <td class="amount" style="background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">
                {{ number_format($withdrawals->sum('net_amount'), 2, ',', ' ') }} €
            </td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── CHARGING SESSIONS ─────────────────────────────────────────── --}}
<div class="section-title">
    Sessions de recharge
    <span>({{ $sessions->count() }} enregistrement(s))</span>
</div>
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Utilisateur</th>
            <th>Borne</th>
            <th>Statut</th>
            <th class="amount">Énergie (kWh)</th>
            <th class="amount">Durée (min)</th>
            <th class="amount">Coût</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sessions as $sess)
        <tr>
            <td class="muted">{{ $sess->id }}</td>
            <td class="muted">{{ $sess->created_at?->format('d/m/Y H:i') }}</td>
            <td>{{ $sess->user?->name ?? '—' }}</td>
            <td class="muted">{{ $sess->chargingPoint?->name ?? ($sess->chargingPoint?->charge_point_id ?? '—') }}</td>
            <td class="center">
                @php
                    $sCls = match($sess->status ?? '') { 'completed','finished' => 'badge-success', 'charging','active' => 'badge-info', 'pending','preparing' => 'badge-warning', 'faulted','error' => 'badge-danger', default => 'badge-gray' };
                @endphp
                <span class="badge {{ $sCls }}">{{ $sess->status ?? '—' }}</span>
            </td>
            <td class="amount">{{ number_format($sess->energy_delivered ?? 0, 2, ',', ' ') }}</td>
            <td class="amount">{{ $sess->duration ? round($sess->duration / 60) : '—' }}</td>
            <td class="amount">{{ number_format($sess->actual_cost ?? 0, 2, ',', ' ') }} €</td>
        </tr>
        @empty
        <tr class="empty-row"><td colspan="8">Aucune session pour cette période</td></tr>
        @endforelse
    </tbody>
    @if($sessions->count() > 0)
    <tfoot>
        <tr>
            <td colspan="5" style="text-align:right;font-weight:bold;background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">Total</td>
            <td class="amount" style="background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">
                {{ number_format($sessions->sum('energy_delivered'), 2, ',', ' ') }} kWh
            </td>
            <td class="amount" style="background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">
                {{ round($sessions->sum('duration') / 60) }} min
            </td>
            <td class="amount" style="background:#f9fafb;border:1px solid #e5e7eb;padding:7px 8px;">
                {{ number_format($sessions->sum('actual_cost'), 2, ',', ' ') }} €
            </td>
        </tr>
    </tfoot>
    @endif
</table>

{{-- ── FOOTER ────────────────────────────────────────────────────── --}}
<div class="footer">
    Rapport financier généré automatiquement le {{ $generatedAt->format('d/m/Y à H:i') }} &nbsp;|&nbsp;
    EVON Power &nbsp;|&nbsp; Document confidentiel
    @if(!empty($filters['date_from']) || !empty($filters['date_to']))
        &nbsp;|&nbsp;
        Période : {{ !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : '–' }}
        → {{ !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'aujourd\'hui' }}
    @endif
</div>

</body>
</html>
