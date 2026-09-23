<!-- TEST LANGUAGE SWITCHER -->
<div class="test-language-switcher">
    <div class="flex items-center space-x-4 p-4 bg-gray-100 rounded-lg">
        <span class="text-sm font-medium">Test Language Switch:</span>
        
        <!-- Test French -->
        <a href="/test-lang-switch/fr" 
           class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
            FR
        </a>
        
        <!-- Test English -->
        <a href="/test-lang-switch/en" 
           class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
            EN
        </a>
        
        <!-- Test Arabic -->
        <a href="/test-lang-switch/ar" 
           class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
            AR
        </a>
        
        <!-- Current Status -->
        <div class="text-sm text-gray-600">
            Current: <strong>{{ app()->getLocale() }}</strong>
        </div>
    </div>
</div>
