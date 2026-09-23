@props(['type', 'message'])

@php
    $baseClasses = 'p-4 mb-4 text-sm rounded-lg';
    $typeClasses = [
        'success' => 'bg-green-100 dark:bg-green-200 text-green-800 dark:text-green-900',
        'error' => 'bg-red-100 dark:bg-red-200 text-red-800 dark:text-red-900',
        'info' => 'bg-blue-100 dark:bg-blue-200 text-blue-800 dark:text-blue-900',
    ];
    $classes = $baseClasses . ' ' . ($typeClasses[$type] ?? '');
@endphp

<div class="{{ $classes }}" role="alert">
    <span class="font-medium">{{ ucfirst($type) }}!</span> {{ $message }}
</div>