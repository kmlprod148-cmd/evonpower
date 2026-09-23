<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Paiement CMI - Réservation #{{ $reservation->id }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
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
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn:hover {
            background: #0056b3;
        }
        .info {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Paiement de la Réservation</h1>
        
        <div class="info">
            <p><strong>Réservation #{{ $reservation->id }}</strong></p>
            <p>Montant: <strong>{{ number_format($reservation->estimated_cost ?? $reservation->amount, 2) }} {{ $reservation->pricingPlan->currency ?? 'EUR' }}</strong></p>
            <p>Point de charge: {{ $reservation->chargingPoint->name ?? 'N/A' }}</p>
        </div>

        <form method="post" action="{{ $paymentUrl }}" id="cmi-payment-form">
            @foreach($paymentData as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <p>Vous allez être redirigé vers la passerelle de paiement sécurisée CMI pour finaliser votre paiement.</p>
            <button type="submit" class="btn">
                Procéder au paiement
            </button>
        </form>
        
        <script>
            // Auto-submit the form
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('cmi-payment-form').submit();
            });
        </script>
    </div>
</body>
</html>

