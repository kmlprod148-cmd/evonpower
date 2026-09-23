<?php

use Illuminate\Support\Facades\Route;

/**
 * Routes de debug pour diagnostiquer l'erreur 419
 * À DÉSACTIVER EN PRODUCTION
 */

// Page de diagnostic CSRF
Route::get('/debug/csrf', function () {
    if (!config('app.debug')) {
        abort(404);
    }
    
    $isMobile = false;
    $ua = request()->userAgent();
    $patterns = ['Android', 'iPhone', 'iPad', 'Mobile'];
    foreach ($patterns as $pattern) {
        if (stripos($ua, $pattern) !== false) {
            $isMobile = true;
            break;
        }
    }
    
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'has_session' => request()->hasSession(),
        'session_driver' => config('session.driver'),
        'session_lifetime' => config('session.lifetime'),
        'session_secure' => config('session.secure'),
        'session_same_site' => config('session.same_site'),
        'session_domain' => config('session.domain'),
        'is_mobile_detected' => $isMobile,
        'user_agent' => $ua,
        'ip' => request()->ip(),
        'csrf_token_refreshed_at' => session('csrf_token_refreshed_at'),
        'time_since_refresh' => session('csrf_token_refreshed_at') ? (time() - session('csrf_token_refreshed_at')) : null,
    ], 200, [], JSON_PRETTY_PRINT);
})->name('debug.csrf');

// Test POST sans CSRF (pour vérifier que la protection fonctionne)
Route::post('/debug/test-csrf', function () {
    if (!config('app.debug')) {
        abort(404);
    }
    
    return response()->json([
        'status' => 'success',
        'message' => 'CSRF token valide !',
        'token' => csrf_token(),
    ]);
})->name('debug.test-csrf');

// Page HTML de test
Route::get('/debug/csrf-test-page', function () {
    if (!config('app.debug')) {
        abort(404);
    }
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <title>Test CSRF - Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .box { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px; }
        button { padding: 10px 20px; margin: 10px 5px; cursor: pointer; font-size: 16px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        #log { white-space: pre-wrap; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🔧 Test CSRF - Debug Erreur 419</h1>
    
    <div class="box">
        <h3>Informations Session</h3>
        <p><strong>CSRF Token:</strong> <code id="csrf-token"></code></p>
        <p><strong>Session ID:</strong> <code id="session-id"></code></p>
        <p><strong>User Agent:</strong> <code id="user-agent"></code></p>
        <p><strong>Mobile détecté:</strong> <span id="is-mobile"></span></p>
    </div>
    
    <div class="box">
        <h3>Tests</h3>
        <button onclick="loadInfo()">📊 Charger les infos</button>
        <button onclick="testCsrf()">✅ Test POST avec CSRF</button>
        <button onclick="testWithoutCsrf()">❌ Test POST sans CSRF</button>
        <button onclick="refreshToken()">🔄 Rafraîchir Token</button>
    </div>
    
    <div class="box">
        <h3>Logs</h3>
        <div id="log"></div>
    </div>
    
    <script>
        const log = (msg, type = 'info') => {
            const logDiv = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'red' : type === 'success' ? 'green' : 'black';
            logDiv.innerHTML += `[${timestamp}] <span style="color: ${color}">${msg}</span>\n`;
        };
        
        async function loadInfo() {
            try {
                const response = await fetch('/debug/csrf');
                const data = await response.json();
                
                document.getElementById('csrf-token').textContent = data.csrf_token.substring(0, 20) + '...';
                document.getElementById('session-id').textContent = data.session_id;
                document.getElementById('user-agent').textContent = data.user_agent;
                document.getElementById('is-mobile').textContent = data.is_mobile_detected ? '📱 OUI' : '💻 NON';
                
                log('Informations chargées avec succès', 'success');
                log(JSON.stringify(data, null, 2));
            } catch (error) {
                log('Erreur lors du chargement: ' + error.message, 'error');
            }
        }
        
        async function testCsrf() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            try {
                const response = await fetch('/debug/test-csrf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ test: 'data' })
                });
                
                if (response.status === 419) {
                    log('❌ ERREUR 419 - Page Expired !', 'error');
                } else if (response.ok) {
                    const data = await response.json();
                    log('✅ Test réussi avec CSRF: ' + JSON.stringify(data), 'success');
                } else {
                    log('⚠️ Erreur HTTP ' + response.status, 'error');
                }
            } catch (error) {
                log('❌ Erreur: ' + error.message, 'error');
            }
        }
        
        async function testWithoutCsrf() {
            try {
                const response = await fetch('/debug/test-csrf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ test: 'data' })
                });
                
                if (response.status === 419) {
                    log('✅ CSRF fonctionne ! Erreur 419 attendue sans token', 'success');
                } else {
                    log('⚠️ PROBLÈME: Pas d\'erreur 419 sans token CSRF !', 'error');
                }
            } catch (error) {
                log('❌ Erreur: ' + error.message, 'error');
            }
        }
        
        async function refreshToken() {
            try {
                const response = await fetch('/csrf-token');
                const data = await response.json();
                
                // Mettre à jour la meta tag
                document.querySelector('meta[name="csrf-token"]').content = data.csrf_token;
                
                log('🔄 Token rafraîchi: ' + data.csrf_token.substring(0, 20) + '...', 'success');
            } catch (error) {
                log('❌ Erreur rafraîchissement: ' + error.message, 'error');
            }
        }
        
        // Charger les infos au démarrage
        loadInfo();
    </script>
</body>
</html>
HTML;
})->name('debug.csrf-test-page');

