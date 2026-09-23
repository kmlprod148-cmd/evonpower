<!-- SIMPLE LANGUAGE SWITCHER FOR TESTING -->
<div class="simple-language-switcher">
    <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.language') }}:</span>
        
        <!-- French Button -->
        <a href="{{ route('language.switch', 'fr') }}" 
           class="inline-flex items-center px-3 py-1 rounded border {{ app()->getLocale() == 'fr' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
            <img src="https://flagcdn.com/w20/fr.png" alt="French" class="w-4 h-auto rounded mr-2">
            <span class="text-sm">FR</span>
        </a>
        
        <!-- English Button -->
        <a href="{{ route('language.switch', 'en') }}" 
           class="inline-flex items-center px-3 py-1 rounded border {{ app()->getLocale() == 'en' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
            <img src="https://flagcdn.com/w20/gb.png" alt="English" class="w-4 h-auto rounded mr-2">
            <span class="text-sm">EN</span>
        </a>
        
        <!-- Arabic Button -->
        <a href="{{ route('language.switch', 'ar') }}" 
           class="inline-flex items-center px-3 py-1 rounded border {{ app()->getLocale() == 'ar' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
            <img src="https://flagcdn.com/w20/ma.png" alt="Arabic" class="w-4 h-auto rounded mr-2">
            <span class="text-sm">AR</span>
        </a>
        
        <!-- Debug Info (only in debug mode) -->
        @if(config('app.debug'))
        <div class="ml-4 text-xs text-gray-500">
            {{ __('messages.current') }}: {{ app()->getLocale() }}
        </div>
        @endif
    </div>
</div> 