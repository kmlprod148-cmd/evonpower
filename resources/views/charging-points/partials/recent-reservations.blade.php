@props(['chargingPoint'])

@php
    $recentReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
        ->with(['user', 'pricingPlan'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
@endphp

<!-- Recent Reservations Section -->
<x-card class="mb-4 md:mb-6">
    <x-section-header title="Réservations Récentes">
        <x-slot:actions>
            <a href="{{ route('reservations.index') }}?charging_point_id={{ $chargingPoint->id }}" class="text-xs md:text-sm text-green-600 hover:text-green-800 whitespace-nowrap font-medium">
                Voir toutes →
            </a>
        </x-slot:actions>
    </x-section-header>
    
    @if($recentReservations->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 md:px-4 lg:px-6 py-2 md:py-3 text-left text-[10px] md:text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-3 md:px-4 lg:px-6 py-2 md:py-3 text-left text-[10px] md:text-xs font-medium text-gray-500 uppercase tracking-wider">Valeur</th>
                    <th class="px-3 md:px-4 lg:px-6 py-2 md:py-3 text-left text-[10px] md:text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-3 md:px-4 lg:px-6 py-2 md:py-3 text-left text-[10px] md:text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-3 md:px-4 lg:px-6 py-2 md:py-3 text-left text-[10px] md:text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($recentReservations as $reservation)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 md:px-4 lg:px-6 py-2 md:py-3 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($reservation->reservation_type === 'kwh')
                                <svg class="h-4 w-4 md:h-5 md:w-5 text-blue-600 mr-1.5 md:mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span class="text-xs md:text-sm font-medium text-gray-900">Énergie</span>
                            @else
                                <svg class="h-4 w-4 md:h-5 md:w-5 text-green-600 mr-1.5 md:mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-xs md:text-sm font-medium text-gray-900">Durée</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-3 md:px-4 lg:px-6 py-2 md:py-3 whitespace-nowrap text-xs md:text-sm text-gray-900">
                        {{ number_format($reservation->reservation_value, 1) }}
                        @if($reservation->reservation_type === 'kwh')
                            kWh
                        @else
                            min
                        @endif
                    </td>
                    <td class="px-3 md:px-4 lg:px-6 py-2 md:py-3 whitespace-nowrap text-xs md:text-sm text-gray-900">
                        @if($reservation->user)
                            {{ $reservation->user->name }}
                        @elseif($reservation->guest_email)
                            <span class="text-gray-500">{{ $reservation->guest_email }}</span>
                        @else
                            <span class="text-gray-400">Invité</span>
                        @endif
                    </td>
                    <td class="px-3 md:px-4 lg:px-6 py-2 md:py-3 whitespace-nowrap text-xs md:text-sm text-gray-500">
                        {{ $reservation->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-3 md:px-4 lg:px-6 py-2 md:py-3 whitespace-nowrap">
                        @php
                            $statusMap = [
                                'pending' => ['text' => 'En attente', 'class' => 'bg-yellow-100 text-yellow-800'],
                                'pending_confirmation' => ['text' => 'En attente de confirmation', 'class' => 'bg-orange-100 text-orange-800'],
                                'confirmed' => ['text' => 'Confirmée', 'class' => 'bg-blue-100 text-blue-800'],
                                'active' => ['text' => 'Active', 'class' => 'bg-green-100 text-green-800'],
                                'completed' => ['text' => 'Terminée', 'class' => 'bg-gray-100 text-gray-800'],
                                'canceled' => ['text' => 'Annulée', 'class' => 'bg-red-100 text-red-800'],
                            ];
                            $status = $reservation->status?->value ?? 'unknown';
                            $statusInfo = $statusMap[$status] ?? ['text' => $status, 'class' => 'bg-gray-100 text-gray-800'];
                            if ($reservation->isPaidByBalance() || ($reservation->isPaid() && in_array($status, ['pending', 'pending_confirmation'], true))) {
                                $statusInfo = ['text' => $reservation->getDisplayStatusLabel(), 'class' => 'bg-green-100 text-green-800'];
                            }
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 md:px-2.5 md:py-0.5 rounded-full text-[10px] md:text-xs font-medium {{ $statusInfo['class'] }}">
                            {{ $statusInfo['text'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune réservation</h3>
        <p class="mt-1 text-sm text-gray-500">
            Aucune réservation n'a encore été effectuée pour cette borne de recharge.
        </p>
        <div class="mt-6">
            <a href="{{ route('reservations.create', $chargingPoint->id) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Créer une réservation
            </a>
        </div>
    </div>
    @endif
</x-card>
