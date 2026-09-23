@props([
    'label' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'positive', // positive, negative
    'icon' => null,
    'color' => 'green' // green, blue, yellow, red, purple
])

@php
    $colorClasses = [
        'green' => 'bg-eco-green-500/20 text-eco-green-600 dark:text-eco-green-400',
        'blue' => 'bg-blue-500/20 text-blue-600 dark:text-blue-400',
        'yellow' => 'bg-yellow-500/20 text-yellow-600 dark:text-yellow-400',
        'red' => 'bg-red-500/20 text-red-600 dark:text-red-400',
        'purple' => 'bg-purple-500/20 text-purple-600 dark:text-purple-400',
    ];
    
    $iconColorClass = $colorClasses[$color] ?? $colorClasses['green'];
@endphp

<div {{ $attributes->merge(['class' => 'evon-stat-card']) }}>
    <div class="evon-stat-card-header">
        <div>
            <p class="evon-stat-card-label">{{ $label }}</p>
        </div>
        @if($icon)
            <div class="evon-stat-card-icon {{ $iconColorClass }}">
                {{ $icon }}
            </div>
        @endif
    </div>
    
    <div>
        <p class="evon-stat-card-value">{{ $value }}</p>
        
        @if($change !== null)
            <div class="evon-stat-card-change {{ $changeType === 'positive' ? 'evon-stat-card-change-positive' : 'evon-stat-card-change-negative' }}">
                @if($changeType === 'positive')
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                @else
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                @endif
                <span>{{ $change }}</span>
            </div>
        @endif
    </div>
    
    {{ $slot }}
</div>
