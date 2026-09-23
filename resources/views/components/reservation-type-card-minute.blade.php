@props([
    'chargingPoint',
    'pricingPlan',
    'availableTypes' => [],
])

@php
    $isAvailable = in_array('minute', $availableTypes);
    $hasPricing = $pricingPlan && $pricingPlan->price_per_minute && $pricingPlan->price_per_minute > 0;
    $maxDuration = $pricingPlan->max_duration ?? null;
    
    $timeOptions = [15, 30, 45, 60, 90, 120, 180];
    if ($maxDuration) {
        $timeOptions = array_filter($timeOptions, fn($time) => $time <= $maxDuration);
        if (!in_array($maxDuration, $timeOptions) && $maxDuration > 0) {
            $timeOptions[] = $maxDuration;
        }
        sort($timeOptions);
    }
@endphp

<div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-2.5 hover:shadow-md transition-all duration-200">
    <div class="flex items-start gap-2 mb-2">
        <div class="bg-green-500 rounded-lg p-1.5 flex-shrink-0">
            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-gray-900">Réservation par Durée</h4>
            <p class="text-[10px] text-gray-600">Réservez une durée de charge spécifique</p>
        </div>
    </div>
    
    <div class="grid grid-cols-3 gap-1 mb-2">
        <div class="bg-white rounded-md p-0.5 text-center border border-green-100">
            <div class="text-[10px] text-gray-500">Prix/min</div>
            <div class="text-xs font-semibold text-green-600">
                @if($hasPricing)
                    {{ number_format($pricingPlan->price_per_minute, 2) }}€
                @else
                    Variable
                @endif
            </div>
        </div>
        
        @if($pricingPlan && $pricingPlan->activation_fee)
            <div class="bg-white rounded-md p-0.5 text-center border border-green-100">
                <div class="text-[10px] text-gray-500">Activation</div>
                <div class="text-xs font-semibold text-green-600">
                    {{ number_format($pricingPlan->activation_fee, 2) }}€
                </div>
            </div>
        @endif
        
        @if($maxDuration)
            <div class="bg-white rounded-md p-0.5 text-center border border-green-100">
                <div class="text-[10px] text-gray-500">Durée max</div>
                <div class="text-xs font-semibold text-green-600">
                    {{ $maxDuration }}min
                </div>
            </div>
        @endif
        
        @if($hasPricing && $pricingPlan->price_per_minute)
            <div class="bg-white rounded-md p-1.5 border border-green-200 mb-2 col-span-3">
                <div class="text-[10px] font-medium text-gray-700 mb-0.5">Prix estimés par durée :</div>
                <div class="grid grid-cols-4 sm:grid-cols-5 gap-0.5">
                    @foreach($timeOptions as $minutes)
                        @php
                            $timeCost = $minutes * $pricingPlan->price_per_minute;
                            $totalCost = $timeCost + ($pricingPlan->activation_fee ?? 0);
                            $estimatedEnergy = $chargingPoint->power_output ? round(($minutes / 60) * $chargingPoint->power_output, 1) : 0;
                        @endphp
                        <div class="bg-green-50 rounded p-0.5 text-center">
                            <div class="text-[10px] font-semibold text-green-800">{{ $minutes }}min</div>
                            <div class="text-[10px] text-green-600">{{ number_format($totalCost, 2) }}€</div>
                            @if($estimatedEnergy > 0)
                                <div class="text-green-500 text-[9px]">~{{ $estimatedEnergy }}kWh</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    
    <div class="flex gap-1">
        @if($isAvailable && $hasPricing)
            <a href="{{ route('public.charging-point.offer.reservation', $chargingPoint->id) }}?type=minute" 
               class="flex-1 bg-green-600 text-white text-center py-1.5 px-2 rounded-lg hover:bg-green-700 transition-colors text-xs font-semibold shadow-sm">
                Réserver par Minute
            </a>
        @else
            <button disabled 
                    class="flex-1 bg-gray-400 text-white text-center py-1.5 px-2 rounded-lg cursor-not-allowed text-xs font-semibold shadow-sm">
                Réserver par Minute (Non disponible)
            </button>
        @endif
        
        <button onclick="showReservationExamples('minute')" 
                class="px-1.5 py-1.5 border border-green-300 text-green-700 rounded-lg hover:bg-green-50 transition-colors flex items-center justify-center">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </button>
    </div>
</div>
