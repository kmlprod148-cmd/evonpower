@props([
    'title' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'neutral', // 'positive', 'negative', 'neutral'
    'icon' => null,
    'iconColor' => 'blue',
    'href' => null,
    'loading' => false,
    'trend' => null, // 'up', 'down', null
])

@php
// Classes de couleur pour les icônes
$iconColorClasses = [
    'blue' => 'bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900/30 dark:to-blue-800/20 text-blue-600 dark:text-blue-400',
    'green' => 'bg-gradient-to-br from-green-100 to-green-50 dark:from-green-900/30 dark:to-green-800/20 text-green-600 dark:text-green-400',
    'red' => 'bg-gradient-to-br from-red-100 to-red-50 dark:from-red-900/30 dark:to-red-800/20 text-red-600 dark:text-red-400',
    'yellow' => 'bg-gradient-to-br from-yellow-100 to-yellow-50 dark:from-yellow-900/30 dark:to-yellow-800/20 text-yellow-600 dark:text-yellow-400',
    'purple' => 'bg-gradient-to-br from-purple-100 to-purple-50 dark:from-purple-900/30 dark:to-purple-800/20 text-purple-600 dark:text-purple-400',
    'eco' => 'bg-gradient-to-br from-eco-green-100 to-eco-green-50 dark:from-eco-green-900/30 dark:to-eco-green-800/20 text-eco-green-600 dark:text-eco-green-400',
];

// Classes pour les changements
$changeClasses = [
    'positive' => 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20',
    'negative' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20',
    'neutral' => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50',
];

$iconColorClass = $iconColorClasses[$iconColor] ?? $iconColorClasses['blue'];
$changeClass = $changeClasses[$changeType] ?? $changeClasses['neutral'];

// Classes de base pour la card
$cardClasses = 'group relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 transition-all duration-300 hover:shadow-xl hover:border-eco-green-300 dark:hover:border-eco-green-700 active:scale-[0.98] overflow-hidden';

// Si href existe, rendre la card cliquable
$isClickable = !empty($href);
if ($isClickable) {
    $cardClasses .= ' cursor-pointer hover:-translate-y-1';
}
@endphp

@php
// Wrapper approprié selon si c'est cliquable ou non
$wrapper = $isClickable ? 'a' : 'div';
@endphp

<{{ $wrapper }} 
    @if($isClickable) href="{{ $href }}" @endif
    class="{{ $cardClasses }}"
    @if($isClickable)
        role="link"
        aria-label="{{ $title }}: {{ $value }}"
    @endif
>
    {{-- Effet de brillance au survol --}}
    <div class="absolute inset-0 bg-gradient-to-br from-transparent via-eco-green-50/0 to-eco-green-100/0 dark:via-eco-green-900/0 dark:to-eco-green-800/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
    
    {{-- Contenu de la card --}}
    <div class="relative z-10">
        {{-- Header avec titre et icône --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0 pr-3">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 line-clamp-2 leading-snug">
                    {{ $title }}
                </h3>
            </div>
            
            @if($icon)
                <div class="flex-shrink-0 w-11 h-11 {{ $iconColorClass }} rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
                    {!! $icon !!}
                </div>
            @endif
        </div>
        
        {{-- Valeur principale --}}
        @if($loading)
            <div class="animate-pulse">
                <div class="h-9 bg-gray-200 dark:bg-gray-700 rounded-lg w-2/3 mb-3"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            </div>
        @else
            <div class="mb-3">
                <p class="text-3xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight">
                    {{ $value }}
                </p>
            </div>
            
            {{-- Footer avec changement/trend --}}
            @if($change || $trend)
                <div class="flex items-center gap-2 flex-wrap">
                    @if($change)
                        <span class="{{ $changeClass }} px-2.5 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                            @if($changeType === 'positive')
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            @elseif($changeType === 'negative')
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                </svg>
                            @endif
                            <span>{{ $change }}</span>
                        </span>
                    @endif
                    
                    @if($trend)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $trend }}
                        </span>
                    @endif
                </div>
            @endif
        @endif
        
        {{-- Slot pour contenu additionnel --}}
        @if($slot->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                {{ $slot }}
            </div>
        @endif
    </div>
    
    {{-- Indicateur de lien si cliquable --}}
    @if($isClickable)
        <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <svg class="w-5 h-5 text-eco-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </div>
    @endif
</{{ $wrapper }}>

