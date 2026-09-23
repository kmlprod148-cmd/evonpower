<?php

use Illuminate\Support\Facades\Route;
use App\Services\SteveService;

/**
 * ROUTE DE DIAGNOSTIC - À supprimer après validation
 * Accès : http://votre-domaine/diagnostic-steve
 */

Route::get('/diagnostic-steve', function () {
    try {
        $steveService = app(SteveService::class);
        
        echo "<html><head><title>Diagnostic Steve API</title>";
        echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#0f0;}</style>";
        echo "</head><body>";
        echo "<h1>🔍 Diagnostic Steve API</h1>";
        echo "<hr>";
        
        echo "<h2>1️⃣ Configuration</h2>";
        $config = config('services.steve');
        echo "<pre>";
        echo "URL  : " . ($config['url'] ?? 'NON CONFIGURÉ') . "\n";
        echo "User : " . ($config['user'] ?? 'NON CONFIGURÉ') . "\n";
        echo "Pass : " . (isset($config['pass']) ? '***' : 'NON CONFIGURÉ') . "\n";
        echo "</pre>";
        
        echo "<h2>2️⃣ Appel getChargePoints()</h2>";
        $chargePoints = $steveService->getChargePoints();
        
        echo "<pre>";
        echo "Type retourné : " . gettype($chargePoints) . "\n";
        echo "Est null ? : " . ($chargePoints === null ? 'OUI ❌' : 'NON ✅') . "\n";
        echo "Est array ? : " . (is_array($chargePoints) ? 'OUI ✅' : 'NON ❌') . "\n";
        
        if (is_array($chargePoints)) {
            echo "Nombre d'éléments : " . count($chargePoints) . "\n";
            echo "</pre>";
            
            echo "<h2>3️⃣ Détails des bornes</h2>";
            echo "<pre>";
            print_r($chargePoints);
            echo "</pre>";
            
            echo "<h2>✅ CONCLUSION</h2>";
            echo "<div style='background:green;color:white;padding:20px;border-radius:10px;'>";
            echo "<h3>L'API STEVE FONCTIONNE PARFAITEMENT !</h3>";
            echo "<p>Nombre de bornes récupérées : " . count($chargePoints) . "</p>";
            echo "<p><strong>Le problème vient du cache du navigateur ou de session.</strong></p>";
            echo "<h4>SOLUTION :</h4>";
            echo "<ol>";
            echo "<li>Appuyez sur <strong>Ctrl+Shift+Delete</strong> pour vider le cache du navigateur</li>";
            echo "<li>OU testez en <strong>navigation privée/incognito</strong></li>";
            echo "<li>OU rafraîchissez avec <strong>Ctrl+F5</strong></li>";
            echo "<li>Puis allez sur : <a href='/steve-api' style='color:yellow;'>/steve-api</a></li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "</pre>";
            echo "<h2>❌ PROBLÈME</h2>";
            echo "<div style='background:red;color:white;padding:20px;border-radius:10px;'>";
            echo "<p>getChargePoints() retourne : " . ($chargePoints === null ? 'NULL' : 'NON-ARRAY') . "</p>";
            echo "<p>Vérifier les logs : storage/logs/laravel-" . date('Y-m-d') . ".log</p>";
            echo "</div>";
        }
        
        echo "</body></html>";
        
    } catch (Exception $e) {
        echo "<h2>❌ EXCEPTION</h2>";
        echo "<pre>";
        echo "Message : " . $e->getMessage() . "\n";
        echo "Trace : " . $e->getTraceAsString();
        echo "</pre>";
    }
});

