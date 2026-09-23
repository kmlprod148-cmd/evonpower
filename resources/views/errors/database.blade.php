<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Indisponible - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-8 text-center">
                <div class="bg-white bg-opacity-20 rounded-full p-4 w-20 h-20 mx-auto mb-4">
                    <i class="fas fa-database text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Service Indisponible</h1>
                <p class="text-red-100">Problème de connexion à la base de données</p>
            </div>
            
            <!-- Content -->
            <div class="px-6 py-8">
                <div class="text-center mb-6">
                    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl p-4 mb-6">
                        <div class="flex items-center justify-center mb-3">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-2xl"></i>
                        </div>
                        <h2 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
                            {{ $error ?? 'Service temporairement indisponible' }}
                        </h2>
                        <p class="text-red-700 dark:text-red-300 text-sm">
                            {{ $message ?? 'La base de données n\'est pas accessible. Veuillez réessayer plus tard.' }}
                        </p>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="space-y-3">
                    <button onclick="window.location.reload()" 
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Réessayer
                    </button>
                    
                    <a href="{{ route('admin.reservations.index') }}" 
                       class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour aux réservations
                    </a>
                    
                    <a href="{{ route('dashboard') }}" 
                       class="w-full bg-primary hover:bg-primary-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-home mr-2"></i>
                        Accueil
                    </a>
                </div>
                
                <!-- Technical Info -->
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <details class="text-sm text-gray-600 dark:text-gray-400">
                        <summary class="cursor-pointer hover:text-gray-800 dark:hover:text-gray-200">
                            <i class="fas fa-info-circle mr-1"></i>
                            Informations techniques
                        </summary>
                        <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <p><strong>Code d'erreur:</strong> 503 Service Unavailable</p>
                            <p><strong>Type:</strong> Database Connection Error</p>
                            <p><strong>Heure:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
                            @if(isset($retry_url))
                            <p><strong>URL:</strong> {{ $retry_url }}</p>
                            @endif
                        </div>
                    </details>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-6 text-gray-500 dark:text-gray-400 text-sm">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
