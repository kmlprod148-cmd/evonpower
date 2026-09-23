<!-- SteVe Connect Button Component -->
<!-- Usage: @include('components.steve-connect-button', ['chargingPoint' => $chargingPoint]) -->

<button 
    onclick="openSteveConnectionModal({{ $chargingPoint->id }}, '{{ $chargingPoint->charge_box_id ?? '' }}')"
    class="px-4 py-2 bg-green-600 hover:bg-green-500 text-black rounded-lg transition-colors font-bold text-sm inline-flex items-center space-x-2"
    title="Connecter au serveur SteVe OCPP"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
    </svg>
    <span>Connecter SteVe</span>
</button>

