@extends('layouts.app')

@section('title', __('Accès aux Réservations'))
@section('page-title', __('Accès aux Réservations'))

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
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Accès aux Réservations') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Gérez et consultez vos réservations de recharge') }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total des réservations') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ isset($reservations) ? $reservations->total() : '0' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    @php
        // Afficher les filtres client pour user et client (pas admin, intégrateur, opérateur, partenaire)
        $user = auth()->user();
        $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
        $hasSystemRole = !empty(array_intersect($userRoles, $systemRoles));
        $isClientOnly = !$hasSystemRole && (in_array('user', $userRoles) || in_array('client', $userRoles));
    @endphp

    @if($isClientOnly)
        <!-- Filtres pour clients -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <form method="GET" action="{{ route('reservations.index') }}" id="filters-form">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Recherche -->
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Rechercher') }}
                        </label>
                        <input type="text" 
                               name="search" 
                               value="{{ $search ?? '' }}" 
                               placeholder="{{ __('Nom de la borne, plan...') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-eco-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Filtre par statut -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Statut') }}
                        </label>
                        <select name="status" 
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-eco-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('Tous') }}</option>
                            @if(isset($statuses))
                                @foreach($statuses as $status)
                                    <option value="{{ $status->value }}" {{ ($statusFilter ?? '') === $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Date de début -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Date début') }}
                        </label>
                        <input type="date" 
                               name="date_from" 
                               value="{{ $dateFrom ?? '' }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-eco-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Date de fin -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Date fin') }}
                        </label>
                        <input type="date" 
                               name="date_to" 
                               value="{{ $dateTo ?? '' }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-eco-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <!-- Tri -->
                <div class="mt-4 flex flex-wrap items-center gap-4">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Trier par') }}:
                        </label>
                        <select name="sort_by" 
                                class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-eco-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="created_at" {{ ($sortBy ?? 'created_at') === 'created_at' ? 'selected' : '' }}>{{ __('Date de création') }}</option>
                            <option value="start_time" {{ ($sortBy ?? '') === 'start_time' ? 'selected' : '' }}>{{ __('Date de début') }}</option>
                            <option value="estimated_cost" {{ ($sortBy ?? '') === 'estimated_cost' ? 'selected' : '' }}>{{ __('Montant') }}</option>
                            <option value="status" {{ ($sortBy ?? '') === 'status' ? 'selected' : '' }}>{{ __('Statut') }}</option>
                        </select>
                        <select name="sort_order" 
                                class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-eco-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                            <option value="desc" {{ ($sortOrder ?? 'desc') === 'desc' ? 'selected' : '' }}>{{ __('Décroissant') }}</option>
                            <option value="asc" {{ ($sortOrder ?? '') === 'asc' ? 'selected' : '' }}>{{ __('Croissant') }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-eco-green-600 text-white font-medium rounded-lg hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 transition-colors">
                            <i class="fas fa-filter mr-2"></i>
                            {{ __('Appliquer') }}
                        </button>
                        <a href="{{ route('reservations.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            {{ __('Réinitialiser') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    @else
        <!-- Search Section pour autres utilisateurs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0">
                <i class="fas fa-search text-xl text-primary-600 dark:text-primary-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Rechercher une réservation') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Entrez votre email ou numéro de téléphone') }}</p>
            </div>
        </div>
            
            <form method="GET" action="{{ route('reservations.index') }}" id="search-form">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input id="search" type="text" 
                                class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-500 @error('search') border-red-500 ring-red-500 @enderror"
                                name="search" 
                                value="{{ $search ?? '' }}" 
                                placeholder="votre.email@example.com ou +212694187833">
                        </div>
                        @error('search') 
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                {{ $message }}
                            </p> 
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" 
                            class="inline-flex items-center px-6 py-3 bg-eco-green-600 text-white font-semibold rounded-lg shadow-sm hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 transition-colors duration-200" 
                            id="search-btn">
                            <i class="fas fa-search mr-2"></i>
                            {{ __('Rechercher') }}
                        </button>
                        @if(isset($search) && !empty($search))
                            <a href="{{ route('reservations.index') }}" 
                                class="inline-flex items-center px-4 py-3 bg-gray-500 text-white font-semibold rounded-lg shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>
                                {{ __('Effacer') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- Alert Messages -->
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

    @if (isset($reservations))
        @if ($reservations->count() > 0)
            <!-- Results Header -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-list-alt text-xl text-primary-600 dark:text-primary-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Vos Réservations') }}</h3>
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
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach ($reservations as $reservation)
                    <div class="group bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200">
                        <!-- Card Header -->
                        <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/50 dark:to-primary-800/50 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="bg-eco-green-600 text-white rounded-lg p-2 mr-3">
                                        <i class="fas fa-charging-station text-sm"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                            {{ $reservation->chargingPoint->name ?? 'Borne inconnue' }}
                                        </h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ $reservation->chargingPoint->address ?? '' }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    #{{ $reservation->id }}
                                </span>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="flex justify-center">
                                @php
                                    $statusValue = $reservation->status?->value ?? $reservation->status ?? 'unknown';
                                    $displayAsConfirmed = $reservation->isPaid() && in_array($statusValue, ['pending', 'pending_confirmation'], true);
                                    $statusColor = ($reservation->isPaidByBalance() || $displayAsConfirmed)
                                        ? 'green'
                                        : ($reservation->status ? $reservation->status->color() : 'gray');
                                @endphp
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900 dark:text-{{ $statusColor }}-200">
                                    <i class="fas fa-circle text-xs mr-1.5 animate-pulse"></i>
                                    {{ $reservation->getDisplayStatusLabel() }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4">
                            <!-- Guest Information -->
                            @if($reservation->guest_email || $reservation->guest_phone)
                                <div class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-700">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-user-friends text-blue-600 dark:text-blue-400 mr-2"></i>
                                        <p class="text-xs font-semibold text-blue-800 dark:text-blue-200">{{ __('messages.guest_reservation') }}</p>
                                    </div>
                                    @if($reservation->guest_email)
                                        <p class="text-xs text-blue-700 dark:text-blue-300 flex items-center">
                                            <i class="fas fa-envelope mr-2"></i>
                                            {{ $reservation->guest_email }}
                                        </p>
                                    @endif
                                    @if($reservation->guest_phone)
                                        <p class="text-xs text-blue-700 dark:text-blue-300 flex items-center">
                                            <i class="fas fa-phone mr-2"></i>
                                            {{ $reservation->guest_phone }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                            
                            <!-- Reservation Details -->
                            <div class="space-y-2 mb-4">
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
                            </div>

                            <!-- Price and Action -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Facturé (TTC)</p>
                                    <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                        {{ number_format($reservation->estimated_cost ?? 0, 2) }} 
                                        <span class="text-xs">{{ $reservation->pricingPlan->currency ?? 'EUR' }}</span>
                                    </p>
                                </div>
                                <a href="{{ route('reservations.show', $reservation->id) }}" 
                                   class="inline-flex items-center px-3 py-2 bg-eco-green-600 text-white text-sm font-medium rounded-lg hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 transition-colors duration-200">
                                    <i class="fas fa-eye mr-1.5"></i>
                                    {{ __('Détails') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="flex justify-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    {{ $reservations->appends(['search' => $search])->links() }}
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
                    @if($isClientOnly)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Vous n\'avez pas encore de réservation. Parcourez les bornes disponibles et réservez votre créneau de recharge.') }}</p>
                        <a href="{{ route('charging-points.index') }}" class="inline-flex items-center px-4 py-2 bg-eco-green-600 text-white text-sm font-medium rounded-lg hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 transition-all duration-200">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            {{ __('Voir les bornes') }}
                        </a>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Essayez de modifier vos critères de recherche ou contactez le support.') }}</p>
                        <a href="{{ route('reservations.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-eco-green-600 text-white text-sm font-medium rounded-lg hover:bg-eco-green-700 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 transition-colors duration-200">
                            <i class="fas fa-refresh mr-2"></i>
                            {{ __('Nouvelle recherche') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('search-form');
        const searchBtn = document.getElementById('search-btn');
        
        if (searchForm && searchBtn) {
            searchForm.addEventListener('submit', function () {
                const originalContent = searchBtn.innerHTML;
                searchBtn.innerHTML = `
                    <div class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent align-[-0.125em] motion-reduce:animate-[spin_1.5s_linear_infinite] mr-2" role="status"></div>
                    {{ __('Recherche...') }}
                `;
                searchBtn.disabled = true;
                searchBtn.classList.add('opacity-75', 'cursor-not-allowed');
                
                // Re-enable button after 3 seconds as fallback
                setTimeout(() => {
                    searchBtn.innerHTML = originalContent;
                    searchBtn.disabled = false;
                    searchBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                }, 3000);
            });
        }
    });
</script>
@endsection
