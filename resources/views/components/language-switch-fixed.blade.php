@php
    $currentLocale = app()->getLocale();
    $flags = [
        'fr' => '🇫🇷', 
        'en' => '🇬🇧', 
        'ar' => '🇲🇦', 
        'es' => '🇪🇸'
    ];
@endphp

<div x-data="window.languageSwitchFixed ? window.languageSwitchFixed() : {}" 
     class="relative" 
     id="language-switch-component">
    <!-- Language Button -->
    <button type="button"
            @click.stop="toggle()" 
            class="evon-header-button relative group"
            :class="{
                'ring-2 ring-eco-green-500': isOpen,
                'opacity-60 cursor-not-allowed': switching
            }"
            :aria-expanded="isOpen ? 'true' : 'false'"
            :aria-disabled="switching ? 'true' : 'false'"
            :aria-busy="switching ? 'true' : 'false'"
            :disabled="switching"
            title="Changer la langue"
            aria-label="Changer la langue">
        
        <div class="flex items-center gap-2">
            <!-- Flag -->
            <span class="text-lg leading-none" 
                  x-show="!switching"
                  x-text="currentFlag">{{ $flags[$currentLocale] ?? '🌐' }}</span>
            
            <!-- Loading Spinner -->
            <svg x-show="switching" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-0"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-0"
                 class="w-4 h-4 animate-spin text-eco-green-600" 
                 fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </button>

    <!-- Dropdown -->
    <div x-show="isOpen"
         x-cloak
         @click.away="isOpen = false"
         @keydown.escape.window="isOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-3 w-56 origin-top-right z-50">
        
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden">
            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Langue / Language</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Choisissez votre langue</p>
            </div>

            <!-- Language Options -->
            <div class="py-2">
                <template x-for="(name, code) in languages" :key="code">
                    <button type="button"
                            @click="switchLanguage(code)"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors"
                            :class="{
                                'bg-eco-green-50 dark:bg-eco-green-900/20 text-eco-green-700 dark:text-eco-green-400 font-semibold': currentLocale === code,
                                'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50': currentLocale !== code,
                                'opacity-60 cursor-not-allowed': switching
                            }"
                            :disabled="switching || currentLocale === code"
                            :aria-label="'Changer la langue en ' + name"
                            :aria-current="currentLocale === code ? 'true' : 'false'">
                        
                        <!-- Flag -->
                        <span class="text-2xl flex-shrink-0" x-text="flags[code]"></span>
                        
                        <!-- Language Name -->
                        <span class="flex-1 text-left" x-text="name"></span>
                        
                        <!-- Checkmark -->
                        <svg x-show="currentLocale === code" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-0"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="w-5 h-5 text-eco-green-600 dark:text-eco-green-400" 
                             fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

<style>
.lang-notification {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
