<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Test</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-600">Tableau de bord de test</p>
            </div>

            <!-- Cartes statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Nombre total de recharge -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Nombre total de recharge</h3>
                    <p class="text-3xl font-bold mt-2 text-gray-900">{{ $stats['totalRecharges'] ?? 1250 }}</p>
                    <div class="mt-4 flex items-center">
                        <div class="h-2 w-2 bg-green-400 rounded-full mr-2"></div>
                        <span class="text-sm text-green-600">+12% ce mois</span>
                    </div>
                </div>

                <!-- Recharge active -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Recharge active</h3>
                    <p class="text-3xl font-bold mt-2 text-gray-900">{{ $stats['rechargesActives'] ?? 8 }}</p>
                    <div class="mt-4 flex items-center">
                        <div class="h-2 w-2 bg-blue-400 rounded-full mr-2"></div>
                        <span class="text-sm text-blue-600">En cours</span>
                    </div>
                </div>

                <!-- Abonnements actifs -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Abonnements actifs</h3>
                    <p class="text-3xl font-bold mt-2 text-gray-900">{{ $stats['abonnementsActifs'] ?? 45 }}</p>
                    <div class="mt-4 flex items-center">
                        <div class="h-2 w-2 bg-purple-400 rounded-full mr-2"></div>
                        <span class="text-sm text-purple-600">+5% ce mois</span>
                    </div>
                </div>

                <!-- Bornes actives -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Bornes actives</h3>
                    <p class="text-3xl font-bold mt-2 text-gray-900">{{ $stats['bornesActives'] ?? 23 }}</p>
                    <div class="mt-4 flex items-center">
                        <div class="h-2 w-2 bg-orange-400 rounded-full mr-2"></div>
                        <span class="text-sm text-orange-600">En ligne</span>
                    </div>
                </div>
            </div>

            <!-- Graphique et tableau -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Graphique des recharges -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Nombre des recharges</h3>
                        <button class="text-sm text-gray-500 border border-gray-300 rounded px-3 py-1">
                            Aujourd'hui
                        </button>
                    </div>
                    <div class="h-72 bg-gray-50 rounded flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-400 mb-2">Graphique</div>
                            <div class="text-sm text-gray-500">Données: {{ json_encode($hourlyData['data'] ?? []) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des recharges actives -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Recharge active ({{ $stats['rechargesActives'] ?? 3 }})</h3>
                    </div>
                    <div class="space-y-3">
                        @if(isset($rechargesActivesList) && count($rechargesActivesList) > 0)
                            @foreach($rechargesActivesList as $recharge)
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                    <span class="font-medium text-gray-900">{{ $recharge['name'] ?? 'N/A' }}</span>
                                    <span class="text-sm text-gray-600">{{ $recharge['kwh'] ?? '0' }} kWh</span>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-gray-500 py-8">
                                Aucune recharge active
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Informations de debug -->
            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-medium text-yellow-800 mb-2">Informations de debug:</h4>
                <div class="text-sm text-yellow-700">
                    <p>Stats: {{ json_encode($stats ?? []) }}</p>
                    <p>Données horaires: {{ json_encode($hourlyData ?? []) }}</p>
                    <p>Recharges actives: {{ json_encode($rechargesActivesList ?? []) }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
