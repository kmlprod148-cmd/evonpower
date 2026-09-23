<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Accès Public</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">Test d'Accès Public</h1>
        
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Test des routes d'offre -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Test des Routes d'Offre (Sans Authentification)</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <h3 class="font-medium">Routes d'Offre</h3>
                        <div class="space-y-1">
                            <a href="/offer/1" class="block p-2 bg-blue-50 rounded hover:bg-blue-100 text-blue-700">
                                /offer/1 (OfferController@show)
                            </a>
                            <a href="/offre/1" class="block p-2 bg-blue-50 rounded hover:bg-blue-100 text-blue-700">
                                /offre/1 (OfferController@show)
                            </a>
                            <a href="/test-offer" class="block p-2 bg-green-50 rounded hover:bg-green-100 text-green-700">
                                /test-offer (Page de test)
                            </a>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <h3 class="font-medium">Routes QR Code</h3>
                        <div class="space-y-1">
                            <a href="/public/charging-points/1/qr-code" class="block p-2 bg-purple-50 rounded hover:bg-purple-100 text-purple-700">
                                /public/charging-points/1/qr-code
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test de l'API d'estimation -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Test de l'API d'Estimation</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ID de la borne</label>
                        <input type="number" id="charging_point_id" value="1" class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                        <select id="mode" class="form-select w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="per_kw">Par kWh</option>
                            <option value="per_min">Par minute</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valeur</label>
                        <input type="number" id="value" value="10" step="0.1" class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <button onclick="testEstimation()" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                        Tester l'API d'estimation
                    </button>
                </div>
                
                <div id="result" class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <p class="text-gray-500">Cliquez sur "Tester l'API d'estimation" pour voir le résultat</p>
                </div>
            </div>

            <!-- Informations sur l'authentification -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-medium text-yellow-800 mb-2">État de l'authentification</h3>
                <div id="auth-status" class="text-sm text-yellow-700">
                    <p>Vérification en cours...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Vérifier l'état de l'authentification
        document.addEventListener('DOMContentLoaded', function() {
            const authStatus = document.getElementById('auth-status');
            
            // Vérifier si l'utilisateur est connecté
            fetch('/api/user', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    authStatus.innerHTML = '<p class="text-green-600">✅ Utilisateur connecté</p>';
                } else {
                    authStatus.innerHTML = '<p class="text-blue-600">🔓 Non connecté (accès public)</p>';
                }
            })
            .catch(error => {
                authStatus.innerHTML = '<p class="text-blue-600">🔓 Non connecté (accès public)</p>';
            });
        });

        function testEstimation() {
            const chargingPointId = document.getElementById('charging_point_id').value;
            const mode = document.getElementById('mode').value;
            const value = parseFloat(document.getElementById('value').value);
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p class="text-blue-600">Test en cours...</p>';
            
            fetch(`/offers/${chargingPointId}/estimate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    mode: mode,
                    value: value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const estimation = data.estimation;
                    resultDiv.innerHTML = `
                        <div class="space-y-2">
                            <h3 class="font-semibold text-green-600">✅ API accessible sans authentification !</h3>
                            <p><strong>Énergie :</strong> ${estimation.energy} kWh</p>
                            <p><strong>Coût de base :</strong> ${estimation.base} ${estimation.currency}</p>
                            <p><strong>Frais :</strong> ${estimation.fees} ${estimation.currency}</p>
                            <p><strong>Total :</strong> <span class="font-bold text-blue-600">${estimation.total} ${estimation.currency}</span></p>
                            <p><strong>Peut réserver :</strong> ${data.canReserve ? 'Oui' : 'Non'}</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="text-red-600">❌ Erreur : ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                resultDiv.innerHTML = `<p class="text-red-600">❌ Erreur lors de la requête : ${error.message}</p>`;
            });
        }
    </script>
</body>
</html>
