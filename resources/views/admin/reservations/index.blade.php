@extends('layouts.app')

@section('title', 'Liste des Réservations - EVON')
@section('page-title', 'Liste des Réservations')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="bg-primary-100 dark:bg-primary-900/30 rounded-lg p-3">
                        <i class="fas fa-calendar-check text-2xl text-primary-600 dark:text-primary-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Réservations</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Gérez toutes les réservations du système</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total des réservations') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $reservations->total() }}
                    </p>
                </div>
                <a href="{{ route('admin.reservations.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-eco-green-600 text-white text-sm font-medium rounded-lg hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvelle Réservation
                </a>
            </div>
        </div>
    </div>
    <!-- Search and Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0">
                <i class="fas fa-search text-xl text-primary-600 dark:text-primary-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Rechercher et Filtrer') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Trouvez rapidement les réservations') }}</p>
        </div>
    </div>
        
        <form method="GET" action="{{ route('admin.reservations.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Rechercher') }}</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="ID, email, téléphone..."
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-500">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Statut') }}</label>
                    <select id="status" 
                            name="status" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">{{ __('Tous les statuts') }}</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('En attente') }}</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>{{ __('Confirmée') }}</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('Annulée') }}</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('Terminée') }}</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Date de début') }}</label>
                    <input type="date" 
                           id="date_from" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-eco-green-600 text-white text-sm font-medium rounded-lg hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-search mr-2"></i>
                    {{ __('Rechercher') }}
                </button>
                <a href="{{ route('admin.reservations.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    {{ __('Effacer') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Reservations List -->
    @if($reservations->count() > 0)
        <!-- Results Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-list-alt text-xl text-primary-600 dark:text-primary-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Réservations') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $reservations->count() }} {{ __('réservation(s) trouvée(s)') }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                        {{ $reservations->count() }} {{ __('résultats') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Reservations Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach ($reservations as $reservation)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/50 dark:to-primary-800/50 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="bg-eco-green-600 text-white rounded-lg p-2 mr-3">
                                    <i class="fas fa-calendar-check text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                        Réservation #{{ $reservation->id }}
                                    </h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300">
                                        {{ $reservation->chargingPoint->name ?? 'Borne inconnue' }}
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                #{{ $reservation->id }}
                            </span>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="flex justify-center">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-100 text-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-800 dark:bg-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-900 dark:text-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-200">
                                <i class="fas fa-circle text-xs mr-1.5 animate-pulse"></i>
                                {{ $reservation->getDisplayStatusLabel() }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4">
                        <!-- Reservation Details -->
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-gray-600 dark:text-gray-300">
                                <i class="fas fa-user text-blue-500 mr-3 w-4"></i>
                                <span class="text-sm font-medium">
                                    @if($reservation->user)
                                        {{ $reservation->user->name ?? $reservation->user->email }}
                                    @elseif($reservation->guest_email)
                                        Invité: {{ $reservation->guest_email }}
                                    @elseif($reservation->guest_phone)
                                        Invité: {{ $reservation->guest_phone }}
                                    @else
                                        Utilisateur inconnu
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center text-gray-600 dark:text-gray-300">
                                <i class="fas fa-clock text-primary-600 mr-3 w-4"></i>
                                <span class="text-sm font-medium">{{ $reservation->start_time ? $reservation->start_time->format('d/m/Y H:i') : 'N/A' }}</span>
                            </div>
                            <div class="flex items-center text-gray-600 dark:text-gray-300">
                                <i class="fas fa-bolt text-yellow-500 mr-3 w-4"></i>
                                <span class="text-sm font-medium">
                                    {{ $reservation->estimated_energy ?? $reservation->estimated_duration }} 
                                    {{ $reservation->estimated_energy ? 'kWh' : 'min' }}
                                </span>
                            </div>
                            <div class="flex items-center text-gray-600 dark:text-gray-300">
                                <i class="fas fa-euro-sign text-green-500 mr-3 w-4"></i>
                                <span class="text-sm font-medium">{{ number_format($reservation->estimated_cost ?? 0, 2) }} €</span>
                            </div>
                            @if($reservation->transactionDetails)
                                <div class="flex items-center text-gray-600 dark:text-gray-300">
                                    <i class="fas fa-share-alt text-red-500 mr-3 w-4"></i>
                                    <span class="text-xs font-medium">
                                        Admin: {{ number_format($reservation->transactionDetails->admin_share_amount ?? 0, 2) }}€ | 
                                        Intégrateur: {{ number_format($reservation->transactionDetails->integrator_share_amount ?? 0, 2) }}€
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-right">
                                <x-reservation-amounts :reservation="$reservation" size="default" />
                            </div>
                            <div class="flex gap-2">
                            @if($reservation->id)
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" 
                                       class="inline-flex items-center px-3 py-2 bg-eco-green-600 text-white text-sm font-medium rounded-lg hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 transition-colors duration-200">
                                        <i class="fas fa-eye mr-1.5"></i>
                                        Voir
                                    </a>
                                    @if($reservation->status && $reservation->status !== \App\Enums\ReservationStatus::CONFIRMED && $reservation->status !== \App\Enums\ReservationStatus::COMPLETED)
                                        <a href="{{ route('admin.reservations.confirm.show', $reservation) }}" 
                                           class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                                            <i class="fas fa-check mr-1.5"></i>
                                            Confirmer
                                        </a>
                                    @endif
                            @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Réservation invalide</span>
                            @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="flex justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                {{ $reservations->appends(request()->query())->links() }}
            </div>
        </div>
    @else
        <!-- No Results State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <div class="max-w-md mx-auto">
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-4 w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-search text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Aucune réservation trouvée') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Essayez de modifier vos critères de recherche ou créez une nouvelle réservation.') }}</p>
                <a href="{{ route('admin.reservations.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-eco-green-600 text-white text-sm font-medium rounded-lg hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    {{ __('Nouvelle réservation') }}
                </a>
            </div>
        </div>
    @endif
    </div>
@endsection