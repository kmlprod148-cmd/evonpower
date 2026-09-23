@php
$messageTypes = [
    'success' => [
        'bg' => 'bg-green-50 dark:bg-green-900/20',
        'border' => 'border-green-500',
        'icon' => 'text-green-400',
        'text' => 'text-green-700 dark:text-green-300',
        'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    ],
    'error' => [
        'bg' => 'bg-red-50 dark:bg-red-900/20',
        'border' => 'border-red-500',
        'icon' => 'text-red-400',
        'text' => 'text-red-700 dark:text-red-300',
        'path' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    ],
    'warning' => [
        'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
        'border' => 'border-yellow-500',
        'icon' => 'text-yellow-400',
        'text' => 'text-yellow-700 dark:text-yellow-300',
        'path' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
    ]
];
@endphp

@foreach($messageTypes as $type => $styles)
    @if(session($type))
    <div 
        x-data="{ show: true }" 
        x-show="show" 
        x-init="setTimeout(() => show = false, 5000)" 
        class="mb-6 {{ $styles['bg'] }} border-l-4 {{ $styles['border'] }} p-4"
    >
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 {{ $styles['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $styles['path'] }}" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm {{ $styles['text'] }}">{{ session($type) }}</p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button 
                        @click="show = false" 
                        class="inline-flex rounded-md p-1.5 {{ $styles['text'] }} hover:bg-{{ $type }}-100 dark:hover:bg-{{ $type }}-800/30 focus:outline-none"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach