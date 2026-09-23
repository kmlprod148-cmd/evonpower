@props([
    'activeTab' => 'overview'
])

@php
    $tabs = [
        'overview' => ['label' => 'Informations', 'icon' => 'information-circle'],
        'sessions' => ['label' => 'Sessions de recharges', 'icon' => 'calendar'],
        'integrations' => ['label' => 'Intégration', 'icon' => 'link'],
        'payments' => ['label' => 'Paiements', 'icon' => 'currency-dollar'],
    ];
@endphp

<div class="flex flex-wrap lg:flex-nowrap space-x-4 lg:space-x-8 -mb-px">
    @foreach($tabs as $tabKey => $tab)
        <button 
            data-tab-target="{{ $tabKey }}" 
            class="tab-button py-4 px-1 border-b-2 transition-colors {{ $tabKey === $activeTab ? 'border-green-500 text-green-600 font-medium' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
        >
            <span>{{ $tab['label'] }}</span>
        </button>
    @endforeach
</div>

