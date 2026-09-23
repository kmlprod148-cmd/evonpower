@props(['chargingPoint'])

<x-card padding="default" shadow="default" class="mb-4 md:mb-6">
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
        <div class="flex-1 min-w-0">
            <p class="text-xs md:text-sm text-gray-500 mb-1">Borne de recharge</p>
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 truncate">{{ $chargingPoint->name }}</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">{{ $chargingPoint->serial_number }}</p>
        </div>
        
        <div class="flex flex-col md:items-end gap-3">
            <div>
                <x-charging-point-status-badge :status="$chargingPoint->status" :chargingPointId="$chargingPoint->id" />
            </div>
            <div class="flex flex-wrap gap-2 md:gap-3 justify-end">
                <x-button variant="outline" size="sm" href="{{ route('charging-points.edit', $chargingPoint->id) }}" 
                    icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>'>
                    <span class="hidden sm:inline">Modifier</span>
                </x-button>
                
                <x-button variant="outline" size="sm" href="{{ route('public.charging-points.qr-code', $chargingPoint->id) }}" class="border-green-300 text-green-700 bg-green-50 hover:bg-green-100"
                    icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>'>
                    <span class="hidden sm:inline">QR Code</span>
                </x-button>
                
                <x-button variant="primary" size="sm" href="{{ route('public.charging-point.offer.reservation', $chargingPoint->id) }}"
                    icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>'>
                    Réserver
                </x-button>
                
                {{-- Bouton Parts - Masqué pour les opérateurs et partenaires --}}
                @if(auth()->check() && !auth()->user()->hasRole('operator') && !auth()->user()->hasRole('partner'))
                <x-button variant="outline" size="sm" type="button" onclick="showPartsInfoModal()" class="border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100"
                    icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'>
                    <span class="hidden lg:inline">Parts</span>
                </x-button>
                @endif
            </div>
        </div>
    </div>
    
    @if($chargingPoint->status == 'offline')
    <div class="mt-6">
        <x-alert type="warning" title="Le point de charge n'est pas connecté !">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <p class="flex-1">Veuillez connecter le point de charge pour commencer à l'utiliser.</p>
                <form action="{{ route('charging-points.update', $chargingPoint->id) }}" method="POST" class="sm:ml-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="online">
                    <x-button variant="primary" size="sm" type="submit">
                        Connecter
                    </x-button>
                </form>
            </div>
        </x-alert>
    </div>
    @endif
</x-card>

