@extends('layouts.app')

@section('title', __('Réservations en attente'))
@section('page-title', __('Réservations en attente'))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="bg-amber-100 dark:bg-amber-900/30 rounded-lg p-3">
                        <i class="fas fa-clock text-2xl text-amber-600 dark:text-amber-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Réservations des clients sur vos bornes') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Approuvez ou rejetez les réservations en attente') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-center sm:text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('En attente') }}</p>
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $pendingReservations->count() }}</p>
                </div>
                <a href="{{ route('reservations.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-list mr-2"></i>
                    {{ __('Toutes mes réservations') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" x-data="{ activeTab: 'pending' }">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <button type="button" 
                        @click="activeTab = 'pending'"
                        :class="activeTab === 'pending' ? 'border-eco-green-500 text-eco-green-600 dark:text-eco-green-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="flex-1 sm:flex-none py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-clock mr-2"></i>
                    {{ __('En attente') }}
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                        {{ $pendingReservations->count() }}
                    </span>
                </button>
                <button type="button" 
                        @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'border-eco-green-500 text-eco-green-600 dark:text-eco-green-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="flex-1 sm:flex-none py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors">
                    <i class="fas fa-list mr-2"></i>
                    {{ __('Toutes les réservations') }}
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        {{ $allReservations->total() }}
                    </span>
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Tab Pending -->
            <div x-show="activeTab === 'pending'" x-transition>
                @if($pendingReservations->count() > 0)
                    <div class="overflow-x-auto -mx-6 sm:mx-0">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('ID') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Point de charge') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Client') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Montant') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($pendingReservations as $reservation)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            #{{ $reservation->id }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-eco-green-100 dark:bg-eco-green-900/30">
                                                <i class="fas fa-charging-station text-eco-green-600 dark:text-eco-green-400"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $reservation->chargingPoint->name ?? '—' }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reservation->chargingPoint->location ?? '—' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if($reservation->user)
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $reservation->user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reservation->user->email }}</div>
                                        @else
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $reservation->guest_email ?? __('Invité') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reservation->guest_phone ?? '—' }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $reservation->reservation_type === 'kwh' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' }}">
                                            {{ $reservation->reservation_type === 'kwh' ? __('Énergie') : __('Durée') }}
                                        </span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">{{ number_format($reservation->reservation_value, 0) }}{{ $reservation->reservation_type === 'kwh' ? ' kWh' : ' min' }}</span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-eco-green-600 dark:text-eco-green-400">{{ number_format($reservation->estimated_cost, 2) }} €</span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $reservation->created_at->format('d/m/Y') }}<br>
                                        <span class="text-xs">{{ $reservation->created_at->format('H:i') }}</span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if(!$reservation->isPaid())
                                            <form action="{{ route('reservations.approve-by-owner', $reservation) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        onclick="return confirm('{{ __('Êtes-vous sûr de vouloir approuver cette réservation ?') }}')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-eco-green-600 text-white text-xs font-medium rounded-lg hover:bg-eco-green-700 transition-colors">
                                                    <i class="fas fa-check mr-1"></i>
                                                    {{ __('Approuver') }}
                                                </button>
                                            </form>
                                            @endif
                                            @if(!$reservation->isPaid())
                                            <button type="button" 
                                                    onclick="document.getElementById('rejectModal{{ $reservation->id }}').showModal()"
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-medium rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                                                <i class="fas fa-times mr-1"></i>
                                                {{ __('Rejeter') }}
                                            </button>
                                            @endif
                                            <a href="{{ route('reservations.show', $reservation) }}" 
                                               class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                <i class="fas fa-eye mr-1"></i>
                                                {{ __('Voir') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                            <i class="fas fa-check-circle text-2xl text-green-600 dark:text-green-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Aucune réservation en attente') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Toutes les réservations ont été traitées.') }}</p>
                    </div>
                @endif
            </div>

            <!-- Tab All -->
            <div x-show="activeTab === 'all'" x-transition>
                @if($allReservations->count() > 0)
                    <div class="overflow-x-auto -mx-6 sm:mx-0">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('ID') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Point de charge') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Client') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Montant') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Statut') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($allReservations as $reservation)
                                @php
                                    $statusVal = is_object($reservation->status) ? $reservation->status->value : $reservation->status;
                                    $displayAsConfirmed = $reservation->isPaid() && in_array($statusVal, ['pending', 'pending_confirmation'], true);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">#{{ $reservation->id }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">{{ $reservation->chargingPoint->name ?? '—' }}</td>
                                    <td class="px-4 py-4">
                                        @if($reservation->user)
                                            <div class="text-sm text-gray-900 dark:text-white">{{ $reservation->user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reservation->user->email }}</div>
                                        @else
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $reservation->guest_email ?? $reservation->guest_phone ?? __('Invité') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ number_format($reservation->estimated_cost ?? 0, 2) }} €</td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $displayAsConfirmed || $statusVal === 'confirmed' || $statusVal === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : '' }}
                                            {{ !$displayAsConfirmed && ($statusVal === 'pending' || $statusVal === 'pending_confirmation') ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : '' }}
                                            {{ $statusVal === 'canceled' || $statusVal === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : '' }}
                                            {{ !in_array($statusVal, ['confirmed','completed','pending','pending_confirmation','canceled','cancelled']) ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}">
                                            {{ $reservation->getDisplayStatusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $reservation->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('reservations.show', $reservation) }}" 
                                               class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                                <i class="fas fa-eye mr-1"></i>
                                                {{ __('Voir') }}
                                            </a>
                                            @if(in_array($statusVal, ['pending', 'pending_confirmation']) && !$reservation->isPaid())
                                            <form action="{{ route('reservations.approve-by-owner', $reservation) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('{{ __('Approuver cette réservation ?') }}')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-eco-green-600 text-white text-xs font-medium rounded-lg hover:bg-eco-green-700 transition-colors">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6 flex justify-center">
                        {{ $allReservations->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                            <i class="fas fa-inbox text-2xl text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Aucune réservation') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Les réservations des clients sur vos bornes apparaîtront ici.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modals de rejet --}}
@foreach($pendingReservations as $reservation)
<dialog id="rejectModal{{ $reservation->id }}" class="rounded-xl shadow-xl p-0 w-full max-w-md backdrop:bg-black/50">
    <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Rejeter la réservation') }}</h3>
        </div>
        <form action="{{ route('reservations.reject-by-owner', $reservation) }}" method="POST">
            @csrf
            <div class="px-6 py-4">
                <label for="rejection_reason_{{ $reservation->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Raison du rejet') }} ({{ __('optionnel') }})</label>
                <textarea name="rejection_reason" 
                          id="rejection_reason_{{ $reservation->id }}" 
                          rows="3" 
                          placeholder="{{ __('Expliquez pourquoi cette réservation est rejetée...') }}"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-eco-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white"></textarea>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                <button type="button" 
                        onclick="document.getElementById('rejectModal{{ $reservation->id }}').close()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                    {{ __('Annuler') }}
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    {{ __('Rejeter la réservation') }}
                </button>
            </div>
        </form>
    </div>
</dialog>
@endforeach
@endsection
