{{-- resources/views/components/translated-switch.blade.php --}}
@props([
    'id' => null,
    'name' => null,
    'value' => false,
    'labelKey' => '', // Clé de traduction pour le label
    'descriptionKey' => '', // Clé de traduction pour la description
    'size' => 'md', // sm, md, lg
    'color' => 'primary', // primary, success, warning, danger
    'disabled' => false,
    'required' => false,
    'onChange' => null,
    'customLabel' => null, // Label personnalisé (override la traduction)
    'customDescription' => null, // Description personnalisée (override la traduction)
    'showStatus' => true, // Afficher le statut (Activé/Désactivé)
    'animate' => true // Animation lors du changement
])

@php
    $switchId = $id ?? 'switch-' . uniqid();
    $switchName = $name ?? $switchId;
    $isChecked = old($switchName, $value);
    
    // Obtenir les traductions
    $label = $customLabel ?? ($labelKey ? trans("switch.{$labelKey}") : '');
    $description = $customDescription ?? ($descriptionKey ? trans("switch.{$descriptionKey}") : '');
    $enabledText = trans('switch.enabled');
    $disabledText = trans('switch.disabled');
    
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

<div class="translated-switch-container {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}" 
     x-data="{ 
         isChecked: {{ $isChecked ? 'true' : 'false' }},
         animate: {{ $animate ? 'true' : 'false' }},
         toggle() {
             if ({{ $disabled ? 'true' : 'false' }}) return;
             
             if (!this.animate) {
                 this.isChecked = !this.isChecked;
                 this.$refs.checkbox.checked = this.isChecked;
                 return;
             }
             
             // Animation de basculement
             const switchElement = this.$refs.switchTrack;
             switchElement.style.transform = 'scale(0.95)';
             
             setTimeout(() => {
                 this.isChecked = !this.isChecked;
                 this.$refs.checkbox.checked = this.isChecked;
                 switchElement.style.transform = 'scale(1)';
             }, 100);
         }
     }"
     x-init="
         // Mise à jour automatique des traductions lors du changement de langue
         $watch('$store.language.current', (newLang) => {
             // Recharger les traductions si nécessaire
             if (window.updateTranslations) {
                 window.updateTranslations();
             }
         })
     ">
    
    @if($label)
        <label for="{{ $switchId }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif
    
    <div class="flex items-center justify-between">
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
                    x-model="isChecked"
                    x-ref="checkbox"
                    @change="isChecked = $event.target.checked"
                >
                
                <!-- Switch Track -->
                <label 
                    for="{{ $switchId }}" 
                    @click.prevent="toggle()"
                    x-ref="switchTrack"
                    class="relative flex items-center cursor-pointer transition-all duration-300 ease-in-out
                           {{ $sizeClasses[$size] }}
                           {{ $isChecked ? $colorClasses[$color] : 'bg-gray-300 dark:bg-gray-600' }}
                           rounded-full shadow-inner
                           {{ $disabled ? 'cursor-not-allowed' : 'hover:shadow-md' }}
                           focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                    :class="{
                        '{{ $colorClasses[$color] }}': isChecked,
                        'bg-gray-300 dark:bg-gray-600': !isChecked
                    }"
                >
                    <!-- Switch Thumb -->
                    <span class="absolute left-0.5 top-0.5 bg-white rounded-full shadow-lg transform transition-transform duration-300 ease-in-out
                                {{ $thumbSizeClasses[$size] }}"
                          :class="{
                              '{{ $translateClasses[$size] }}': isChecked,
                              'translate-x-0': !isChecked
                          }">
                    </span>
                </label>
            </div>
            
            @if($description)
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </span>
            @endif
        </div>
        
        @if($showStatus)
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium transition-colors duration-200"
                      :class="{
                          'text-green-600 dark:text-green-400': isChecked,
                          'text-gray-500 dark:text-gray-400': !isChecked
                      }"
                      x-text="isChecked ? '{{ $enabledText }}' : '{{ $disabledText }}'">
                </span>
                
                <!-- Indicateur visuel -->
                <div class="w-2 h-2 rounded-full transition-all duration-200"
                     :class="{
                         'bg-green-500': isChecked,
                         'bg-gray-400': !isChecked
                     }">
                </div>
            </div>
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
.translated-switch-container {
    @apply transition-all duration-200;
}

.translated-switch-container:hover:not(.opacity-50) {
    @apply transform scale-105;
}

/* Animation personnalisée pour le thumb */
.translated-switch-container input[type="checkbox"]:checked + label span {
    @apply transform transition-transform duration-300 ease-in-out;
}

/* Effet de focus amélioré */
.translated-switch-container input[type="checkbox"]:focus + label {
    @apply ring-4 ring-opacity-50;
}

/* Animation de pulsation pour les états actifs */
.translated-switch-container input[type="checkbox"]:checked + label::before {
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
.dark .translated-switch-container label {
    @apply shadow-lg;
}

.dark .translated-switch-container input[type="checkbox"]:checked + label {
    @apply shadow-indigo-500/25;
}

/* Support RTL */
[dir="rtl"] .translated-switch-container .flex {
    @apply flex-row-reverse;
}

[dir="rtl"] .translated-switch-container .space-x-3 > * + * {
    @apply ml-0 mr-3;
}

/* Animation de changement de langue */
.translated-switch-container .transition-colors {
    transition: color 0.3s ease, background-color 0.3s ease;
}

/* Effet de glow pour les switches actifs */
.translated-switch-container input[type="checkbox"]:checked + label {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Animation de hover améliorée */
.translated-switch-container:hover input[type="checkbox"]:checked + label {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}
</style>
@endpush

@push('scripts')
<script>
// Fonction globale pour mettre à jour les traductions
window.updateTranslations = function() {
    // Cette fonction sera appelée lors du changement de langue
    // Les composants Alpine.js se mettront à jour automatiquement
    console.log('Updating switch translations...');
};

// Store Alpine.js pour la langue
document.addEventListener('alpine:init', () => {
    Alpine.store('language', {
        current: '{{ app()->getLocale() }}',
        set(locale) {
            this.current = locale;
        }
    });
});
</script>
@endpush
