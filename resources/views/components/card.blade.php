@props([
    'padding' => 'default', // 'none', 'sm', 'default', 'md', 'lg'
    'shadow' => 'default', // 'none', 'sm', 'default', 'md', 'lg'
    'bordered' => true,
    'hoverable' => false,
    'rounded' => 'lg', // 'none', 'sm', 'md', 'lg', 'xl'
    'class' => '',
])

@php
    $paddingClasses = [
        'none' => '',
        'sm' => 'p-3',
        'default' => 'p-4 md:p-5',
        'md' => 'p-5 md:p-6',
        'lg' => 'p-6 md:p-8',
    ];
    
    $shadowClasses = [
        'none' => '',
        'sm' => 'shadow-sm dark:shadow-gray-900/20',
        'default' => 'shadow-sm dark:shadow-gray-900/30',
        'md' => 'shadow-md dark:shadow-gray-900/40',
        'lg' => 'shadow-lg dark:shadow-gray-900/50',
    ];
    
    $roundedClasses = [
        'none' => 'rounded-none',
        'sm' => 'rounded-sm',
        'md' => 'rounded-md',
        'lg' => 'rounded-xl',
        'xl' => 'rounded-2xl',
    ];
    
    $classes = 'bg-white dark:bg-gray-800 ' . 
               ($bordered ? 'border border-gray-200 dark:border-gray-700 ' : '') .
               $shadowClasses[$shadow] . ' ' .
               $roundedClasses[$rounded] . ' ' .
               $paddingClasses[$padding] . ' ' .
               ($hoverable ? 'transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 hover:border-green-200 dark:hover:border-green-800 cursor-pointer ' : 'transition-all duration-200 ') .
               $class;
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
