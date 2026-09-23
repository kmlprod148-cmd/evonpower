@props(['class' => '', 'dropdownPosition' => 'bottom-right'])

@php
    $currencies = $availableCurrencies ?? [];
    $current = $currentCurrency ?? 'MAD';
    $currentConfig = $currencies[$current] ?? null;
@endphp

<div 
    x-data="{ 
        open: false,
        currentCurrency: '{{ $current }}',
        changeCurrency(code) {
            fetch('{{ route('currency.switch') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ currency: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.currentCurrency = data.currency;
                    window.location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
            
            this.open = false;
        }
    }"
    class="relative {{ $class }}"
    @click.away="open = false"
>
    {{-- Trigger Button --}}
    <button 
        @click="open = !open"
        type="button"
        class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        :class="{ 'ring-2 ring-green-500': open }"
    >
        @if($currentConfig)
            <span class="text-lg">{{ $currentConfig['flag'] ?? '' }}</span>
            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $current }}</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $currentConfig['symbol'] ?? '' }}</span>
        @else
            <span class="font-medium text-gray-700 dark:text-gray-200">{{ $current }}</span>
        @endif
        <svg class="w-4 h-4 text-gray-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @class([
            'absolute z-50 mt-2 py-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700',
            'right-0' => str_contains($dropdownPosition, 'right'),
            'left-0' => str_contains($dropdownPosition, 'left'),
            'bottom-full mb-2' => str_contains($dropdownPosition, 'top'),
        ])
        style="display: none;"
    >
        @foreach($currencies as $code => $currency)
            <button
                type="button"
                @click="changeCurrency('{{ $code }}')"
                class="w-full flex items-center gap-3 px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                :class="{ 'bg-green-50 dark:bg-green-900/20': currentCurrency === '{{ $code }}' }"
            >
                <span class="text-lg">{{ $currency['flag'] ?? '' }}</span>
                <div class="flex-1">
                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $currency['name'] ?? $code }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $code }}</div>
                </div>
                <span class="text-gray-600 dark:text-gray-400 font-semibold">{{ $currency['symbol'] ?? '' }}</span>
                <template x-if="currentCurrency === '{{ $code }}'">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </template>
            </button>
        @endforeach
    </div>
</div>
