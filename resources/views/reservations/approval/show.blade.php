@extends('layouts.app')

@section('title', __('Approbation - Réservation #') . $reservation->id)
@section('page-title', __('Approbation - Réservation #') . $reservation->id)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="bg-amber-100 dark:bg-amber-900/30 rounded-lg p-3">
                        <i class="fas fa-clipboard-check text-2xl text-amber-600 dark:text-amber-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ __('Réservation #') }}{{ $reservation->id }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Créée le') }} {{ $reservation->created_at?->format('d/m/Y à H:i') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $statusVal = is_object($reservation->status) ? $reservation->status->value : ($reservation->status ?? 'pending');
                    $statusColors = ['pending' => 'yellow', 'pending_confirmation' => 'orange', 'confirmed' => 'blue', 'approved' => 'green', 'active' => 'green', 'completed' => 'gray', 'cancelled' => 'red', 'canceled' => 'red'];
                    $statusColor = $statusColors[$statusVal] ?? 'gray';
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium
                    bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/30 dark:text-{{ $statusColor }}-200">
                    <i class="fas fa-circle text-xs mr-1.5"></i>
                    {{ ucfirst(str_replace('_', ' ', $statusVal)) }}
                </span>
                <a href="{{ route('reservations.approval.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    {{ __('Retour') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-0.5"></i>
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Reservation Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-info-circle text-primary-500 mr-2"></i>
                    {{ __('Détails de la Réservation') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Type de recharge') }}</label>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            @if ($reservation->reservation_type === 'kwh')
                                <i class="fas fa-bolt text-blue-500 mr-1"></i> {{ $reservation->reservation_value }} kWh
                            @else
                                <i class="fas fa-clock text-purple-500 mr-1"></i> {{ $reservation->reservation_value }} minutes
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Coût estimé') }}</label>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ number_format($reservation->estimated_cost ?? $reservation->amount ?? 0, 2) }}
                            {{ strtoupper($reservation->pricingPlan->currency ?? 'EUR') }}
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Statut paiement') }}</label>
                        <p class="mt-1">
                            @php
                                $payStatus = strtoupper($reservation->payment_status ?? 'PENDING');
                                $payColors = ['PAID' => 'green', 'PAYE' => 'green', 'PENDING' => 'yellow', 'FAILED' => 'red'];
                                $payColor = $payColors[$payStatus] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                bg-{{ $payColor }}-100 text-{{ $payColor }}-800 dark:bg-{{ $payColor }}-900/30 dark:text-{{ $payColor }}-200">
                                {{ $payStatus }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Mode de paiement') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ ucfirst($reservation->payment_method ?? 'N/A') }}
                            @if ($reservation->payment_mode)
                                <span class="text-xs text-gray-500">({{ $reservation->payment_mode }})</span>
                            @endif
                        </p>
                    </div>
                    @if ($reservation->start_time)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Heure de début') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $reservation->start_time->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                    @if ($reservation->ocppTag)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Tag OCPP') }}</label>
                        <p class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $reservation->ocppTag->ocpp_tag }}</p>
                    </div>
                    @endif
                </div>
                @if ($reservation->notes)
                    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Notes') }}</label>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reservation->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Charging Sessions -->
            @if ($reservation->chargingSessions && $reservation->chargingSessions->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                    {{ __('Sessions de Charge') }}
                </h2>
                <div class="space-y-3">
                    @foreach ($reservation->chargingSessions as $session)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('Session') }} #{{ $session->id }}
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ $session->status }}
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $session->started_at?->format('d/m/Y H:i') ?? 'N/A' }}
                                    @if ($session->meter_start !== null)
                                        — Meter: {{ $session->meter_start }} Wh
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar: Client & Charging Point + Actions -->
        <div class="space-y-6">
            <!-- Client Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-user text-primary-500 mr-2"></i>
                    {{ __('Client') }}
                </h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $reservation->user->name ?? $reservation->guest_email ?? __('Invité') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $reservation->user->email ?? $reservation->guest_email ?? '' }}
                        </p>
                    </div>
                    @if ($reservation->guest_phone)
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <i class="fas fa-phone mr-2 text-gray-400"></i>{{ $reservation->guest_phone }}
                        </p>
                    @endif
                    @if ($reservation->user && $reservation->user->ocppTags && $reservation->user->ocppTags->count() > 0)
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Tags OCPP') }}</p>
                            @foreach ($reservation->user->ocppTags as $tag)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 mr-1 mb-1">
                                    {{ $tag->ocpp_tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Charging Point Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-charging-station text-green-500 mr-2"></i>
                    {{ __('Borne de Recharge') }}
                </h2>
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $reservation->chargingPoint->name ?? 'N/A' }}
                    </p>
                    @if ($reservation->chargingPoint)
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            ID Steve: {{ $reservation->chargingPoint->steve_charging_point_id ?? 'N/A' }}
                        </p>
                        @if ($reservation->chargingPoint->location)
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-map-marker-alt mr-1"></i>{{ $reservation->chargingPoint->location }}
                            </p>
                        @endif
                    @endif
                    @if ($reservation->connector)
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Connecteur') }} #{{ $reservation->connector->connector_id ?? $reservation->connector->id }}
                            ({{ $reservation->connector->type ?? 'N/A' }})
                        </p>
                    @endif
                </div>
            </div>

            <!-- Approval Actions -->
            @if (in_array($statusVal, ['pending', 'pending_confirmation']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-gavel text-amber-500 mr-2"></i>
                    {{ __('Actions') }}
                </h2>

                <!-- Approve Form -->
                <form method="POST" action="{{ route('reservations.approval.approve', $reservation) }}" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="notes" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Notes (optionnel)') }}
                        </label>
                        <textarea name="notes" id="notes" rows="2"
                                  class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                                  placeholder="{{ __('Ajouter une note...') }}"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
                            onclick="return confirm('{{ __('Approuver cette réservation ?') }}')">
                        <i class="fas fa-check mr-2"></i>
                        {{ __('Approuver') }}
                    </button>
                </form>

                <!-- Reject Form -->
                <form method="POST" action="{{ route('reservations.approval.reject', $reservation) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="reason" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                            {{ __('Motif du rejet') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea name="reason" id="reason" rows="2" required
                                  class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-red-500 focus:border-red-500"
                                  placeholder="{{ __('Indiquez le motif du rejet...') }}"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors"
                            onclick="return confirm('{{ __('Rejeter cette réservation ?') }}')">
                        <i class="fas fa-times mr-2"></i>
                        {{ __('Rejeter') }}
                    </button>
                </form>
            </div>
            @elseif ($statusVal === 'approved' || $statusVal === 'confirmed')
            <!-- Manual Start -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-play text-green-500 mr-2"></i>
                    {{ __('Démarrage Manuel') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    {{ __('La réservation est approuvée. Vous pouvez démarrer manuellement la session si nécessaire.') }}
                </p>
                <form method="POST" action="{{ route('reservations.approval.start-manually', $reservation) }}">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                            onclick="return confirm('{{ __('Démarrer manuellement la session ?') }}')">
                        <i class="fas fa-play mr-2"></i>
                        {{ __('Démarrer la session') }}
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
