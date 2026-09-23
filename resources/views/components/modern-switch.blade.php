{{-- resources/views/components/modern-switch.blade.php --}}
@props([
    'id' => null,
    'name' => null,
    'value' => false,
    'label' => '',
    'description' => '',
    'size' => 'md', // sm, md, lg
    'color' => 'primary', // primary, success, warning, danger
    'disabled' => false,
    'required' => false,
    'onChange' => null
])

@php
    $switchId = $id ?? 'switch-' . uniqid();
    $switchName = $name ?? $switchId;
    $isChecked = old($switchName, $value);
    
    // Classes de taille
    $sizeClasses = [
        'sm' => 'w-8 h-4',
        'md' => 'w-11 h-6', 
        'lg' => 'w-14 h-7'
    ];
    
    $thumbSizeClasses = [
        'sm' => 'w-3 h-3',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6'
    ];
    
    $translateClasses = [
        'sm' => 'translate-x-4',
        'md' => 'translate-x-5',
        'lg' => 'translate-x-7'
    ];
    
    // Classes de couleur
    $colorClasses = [
        'primary' => 'bg-indigo-600 focus:ring-indigo-500',
        'success' => 'bg-green-600 focus:ring-green-500',
        'warning' => 'bg-yellow-600 focus:ring-yellow-500',
        'danger' => 'bg-red-600 focus:ring-red-500'
    ];
@endphp

<div class="modern-switch-container {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
    @if($label)
        <label for="{{ $switchId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif
    
    <div class="flex items-center space-x-3">
        <!-- Switch Container -->
        <div class="relative inline-flex items-center">
            <input 
                type="checkbox" 
                id="{{ $switchId }}"
                name="{{ $switchName }}"
                value="1"
                {{ $isChecked ? 'checked' : '' }}
                {{ $disabled ? 'disabled' : '' }}
                {{ $required ? 'required' : '' }}
                class="sr-only peer"
                @if($onChange) onchange="{{ $onChange }}" @endif
            >
            
            <!-- Switch Track -->
            <label 
                for="{{ $switchId }}" 
                class="relative flex items-center cursor-pointer transition-all duration-200 ease-in-out
                       {{ $sizeClasses[$size] }}
                       {{ $isChecked ? $colorClasses[$color] : 'bg-gray-300 dark:bg-gray-600' }}
                       rounded-full shadow-inner
                       {{ $disabled ? 'cursor-not-allowed' : 'hover:shadow-md' }}
                       focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
            >
                <!-- Switch Thumb -->
                <span class="absolute left-0.5 top-0.5 bg-white rounded-full shadow-lg transform transition-transform duration-200 ease-in-out
                            {{ $thumbSizeClasses[$size] }}
                            {{ $isChecked ? $translateClasses[$size] : 'translate-x-0' }}
                            peer-focus:ring-4 peer-focus:ring-indigo-300">
                </span>
            </label>
        </div>
        
        @if($description)
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ $description }}
            </span>
        @endif
    </div>
    
    @error($switchName)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ $message }}
        </p>
    @enderror
</div>

@push('styles')
<style>
.modern-switch-container {
    @apply transition-all duration-200;
}

.modern-switch-container:hover:not(.opacity-50) {
    @apply transform scale-105;
}

/* Animation personnalisée pour le thumb */
.modern-switch-container input[type="checkbox"]:checked + label span {
    @apply transform transition-transform duration-300 ease-in-out;
}

/* Effet de focus amélioré */
.modern-switch-container input[type="checkbox"]:focus + label {
    @apply ring-4 ring-opacity-50;
}

/* Animation de pulsation pour les états actifs */
.modern-switch-container input[type="checkbox"]:checked + label::before {
    content: '';
    @apply absolute inset-0 rounded-full opacity-0;
    background: inherit;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 0.7;
    }
    70% {
        transform: scale(1.1);
        opacity: 0;
    }
    100% {
        transform: scale(1.1);
        opacity: 0;
    }
}

/* Support pour le mode sombre */
.dark .modern-switch-container label {
    @apply shadow-lg;
}

.dark .modern-switch-container input[type="checkbox"]:checked + label {
    @apply shadow-indigo-500/25;
}
</style>
@endpush
