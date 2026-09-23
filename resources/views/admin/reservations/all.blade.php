@extends('layouts.app')

@section('page-title', 'Toutes les Réservations')

@section('content')
<!-- Header Section -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6">
    <div class="bg-gradient-to-r from-primary to-primary-600 rounded-t-2xl p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                    <i class="fas fa-list text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Toutes les Réservations</h1>
                    <p class="text-primary-100 mt-1">Vue d'ensemble de toutes les réservations</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.reservations.index') }}" 
                   class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition-all duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reservations Table -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Liste des Réservations</h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Borne</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($reservations as $reservation)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        #{{ $reservation->id }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        @if($reservation->user)
                            {{ $reservation->user->name }}
                        @else
                            <span class="text-gray-500">Invité</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ $reservation->chargingPoint->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($reservation->status === \App\Enums\ReservationStatus::PENDING_CONFIRMATION)
                                bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($reservation->status === \App\Enums\ReservationStatus::CONFIRMED)
                                bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($reservation->status === \App\Enums\ReservationStatus::CANCELLED)
                                bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @else
                                bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                            @endif">
                            {{ $reservation->getDisplayStatusLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ $reservation->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <!-- Voir -->
                            <a href="{{ route('admin.reservations.show', $reservation) }}" 
                               class="text-primary hover:text-primary-600 dark:text-primary-400 dark:hover:text-primary-300 p-2 rounded hover:bg-primary-100 dark:hover:bg-primary-900 transition-colors" 
                               title="Voir">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            
                            <!-- Modifier -->
                            <a href="{{ route('admin.reservations.edit', $reservation) }}" 
                               class="text-warning-600 hover:text-warning-900 dark:text-warning-400 dark:hover:text-warning-300 p-2 rounded hover:bg-warning-100 dark:hover:bg-warning-900 transition-colors" 
                               title="Modifier">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            
                            <!-- Confirmer -->
                            @if($reservation->status === \App\Enums\ReservationStatus::PENDING_CONFIRMATION)
                            <form action="{{ route('admin.reservations.confirm', $reservation) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="text-success-600 hover:text-success-900 dark:text-success-400 dark:hover:text-success-300 p-2 rounded hover:bg-success-100 dark:hover:bg-success-900 transition-colors" 
                                        onclick="return confirm('Confirmer cette réservation ?')" 
                                        title="Confirmer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </form>
                            
                            <!-- Rejeter -->
                            <form action="{{ route('admin.reservations.reject', $reservation) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="text-danger-600 hover:text-danger-900 dark:text-danger-400 dark:hover:text-danger-300 p-2 rounded hover:bg-danger-100 dark:hover:bg-danger-900 transition-colors" 
                                        onclick="return confirm('Êtes-vous sûr de vouloir rejeter cette réservation ?')" 
                                        title="Rejeter">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-calendar-times text-4xl mb-4"></i>
                        <p>Aucune réservation trouvée</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($reservations->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $reservations->links() }}
    </div>
    @endif
</div>
@endsection
