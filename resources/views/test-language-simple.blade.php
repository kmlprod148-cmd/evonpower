<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('messages.language') }} - Test</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">
            {{ __('messages.language') }} - Test Page
        </h1>

        <!-- Language Switcher Component -->
        <div class="mb-8">
            @include('components.direct-language-switcher')
        </div>

        <!-- Current Language Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-blue-800">{{ __('messages.current') }} {{ __('messages.language') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-600">App Locale:</p>
                    <p class="font-mono text-lg font-bold text-blue-600">{{ app()->getLocale() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Session Locale:</p>
                    <p class="font-mono text-lg font-bold text-blue-600">{{ session('locale', 'not set') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Config Locale:</p>
                    <p class="font-mono text-lg font-bold text-blue-600">{{ config('app.locale') }}</p>
                </div>
            </div>
        </div>

        <!-- Translated Messages -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-green-800">{{ __('messages.translated_messages') ?? 'Translated Messages' }}</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-green-200">
                    <span class="text-gray-600">messages.welcome:</span>
                    <span class="font-semibold text-green-700">{{ __('messages.welcome') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-green-200">
                    <span class="text-gray-600">messages.dashboard:</span>
                    <span class="font-semibold text-green-700">{{ __('messages.dashboard') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-green-200">
                    <span class="text-gray-600">messages.settings:</span>
                    <span class="font-semibold text-green-700">{{ __('messages.settings') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-green-200">
                    <span class="text-gray-600">messages.logout:</span>
                    <span class="font-semibold text-green-700">{{ __('messages.logout') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-green-200">
                    <span class="text-gray-600">messages.language_changed_successfully:</span>
                    <span class="font-semibold text-green-700">{{ __('messages.language_changed_successfully') }}</span>
                </div>
            </div>
        </div>

        <!-- Direction Test -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-purple-800">{{ __('messages.direction') ?? 'Text Direction' }}</h2>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Current Direction:</span>
                <span class="font-mono text-lg font-bold text-purple-600">{{ in_array(app()->getLocale(), ['ar']) ? 'RTL (Right to Left)' : 'LTR (Left to Right)' }}</span>
            </div>
        </div>

        <!-- Refresh Button -->
        <div class="mt-8 flex justify-center">
            <button onclick="window.location.reload()" 
                    class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg shadow-md transition-colors">
                🔄 {{ __('messages.refresh') ?? 'Refresh Page' }}
            </button>
        </div>
    </div>

    <script>
        // Log locale info to console
        console.log('Language Test Page Loaded');
        console.log('App Locale:', '{{ app()->getLocale() }}');
        console.log('Session Locale:', '{{ session('locale', 'not set') }}');
        console.log('Config Locale:', '{{ config('app.locale') }}');
        console.log('HTML dir attribute:', document.documentElement.getAttribute('dir'));
        console.log('HTML lang attribute:', document.documentElement.getAttribute('lang'));
    </script>
</body>
</html>

