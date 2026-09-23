@extends('layouts.app')

@section('title', 'Activité Récente')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Activité Récente</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Suivez toutes vos activités et interactions</p>
    </div>

    <!-- Navigation -->
    <div class="mb-6">
        <nav class="flex space-x-4">
            <a href="{{ route('operator.profile') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                <i class="fas fa-user mr-1"></i>Profil
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-600 dark:text-gray-400">Activité</span>
        </nav>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filtres</h3>
        <div class="flex flex-wrap gap-4">
            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-list mr-2"></i>Tout
            </button>
            <button class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-exchange-alt mr-2"></i>Transactions
            </button>
            <button class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-calendar-check mr-2"></i>Réservations
            </button>
            <button class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-charging-station mr-2"></i>Points de charge
            </button>
        </div>
    </div>

    <!-- Liste d'activité -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activité Récente</h3>
        </div>
        
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($recentActivity as $activity)
            <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex items-start space-x-4">
                    <!-- Icône selon le type -->
                    <div class="flex-shrink-0">
                        @if($activity['type'] === 'transaction')
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-exchange-alt text-green-600 dark:text-green-400"></i>
                            </div>
                        @elseif($activity['type'] === 'reservation')
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-calendar-check text-blue-600 dark:text-blue-400"></i>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-info-circle text-gray-600 dark:text-gray-400"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Contenu de l'activité -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $activity['description'] }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $activity['date']->diffForHumans() }}
                            </p>
                        </div>
                        
                        <div class="mt-1 flex items-center space-x-4">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $activity['date']->format('d/m/Y à H:i') }}
                            </span>
                            
                            @if(isset($activity['amount']))
                                <span class="text-xs font-medium text-green-600 dark:text-green-400">
                                    {{ number_format($activity['amount'], 2) }}€
                                </span>
                            @endif
                            
                            @if(isset($activity['status']))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($activity['status'] === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($activity['status'] === 'completed') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 @endif">
                                    {{ ucfirst($activity['status']) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-history text-gray-400 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune activité récente</h3>
                <p class="text-gray-600 dark:text-gray-400">Vos activités apparaîtront ici au fur et à mesure.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination (si nécessaire) -->
    @if($recentActivity->count() >= 20)
    <div class="mt-6 flex justify-center">
        <nav class="flex items-center space-x-2">
            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">
                Précédent
            </button>
            <button class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                1
            </button>
            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700">
                Suivant
            </button>
        </nav>
    </div>
    @endif

    <!-- Actions -->
    <div class="mt-6 flex justify-between items-center">
        <a href="{{ route('operator.profile') }}" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Retour au profil
        </a>
        <a href="{{ route('operator.statistics') }}" 
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition-colors">
            <i class="fas fa-chart-bar mr-2"></i>Voir les statistiques
        </a>
    </div>
</div>
@endsection
