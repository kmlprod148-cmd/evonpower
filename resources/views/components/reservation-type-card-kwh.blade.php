@props([
    'chargingPoint',
    'pricingPlan',
    'availableTypes' => [],
])

@php
    $isAvailable = in_array('kwh', $availableTypes);
    $hasPricing = $pricingPlan && $pricingPlan->price_per_kwh && $pricingPlan->price_per_kwh > 0;
    $maxEnergy = null;
    
    if ($chargingPoint->power_output && $pricingPlan && $pricingPlan->max_duration) {
        $maxEnergy = $chargingPoint->power_output * ($pricingPlan->max_duration / 60);
    }
    
    $energyOptions = [5, 10, 20, 30, 50, 75];
    if ($maxEnergy) {
        $energyOptions = array_filter($energyOptions, fn($energy) => $energy <= $maxEnergy);
        if (!in_array($maxEnergy, $energyOptions) && $maxEnergy > 0) {
            $energyOptions[] = round($maxEnergy, 1);
        }
        sort($energyOptions);
    }
@endphp

<div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-2.5 hover:shadow-md transition-all duration-200">
    <div class="flex items-start gap-2 mb-2">
        <div class="bg-blue-500 rounded-lg p-1.5 flex-shrink-0">
            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-gray-900">Réservation par Énergie</h4>
            <p class="text-[10px] text-gray-600">Réservez une quantité d'énergie spécifique</p>
        </div>
    </div>
    
    <div class="grid grid-cols-3 gap-1 mb-2">
        <div class="bg-white rounded-md p-0.5 text-center border border-blue-100">
            <div class="text-[10px] text-gray-500">Prix/kWh</div>
            <div class="text-xs font-semibold text-blue-600">
                @if($hasPricing)
                    {{ number_format($pricingPlan->price_per_kwh, 2) }}€
                @else
                    Variable
                @endif
            </div>
        </div>
        
        @if($pricingPlan && $pricingPlan->activation_fee)
            <div class="bg-white rounded-md p-0.5 text-center border border-blue-100">
                <div class="text-[10px] text-gray-500">Activation</div>
                <div class="text-xs font-semibold text-blue-600">
                    {{ number_format($pricingPlan->activation_fee, 2) }}€
                </div>
            </div>
        @endif
        
        @if($maxEnergy)
            <div class="bg-white rounded-md p-0.5 text-center border border-blue-100">
                <div class="text-[10px] text-gray-500">Énergie max</div>
                <div class="text-xs font-semibold text-blue-600">
                    {{ number_format($maxEnergy, 1) }}kWh
                </div>
            </div>
        @endif
        
        @if($hasPricing && $pricingPlan->price_per_kwh)
            <div class="bg-white rounded-md p-1.5 border border-blue-200 mb-2 col-span-3">
                <div class="text-[10px] font-medium text-gray-700 mb-0.5">Prix estimés par quantité :</div>
                <div class="grid grid-cols-4 sm:grid-cols-5 gap-0.5">
                    @foreach($energyOptions as $energy)
                        @php
                            $energyCost = $energy * $pricingPlan->price_per_kwh;
                            $totalCost = $energyCost + ($pricingPlan->activation_fee ?? 0);
                            $estimatedDuration = $chargingPoint->power_output ? round(($energy / $chargingPoint->power_output) * 60) : 0;
                        @endphp
                        <div class="bg-blue-50 rounded p-0.5 text-center">
                            <div class="text-[10px] font-semibold text-blue-800">{{ $energy }}kWh</div>
                            <div class="text-[10px] text-blue-600">{{ number_format($totalCost, 2) }}€</div>
                            @if($estimatedDuration > 0)
                                <div class="text-blue-500 text-[9px]">{{ $estimatedDuration }}min</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    
    <div class="flex gap-1">
        @if($isAvailable && $hasPricing)
            <a href="{{ route('public.charging-point.offer.reservation', $chargingPoint->id) }}?type=kwh" 
               class="flex-1 bg-blue-600 text-white text-center py-1.5 px-2 rounded-lg hover:bg-blue-700 transition-colors text-xs font-semibold shadow-sm">
                Réserver par kWh
            </a>
        @else
            <button disabled 
                    class="flex-1 bg-gray-400 text-white text-center py-1.5 px-2 rounded-lg cursor-not-allowed text-xs font-semibold shadow-sm">
                Réserver par kWh (Non disponible)
            </button>
        @endif
        
        <button onclick="showReservationExamples('kwh')" 
                class="px-1.5 py-1.5 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 transition-colors flex items-center justify-center">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </button>
    </div>
</div>
