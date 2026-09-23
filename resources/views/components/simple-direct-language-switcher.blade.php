<!-- SIMPLE DIRECT LANGUAGE SWITCHER -->
<div class="simple-direct-language-switcher">
    <div class="flex items-center space-x-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Language:</span>
        
        <!-- French Button -->
        <a href="/lang-switch/fr" 
           class="inline-flex items-center px-3 py-1 rounded border {{ app()->getLocale() == 'fr' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
            <img src="https://flagcdn.com/w20/fr.png" alt="French" class="w-4 h-auto rounded mr-2">
            <span class="text-sm">FR</span>
        </a>
        
        <!-- English Button -->
        <a href="/lang-switch/en" 
           class="inline-flex items-center px-3 py-1 rounded border {{ app()->getLocale() == 'en' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
            <img src="https://flagcdn.com/w20/gb.png" alt="English" class="w-4 h-auto rounded mr-2">
            <span class="text-sm">EN</span>
        </a>
        
        <!-- Arabic Button -->
        <a href="/lang-switch/ar" 
           class="inline-flex items-center px-3 py-1 rounded border {{ app()->getLocale() == 'ar' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
            <img src="https://flagcdn.com/w20/ma.png" alt="Arabic" class="w-4 h-auto rounded mr-2">
            <span class="text-sm">AR</span>
        </a>
        
        <!-- Current Status -->
        <div class="text-sm text-gray-500">
            Current: <strong>{{ app()->getLocale() }}</strong>
        </div>
    </div>
</div>
