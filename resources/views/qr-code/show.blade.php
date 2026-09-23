<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - EVON</title>
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
        .qr-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .qr-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .qr-title {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .qr-description {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .qr-code-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px dashed #dee2e6;
        }
        .qr-code {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .qr-info {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        .qr-info h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .qr-info p {
            color: #7f8c8d;
            margin: 5px 0;
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
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background: #229954;
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
        .loading {
            display: none;
            color: #7f8c8d;
            font-style: italic;
        }
        .error-message {
            color: #e74c3c;
            background: #fdf2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        @if(isset($improved) && $improved)
            <div class="improved-badge">Service Amélioré</div>
        @endif
        
        <div class="qr-icon">📱</div>
        
        <h1 class="qr-title">QR Code de la Borne</h1>
        
        <div class="qr-description">
            Scannez ce QR code avec votre smartphone pour accéder à la réservation de la borne de recharge.
        </div>
        
        <div class="qr-code-container">
            <img src="{{ $qr_code_url }}" alt="QR Code" class="qr-code" id="qrCodeImage" onerror="showError()">
            <div class="loading" id="loading">Chargement du QR code...</div>
            <div class="error-message" id="errorMessage">
                Erreur lors du chargement du QR code. Veuillez réessayer.
            </div>
        </div>
        
        <div class="qr-info">
            <h4>Informations :</h4>
            <p><strong>ID de la borne :</strong> {{ $charging_point_id ?? 'N/A' }}</p>
            <p><strong>URL du QR code :</strong> <a href="{{ $qr_code_url }}" target="_blank">{{ $qr_code_url }}</a></p>
            <p><strong>Généré le :</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
        
        <div class="action-buttons">
            <a href="{{ route('improved.generate', $charging_point_id ?? 1) }}" class="btn btn-primary" onclick="showLoading()">
                🔄 Régénérer
            </a>
            
            <a href="{{ route('improved.status', $charging_point_id ?? 1) }}" class="btn btn-secondary">
                📊 Vérifier le Statut
            </a>
            
            <button onclick="downloadQRCode()" class="btn btn-success">
                💾 Télécharger
            </button>
        </div>
        
        <div style="margin-top: 30px; font-size: 14px; color: #95a5a6;">
            <p>Le QR code est valide et peut être utilisé pour les réservations.</p>
        </div>
    </div>

    <script>
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('qrCodeImage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
        }
        
        function showError() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('qrCodeImage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'block';
        }
        
        function downloadQRCode() {
            const qrCodeUrl = '{{ $qr_code_url }}';
            const link = document.createElement('a');
            link.href = qrCodeUrl;
            link.download = 'qr-code-borne-{{ $charging_point_id ?? "unknown" }}.svg';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Auto-hide loading after 3 seconds
        setTimeout(function() {
            const loading = document.getElementById('loading');
            if (loading.style.display === 'block') {
                loading.style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>