@props(['chargingPoint', 'pricingPlan' => null])

@php
    use App\View\Helpers\ChargingPointShowHelper;
    $availableTypes = ChargingPointShowHelper::getAvailableReservationTypes($pricingPlan ?? $chargingPoint->pricingPlan ?? null);
    $currentPricingPlan = $pricingPlan ?? $chargingPoint->pricingPlan ?? null;
@endphp

<!-- Reservation Types Section -->
<x-card padding="sm" rounded="xl" class="mb-3 md:mb-4">
    <x-section-header title="Types de Réservation Disponibles" subtitle="Options selon le plan tarifaire" class="mb-2" />
    
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-2">
        @if(in_array('kwh', $availableTypes))
        <x-reservation-type-card-kwh 
            :chargingPoint="$chargingPoint"
            :pricingPlan="$currentPricingPlan"
            :availableTypes="$availableTypes"
        />
        @endif
        
        @if(in_array('minute', $availableTypes))
        <x-reservation-type-card-minute 
            :chargingPoint="$chargingPoint"
            :pricingPlan="$currentPricingPlan"
            :availableTypes="$availableTypes"
        />
        @endif
    </div>

    @include('charging-points.partials.pricing-plan-info', ['pricingPlan' => $currentPricingPlan, 'chargingPoint' => $chargingPoint, 'availableTypes' => $availableTypes])
</x-card>

