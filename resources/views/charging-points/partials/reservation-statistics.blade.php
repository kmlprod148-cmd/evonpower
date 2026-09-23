@props(['chargingPoint'])

@php
    $totalReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)->count();
    $kwhReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
        ->where('reservation_type', 'kwh')->count();
    $minuteReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
        ->where('reservation_type', 'minute')->count();
    $completedReservations = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
        ->where('status', 'completed')->count();
    $totalRevenue = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
        ->where('status', 'completed')
        ->sum('actual_cost');
    
    $kwhPercentage = $totalReservations > 0 ? round(($kwhReservations / $totalReservations) * 100, 1) : 0;
    $minutePercentage = $totalReservations > 0 ? round(($minuteReservations / $totalReservations) * 100, 1) : 0;
    $completionRate = $totalReservations > 0 ? round(($completedReservations / $totalReservations) * 100, 1) : 0;
@endphp

<!-- Reservation Statistics Section -->
<x-card class="mb-4 md:mb-6">
    <x-section-header title="Statistiques des Réservations" subtitle="Vue d'ensemble des performances" />
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-5">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-3 md:p-4 shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-[10px] md:text-xs font-semibold text-blue-700 uppercase tracking-wide mb-1">Total Réservations</p>
                    <p class="text-2xl md:text-3xl font-bold text-blue-900 mb-1 md:mb-2">{{ $totalReservations }}</p>
                </div>
                <div class="flex-shrink-0 bg-blue-500 rounded-lg p-2 md:p-2.5 shadow-sm">
                    <svg class="h-5 w-5 md:h-6 md:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-3 md:p-4 shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-[10px] md:text-xs font-semibold text-green-700 uppercase tracking-wide mb-1">Taux de Réussite</p>
                    <p class="text-2xl md:text-3xl font-bold text-green-900 mb-1 md:mb-2">{{ $completionRate }}%</p>
                </div>
                <div class="flex-shrink-0 bg-green-500 rounded-lg p-2 md:p-2.5 shadow-sm">
                    <svg class="h-5 w-5 md:h-6 md:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-3 md:p-4 shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-[10px] md:text-xs font-semibold text-purple-700 uppercase tracking-wide mb-1">Revenus Totaux</p>
                    <p class="text-2xl md:text-3xl font-bold text-purple-900 mb-1 md:mb-2">{{ number_format($totalRevenue, 2) }} <span class="text-sm md:text-lg text-purple-700">EUR</span></p>
                </div>
                <div class="flex-shrink-0 bg-purple-500 rounded-lg p-2 md:p-2.5 shadow-sm">
                    <svg class="h-5 w-5 md:h-6 md:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-3 md:p-4 shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-[10px] md:text-xs font-semibold text-orange-700 uppercase tracking-wide mb-1">Moyenne/Réservation</p>
                    <p class="text-2xl md:text-3xl font-bold text-orange-900 mb-1 md:mb-2">
                        {{ $completedReservations > 0 ? number_format($totalRevenue / $completedReservations, 2) : '0.00' }} <span class="text-sm md:text-lg text-orange-700">EUR</span>
                    </p>
                </div>
                <div class="flex-shrink-0 bg-orange-500 rounded-lg p-2 md:p-2.5 shadow-sm">
                    <svg class="h-5 w-5 md:h-6 md:w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    @if($totalReservations > 0)
        @include('charging-points.partials.reservation-distribution', [
            'totalReservations' => $totalReservations,
            'kwhReservations' => $kwhReservations,
            'minuteReservations' => $minuteReservations,
            'kwhPercentage' => $kwhPercentage,
            'minutePercentage' => $minutePercentage,
            'chargingPoint' => $chargingPoint
        ])
    @else
    <div class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune donnée</h3>
        <p class="mt-1 text-sm text-gray-500">
            Les statistiques apparaîtront une fois que des réservations auront été effectuées.
        </p>
    </div>
    @endif
</x-card>

