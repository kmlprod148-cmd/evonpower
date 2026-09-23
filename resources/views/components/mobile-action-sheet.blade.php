@props([
    'id' => 'action-sheet-' . uniqid(),
    'title' => '',
    'subtitle' => '',
    'cancelText' => 'Annuler',
    'showCancel' => true,
])

{{-- Action Sheet Modal (iOS/Android style) --}}
<div 
    x-data="{ open: false }"
    @action-sheet-open.window="if ($event.detail.id === '{{ $id }}') open = true"
    @action-sheet-close.window="if ($event.detail.id === '{{ $id }}') open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center sm:p-4"
    style="display: none;"
>
    {{-- Backdrop avec blur --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
        aria-hidden="true"
    ></div>
    
    {{-- Action Sheet Panel --}}
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full sm:scale-95 sm:translate-y-0 opacity-0"
        x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 sm:scale-100 opacity-100"
        x-transition:leave-end="translate-y-full sm:scale-95 sm:translate-y-0 opacity-0"
        class="relative w-full max-w-lg bg-white dark:bg-gray-800 shadow-2xl sm:rounded-2xl overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
        @click.away="open = false"
    >
        {{-- Handle bar (mobile) --}}
        <div class="sm:hidden flex justify-center pt-3 pb-2">
            <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>
        
        {{-- Header --}}
        @if($title || $subtitle)
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                @if($title)
                    <h3 id="{{ $id }}-title" class="text-lg font-bold text-gray-900 dark:text-white text-center">
                        {{ $title }}
                    </h3>
                @endif
                @if($subtitle)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 text-center">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
        @endif
        
        {{-- Actions Container --}}
        <div class="py-2 max-h-[60vh] sm:max-h-[70vh] overflow-y-auto">
            {{ $slot }}
        </div>
        
        {{-- Cancel Button (iOS style) --}}
        @if($showCancel)
            <div class="border-t-8 border-gray-100 dark:border-gray-900 sm:border-t">
                <button 
                    @click="open = false"
                    type="button"
                    class="w-full px-5 py-4 text-base font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150 active:scale-[0.99]"
                >
                    {{ $cancelText }}
                </button>
            </div>
        @endif
    </div>
</div>

{{-- Action Sheet Item Component (utilisé dans le slot) --}}
@php
// Ce composant sera utilisé comme ceci:
// <x-mobile-action-sheet id="my-sheet" title="Choisir une action">
//     <button class="action-sheet-item">Action 1</button>
//     <button class="action-sheet-item action-sheet-item-danger">Supprimer</button>
// </x-mobile-action-sheet>
@endphp

<style>
/* Styles pour les items d'action sheet */
.action-sheet-item {
    @apply w-full px-5 py-4 text-left text-base font-medium text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150 border-b border-gray-100 dark:border-gray-700 last:border-b-0 flex items-center gap-3 active:scale-[0.99];
}

.action-sheet-item-danger {
    @apply text-red-600 dark:text-red-400;
}

.action-sheet-item-disabled {
    @apply opacity-50 cursor-not-allowed pointer-events-none;
}

.action-sheet-item svg {
    @apply w-5 h-5 flex-shrink-0;
}
</style>

<script>
// Helper functions pour ouvrir/fermer l'action sheet
window.openActionSheet = function(id) {
    window.dispatchEvent(new CustomEvent('action-sheet-open', { detail: { id } }));
    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
};

window.closeActionSheet = function(id) {
    window.dispatchEvent(new CustomEvent('action-sheet-close', { detail: { id } }));
    // Restaurer le scroll du body
    document.body.style.overflow = '';
};

// Fermer l'action sheet avec la touche Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.body.style.overflow = '';
    }
});
</script>

