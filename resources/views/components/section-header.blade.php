@props([
    'title' => '',
    'subtitle' => '',
    'actions' => null,
    'badge' => null,
    'class' => '',
])

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 mb-4 md:mb-5 {{ $class }}">
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <h3 class="text-lg md:text-xl font-semibold text-gray-900">{{ $title }}</h3>
            @if($badge)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $badge['text'] ?? '' }}
                </span>
            @endif
        </div>
        @if($subtitle)
            <p class="text-xs md:text-sm text-gray-600 mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @if($actions)
        <div class="flex-shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>

