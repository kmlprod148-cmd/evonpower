<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Offre</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">Test de l'Offre de Recharge</h1>
        
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Test de l'API d'estimation</h2>
            
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
                    Tester l'estimation
                </button>
            </div>
            
            <div id="result" class="mt-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-gray-500">Cliquez sur "Tester l'estimation" pour voir le résultat</p>
            </div>
        </div>
    </div>

    <script>
        function testEstimation() {
            const chargingPointId = document.getElementById('charging_point_id').value;
            const mode = document.getElementById('mode').value;
            const value = parseFloat(document.getElementById('value').value);
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p class="text-blue-600">Calcul en cours...</p>';
            
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
                            <h3 class="font-semibold text-green-600">Estimation réussie !</h3>
                            <p><strong>Énergie :</strong> ${estimation.energy} kWh</p>
                            <p><strong>Coût de base :</strong> ${estimation.base} ${estimation.currency}</p>
                            <p><strong>Frais :</strong> ${estimation.fees} ${estimation.currency}</p>
                            <p><strong>Total :</strong> <span class="font-bold text-blue-600">${estimation.total} ${estimation.currency}</span></p>
                            <p><strong>Mode :</strong> ${estimation.mode}</p>
                            <p><strong>Peut réserver :</strong> ${data.canReserve ? 'Oui' : 'Non'}</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="text-red-600">Erreur : ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                resultDiv.innerHTML = `<p class="text-red-600">Erreur lors de la requête : ${error.message}</p>`;
            });
        }
    </script>
</body>
</html>
