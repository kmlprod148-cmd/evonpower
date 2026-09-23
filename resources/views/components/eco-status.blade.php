{{-- Composant indicateur de statut éco-énergétique --}}
@props([
    'status' => 'online',
    'animated' => true,
    'size' => 'md'
])

@php
    $baseClasses = 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium';
    
    $statusClasses = [
        'online' => 'bg-eco-green-100 text-eco-green-800 border border-eco-green-200',
        'charging' => 'bg-energy-green-100 text-energy-green-800 border border-energy-green-200',
        'offline' => 'bg-eco-gray-100 text-eco-gray-600 border border-eco-gray-200',
        'error' => 'bg-red-100 text-red-800 border border-red-200',
        'warning' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
        'maintenance' => 'bg-blue-100 text-blue-800 border border-blue-200',
    ];
    
    $sizeClasses = [
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-3 py-1 text-sm',
        'lg' => 'px-4 py-2 text-base',
    ];
    
    $animationClasses = [
        'online' => $animated ? 'animate-glow-green' : '',
        'charging' => $animated ? 'animate-pulse-energy' : '',
        'offline' => '',
        'error' => '',
        'warning' => '',
        'maintenance' => '',
    ];
    
    $classes = implode(' ', [
        $baseClasses,
        $statusClasses[$status] ?? $statusClasses['online'],
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $animationClasses[$status] ?? '',
    ]);
    
    $icons = [
        'online' => '🟢',
        'charging' => '⚡',
        'offline' => '🔴',
        'error' => '❌',
        'warning' => '⚠️',
        'maintenance' => '🔧',
    ];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    <span class="mr-1">{{ $icons[$status] ?? '🟢' }}</span>
    {{ $slot }}
</span>
