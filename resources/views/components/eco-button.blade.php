{{-- Composant bouton éco-énergétique --}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'animated' => true,
    'icon' => null,
    'loading' => false,
    'disabled' => false
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    $variantClasses = [
        'primary' => 'bg-eco-green-600 hover:bg-eco-green-700 text-white shadow-eco hover:shadow-eco-lg focus:ring-eco-green-500',
        'secondary' => 'bg-eco-green-100 hover:bg-eco-green-200 text-eco-green-800 border border-eco-green-300 hover:border-eco-green-400 focus:ring-eco-green-500',
        'energy' => 'bg-energy-green-600 hover:bg-energy-green-700 text-white shadow-energy hover:shadow-energy-lg focus:ring-energy-green-500',
        'nature' => 'bg-nature-green-600 hover:bg-nature-green-700 text-white shadow-nature hover:shadow-nature focus:ring-nature-green-500',
        'outline' => 'bg-transparent hover:bg-eco-green-50 text-eco-green-600 border border-eco-green-300 hover:border-eco-green-400 focus:ring-eco-green-500',
        'ghost' => 'bg-transparent hover:bg-eco-green-100 text-eco-green-600 focus:ring-eco-green-500',
    ];
    
    $sizeClasses = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg',
        'xl' => 'px-8 py-4 text-xl',
    ];
    
    $animationClasses = $animated ? 'transform hover:-translate-y-1 btn-eco-hover' : '';
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';
    $loadingClasses = $loading ? 'cursor-wait' : '';
    
    $classes = implode(' ', [
        $baseClasses,
        $variantClasses[$variant] ?? $variantClasses['primary'],
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $animationClasses,
        $disabledClasses,
        $loadingClasses,
    ]);
@endphp

<button {{ $attributes->merge(['class' => $classes, 'disabled' => $disabled || $loading]) }}>
    @if($loading)
        <div class="loading-energy-dots mr-2">
            <span></span>
            <span></span>
            <span></span>
        </div>
    @elseif($icon)
        <span class="mr-2">{{ $icon }}</span>
    @endif
    
    {{ $slot }}
</button>
