<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Minimal</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard Minimal</h1>
                <p class="text-gray-600">Version ultra-simple pour test</p>
            </div>

            <!-- Navigation -->
            <div class="mb-8 bg-white rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Navigation</h2>
                <div class="flex flex-wrap gap-4">
                    <a href="/dashboard-minimal-public" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Dashboard Minimal (actuel)
                    </a>
                    <a href="/dashboard-static" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        Dashboard Complet
                    </a>
                    <a href="/dashboard" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                        Dashboard Principal
                    </a>
                </div>
            </div>

            <!-- Cartes statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Total Recharges</h3>
                    <p class="text-3xl font-bold mt-2 text-gray-900">1250</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Recharges Actives</h3>
                    <p class="text-3xl font-bold mt-2 text-gray-900">8</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Abonnements Actifs</h3>
                    <p class="text-3xl font-bold mt-2 text-gray-900">45</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Bornes Actives</h3>
                    <p class="text-3xl font-bold mt-2 text-gray-900">23</p>
                </div>
            </div>

            <!-- Message de test -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 class="font-medium text-green-800 mb-2">✅ Test réussi !</h4>
                <p class="text-green-700">Si vous voyez ce message, le dashboard fonctionne correctement.</p>
            </div>

            <!-- Informations de debug -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-medium text-blue-800 mb-2">Informations de debug:</h4>
                <div class="text-sm text-blue-700">
                    <p>Timestamp: {{ date('Y-m-d H:i:s') }}</p>
                    <p>User Agent: {{ $_SERVER['HTTP_USER_AGENT'] ?? 'N/A' }}</p>
                    <p>PHP Version: {{ PHP_VERSION }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
