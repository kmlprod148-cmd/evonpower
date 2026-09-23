<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export des Transactions - {{ date('Y-m-d') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary h3 {
            margin-top: 0;
            color: #333;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        .summary-item .label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .amount {
            text-align: right;
        }
        .status-completed {
            color: #28a745;
            font-weight: bold;
        }
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        .status-cancelled {
            color: #dc3545;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport des Transactions</h1>
        <p>Généré le {{ date('d/m/Y à H:i') }}</p>
        @if(!empty($filters['start_date']) || !empty($filters['end_date']))
            <p>
                Période: 
                {{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') : 'Début' }}
                - 
                {{ $filters['end_date'] ? \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') : 'Fin' }}
            </p>
        @endif
    </div>

    <div class="summary">
        <h3>Résumé</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value">{{ $transactions->count() }}</div>
                <div class="label">Total Transactions</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ number_format($transactions->sum('price_total'), 2) }} EUR</div>
                <div class="label">Montant Total</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ $transactions->where('status', 'completed')->count() }}</div>
                <div class="label">Terminées</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ $transactions->where('status', 'pending')->count() }}</div>
                <div class="label">En Attente</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Client</th>
                <th>Point de Charge</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Commission Admin</th>
                <th>Commission Intégrateur</th>
                <th>Commission Partenaire</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->id }}</td>
                    <td>
                        @switch($transaction->transaction_type ?? 'client')
                            @case('client')
                                Client
                                @break
                            @case('admin_integrator')
                                Admin → Intégrateur
                                @break
                            @case('integrator_operator')
                                Intégrateur → Opérateur
                                @break
                            @default
                                Autre
                        @endswitch
                    </td>
                    <td>
                        @if($transaction->reservation && $transaction->reservation->user)
                            {{ $transaction->reservation->user->name }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if($transaction->chargingPoint)
                            {{ $transaction->chargingPoint->name }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="amount">{{ number_format($transaction->price_total ?? $transaction->amount ?? 0, 2) }} EUR</td>
                    <td class="status-{{ $transaction->status ?? 'unknown' }}">
                        {{ ucfirst($transaction->status ?? 'Non défini') }}
                    </td>
                    <td>{{ $transaction->created_at ? $transaction->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                    <td class="amount">{{ number_format($transaction->admin_commission ?? 0, 2) }} EUR</td>
                    <td class="amount">{{ number_format($transaction->integrator_commission ?? 0, 2) }} EUR</td>
                    <td class="amount">{{ number_format($transaction->partner_commission ?? 0, 2) }} EUR</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Rapport généré automatiquement par le système EVON Power</p>
        <p>Page 1 - {{ $transactions->count() }} transactions affichées</p>
    </div>
</body>
</html>
