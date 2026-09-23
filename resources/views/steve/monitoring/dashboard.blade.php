<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord SteVe - Monitoring</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #0a0a0a;
            color: #00ff00;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #001100, #003300);
            border: 2px solid #00ff00;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }

        .header h1 {
            color: #00ff00;
            font-size: 28px;
            text-shadow: 0 0 15px #00ff00;
            margin-bottom: 10px;
        }

        .header p {
            color: #888;
            font-size: 16px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .nav-btn {
            background-color: #001100;
            border: 2px solid #00ff00;
            color: #00ff00;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        .nav-btn:hover {
            background-color: #00ff00;
            color: #000000;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }

        .nav-btn.active {
            background-color: #00ff00;
            color: #000000;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #001100, #002200);
            border: 2px solid #00ff00;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 25px rgba(0, 255, 0, 0.4);
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #00ff00;
            text-shadow: 0 0 10px #00ff00;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 14px;
            color: #888;
            text-transform: uppercase;
        }

        .status-indicator {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .status-online {
            background-color: #00ff00;
            box-shadow: 0 0 10px #00ff00;
        }

        .status-offline {
            background-color: #ff0000;
            box-shadow: 0 0 10px #ff0000;
        }

        .status-warning {
            background-color: #ffff00;
            box-shadow: 0 0 10px #ffff00;
        }

        .charging-points-section {
            background: linear-gradient(135deg, #001100, #002200);
            border: 2px solid #00ff00;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .section-title {
            color: #00ff00;
            font-size: 20px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .charging-points-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .charging-point-card {
            background-color: #000000;
            border: 1px solid #00ff00;
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .charging-point-card:hover {
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.3);
            transform: translateY(-2px);
        }

        .charging-point-card.online {
            border-color: #00ff00;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.2);
        }

        .charging-point-card.offline {
            border-color: #ff0000;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.2);
        }

        .point-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .point-name {
            font-weight: bold;
            color: #00ff00;
            font-size: 16px;
        }

        .point-status {
            display: flex;
            align-items: center;
            font-size: 12px;
        }

        .point-details {
            color: #888;
            font-size: 12px;
            line-height: 1.4;
        }

        .point-actions {
            margin-top: 10px;
            display: flex;
            gap: 5px;
        }

        .action-btn {
            background-color: #001100;
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background-color: #00ff00;
            color: #000000;
        }

        .connectivity-test {
            background: linear-gradient(135deg, #001100, #002200);
            border: 2px solid #00ff00;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .test-btn {
            background-color: #001100;
            border: 2px solid #00ff00;
            color: #00ff00;
            padding: 15px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            margin-right: 15px;
        }

        .test-btn:hover {
            background-color: #00ff00;
            color: #000000;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
        }

        .test-results {
            margin-top: 20px;
            padding: 15px;
            background-color: #000000;
            border: 1px solid #00ff00;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .loading {
            color: #ffff00;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }

        .success {
            color: #00ff00;
        }

        .error {
            color: #ff0000;
        }

        .warning {
            color: #ffff00;
        }

        .refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #001100;
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .timestamp {
            color: #888;
            font-size: 12px;
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔌 SteVe Monitoring Dashboard</h1>
            <p>Tableau de bord de surveillance des connexions SteVe (EVON Server)</p>
        </div>

        <div class="nav-buttons">
            <a href="/steve/monitoring/dashboard" class="nav-btn active">📊 Dashboard</a>
            <a href="/steve/monitoring/realtime" class="nav-btn">🖥️ Temps Réel</a>
            <a href="/steve/monitoring/connectivity" class="nav-btn">🔍 Tests</a>
            <a href="/api/steve/monitoring-stats" class="nav-btn">📈 Statistiques</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" id="total-points">-</div>
                <div class="stat-label">Points de Charge</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="online-points">-</div>
                <div class="stat-label">En Ligne</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="offline-points">-</div>
                <div class="stat-label">Hors Ligne</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="server-status">-</div>
                <div class="stat-label">Serveur SteVe</div>
            </div>
        </div>

        <div class="connectivity-test">
            <h3 class="section-title">🔍 Tests de Connectivité</h3>
            <button class="test-btn" onclick="runConnectivityTest()">Tester la Connectivité</button>
            <button class="test-btn" onclick="refreshData()">Actualiser les Données</button>
            <div class="test-results" id="test-results">
                <div class="loading">Cliquez sur "Tester la Connectivité" pour commencer...</div>
            </div>
        </div>

        <div class="charging-points-section">
            <h3 class="section-title">🔌 Points de Charge</h3>
            <div class="charging-points-grid" id="charging-points-grid">
                <div class="loading">Chargement des points de charge...</div>
            </div>
        </div>

        <div class="timestamp" id="last-update">
            Dernière mise à jour: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <div class="refresh-indicator" id="refresh-indicator">
        🔄 Auto-refresh: ON
    </div>

    <script>
        let refreshInterval;

        // Actualiser les données
        async function refreshData() {
            try {
                // Récupérer les statistiques
                const statsResponse = await fetch('/api/steve/monitoring-stats');
                const statsData = await statsResponse.json();
                
                if (statsData.success) {
                    updateStats(statsData.data);
                }

                // Récupérer les points de charge
                const pointsResponse = await fetch('/api/steve/charging-points-status');
                const pointsData = await pointsResponse.json();
                
                if (pointsData.success) {
                    updateChargingPoints(pointsData.data);
                }

                // Mettre à jour le timestamp
                document.getElementById('last-update').textContent = 
                    `Dernière mise à jour: ${new Date().toLocaleString('fr-FR')}`;

            } catch (error) {
                console.error('Erreur lors de l\'actualisation:', error);
            }
        }

        // Mettre à jour les statistiques
        function updateStats(data) {
            document.getElementById('total-points').textContent = data.total_charging_points || 0;
            document.getElementById('online-points').textContent = data.online_points || 0;
            document.getElementById('offline-points').textContent = data.offline_points || 0;
            document.getElementById('server-status').textContent = 'ONLINE';
        }

        // Mettre à jour les points de charge
        function updateChargingPoints(data) {
            const container = document.getElementById('charging-points-grid');
            
            if (data.charging_points && data.charging_points.length > 0) {
                container.innerHTML = data.charging_points.map(point => `
                    <div class="charging-point-card ${point.is_online ? 'online' : 'offline'}">
                        <div class="point-header">
                            <div class="point-name">${point.name}</div>
                            <div class="point-status">
                                <span class="status-indicator status-${point.is_online ? 'online' : 'offline'}"></span>
                                ${point.is_online ? 'EN LIGNE' : 'HORS LIGNE'}
                            </div>
                        </div>
                        <div class="point-details">
                            <div><strong>ID:</strong> ${point.charge_box_id}</div>
                            <div><strong>Profil:</strong> ${point.business_profile}</div>
                            <div><strong>Intégrateur:</strong> ${point.integrator}</div>
                            <div><strong>Dernière connexion:</strong> ${point.last_connected || 'N/A'}</div>
                        </div>
                        <div class="point-actions">
                            <button class="action-btn" onclick="checkStatus(${point.id})">Statut</button>
                            <button class="action-btn" onclick="sendCommand(${point.id})">Commande</button>
                            <button class="action-btn" onclick="viewLogs(${point.id})">Logs</button>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="warning">Aucun point de charge trouvé</div>';
            }
        }

        // Tester la connectivité
        async function runConnectivityTest() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = '<div class="loading">Test de connectivité en cours...</div>';

            try {
                const response = await fetch('/api/steve/test-connectivity');
                const data = await response.json();
                
                if (data.success) {
                    const results = data.data;
                    let html = '<div class="success">✅ Test de connectivité terminé</div><br>';
                    
                    html += `<div><strong>Statut global:</strong> <span class="${results.overall_status}">${results.overall_status.toUpperCase()}</span></div>`;
                    html += `<div><strong>Temps de réponse:</strong> ${results.response_time_ms}ms</div><br>`;
                    
                    // Serveur
                    const server = results.details.server_reachability;
                    html += `<div><strong>Serveur:</strong> <span class="${server.status}">${server.status.toUpperCase()}</span> - ${server.message}</div>`;
                    
                    // Authentification
                    const auth = results.details.authentication;
                    html += `<div><strong>Authentification:</strong> <span class="${auth.status}">${auth.status.toUpperCase()}</span> - ${auth.message}</div>`;
                    
                    // OCPP
                    const ocpp = results.details.ocpp_connectivity;
                    html += `<div><strong>OCPP:</strong> <span class="${ocpp.status}">${ocpp.status.toUpperCase()}</span> - ${ocpp.message}</div>`;
                    
                    // Base de données
                    const db = results.details.database_connectivity;
                    html += `<div><strong>Base de données:</strong> <span class="${db.status}">${db.status.toUpperCase()}</span> - ${db.message}</div>`;
                    
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = `<div class="error">❌ Erreur lors du test: ${data.message}</div>`;
                }
            } catch (error) {
                resultsDiv.innerHTML = `<div class="error">❌ Erreur de connexion: ${error.message}</div>`;
            }
        }

        // Actions sur les points de charge
        async function checkStatus(pointId) {
            try {
                const response = await fetch(`/api/steve/charging-points/${pointId}/connection-status`);
                const data = await response.json();
                
                if (data.success) {
                    alert(`Statut du point de charge: ${JSON.stringify(data.data.status, null, 2)}`);
                } else {
                    alert(`Erreur: ${data.message}`);
                }
            } catch (error) {
                alert(`Erreur: ${error.message}`);
            }
        }

        async function sendCommand(pointId) {
            const command = prompt('Entrez la commande OCPP (ex: StartTransaction, StopTransaction, Reset):');
            if (command) {
                try {
                    const response = await fetch(`/api/steve/charging-points/${pointId}/send-command`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            command: command,
                            parameters: {}
                        })
                    });
                    
                    const data = await response.json();
                    alert(data.success ? `Commande envoyée: ${data.message}` : `Erreur: ${data.message}`);
                } catch (error) {
                    alert(`Erreur: ${error.message}`);
                }
            }
        }

        async function viewLogs(pointId) {
            try {
                const response = await fetch(`/api/steve/charging-points/${pointId}/action-log`);
                const data = await response.json();
                
                if (data.success) {
                    const logs = data.data.logs;
                    if (logs.length > 0) {
                        alert(`Logs du point de charge:\n${logs.map(log => `[${log.timestamp}] ${log.level}: ${log.message}`).join('\n')}`);
                    } else {
                        alert('Aucun log disponible pour ce point de charge');
                    }
                } else {
                    alert(`Erreur: ${data.message}`);
                }
            } catch (error) {
                alert(`Erreur: ${error.message}`);
            }
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            refreshData();
            
            // Actualisation automatique toutes les 30 secondes
            refreshInterval = setInterval(refreshData, 30000);
        });

        // Nettoyage à la fermeture
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>
