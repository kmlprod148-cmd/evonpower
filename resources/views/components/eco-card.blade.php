{{-- Composant carte éco-énergétique --}}
@props([
    'variant' => 'default',
    'animated' => true,
    'hover' => true,
    'padding' => 'lg'
])

@php
    $baseClasses = 'bg-white rounded-lg border transition-all duration-300';
    
    $variantClasses = [
        'default' => 'shadow-eco border-eco-green-200 hover:border-eco-green-300',
        'energy' => 'shadow-energy border-energy-green-200 hover:border-energy-green-300 bg-gradient-energy',
        'nature' => 'shadow-nature border-nature-green-200 hover:border-nature-green-300 bg-gradient-nature',
        'floating' => 'shadow-eco-xl border-eco-green-100 hover:border-eco-green-200',
        'elevated' => 'shadow-eco-lg border-eco-green-200 hover:border-eco-green-300',
    ];
    
    $paddingClasses = [
        'sm' => 'p-4',
        'md' => 'p-5',
        'lg' => 'p-6',
        'xl' => 'p-8',
    ];
    
    $hoverClasses = $hover ? 'hover:shadow-eco-lg transform hover:-translate-y-1' : '';
    $animationClasses = $animated ? 'animate-fade-in' : '';
    
    $classes = implode(' ', [
        $baseClasses,
        $variantClasses[$variant] ?? $variantClasses['default'],
        $paddingClasses[$padding] ?? $paddingClasses['lg'],
        $hoverClasses,
        $animationClasses,
    ]);
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
