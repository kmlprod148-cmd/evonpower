@props(['chargingPoint'])

<x-card>
    <x-section-header title="Sessions de Recharges">
        <x-slot:actions>
            <x-button variant="primary" size="sm" href="{{ route('reservations.index') }}?charging_point_id={{ $chargingPoint->id }}">
                Voir toutes les sessions
            </x-button>
        </x-slot:actions>
    </x-section-header>
    
    <div class="text-center py-12">
        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-gray-600 mb-4">La liste des sessions de recharge sera affichée ici.</p>
    </div>
</x-card>

