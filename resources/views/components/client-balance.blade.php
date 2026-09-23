@props(['variant' => 'inline', 'showLabel' => true, 'formatted' => '', 'rechargeUrl' => null])

@php
    $rechargeUrl ??= route('credit-recharge.index');
@endphp

<a href="{{ $rechargeUrl }}"
   class="client-balance client-balance-{{ $variant }} flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-800 hover:shadow-md transition-all"
   title="{{ __('Solde - Cliquez pour recharger') }}"
   aria-label="{{ __('Solde') }}: {{ $formatted }}"
   data-balance-updatable>
    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div class="flex flex-col min-w-0">
        @if($showLabel)
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Solde') }}</span>
        @endif
        <span class="text-sm font-bold text-green-600 dark:text-green-400 truncate" data-balance-value>{{ $formatted }}</span>
    </div>
</a>
