<!-- JAVASCRIPT LANGUAGE SWITCHER -->
<div class="js-language-switcher" x-data="{ 
    currentLocale: '{{ app()->getLocale() }}',
    switching: false,
    async switchLanguage(locale) {
        this.switching = true;
        try {
            const response = await fetch(`/lang-switch/${locale}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentLocale = locale;
                console.log('Language switched to:', locale);
                console.log('Session locale:', data.session_locale);
                console.log('App locale:', data.app_locale);
                
                // Wait a moment for session to be saved, then reload
                setTimeout(() => {
                    window.location.reload();
                }, 100);
            } else {
                console.error('Language switch failed:', data);
                alert('Failed to switch language: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error switching language:', error);
            alert('Error switching language: ' + error.message);
        } finally {
            this.switching = false;
        }
    }
}">
    <div class="relative">
        <button @click="open = !open" 
                class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200"
                :disabled="switching">
            <div class="flex items-center">
                <img :src="currentLocale === 'fr' ? 'https://flagcdn.com/w20/fr.png' : (currentLocale === 'en' ? 'https://flagcdn.com/w20/gb.png' : 'https://flagcdn.com/w20/ma.png')" 
                     :alt="currentLocale" 
                     class="w-4 h-auto rounded mr-2">
                <span x-text="currentLocale === 'fr' ? 'Français' : (currentLocale === 'en' ? 'English' : 'العربية')"></span>
            </div>
            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        
        <div x-show="open" 
             @click.away="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="py-1">
                @foreach(config('app.available_locales') as $locale)
                    <button @click="switchLanguage('{{ $locale }}')" 
                            :disabled="switching || currentLocale === '{{ $locale }}'"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed {{ app()->getLocale() === $locale ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                        <img src="{{ $locale === 'fr' ? 'https://flagcdn.com/w20/fr.png' : ($locale === 'en' ? 'https://flagcdn.com/w20/gb.png' : 'https://flagcdn.com/w20/ma.png') }}" 
                             alt="{{ $locale }}" 
                             class="w-4 h-auto rounded mr-3">
                        <span class="flex-1 text-left">{{ __('messages.' . $locale) }}</span>
                        <div x-show="switching" class="w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
                        <svg x-show="!switching && currentLocale === '{{ $locale }}'" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
