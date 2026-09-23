@props([
    'for' => null,
    'required' => false,
    'class' => '',
    'tooltip' => null,
    'hint' => null,
    'icon' => null,
    'error' => null
])

<div class="form-label-wrapper">
    <div class="flex items-center justify-between mb-1">
        <label 
            @if($for)
                for="{{ $for }}"
            @endif
            {{ $attributes->merge([
                'class' => 'block text-sm font-medium text-gray-700 ' . 
                           ($required ? 'font-semibold' : '') . 
                           ' ' . $class
            ]) }}
        >
            <div class="flex items-center space-x-2">
                {{-- Icon (optional) --}}
                @if($icon)
                    <span class="text-gray-500">
                        @switch($icon)
                            @case('user')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            @case('email')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            @case('lock')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            @case('calendar')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            @case('phone')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                        @endswitch
                    </span>
                @endif

                {{-- Label Text --}}
                <span>{{ $slot }}</span>

                {{-- Required Indicator --}}
                @if($required)
                    <span class="text-red-500">*</span>
                @endif

                {{-- Tooltip (optional) --}}
                @if($tooltip)
                    <div 
                        x-data="{ tooltipOpen: false }" 
                        class="relative inline-block"
                    >
                        <button 
                            type="button" 
                            @mouseenter="tooltipOpen = true" 
                            @mouseleave="tooltipOpen = false"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </button>
                        <div 
                            x-show="tooltipOpen"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute z-50 p-2 -mt-2 text-sm text-gray-600 bg-white rounded-lg shadow-lg border border-gray-200 
                                   transform -translate-x-1/2 left-1/2 top-full min-w-max"
                        >
                            {{ $tooltip }}
                        </div>
                    </div>
                @endif
            </div>
        </label>
    </div>

    {{-- Optional Hint Text --}}
    @if($hint)
        <p class="text-xs text-gray-500 mt-1">{{ $hint }}</p>
    @endif

    {{-- Error Message --}}
    @if($error || $errors->has($for))
        <p class="text-sm text-red-600 mt-1">
            {{ $error ?? $errors->first($for) }}
        </p>
    @endif
</div>

{{-- Alpine.js for interactivity --}}
@push('scripts')
<script src="{{ asset('js/vendor/alpine.min.js') }}" defer></script>
@endpush