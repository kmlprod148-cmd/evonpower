@props([
    'variant' => 'default', // 'default', 'success', 'error', 'warning', 'info', 'primary'
    'size' => 'default', // 'xs', 'sm', 'default', 'md'
    'dot' => false,
    'pulse' => false,
    'class' => '',
])

@php
    $variantClasses = [
        'default' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
        'success' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800/50',
        'error' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800/50',
        'warning' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800/50',
        'info' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800/50',
        'primary' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800/50',
    ];
    
    $sizeClasses = [
        'xs' => 'px-1.5 py-0.5 text-[10px]',
        'sm' => 'px-2 py-0.5 text-[11px]',
        'default' => 'px-2.5 py-1 text-xs',
        'md' => 'px-3 py-1 text-sm',
    ];
    
    $dotColors = [
        'default' => 'bg-gray-500',
        'success' => 'bg-green-500',
        'error' => 'bg-red-500',
        'warning' => 'bg-yellow-500',
        'info' => 'bg-blue-500',
        'primary' => 'bg-green-500',
    ];
    
    $pulseColors = [
        'default' => 'bg-gray-400',
        'success' => 'bg-green-400',
        'error' => 'bg-red-400',
        'warning' => 'bg-yellow-400',
        'info' => 'bg-blue-400',
        'primary' => 'bg-green-400',
    ];
    
    $classes = 'inline-flex items-center rounded-full font-medium border ' .
               $variantClasses[$variant] . ' ' .
               $sizeClasses[$size] . ' ' .
               $class;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot || $pulse)
        <span class="mr-1.5 md:mr-2">
            @if($pulse)
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $pulseColors[$variant] }}"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 {{ $dotColors[$variant] }}"></span>
                </span>
            @else
                <span class="h-1.5 w-1.5 md:h-2 md:w-2 rounded-full {{ $dotColors[$variant] }}"></span>
            @endif
        </span>
    @endif
    {{ $slot }}
</span>
