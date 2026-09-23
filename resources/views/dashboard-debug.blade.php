<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat {
            display: inline-block;
            margin: 10px;
            padding: 15px;
            background: #007bff;
            color: white;
            border-radius: 5px;
            min-width: 150px;
            text-align: center;
        }
        h1 { color: #333; }
        .success { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Dashboard Debug</h1>
        
        <p class="success">✅ {{ $message ?? 'Message non défini' }}</p>
        
        <h2>📊 Statistiques</h2>
        <div>
            <div class="stat">
                <div>Total Recharges</div>
                <div style="font-size: 24px;">{{ $stats['totalRecharges'] ?? 'N/A' }}</div>
            </div>
            
            <div class="stat">
                <div>Recharges Actives</div>
                <div style="font-size: 24px;">{{ $stats['rechargesActives'] ?? 'N/A' }}</div>
            </div>
            
            <div class="stat">
                <div>Abonnements Actifs</div>
                <div style="font-size: 24px;">{{ $stats['abonnementsActifs'] ?? 'N/A' }}</div>
            </div>
            
            <div class="stat">
                <div>Bornes Actives</div>
                <div style="font-size: 24px;">{{ $stats['bornesActives'] ?? 'N/A' }}</div>
            </div>
        </div>
        
        <h2>🚀 Test du Controller</h2>
        <p>Si vous voyez cette page, cela signifie que :</p>
        <ul>
            <li>✅ Les routes fonctionnent</li>
            <li>✅ Les vues Blade fonctionnent</li>
            <li>✅ Les données sont transmises correctement</li>
        </ul>
        
        <h2>🔍 Diagnostic</h2>
        <p>Le problème du dashboard principal est probablement dans :</p>
        <ul>
            <li>📄 La vue <code>dashboard.blade.php</code> (erreur de syntaxe)</li>
            <li>🏗️ Le layout <code>layouts.app</code> (ressources manquantes)</li>
            <li>⚙️ Une directive Blade qui échoue silencieusement</li>
        </ul>
        
        <hr>
        <p><strong>URL de test :</strong> <a href="https://devcharge.evonpower.com/dashboard-debug">https://devcharge.evonpower.com/dashboard-debug</a></p>
    </div>
</body>
</html>
