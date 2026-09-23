<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer Réservation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 3px; cursor: pointer; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Confirmer Réservation</h1>
        
        <div class="card">
            <h2>Détails de la Réservation</h2>
            <p><strong>ID:</strong> {{ $reservation->id }}</p>
            <p><strong>Statut:</strong> {{ $reservation->status ?? 'Non défini' }}</p>
            <p><strong>Coût estimé:</strong> {{ $reservation->estimated_cost ?? 'Non défini' }} €</p>
            <p><strong>Énergie estimée:</strong> {{ $reservation->estimated_energy ?? 'Non défini' }} kWh</p>
            <p><strong>Durée estimée:</strong> {{ $reservation->estimated_duration ?? 'Non défini' }} minutes</p>
            <p><strong>Notes:</strong> {{ $reservation->notes ?? 'Aucune note' }}</p>
        </div>
        
        <div class="card">
            <h2>Confirmer cette réservation ?</h2>
            <p>Êtes-vous sûr de vouloir confirmer cette réservation ?</p>
            
            <form method="POST" action="{{ route('admin.reservations.confirm', $reservation) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Confirmer la Réservation</button>
                <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</body>
</html>