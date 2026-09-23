<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Monitoring SteVe - Temps Réel' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #000000;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.4;
            overflow-x: auto;
        }

        .terminal {
            background-color: #000000;
            color: #00ff00;
            padding: 20px;
            min-height: 100vh;
            position: relative;
        }

        .header {
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #00ff00;
            font-size: 24px;
            text-shadow: 0 0 10px #00ff00;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-card {
            background-color: #001100;
            border: 1px solid #00ff00;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.3);
        }

        .status-card h3 {
            color: #00ff00;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
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

        .charging-points {
            margin-top: 20px;
        }

        .charging-point {
            background-color: #001100;
            border: 1px solid #00ff00;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .charging-point.online {
            border-color: #00ff00;
            box-shadow: 0 0 5px rgba(0, 255, 0, 0.3);
        }

        .charging-point.offline {
            border-color: #ff0000;
            box-shadow: 0 0 5px rgba(255, 0, 0, 0.3);
        }

        .point-info {
            flex: 1;
        }

        .point-name {
            font-weight: bold;
            color: #00ff00;
        }

        .point-id {
            color: #888;
            font-size: 12px;
        }

        .point-status {
            text-align: right;
        }

        .timestamp {
            color: #888;
            font-size: 12px;
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }

        .loading {
            color: #ffff00;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }

        .error {
            color: #ff0000;
        }

        .success {
            color: #00ff00;
        }

        .warning {
            color: #ffff00;
        }

        .refresh-btn {
            background-color: #001100;
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 3px;
            margin-bottom: 20px;
        }

        .refresh-btn:hover {
            background-color: #00ff00;
            color: #000000;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-item {
            background-color: #001100;
            border: 1px solid #00ff00;
            padding: 10px;
            text-align: center;
            border-radius: 3px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #00ff00;
        }

        .stat-label {
            font-size: 12px;
            color: #888;
        }

        .log-container {
            background-color: #000000;
            border: 1px solid #00ff00;
            padding: 10px;
            height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        .log-entry {
            margin-bottom: 5px;
            padding: 2px 0;
        }

        .log-timestamp {
            color: #888;
        }

        .log-level-info {
            color: #00ff00;
        }

        .log-level-error {
            color: #ff0000;
        }

        .log-level-warning {
            color: #ffff00;
        }
    </style>
</head>
<body>
    <div class="terminal">
        <div class="header">
            <h1>🔌 STeVe MONITORING - EVON SERVER</h1>
            <div class="timestamp" id="current-time"></div>
        </div>

        <button class="refresh-btn" onclick="refreshData()">🔄 Actualiser</button>

        <div class="stats" id="stats">
            <div class="stat-item">
                <div class="stat-value" id="total-points">-</div>
                <div class="stat-label">Points Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="online-points">-</div>
                <div class="stat-label">En Ligne</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="offline-points">-</div>
                <div class="stat-label">Hors Ligne</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="server-status">-</div>
                <div class="stat-label">Serveur</div>
            </div>
        </div>

        <div class="status-grid">
            <div class="status-card">
                <h3>🌐 Connectivité Serveur</h3>
                <div id="server-connectivity">
                    <span class="loading">Vérification en cours...</span>
                </div>
            </div>

            <div class="status-card">
                <h3>🔌 Connexions OCPP</h3>
                <div id="ocpp-connections">
                    <span class="loading">Vérification en cours...</span>
                </div>
            </div>

            <div class="status-card">
                <h3>💾 Base de Données</h3>
                <div id="database-status">
                    <span class="loading">Vérification en cours...</span>
                </div>
            </div>

            <div class="status-card">
                <h3>⚡ Santé Système</h3>
                <div id="system-health">
                    <span class="loading">Vérification en cours...</span>
                </div>
            </div>
        </div>

        <div class="charging-points">
            <h3>🔌 Points de Charge</h3>
            <div id="charging-points-list">
                <span class="loading">Chargement des points de charge...</span>
            </div>
        </div>

        <div class="status-card">
            <h3>📋 Logs d'Activité</h3>
            <div class="log-container" id="activity-logs">
                <div class="log-entry">
                    <span class="log-timestamp">[{{ now()->format('H:i:s') }}]</span>
                    <span class="log-level-info">[INFO]</span>
                    <span>Initialisation du monitoring SteVe...</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        let refreshInterval;
        let logEntries = [];

        // Mise à jour de l'heure
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                `Dernière mise à jour: ${now.toLocaleString('fr-FR')}`;
        }

        // Ajouter une entrée de log
        function addLogEntry(level, message) {
            const timestamp = new Date().toLocaleTimeString('fr-FR');
            const logEntry = {
                timestamp,
                level,
                message
            };
            
            logEntries.unshift(logEntry);
            if (logEntries.length > 50) {
                logEntries.pop();
            }
            
            updateLogDisplay();
        }

        // Mettre à jour l'affichage des logs
        function updateLogDisplay() {
            const container = document.getElementById('activity-logs');
            container.innerHTML = logEntries.map(entry => `
                <div class="log-entry">
                    <span class="log-timestamp">[${entry.timestamp}]</span>
                    <span class="log-level-${entry.level.toLowerCase()}">[${entry.level}]</span>
                    <span>${entry.message}</span>
                </div>
            `).join('');
        }

        // Actualiser les données
        async function refreshData() {
            addLogEntry('INFO', 'Actualisation des données...');
            
            try {
                // Test de connectivité complet
                const connectivityResponse = await fetch('/api/steve/test-connectivity');
                const connectivityData = await connectivityResponse.json();
                
                if (connectivityData.success) {
                    updateConnectivityStatus(connectivityData.data);
                    addLogEntry('INFO', 'Test de connectivité réussi');
                } else {
                    addLogEntry('ERROR', 'Échec du test de connectivité');
                }

                // Statut en temps réel
                const statusResponse = await fetch('/api/steve/real-time-status');
                const statusData = await statusResponse.json();
                
                if (statusData.success) {
                    updateRealTimeStatus(statusData.data);
                    addLogEntry('INFO', 'Statut temps réel mis à jour');
                } else {
                    addLogEntry('ERROR', 'Échec de la récupération du statut');
                }

                // Points de charge
                const pointsResponse = await fetch('/api/steve/charging-points-status');
                const pointsData = await pointsResponse.json();
                
                if (pointsData.success) {
                    updateChargingPoints(pointsData.data);
                    addLogEntry('INFO', 'Statut des points de charge mis à jour');
                } else {
                    addLogEntry('ERROR', 'Échec de la récupération des points de charge');
                }

            } catch (error) {
                addLogEntry('ERROR', `Erreur de connexion: ${error.message}`);
                console.error('Erreur lors de l\'actualisation:', error);
            }
        }

        // Mettre à jour le statut de connectivité
        function updateConnectivityStatus(data) {
            const serverDiv = document.getElementById('server-connectivity');
            const ocppDiv = document.getElementById('ocpp-connections');
            const dbDiv = document.getElementById('database-status');
            const healthDiv = document.getElementById('system-health');

            // Serveur
            const serverStatus = data.details.server_reachability.status;
            serverDiv.innerHTML = `
                <span class="status-indicator status-${serverStatus}"></span>
                ${serverStatus === 'success' ? 'Serveur accessible' : 'Serveur non accessible'}
                <br><small>${data.details.server_reachability.message}</small>
            `;

            // OCPP
            const ocppStatus = data.details.ocpp_connectivity.status;
            ocppDiv.innerHTML = `
                <span class="status-indicator status-${ocppStatus}"></span>
                ${ocppStatus === 'success' ? 'OCPP actif' : 'OCPP inactif'}
                <br><small>${data.details.ocpp_connectivity.message}</small>
            `;

            // Base de données
            const dbStatus = data.details.database_connectivity.status;
            dbDiv.innerHTML = `
                <span class="status-indicator status-${dbStatus}"></span>
                ${dbStatus === 'success' ? 'Base de données accessible' : 'Base de données inaccessible'}
                <br><small>${data.details.database_connectivity.message}</small>
            `;

            // Santé système
            healthDiv.innerHTML = `
                <span class="status-indicator status-${data.overall_status}"></span>
                ${data.overall_status === 'success' ? 'Système opérationnel' : 'Problèmes détectés'}
                <br><small>Temps de réponse: ${data.response_time_ms}ms</small>
            `;
        }

        // Mettre à jour le statut en temps réel
        function updateRealTimeStatus(data) {
            document.getElementById('server-status').textContent = 
                data.server_status.status === 'online' ? 'ONLINE' : 'OFFLINE';
        }

        // Mettre à jour les points de charge
        function updateChargingPoints(data) {
            document.getElementById('total-points').textContent = data.summary.total;
            document.getElementById('online-points').textContent = data.summary.online;
            document.getElementById('offline-points').textContent = data.summary.offline;

            const container = document.getElementById('charging-points-list');
            container.innerHTML = data.charging_points.map(point => `
                <div class="charging-point ${point.is_online ? 'online' : 'offline'}">
                    <div class="point-info">
                        <div class="point-name">${point.name}</div>
                        <div class="point-id">ID: ${point.charge_box_id}</div>
                    </div>
                    <div class="point-status">
                        <span class="status-indicator status-${point.is_online ? 'online' : 'offline'}"></span>
                        ${point.is_online ? 'EN LIGNE' : 'HORS LIGNE'}
                        <br><small>Dernière connexion: ${point.last_connected || 'N/A'}</small>
                    </div>
                </div>
            `).join('');
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            updateTime();
            setInterval(updateTime, 1000);
            
            // Actualisation automatique toutes les 30 secondes
            refreshData();
            refreshInterval = setInterval(refreshData, 30000);
            
            addLogEntry('INFO', 'Système de monitoring SteVe initialisé');
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
