<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SteVe API Test - Terminal</title>
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
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: #00ff00;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
            text-shadow: 0 0 10px #00ff00;
        }
        
        .section {
            margin-bottom: 30px;
            border: 1px solid #00ff00;
            padding: 20px;
            background-color: rgba(0, 255, 0, 0.05);
        }
        
        .section-title {
            color: #00ff00;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #00ff00;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="number"] {
            background-color: #000000;
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 8px;
            width: 100%;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #00ff00;
            box-shadow: 0 0 5px #00ff00;
        }
        
        button {
            background-color: #000000;
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 10px 20px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        button:hover {
            background-color: #00ff00;
            color: #000000;
            box-shadow: 0 0 10px #00ff00;
        }
        
        button:active {
            transform: scale(0.95);
        }
        
        .response {
            margin-top: 20px;
            padding: 15px;
            background-color: #000000;
            border: 1px solid #00ff00;
            min-height: 100px;
            max-height: 400px;
            overflow-y: auto;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .response.loading {
            color: #ffff00;
        }
        
        .response.success {
            color: #00ff00;
        }
        
        .response.error {
            color: #ff0000;
        }
        
        .status {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #00ff00;
            font-size: 12px;
        }
        
        .config-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px dashed #00ff00;
        }
        
        .config-section label {
            display: inline-block;
            width: 150px;
        }
        
        .config-section input {
            width: calc(100% - 160px);
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ SteVe API Test Terminal ⚡</h1>
        
        <!-- Configuration Section -->
        <div class="config-section">
            <div class="section-title">Configuration</div>
            <div class="form-group">
                <label>Base URL:</label>
                <input type="text" id="baseUrl" value="http://158.69.27.239:8180/steve/api/v1" placeholder="http://158.69.27.239:8180/steve/api/v1">
            </div>
            <div class="form-group">
                <label>Username:</label>
                <input type="text" id="username" value="admin" placeholder="admin">
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="text" id="password" value="1234" placeholder="1234">
            </div>
            <div class="form-group">
                <label>Charge Point ID:</label>
                <input type="text" id="chargePointId" value="CP-001" placeholder="CP-001">
            </div>
        </div>
        
        <!-- Start Charge Section -->
        <div class="section">
            <div class="section-title">✅ 1. Start a charge</div>
            <div class="form-group">
                <label>Connector ID:</label>
                <input type="number" id="startConnectorId" value="1" min="1">
            </div>
            <button onclick="startCharge()">▶ START CHARGE</button>
            <div id="startResponse" class="response" style="display: none;"></div>
        </div>
        
        <!-- Stop Charge Section -->
        <div class="section">
            <div class="section-title">⏹ 2. Stop a charge</div>
            <div class="form-group">
                <label>Connector ID:</label>
                <input type="number" id="stopConnectorId" value="1" min="1">
            </div>
            <button onclick="stopCharge()">⏹ STOP CHARGE</button>
            <div id="stopResponse" class="response" style="display: none;"></div>
        </div>
        
        <!-- Unlock Connector Section -->
        <div class="section">
            <div class="section-title">🔓 3. Unlock connector</div>
            <button onclick="unlockConnector()">🔓 UNLOCK CONNECTOR</button>
            <div id="unlockResponse" class="response" style="display: none;"></div>
        </div>
        
        <!-- Reset Charge Point Section -->
        <div class="section">
            <div class="section-title">🔄 4. Reset charge point</div>
            <div class="form-group">
                <label>Reset Type:</label>
                <select id="resetType" style="background-color: #000000; border: 1px solid #00ff00; color: #00ff00; padding: 8px; width: 100%; font-family: 'Courier New', monospace;">
                    <option value="Soft">Soft</option>
                    <option value="Hard">Hard</option>
                </select>
            </div>
            <button onclick="resetChargePoint()">🔄 RESET CHARGE POINT</button>
            <div id="resetResponse" class="response" style="display: none;"></div>
        </div>
    </div>
    
    <script>
        function getConfig() {
            return {
                baseUrl: document.getElementById('baseUrl').value.trim(),
                username: document.getElementById('username').value.trim(),
                password: document.getElementById('password').value.trim(),
                chargePointId: document.getElementById('chargePointId').value.trim()
            };
        }
        
        function showResponse(elementId, data, isError = false) {
            const responseDiv = document.getElementById(elementId);
            responseDiv.style.display = 'block';
            responseDiv.className = 'response ' + (isError ? 'error' : 'success');
            responseDiv.textContent = JSON.stringify(data, null, 2);
        }
        
        function showLoading(elementId) {
            const responseDiv = document.getElementById(elementId);
            responseDiv.style.display = 'block';
            responseDiv.className = 'response loading';
            responseDiv.textContent = 'Loading...';
        }
        
        async function makeRequest(endpoint, method = 'POST', body = null) {
            const config = getConfig();
            const url = `${config.baseUrl}${endpoint}`;
            
            // Create basic auth header
            const credentials = btoa(`${config.username}:${config.password}`);
            
            const headers = {
                'Content-Type': 'application/json',
                'Authorization': `Basic ${credentials}`
            };
            
            const options = {
                method: method,
                headers: headers
            };
            
            if (body) {
                options.body = JSON.stringify(body);
            }
            
            try {
                const response = await fetch(url, options);
                let data;
                
                try {
                    data = await response.json();
                } catch (e) {
                    const text = await response.text();
                    data = { 
                        status: response.status, 
                        statusText: response.statusText,
                        text: text
                    };
                }
                
                return {
                    success: response.ok,
                    status: response.status,
                    statusText: response.statusText,
                    data: data
                };
            } catch (error) {
                return {
                    success: false,
                    error: error.message,
                    status: 0
                };
            }
        }
        
        async function startCharge() {
            const config = getConfig();
            const connectorId = parseInt(document.getElementById('startConnectorId').value);
            
            showLoading('startResponse');
            
            const result = await makeRequest(
                `/commands/${config.chargePointId}/start`,
                'POST',
                { connectorId: connectorId }
            );
            
            showResponse('startResponse', result, !result.success);
        }
        
        async function stopCharge() {
            const config = getConfig();
            const connectorId = parseInt(document.getElementById('stopConnectorId').value);
            
            showLoading('stopResponse');
            
            const result = await makeRequest(
                `/commands/${config.chargePointId}/stop`,
                'POST',
                { connectorId: connectorId }
            );
            
            showResponse('stopResponse', result, !result.success);
        }
        
        async function unlockConnector() {
            const config = getConfig();
            
            showLoading('unlockResponse');
            
            const result = await makeRequest(
                `/commands/${config.chargePointId}/unlock`,
                'POST'
            );
            
            showResponse('unlockResponse', result, !result.success);
        }
        
        async function resetChargePoint() {
            const config = getConfig();
            const resetType = document.getElementById('resetType').value;
            
            showLoading('resetResponse');
            
            const result = await makeRequest(
                `/commands/${config.chargePointId}/reset`,
                'POST',
                { type: resetType }
            );
            
            showResponse('resetResponse', result, !result.success);
        }
    </script>
</body>
</html>

