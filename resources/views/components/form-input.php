@props([
    'name',
    'type' => 'text',
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'icon' => null,
    'error' => null,
])

@php
    $hasError = $errors->has($name) || $error;
@endphp

<div>
    @if($label)
        <label 
            for="{{ $name }}" 
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
        >
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    @switch($icon)
                        @case('user')
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            @break
                        @case('email')
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            @break
                        @case('lock')
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            @break
                        @case('search')
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            @break
                    @endswitch
                </svg>
            </div>
        @endif

        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $name }}" 
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            class="
                w-full 
                px-4 py-2.5
                bg-white dark:bg-gray-800
                rounded-lg
                border
                {{ $hasError 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-200 dark:border-red-700 dark:focus:border-red-500 dark:focus:ring-red-900/30' 
                    : 'border-gray-200 dark:border-gray-600 focus:border-green-500 focus:ring-green-200 dark:focus:border-green-500 dark:focus:ring-green-900/30'
                }}
                shadow-inner
                text-gray-900 dark:text-white
                placeholder-gray-400 dark:placeholder-gray-500
                focus:outline-none 
                focus:ring-2 
                focus:ring-opacity-50
                {{ $icon ? 'pl-10' : '' }}
                {{ $disabled ? 'bg-gray-100 dark:bg-gray-700/50 cursor-not-allowed opacity-60' : '' }}
                {{ $readonly ? 'bg-gray-50 dark:bg-gray-700/30' : '' }}
                transition-all duration-200
            "
            {{ $attributes }}
        >
    </div>

    @if($help && !$hasError)
        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif

    @if($hasError)
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">
            {{ $error ?? $errors->first($name) }}
        </p>
    @endif
</div>
