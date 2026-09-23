@props(['status'])

@php
    $statusConfig = [
        'completed' => [
            'bg' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'border' => 'border-green-200 dark:border-green-800',
            'icon' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
        ],
        'pending' => [
            'bg' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'border' => 'border-yellow-200 dark:border-yellow-800',
            'icon' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>',
        ],
        'processing' => [
            'bg' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'border' => 'border-blue-200 dark:border-blue-800',
            'icon' => null,
        ],
        'failed' => [
            'bg' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'border' => 'border-red-200 dark:border-red-800',
            'icon' => null,
        ],
    ];
    
    $config = $statusConfig[$status] ?? [
        'bg' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        'border' => 'border-gray-200 dark:border-gray-700',
        'icon' => null,
    ];
@endphp

<span class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full border {{ $config['bg'] }} {{ $config['border'] }}">
    @if($config['icon'])
    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
        {!! $config['icon'] !!}
    </svg>
    @endif
    {{ ucfirst($status) }}
</span>

