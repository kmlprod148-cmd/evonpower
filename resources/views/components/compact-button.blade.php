@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => '',
    'loading' => false,
    'disabled' => false,
    'class' => ''
])

@php
$baseClasses = 'compact-btn compact-transition compact-hover';
$sizeClasses = match($size) {
    'sm' => 'compact-btn-sm',
    'md' => '',
    'lg' => 'compact-btn-lg',
    default => ''
};
$variantClasses = match($variant) {
    'primary' => 'compact-bg-blue-600 compact-text-white hover:compact-bg-blue-700',
    'secondary' => 'compact-bg-gray-100 compact-text-gray-700 hover:compact-bg-gray-200',
    'success' => 'compact-bg-green-600 compact-text-white hover:compact-bg-green-700',
    'danger' => 'compact-bg-red-600 compact-text-white hover:compact-bg-red-700',
    'warning' => 'compact-bg-yellow-600 compact-text-white hover:compact-bg-yellow-700',
    'info' => 'compact-bg-blue-100 compact-text-blue-800 hover:compact-bg-blue-200',
    default => 'compact-bg-gray-100 compact-text-gray-700 hover:compact-bg-gray-200'
};
$disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';
$loadingClasses = $loading ? 'opacity-75 cursor-wait' : '';
@endphp

<button 
    {{ $attributes->merge([
        'class' => "{$baseClasses} {$sizeClasses} {$variantClasses} {$disabledClasses} {$loadingClasses} {$class}",
        'disabled' => $disabled || $loading
    ]) }}
>
    @if($loading)
    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    @elseif($icon)
    <span class="compact-mr-1">{!! $icon !!}</span>
    @endif
    
    {{ $slot }}
</button>
