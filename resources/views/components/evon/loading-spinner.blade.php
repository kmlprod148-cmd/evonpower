@props([
    'size' => 'md', // sm, md, lg
    'message' => ''
])

@php
    $sizeClasses = [
        'sm' => 'w-4 h-4 border-2',
        'md' => 'w-6 h-6 border-2',
        'lg' => 'w-8 h-8 border-4',
    ];
    
    $spinnerClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex flex-col items-center justify-center']) }} role="status" aria-live="polite">
    <div class="{{ $spinnerClass }} border-gray-200 dark:border-gray-700 border-t-eco-green-500 rounded-full animate-spin" aria-hidden="true"></div>
    
    @if($message)
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $message }}</p>
    @endif
    
    <span class="sr-only">Chargement en cours...</span>
    
    {{ $slot }}
</div>

