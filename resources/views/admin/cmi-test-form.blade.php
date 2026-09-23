<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirection vers CMI...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 400px;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        p {
            color: #718096;
            margin-bottom: 1.5rem;
        }
        .info {
            background: #f7fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: #4a5568;
        }
        .amount {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h2>Redirection vers CMI</h2>
        <p>Vous allez être redirigé vers la page de paiement sécurisée CMI...</p>
        
        <div class="amount">
            {{ $paymentData['amount'] }} {{ $paymentData['currency'] }}
        </div>
        
        <div class="info">
            <p style="margin: 0;"><strong>Référence:</strong> {{ $paymentData['oid'] }}</p>
        </div>

        <!-- Formulaire auto-submit vers CMI -->
        <form id="cmiForm" action="{{ $paymentUrl }}" method="POST" style="display: none;">
            @foreach($paymentData as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
        </form>
    </div>

    <script>
        // Auto-submit le formulaire après un court délai
        setTimeout(function() {
            document.getElementById('cmiForm').submit();
        }, 1500);
    </script>
</body>
</html>
