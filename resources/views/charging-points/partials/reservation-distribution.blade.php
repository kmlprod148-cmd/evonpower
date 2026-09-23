@props(['totalReservations', 'kwhReservations', 'minuteReservations', 'kwhPercentage', 'minutePercentage', 'chargingPoint'])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5">
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-lg p-4 md:p-5 shadow-sm">
        <h4 class="text-sm md:text-base font-bold text-gray-900 mb-3 md:mb-4 flex items-center">
            <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Répartition par Type
        </h4>
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <div class="bg-blue-500 rounded-lg p-1.5 mr-2">
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-800">Par Énergie (kWh)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-bold text-gray-900">{{ $kwhReservations }}</span>
                        <span class="text-xs font-medium text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">({{ $kwhPercentage }}%)</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $kwhPercentage }}%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <div class="bg-green-500 rounded-lg p-1.5 mr-2">
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-800">Par Durée (Minutes)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-bold text-gray-900">{{ $minuteReservations }}</span>
                        <span class="text-xs font-medium text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">({{ $minutePercentage }}%)</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $minutePercentage }}%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-lg p-4 md:p-5 shadow-sm">
        <h4 class="text-sm md:text-base font-bold text-gray-900 mb-3 md:mb-4 flex items-center">
            <svg class="h-4 w-4 md:h-5 md:w-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Statut des Réservations
        </h4>
        @php
            $statusCounts = \App\Models\Reservation::where('charging_point_id', $chargingPoint->id)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            $statusConfig = [
                'pending' => ['label' => 'En attente', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'border' => 'border-yellow-300'],
                'pending_confirmation' => ['label' => 'En attente de confirmation', 'bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'border' => 'border-amber-300'],
                'confirmed' => ['label' => 'Confirmée', 'bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-300'],
                'active' => ['label' => 'Active', 'bg' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-300'],
                'completed' => ['label' => 'Terminée', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'border' => 'border-emerald-300'],
                'canceled' => ['label' => 'Annulée', 'bg' => 'bg-red-100', 'text' => 'text-red-800', 'border' => 'border-red-300'],
            ];
        @endphp
        <div class="space-y-3">
            @foreach($statusCounts as $status => $count)
            @php
                $config = $statusConfig[$status] ?? ['label' => ucfirst($status), 'bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'border' => 'border-gray-300'];
            @endphp
            <div class="flex items-center justify-between p-3 {{ $config['bg'] }} {{ $config['border'] }} border rounded-lg hover:shadow-sm transition-shadow">
                <div class="flex items-center">
                    <div class="w-2 h-2 rounded-full bg-current {{ $config['text'] }} mr-3"></div>
                    <span class="text-sm font-medium {{ $config['text'] }}">{{ $config['label'] }}</span>
                </div>
                <span class="text-sm font-bold {{ $config['text'] }} bg-white px-3 py-1 rounded-full shadow-sm">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

