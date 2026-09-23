@props(['label', 'value', 'color' => 'green'])

@php
    $colors = [
        'green' => [
            'bg' => 'bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-600 dark:text-green-400',
            'value' => 'text-green-700 dark:text-green-300',
        ],
        'blue' => [
            'bg' => 'bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-600 dark:text-blue-400',
            'value' => 'text-blue-700 dark:text-blue-300',
        ],
        'purple' => [
            'bg' => 'bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20',
            'border' => 'border-purple-200 dark:border-purple-800',
            'text' => 'text-purple-600 dark:text-purple-400',
            'value' => 'text-purple-700 dark:text-purple-300',
        ],
        'yellow' => [
            'bg' => 'bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20',
            'border' => 'border-yellow-200 dark:border-yellow-800',
            'text' => 'text-yellow-600 dark:text-yellow-400',
            'value' => 'text-yellow-700 dark:text-yellow-300',
        ],
    ];
    
    $style = $colors[$color] ?? $colors['green'];
@endphp

<div class="{{ $style['bg'] }} rounded-lg p-4 border {{ $style['border'] }}">
    <div class="text-xs {{ $style['text'] }} font-medium mb-1">{{ $label }}</div>
    <div class="text-2xl font-bold {{ $style['value'] }}">{{ $value }}</div>
</div>

