{{-- Card Content --}}
<div class="flex items-start gap-4">
    @if($icon)
        <div class="{{ $iconColorClass }} w-12 h-12 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center flex-shrink-0">
            {!! $icon !!}
        </div>
    @endif
    
    <div class="flex-1 min-w-0">
        @if($title)
            <h3 class="text-base sm:text-sm font-semibold text-gray-900 dark:text-white mb-1 truncate">
                {{ $title }}
            </h3>
        @endif
        
        @if($subtitle)
            <p class="text-sm sm:text-xs text-gray-600 dark:text-gray-400 mb-2">
                {{ $subtitle }}
            </p>
        @endif
        
        {{ $slot }}
    </div>
    
    @if($href)
        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    @endif
</div>

