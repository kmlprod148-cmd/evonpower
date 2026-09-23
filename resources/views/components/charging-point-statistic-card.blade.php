@props([
    'label',
    'value',
    'icon' => null,
    'iconColor' => 'green',
    'id' => null,
])

@php
    $iconColors = [
        'green' => ['text' => 'text-green-500', 'bg' => 'bg-green-50'],
        'blue' => ['text' => 'text-blue-500', 'bg' => 'bg-blue-50'],
        'red' => ['text' => 'text-red-500', 'bg' => 'bg-red-50'],
        'yellow' => ['text' => 'text-yellow-500', 'bg' => 'bg-yellow-50'],
        'purple' => ['text' => 'text-purple-500', 'bg' => 'bg-purple-50'],
    ];
    $colorClasses = $iconColors[$iconColor] ?? $iconColors['green'];
    $iconColorClass = $colorClasses['text'];
    $iconBgClass = $colorClasses['bg'];
@endphp

<div class="bg-white shadow-sm hover:shadow-md rounded-lg p-4 md:p-5 transition-shadow duration-200 border border-gray-100">
    <div class="flex items-center gap-3 md:gap-4">
        @if($icon)
            <div class="flex-shrink-0">
                <div class="p-2 md:p-2.5 rounded-lg {{ $iconBgClass }}">
                    <x-dynamic-component :component="'icons.' . $icon" class="h-5 w-5 md:h-6 md:w-6 {{ $iconColorClass }}" />
                </div>
            </div>
        @else
            <div class="flex-shrink-0">
                <div class="p-2 md:p-2.5 rounded-lg {{ $iconBgClass }}">
                    <svg class="h-5 w-5 md:h-6 md:w-6 {{ $iconColorClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <p class="text-xs md:text-sm font-medium text-gray-500 mb-0.5 md:mb-1">{{ $label }}</p>
            <p class="text-xl md:text-2xl font-bold text-gray-900 truncate" @if($id) id="{{ $id }}" @endif>
                {{ $value }}
            </p>
        </div>
    </div>
</div>
