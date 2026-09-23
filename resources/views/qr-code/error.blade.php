<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur QR Code - EVON</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .error-title {
            color: #e74c3c;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .error-message {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        .error-details h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .error-details p {
            color: #7f8c8d;
            margin: 5px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        .btn-retry {
            background: #e74c3c;
            color: white;
        }
        .btn-retry:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .improved-badge {
            background: #27ae60;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="error-container">
        @if(isset($improved) && $improved)
            <div class="improved-badge">Service Amélioré</div>
        @endif
        
        <div class="error-icon">❌</div>
        
        <h1 class="error-title">Erreur de Génération QR Code</h1>
        
        <div class="error-message">
            Une erreur s'est produite lors de la génération du QR code pour la borne de recharge.
        </div>
        
        <div class="error-details">
            <h4>Détails de l'erreur :</h4>
            <p><strong>ID de la borne :</strong> {{ $charging_point_id ?? 'N/A' }}</p>
            <p><strong>Erreur :</strong> {{ $error ?? 'Erreur inconnue' }}</p>
            <p><strong>Heure :</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
        
        <div class="action-buttons">
            <a href="{{ route('improved.test', $charging_point_id ?? 1) }}" class="btn btn-primary">
                🧪 Tester la Génération
            </a>
            
            <a href="{{ route('improved.status', $charging_point_id ?? 1) }}" class="btn btn-secondary">
                📊 Vérifier le Statut
            </a>
            
            <button onclick="location.reload()" class="btn btn-retry">
                🔄 Réessayer
            </button>
        </div>
        
        <div style="margin-top: 30px; font-size: 14px; color: #95a5a6;">
            <p>Si le problème persiste, contactez le support technique.</p>
        </div>
    </div>
</body>
</html>