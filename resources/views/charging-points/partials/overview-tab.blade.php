@props(['chargingPoint', 'pricingPlan' => null])

<x-charging-point-header :chargingPoint="$chargingPoint" />

<!-- Statistics -->
<x-card class="mb-4 md:mb-6">
    <x-section-header title="Statistiques" subtitle="Performances de la borne">
        <x-slot:actions>
        <div class="relative w-full sm:w-auto">
            <select id="stats-period" class="block appearance-none bg-white border border-gray-300 hover:border-gray-400 px-3 py-1.5 md:px-4 md:py-2 pr-8 rounded-lg leading-tight focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-xs md:text-sm w-full sm:w-auto">
                <option value="day">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="year">Cette année</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                <svg class="fill-current h-3 w-3 md:h-4 md:w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
            </div>
        </x-slot:actions>
    </x-section-header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
        <x-charging-point-statistic-card 
            label="Consommation totale"
            :value="number_format($chargingPoint->energy_delivered ?? 0, 2) . ' kWh'"
            iconColor="green"
            id="total-consumption"
        />
        <x-charging-point-statistic-card 
            label="Sessions de recharges"
            :value="$chargingPoint->transactions_count ?? 0"
            iconColor="blue"
            id="session-count"
        />
        <x-charging-point-statistic-card 
            label="Charges réussies"
            :value="($chargingPoint->success_rate ?? '99') . '%'"
            iconColor="green"
            id="success-rate"
        />
    </div>
</x-card>

@include('charging-points.partials.energy-chart')
@include('charging-points.partials.station-information', ['chargingPoint' => $chargingPoint])
@include('charging-points.partials.reservation-types', ['chargingPoint' => $chargingPoint, 'pricingPlan' => $pricingPlan])
@include('charging-points.partials.recent-reservations', ['chargingPoint' => $chargingPoint])
@include('charging-points.partials.reservation-statistics', ['chargingPoint' => $chargingPoint])

