@props(['type' => 'info', 'message'])

@php
    $styles = [
        'success' => [
            'bg' => 'bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20',
            'border' => 'border-green-500',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => 'text-green-600 dark:text-green-400',
            'svg' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
        ],
        'error' => [
            'bg' => 'bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20',
            'border' => 'border-red-500',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => 'text-red-600 dark:text-red-400',
            'svg' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
        ],
        'info' => [
            'bg' => 'bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20',
            'border' => 'border-blue-500',
            'text' => 'text-blue-800 dark:text-blue-200',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'svg' => '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>'
        ]
    ];
    
    $style = $styles[$type] ?? $styles['info'];
@endphp

<div class="mb-6 {{ $style['bg'] }} border-l-4 {{ $style['border'] }} {{ $style['text'] }} p-4 rounded-lg shadow-md flex items-start space-x-3" role="alert">
    <svg class="w-5 h-5 {{ $style['icon'] }} mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        {!! $style['svg'] !!}
    </svg>
    <p class="font-medium">{{ $message }}</p>
</div>

