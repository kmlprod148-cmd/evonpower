@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'icon' => null,
    'iconPosition' => 'left', // 'left' or 'right'
    'helpText' => '',
    'errorMessage' => '',
    'showError' => false,
    'autocomplete' => 'off',
    'pattern' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'maxlength' => null,
    'inputmode' => null, // 'none', 'text', 'decimal', 'numeric', 'tel', 'search', 'email', 'url'
])

@php
// Générer un ID unique si name est fourni
$inputId = $attributes->get('id') ?? 'input-' . $name . '-' . uniqid();

// Classes de base pour l'input (mobile-first)
$inputClasses = 'block w-full px-4 py-3.5 text-base bg-white dark:bg-gray-800 border rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed';

// Ajuster le padding si icône présente
if ($icon) {
    $inputClasses .= $iconPosition === 'left' ? ' pl-12' : ' pr-12';
}

// États de bordure et focus
if ($showError && $errorMessage) {
    $inputClasses .= ' border-red-300 dark:border-red-700 focus:border-red-500 focus:ring-red-500/20 text-red-900 dark:text-red-100 placeholder-red-400 dark:placeholder-red-500';
} else {
    $inputClasses .= ' border-gray-300 dark:border-gray-600 focus:border-eco-green-500 focus:ring-eco-green-500/20 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500';
}

// Input mode optimal pour mobile
$inputModeAttr = $inputmode;
if (!$inputModeAttr) {
    // Auto-détecter le meilleur inputmode selon le type
    $inputModeAttr = match($type) {
        'email' => 'email',
        'tel', 'phone' => 'tel',
        'number' => 'numeric',
        'url' => 'url',
        'search' => 'search',
        default => 'text'
    };
}
@endphp

<div class="space-y-2">
    {{-- Label --}}
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-0.5" aria-label="obligatoire">*</span>
            @endif
        </label>
    @endif
    
    {{-- Container de l'input avec icône optionnelle --}}
    <div class="relative">
        {{-- Icône (si présente) --}}
        @if($icon)
            <div class="absolute inset-y-0 {{ $iconPosition === 'left' ? 'left-0 pl-4' : 'right-0 pr-4' }} flex items-center pointer-events-none">
                <div class="w-5 h-5 {{ $showError && $errorMessage ? 'text-red-500' : 'text-gray-400 dark:text-gray-500' }}">
                    {!! $icon !!}
                </div>
            </div>
        @endif
        
        {{-- Input field --}}
        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($pattern) pattern="{{ $pattern }}" @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($max !== null) max="{{ $max }}" @endif
            @if($step !== null) step="{{ $step }}" @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($inputModeAttr) inputmode="{{ $inputModeAttr }}" @endif
            class="{{ $inputClasses }}"
            aria-describedby="{{ $helpText ? $inputId . '-help' : '' }} {{ $showError && $errorMessage ? $inputId . '-error' : '' }}"
            @if($showError && $errorMessage) aria-invalid="true" @endif
            {{ $attributes->except(['id', 'class']) }}
        >
        
        {{-- Indicateur de validation (checkmark ou X) --}}
        @if(!$showError && old($name) && !$errorMessage)
            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </div>
        @endif
    </div>
    
    {{-- Texte d'aide --}}
    @if($helpText)
        <p id="{{ $inputId }}-help" class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
            {{ $helpText }}
        </p>
    @endif
    
    {{-- Message d'erreur --}}
    @if($showError && $errorMessage)
        <div id="{{ $inputId }}-error" class="flex items-start gap-2 text-sm text-red-600 dark:text-red-400" role="alert">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $errorMessage }}</span>
        </div>
    @elseif($errors->has($name))
        <div id="{{ $inputId }}-error" class="flex items-start gap-2 text-sm text-red-600 dark:text-red-400" role="alert">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $errors->first($name) }}</span>
        </div>
    @endif
</div>

