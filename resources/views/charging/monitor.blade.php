<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de recharge - Session #{{ $data['session']->session_id }}</title>
    {{-- Tailwind deja inclus dans app.css --}}
    <link href="{{ asset("vendor/fontawesome/css/all.min.css") }}" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring-circle {
            transition: stroke-dashoffset 0.35s;
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('images/evon-logo.png') }}" alt="EVON Logo" class="h-12 w-auto">
                    <div>
                        <h1 class="text-2xl font-bold">Recharge en cours</h1>
                        <p class="text-blue-100">Session #{{ $data['session']->session_id }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white bg-opacity-20 px-3 py-2 rounded-lg">
                        <i class="fas fa-clock mr-2"></i>
                        <span id="sessionTimer">00:00</span>
                    </div>
                    <form action="{{ route('charging.stop', $data['session']->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors"
                                onclick="return confirm('Êtes-vous sûr de vouloir arrêter la recharge ?')">
                            <i class="fas fa-stop mr-2"></i>Arrêter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Informations de la session -->
        <div class="bg-white rounded-xl card-shadow p-6 mb-8">
            <div class="flex items-center space-x-4 mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center pulse-animation">
                    <i class="fas fa-bolt text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $data['session']->chargingPoint->name }}</h2>
                    <p class="text-gray-600">{{ $data['session']->chargingPoint->address }}</p>
                    <p class="text-sm text-gray-500">Connecteur {{ $data['session']->connector->connector_id }}</p>
                </div>
            </div>
        </div>

        <!-- Métriques principales -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Durée -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800" id="durationDisplay">
                        {{ $data['metrics']['duration_formatted'] }}
                    </span>
                </div>
                <h3 class="text-sm font-medium text-gray-600">Durée</h3>
            </div>

            <!-- Énergie consommée -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bolt text-green-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800" id="energyDisplay">
                        {{ $data['metrics']['energy_consumed_kwh'] }} kWh
                    </span>
                </div>
                <h3 class="text-sm font-medium text-gray-600">Énergie consommée</h3>
            </div>

            <!-- Coût actuel -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-euro-sign text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800" id="costDisplay">
                        {{ number_format($data['metrics']['total_cost'], 2) }} €
                    </span>
                </div>
                <h3 class="text-sm font-medium text-gray-600">Coût actuel</h3>
            </div>

            <!-- Puissance actuelle -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-tachometer-alt text-orange-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800" id="powerDisplay">
                        {{ $data['session']->connector->power_kw }} kW
                    </span>
                </div>
                <h3 class="text-sm font-medium text-gray-600">Puissance</h3>
            </div>
        </div>

        <!-- Graphique de progression -->
        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <!-- Progression circulaire -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Progression</h3>
                <div class="flex justify-center">
                    <div class="relative">
                        <svg class="progress-ring w-32 h-32" viewBox="0 0 120 120">
                            <circle class="progress-ring-circle" 
                                    stroke="#e5e7eb" 
                                    stroke-width="8" 
                                    fill="transparent" 
                                    r="52" 
                                    cx="60" 
                                    cy="60"/>
                            <circle class="progress-ring-circle" 
                                    stroke="#10b981" 
                                    stroke-width="8" 
                                    fill="transparent" 
                                    r="52" 
                                    cx="60" 
                                    cy="60"
                                    id="progressCircle"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-800" id="progressPercent">0%</div>
                                <div class="text-sm text-gray-600">Complété</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations détaillées -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Détails</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Prix par kWh:</span>
                        <span class="font-semibold">{{ number_format($data['metrics']['cost_per_kwh'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Début:</span>
                        <span class="font-semibold">{{ $data['metrics']['start_time'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Heure actuelle:</span>
                        <span class="font-semibold" id="currentTime">{{ $data['metrics']['current_time'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Statut:</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                            En cours
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Actions</h3>
            <div class="flex flex-wrap gap-4">
                <form action="{{ route('charging.stop', $data['session']->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg transition-colors"
                            onclick="return confirm('Êtes-vous sûr de vouloir arrêter la recharge ?')">
                        <i class="fas fa-stop mr-2"></i>Arrêter la recharge
                    </button>
                </form>
                <a href="{{ route('charging.receipt', $data['session']->id) }}" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-receipt mr-2"></i>Voir le reçu
                </a>
                <button onclick="window.print()" 
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg transition-colors">
                    <i class="fas fa-print mr-2"></i>Imprimer
                </button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let sessionStartTime = new Date('{{ $data['session']->started_at }}');
        let updateInterval;

        // Initialiser le suivi
        document.addEventListener('DOMContentLoaded', function() {
            updateMetrics();
            updateInterval = setInterval(updateMetrics, 10000); // Mise à jour toutes les 10 secondes
        });

        // Mettre à jour les métriques
        function updateMetrics() {
            const now = new Date();
            const durationMs = now - sessionStartTime;
            const durationMinutes = Math.floor(durationMs / (1000 * 60));
            
            // Mettre à jour l'affichage de la durée
            const hours = Math.floor(durationMinutes / 60);
            const minutes = durationMinutes % 60;
            const durationDisplay = hours > 0 ? 
                `${hours}h ${minutes.toString().padStart(2, '0')}m` : 
                `${minutes}m`;
            
            document.getElementById('sessionTimer').textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
            document.getElementById('durationDisplay').textContent = durationDisplay;
            
            // Mettre à jour l'heure actuelle
            document.getElementById('currentTime').textContent = 
                now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            
            // Calculer l'énergie consommée (simulation)
            const energyKwh = (durationMinutes / 60) * {{ $data['session']->connector->power_kw }};
            document.getElementById('energyDisplay').textContent = 
                `${energyKwh.toFixed(2)} kWh`;
            
            // Calculer le coût
            const cost = energyKwh * {{ $data['metrics']['cost_per_kwh'] }};
            document.getElementById('costDisplay').textContent = 
                `${cost.toFixed(2)} €`;
            
            // Mettre à jour la progression (basée sur une session typique de 1h)
            const maxDuration = 60; // 1 heure
            const progress = Math.min((durationMinutes / maxDuration) * 100, 100);
            document.getElementById('progressPercent').textContent = 
                `${Math.round(progress)}%`;
            
            // Mettre à jour le cercle de progression
            updateProgressCircle(progress);
        }

        // Mettre à jour le cercle de progression
        function updateProgressCircle(percent) {
            const circle = document.getElementById('progressCircle');
            const radius = 52;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (percent / 100) * circumference;
            
            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = offset;
        }

        // Nettoyer l'intervalle lors de la fermeture
        // Utilise pagehide au lieu de beforeunload (évite les warnings de dépréciation)
        window.addEventListener('pagehide', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        }, { capture: true });
    </script>
</body>
</html> 