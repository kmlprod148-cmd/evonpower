@props(['chargingPoint'])

<!-- Station Information -->
<x-card class="mb-4 md:mb-6">
    <x-section-header title="Informations de la Station" subtitle="Détails techniques" />
    
    <div class="space-y-4 md:space-y-5">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 md:p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="mr-3 md:mr-4">
                        <svg class="h-8 w-8 md:h-10 md:w-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-base md:text-lg font-semibold text-gray-900">Station {{ $chargingPoint->name }}</h4>
                        <p class="text-xs md:text-sm text-gray-600 mt-0.5">{{ $chargingPoint->power_output ?? 'N/A' }} kW • {{ $chargingPoint->model ?? 'N/A' }}</p>
                    </div>
                </div>
                <div>
                    @php
                        $statusMap = [
                            'online' => ['text' => 'Online', 'class' => 'bg-green-100 text-green-800'],
                            'offline' => ['text' => 'Hors ligne', 'class' => 'bg-red-100 text-red-800'],
                            'maintenance' => ['text' => 'Maintenance', 'class' => 'bg-yellow-100 text-yellow-800'],
                            'charging' => ['text' => 'En charge', 'class' => 'bg-blue-100 text-blue-800'],
                            'available' => ['text' => 'Disponible', 'class' => 'bg-green-100 text-green-800'],
                        ];
                        $currentStatus = strtolower($chargingPoint->status ?? '');
                        $defaultStatus = ['text' => $chargingPoint->status ?? 'Inconnu', 'class' => 'bg-gray-100 text-gray-800'];
                        $statusInfo = $statusMap[$currentStatus] ?? $defaultStatus;
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs md:text-sm font-medium {{ $statusInfo['class'] }}">
                        <span class="h-1.5 w-1.5 md:h-2 md:w-2 mr-1.5 md:mr-2 rounded-full {{ str_contains($statusInfo['class'], 'green') ? 'bg-green-500' : (str_contains($statusInfo['class'], 'red') ? 'bg-red-500' : (str_contains($statusInfo['class'], 'yellow') ? 'bg-yellow-500' : (str_contains($statusInfo['class'], 'blue') ? 'bg-blue-500' : 'bg-gray-500'))) }}"></span>
                        {{ $statusInfo['text'] }}
                    </span>
                </div>
            </div>
            @if($chargingPoint->pricingPlan)
            <div class="mt-3 md:mt-4 p-3 md:p-4 bg-white rounded-lg border border-gray-200">
                <div class="flex items-center">
                    <svg class="h-4 w-4 md:h-5 md:w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                    <span class="text-xs md:text-sm font-medium text-gray-900">Plan tarifaire: {{ $chargingPoint->pricingPlan->name }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-card>

