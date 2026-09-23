@props(['pricingPlan', 'chargingPoint', 'availableTypes'])

@if($pricingPlan && ($pricingPlan->price_per_kwh > 0 || $pricingPlan->price_per_minute > 0))
    <!-- Comparaison des prix -->
    @if($pricingPlan->price_per_kwh > 0 && $pricingPlan->price_per_minute > 0)
    <div class="mt-2 p-2 bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg">
        <div class="mb-2 p-2 bg-white rounded-lg border border-purple-200">
            <h5 class="text-sm font-semibold text-gray-900 mb-2 flex items-center">
                <svg class="h-4 w-4 text-purple-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Comparaison des prix
            </h5>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                @php
                    $commonScenarios = [
                        ['name' => 'Recharge rapide', 'kwh' => 10, 'minutes' => 30],
                        ['name' => 'Recharge standard', 'kwh' => 20, 'minutes' => 60],
                        ['name' => 'Recharge complète', 'kwh' => 40, 'minutes' => 120],
                    ];
                @endphp
                
                @foreach($commonScenarios as $scenario)
                    @php
                        $kwhCost = $scenario['kwh'] * $pricingPlan->price_per_kwh;
                        $kwhTotal = $kwhCost + ($pricingPlan->activation_fee ?? 0);
                        $minuteCost = $scenario['minutes'] * $pricingPlan->price_per_minute;
                        $minuteTotal = $minuteCost + ($pricingPlan->activation_fee ?? 0);
                        $kwhBetter = $kwhTotal < $minuteTotal;
                    @endphp
                    <div class="bg-gray-50 rounded-lg p-2">
                        <div class="font-medium text-gray-900 mb-1 text-xs">{{ $scenario['name'] }}</div>
                        <div class="space-y-0.5">
                            <div class="flex justify-between text-[10px]">
                                <span class="text-blue-600">kWh ({{ $scenario['kwh'] }}):</span>
                                <span class="font-medium {{ $kwhBetter ? 'text-green-600' : 'text-gray-600' }}">
                                    {{ number_format($kwhTotal, 2) }}€
                                </span>
                            </div>
                            <div class="flex justify-between text-[10px]">
                                <span class="text-green-600">min ({{ $scenario['minutes'] }}):</span>
                                <span class="font-medium {{ !$kwhBetter ? 'text-green-600' : 'text-gray-600' }}">
                                    {{ number_format($minuteTotal, 2) }}€
                                </span>
                            </div>
                            @if($kwhTotal != $minuteTotal)
                            <div class="text-[9px] text-gray-500">
                                <span class="font-medium {{ $kwhBetter ? 'text-green-600' : 'text-blue-600' }}">
                                    {{ $kwhBetter ? 'kWh' : 'Minute' }} +
                                </span>
                                ({{ number_format(abs($kwhTotal - $minuteTotal), 2) }}€)
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
@elseif($pricingPlan)
    <div class="mt-6 p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="bg-yellow-500 rounded-lg p-2">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <h5 class="text-lg font-semibold text-yellow-900 mb-3">Plan tarifaire incomplet</h5>
                <p class="text-sm text-yellow-700 mb-4">
                    Le plan tarifaire "{{ $pricingPlan->name }}" n'a pas de prix configuré.
                </p>
            </div>
        </div>
    </div>
@else
    <div class="mt-6 p-6 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="bg-red-500 rounded-lg p-2">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <h5 class="text-lg font-semibold text-red-900 mb-3">Aucun plan tarifaire configuré</h5>
                <p class="text-sm text-red-700 mb-4">
                    Cette borne de recharge n'a pas de plan tarifaire assigné.
                </p>
                <a href="{{ route('charging-points.edit', $chargingPoint->id) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    Configurer le plan tarifaire
                </a>
            </div>
        </div>
    </div>
@endif

