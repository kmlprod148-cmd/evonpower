<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Réservation</title>
</head>
<body>
    <h1>Test de la page de réservation</h1>
    <p>Point de charge: {{ $chargingPoint->name ?? 'Non défini' }}</p>
    <p>Plan tarifaire: {{ $pricingPlan->name ?? 'Non défini' }}</p>
    <p>Devise: {{ $currency ?? 'EUR' }}</p>
    <p>✅ Page accessible avec succès !</p>
</body>
</html>
