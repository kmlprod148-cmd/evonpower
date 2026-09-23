@props([
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'iconColor' => 'blue',
    'href' => null,
    'swipeable' => false
])

@php
$classes = 'card transition-all duration-200 hover:shadow-lg';
if ($swipeable) {
    $classes .= ' evon-swipeable-card';
}

$iconColors = [
    'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    'green' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    'red' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    'yellow' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
    'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
];

$iconColorClass = $iconColors[$iconColor] ?? $iconColors['blue'];
@endphp

@if($href)
    <a href="{{ $href }}" class="{{ $classes }} block">
        @include('components.mobile-optimized-card-content')
    </a>
@else
    <div class="{{ $classes }}">
        @include('components.mobile-optimized-card-content')
    </div>
@endif

{{-- Card Content Template --}}
@php
$cardContent = <<<'BLADE'
<div class="flex items-start gap-4">
    @if($icon)
        <div class="{{ $iconColorClass }} w-12 h-12 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0">
            {!! $icon !!}
        </div>
    @endif
    
    <div class="flex-1 min-w-0">
        @if($title)
            <h3 class="text-base sm:text-sm font-semibold text-gray-900 dark:text-white mb-1 truncate">
                {{ $title }}
            </h3>
        @endif
        
        @if($subtitle)
            <p class="text-sm sm:text-xs text-gray-600 dark:text-gray-400 mb-2">
                {{ $subtitle }}
            </p>
        @endif
        
        {{ $slot }}
    </div>
    
    @if($href)
        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    @endif
</div>
BLADE;
@endphp

