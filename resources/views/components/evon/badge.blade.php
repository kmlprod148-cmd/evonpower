@props([
    'variant' => 'default',
    'size' => 'md',
    'rounded' => false
])

@php
    $baseClasses = 'inline-flex items-center gap-1 font-medium';
    
    $variantClasses = [
        'default' => 'bg-gray-100 text-gray-800',
        'primary' => 'bg-[rgba(77,208,124,0.1)] text-[#4dd07b]',
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-yellow-100 text-yellow-800',
        'danger' => 'bg-red-100 text-red-800',
        'info' => 'bg-blue-100 text-blue-800',
    ][$variant];
    
    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
        'lg' => 'px-3 py-1.5 text-base',
    ][$size];
    
    $roundedClass = $rounded ? 'rounded-full' : 'rounded';
    
    $classes = $baseClasses . ' ' . $variantClasses . ' ' . $sizeClasses . ' ' . $roundedClass;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }} style="font-family: 'Poppins', sans-serif;">
    {{ $slot }}
</span>