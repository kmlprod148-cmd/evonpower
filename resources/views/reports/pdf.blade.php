<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport: {{ $report->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        h1 {
            font-size: 18px;
            text-align: center;
            margin-bottom: 20px;
            color: #2d3748;
        }
        .info-block {
            margin-bottom: 20px;
        }
        .info-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
            color: #2d3748;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-published {
            background-color: #c6f6d5;
            color: #22543d;
        }
        .status-draft {
            background-color: #fefcbf;
            color: #744210;
        }
        .status-archived {
            background-color: #e2e8f0;
            color: #1a202c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #e2e8f0;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f7fafc;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #718096;
        }
    </style>
</head>
<body>
    <h1>{{ $report->title }}</h1>
    
    <div class="info-block">
        <div class="info-title">Informations générales</div>
        <table>
            <tr>
                <th width="30%">ID</th>
                <td>{{ $report->id }}</td>
            </tr>
            <tr>
                <th>Période</th>
                <td>{{ \Carbon\Carbon::parse($report->period_start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($report->period_end)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Statut</th>
                <td>
                    <span class="status-badge status-{{ $report->status }}">
                        @if($report->status === 'published') Publié 
                        @elseif($report->status === 'draft') Brouillon 
                        @else Archivé @endif
                    </span>
                </td>
            </tr>
            <tr>
                <th>Date de création</th>
                <td>{{ $report->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Créé par</th>
                <td>{{ $report->user->name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
    
    <div class="info-block">
        <div class="info-title">Description</div>
        <p>{{ $report->description ?: 'Aucune description fournie' }}</p>
    </div>
    
    <div class="info-block">
        <div class="info-title">Données du rapport</div>
        <table>
            <thead>
                <tr>
                    <th>Métrique</th>
                    <th>Valeur</th>
                </tr>
            </thead>
            <tbody>
                <!-- Add your actual report data here -->
                <tr>
                    <td>Total des transactions</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>Montant total</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>Moyenne par transaction</td>
                    <td>-</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} | Rapport #{{ $report->id }}
    </div>
</body>
</html>