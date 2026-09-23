@props(['chargingPoint'])

<x-card shadow="sm" hover="true" class="transition-shadow">
    <x-section-header title="Détails" subtitle="Informations techniques" />
    
    <div class="space-y-4 md:space-y-5">
        
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Marque & Modèle</p>
            <p class="text-sm md:text-base font-medium text-gray-900">{{ $chargingPoint->manufacturer }} - {{ $chargingPoint->model }}</p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Puissance maximale</p>
            <p class="text-sm md:text-base font-medium text-gray-900">{{ $chargingPoint->power_output }} kW</p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Numéro de série</p>
            <p class="text-sm md:text-base font-medium text-gray-900">{{ $chargingPoint->serial_number }}</p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Groupe</p>
            <p class="text-sm md:text-base font-medium">
                @if($chargingPoint->group)
                    <a href="{{ route('groups.show', $chargingPoint->group) }}" class="text-green-600 hover:text-green-800">{{ $chargingPoint->group->name }}</a>
                @else
                    <span class="text-gray-400">Non assigné</span>
                @endif
            </p>
        </div>

        @if($chargingPoint->partner)
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Partenaire</p>
            <p class="text-sm md:text-base font-medium text-gray-900">{{ $chargingPoint->partner->name }}</p>
        </div>
        @endif

        @if($chargingPoint->integrator)
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Intégrateur</p>
            <p class="text-sm md:text-base font-medium text-gray-900">{{ $chargingPoint->integrator->name }}</p>
        </div>
        @endif

        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Localisation</p>
            <p class="text-sm md:text-base font-medium text-gray-900">
                @if($chargingPoint->location && is_object($chargingPoint->location))
                    {{ $chargingPoint->location->address ?? 'N/A' }}, {{ $chargingPoint->location->city ?? 'N/A' }}
                @elseif($chargingPoint->location && is_string($chargingPoint->location))
                    {{ $chargingPoint->location }}
                @else
                    <span class="text-gray-400">Non définie</span>
                @endif
            </p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Date d'installation</p>
            <p class="text-sm md:text-base font-medium text-gray-900">{{ $chargingPoint->installation_date ? \Carbon\Carbon::parse($chargingPoint->installation_date)->format('d/m/Y') : 'Non définie' }}</p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Dernière connexion</p>
            <p class="text-sm md:text-base font-medium text-gray-900">{{ $chargingPoint->last_connection ? \Carbon\Carbon::parse($chargingPoint->last_connection)->format('d/m/Y H:i') : 'Jamais' }}</p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-3 md:p-4">
            <p class="text-xs md:text-sm font-semibold text-gray-700 mb-1">Version du firmware</p>
            <p class="text-sm md:text-base font-medium text-gray-900">{{ $chargingPoint->firmware_version ?? 'Inconnue' }}</p>
        </div>
    </div>
</x-card>

