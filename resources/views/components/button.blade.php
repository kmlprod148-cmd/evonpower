@props([
    'variant' => 'primary', // 'primary', 'secondary', 'outline', 'ghost', 'danger', 'success'
    'size' => 'default', // 'xs', 'sm', 'default', 'md', 'lg'
    'icon' => null,
    'iconPosition' => 'left', // 'left', 'right'
    'type' => 'button',
    'fullWidth' => false,
    'loading' => false,
    'disabled' => false,
    'class' => '',
])

@php
    $variantClasses = [
        'primary' => 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white shadow-md hover:shadow-lg shadow-green-500/25 focus:ring-green-500 active:scale-[0.98] transition-all duration-200',
        'secondary' => 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm focus:ring-gray-500 transition-all duration-200',
        'outline' => 'border-2 border-gray-200 dark:border-gray-600 hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/20 text-gray-700 dark:text-gray-200 focus:ring-green-500 transition-all duration-200',
        'ghost' => 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:ring-gray-500 transition-all duration-200',
        'danger' => 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white shadow-md hover:shadow-lg shadow-red-500/25 focus:ring-red-500 active:scale-[0.98] transition-all duration-200',
        'success' => 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white shadow-md hover:shadow-lg shadow-green-500/25 focus:ring-green-500 active:scale-[0.98] transition-all duration-200',
    ];
    
    $sizeClasses = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-xs md:text-sm',
        'default' => 'px-4 py-2 text-sm',
        'md' => 'px-4 py-2.5 text-base',
        'lg' => 'px-6 py-3 text-lg',
    ];
    
    $iconSizes = [
        'xs' => 'h-3 w-3',
        'sm' => 'h-3.5 w-3.5 md:h-4 md:w-4',
        'default' => 'h-4 w-4',
        'md' => 'h-5 w-5',
        'lg' => 'h-6 w-6',
    ];
    
    $isDisabled = $disabled || $loading;
    
    $classes = 'inline-flex items-center justify-center ' .
               'font-semibold rounded-lg ' .
               'border border-transparent ' .
               'focus:outline-none focus:ring-2 focus:ring-offset-2 ' .
               $variantClasses[$variant] . ' ' .
               $sizeClasses[$size] . ' ' .
               ($fullWidth ? 'w-full ' : '') .
               ($isDisabled ? 'opacity-60 cursor-not-allowed pointer-events-none ' : '') .
               $class;
@endphp

@if($attributes->has('href') && !$isDisabled)
    <a {{ $attributes->merge(['class' => $classes]) }}>
        @isset($icon)
            @if($iconPosition === 'left')
                <span class="{{ $iconSizes[$size] }} mr-1.5 md:mr-2">{!! $icon !!}</span>
            @endif
        @endisset
        <span>{{ $slot }}</span>
        @isset($icon)
            @if($iconPosition === 'right')
                <span class="{{ $iconSizes[$size] }} ml-1.5 md:ml-2">{!! $icon !!}</span>
            @endif
        @endisset
    </a>
@else
    <button 
        type="{{ $type }}" 
        {{ $attributes->merge(['class' => $classes]) }}
        {{ $isDisabled ? 'disabled' : '' }}
    >
        @if($loading)
            <svg class="animate-spin -ml-1 mr-2 {{ $iconSizes[$size] }}" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @elseif($icon && $iconPosition === 'left')
            <span class="{{ $iconSizes[$size] }} mr-1.5 md:mr-2">{!! $icon !!}</span>
        @endif
        <span>{{ $slot }}</span>
        @if($icon && $iconPosition === 'right' && !$loading)
            <span class="{{ $iconSizes[$size] }} ml-1.5 md:ml-2">{!! $icon !!}</span>
        @endif
    </button>
@endif
