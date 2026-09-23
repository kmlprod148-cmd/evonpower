<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript Language Test - {{ app()->getLocale() }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">JavaScript Language Test - {{ app()->getLocale() }}</h1>
        
        <!-- Current Status -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Current Status</h2>
            <div class="grid grid-cols-2 gap-4">
                <div><strong>App Locale:</strong> {{ app()->getLocale() }}</div>
                <div><strong>Session Locale:</strong> {{ session('locale') ?? 'Not set' }}</div>
                <div><strong>Config Locale:</strong> {{ config('app.locale') }}</div>
                <div><strong>Welcome Message:</strong> {{ __('messages.welcome') }}</div>
            </div>
        </div>

        <!-- Language Switcher with JavaScript -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Language Switcher (JavaScript)</h2>
            <div class="flex space-x-4">
                <button onclick="switchLanguage('fr')" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 {{ app()->getLocale() == 'fr' ? 'bg-blue-700' : '' }}">
                    Français (FR)
                </button>
                <button onclick="switchLanguage('en')" 
                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 {{ app()->getLocale() == 'en' ? 'bg-green-700' : '' }}">
                    English (EN)
                </button>
                <button onclick="switchLanguage('ar')" 
                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 {{ app()->getLocale() == 'ar' ? 'bg-red-700' : '' }}">
                    العربية (AR)
                </button>
            </div>
        </div>

        <!-- Test Messages -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Test Messages</h2>
            <div class="space-y-2">
                <p><strong>Welcome:</strong> {{ __('messages.welcome') }}</p>
                <p><strong>Dashboard:</strong> {{ __('messages.dashboard') }}</p>
                <p><strong>Settings:</strong> {{ __('messages.settings') }}</p>
                <p><strong>Language:</strong> {{ __('messages.language') }}</p>
            </div>
        </div>

        <!-- Debug Info -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <div class="space-y-2 text-sm">
                <p><strong>Session ID:</strong> {{ session()->getId() }}</p>
                <p><strong>Request URL:</strong> {{ request()->fullUrl() }}</p>
                <p><strong>Available Locales:</strong> {{ implode(', ', config('app.available_locales')) }}</p>
                <p><strong>Cookies:</strong> {{ json_encode(request()->cookies->all()) }}</p>
            </div>
        </div>
    </div>

    <script>
        function switchLanguage(locale) {
            console.log('Switching to language:', locale);
            
            // Show loading state
            const buttons = document.querySelectorAll('button');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.textContent = 'Switching...';
            });
            
            // Make request to language switch endpoint
            fetch('/lang-switch/' + locale)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.text();
                })
                .then(data => {
                    console.log('Response data:', data);
                    // Reload the page to see the changes
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error switching language:', error);
                    alert('Error switching language: ' + error.message);
                    
                    // Re-enable buttons
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        btn.textContent = btn.getAttribute('onclick').includes('fr') ? 'Français (FR)' : 
                                        btn.getAttribute('onclick').includes('en') ? 'English (EN)' : 'العربية (AR)';
                    });
                });
        }
    </script>
</body>
</html>
