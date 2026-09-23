@extends('layouts.app')

@section('title', 'Détails de la Réservation #' . $reservation->id . ' - Administration')
@section('page-title', 'Détails de la Réservation #' . $reservation->id)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="bg-primary-100 dark:bg-primary-900/30 rounded-lg p-3">
                        <i class="fas fa-file-invoice text-2xl text-primary-600 dark:text-primary-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Détails de la Réservation #{{ $reservation->id }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $reservation->chargingPoint->name ?? 'Borne inconnue' }} - {{ $reservation->start_time ? $reservation->start_time->format('d/m/Y H:i') : 'N/A' }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-100 text-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-800 dark:bg-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-900 dark:text-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-200">
                    <i class="fas fa-circle text-xs mr-1.5 animate-pulse"></i>
                    {{ $reservation->getDisplayStatusLabel() }}
                </span>
            </div>
        </div>
    </div>
    <!-- Alert Messages -->
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700 dark:text-green-300 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 dark:text-red-300 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <!-- Reservation Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle text-primary-600 dark:text-primary-400 mr-2"></i>
                        Informations Générales
                    </h3>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">ID:</span> {{ $reservation->id }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Statut:</span> 
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-100 text-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-800 dark:bg-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-900 dark:text-{{ $reservation->status ? $reservation->status->color() : 'gray' }}-200">
                                {{ $reservation->getDisplayStatusLabel() }}
                            </span>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Coût estimé:</span> {{ number_format($reservation->estimated_cost ?? 0, 2) }} €
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Coût réel:</span> {{ number_format($reservation->actual_cost ?? 0, 2) }} €
                        </p>
                    </div>
                </div>
                <div class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-bolt text-primary-600 dark:text-primary-400 mr-2"></i>
                        Détails Énergétiques
                    </h3>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Énergie estimée:</span> {{ $reservation->estimated_energy ?? 'N/A' }} kWh
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Énergie réelle:</span> {{ $reservation->actual_energy ?? 'N/A' }} kWh
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Durée estimée:</span> {{ $reservation->estimated_duration ?? 'N/A' }} minutes
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Durée réelle:</span> {{ $reservation->actual_duration ?? 'N/A' }} minutes
                        </p>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            @if($reservation->notes || $reservation->start_time || $reservation->end_time)
                <hr class="my-6 border-gray-200 dark:border-gray-700">
                
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-clock text-primary-600 dark:text-primary-400 mr-2"></i>
                        Informations Complémentaires
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            @if($reservation->start_time)
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Début:</span> {{ $reservation->start_time->format('d/m/Y H:i') }}
                                </p>
                            @endif
                            @if($reservation->end_time)
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Fin:</span> {{ $reservation->end_time->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @if($reservation->notes)
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Notes:</span> {{ $reservation->notes }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <!-- Actions Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-cogs text-primary-600 dark:text-primary-400 mr-2"></i>
                Actions
            </h3>
            <div class="flex flex-wrap gap-3">
                @if($reservation->status && $reservation->status !== \App\Enums\ReservationStatus::CONFIRMED && $reservation->status !== \App\Enums\ReservationStatus::COMPLETED)
                    <a href="{{ route('admin.reservations.confirm.show', $reservation) }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-check mr-2"></i>
                        Confirmer
                    </a>
                @else
                    <div class="flex items-center px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg">
                        <i class="fas fa-info-circle mr-2"></i>
                        Réservation {{ $reservation->status === \App\Enums\ReservationStatus::CONFIRMED ? 'confirmée' : 'terminée' }}
                    </div>
                @endif
                <a href="{{ route('admin.reservations.edit', $reservation) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-edit mr-2"></i>
                    Modifier
                </a>
                <a href="{{ route('admin.reservations.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour à la liste
                </a>
            </div>
        </div>
    </div>
</div>
@endsection