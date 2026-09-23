<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Résultat du Paiement</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="now">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        table th, table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            margin-top: 1rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Résultat du Paiement</h1>

        @if($result['hash_valid'] && $result['payment_approved'])
            <div class="success">
                <h4>✓ Paiement réussi</h4>
                <p>Votre paiement a été traité avec succès. Votre réservation est en cours de traitement.</p>
            </div>
        @elseif($result['hash_valid'] && !$result['payment_approved'])
            <div class="error">
                <h4>✗ Paiement échoué</h4>
                <p>{{ $result['error_message'] ?? 'Le paiement n\'a pas pu être effectué.' }}</p>
            </div>
        @else
            <div class="warning">
                <h4>⚠ Alerte de sécurité</h4>
                <p>La signature numérique n'est pas valide. Veuillez contacter le support si vous avez effectué un paiement.</p>
            </div>
        @endif

        <h3>Détails de la transaction</h3>
        <table>
            <tr>
                <th>Paramètre</th>
                <th>Valeur</th>
            </tr>
            @foreach($postData as $key => $value)
                <tr>
                    <td>{{ $key }}</td>
                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                </tr>
            @endforeach
        </table>

        <h3>Informations de la réservation</h3>
        <table>
            <tr>
                <th>ID Réservation</th>
                <td>{{ $reservation->id }}</td>
            </tr>
            <tr>
                <th>Montant</th>
                <td>{{ number_format($reservation->estimated_cost ?? $reservation->amount, 2) }} {{ $reservation->pricingPlan->currency ?? 'EUR' }}</td>
            </tr>
            <tr>
                <th>Statut</th>
                <td>{{ $reservation->status }}</td>
            </tr>
            <tr>
                <th>Point de charge</th>
                <td>{{ $reservation->chargingPoint->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <div style="margin-top: 2rem;">
            @if($result['hash_valid'] && $result['payment_approved'])
                <a href="{{ route('reservations.thank-you', $reservation) }}" class="btn btn-success">
                    Voir ma réservation
                </a>
            @else
                <a href="{{ route('reservations.index') }}" class="btn">
                    Retour aux réservations
                </a>
            @endif
        </div>
    </div>
</body>
</html>

