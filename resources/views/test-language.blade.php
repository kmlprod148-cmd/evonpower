<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Language Switching</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Test Language Switching</h1>
        
        <!-- Current Status -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Current Status</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <strong>App Locale:</strong> {{ app()->getLocale() }}
                </div>
                <div>
                    <strong>Session Locale:</strong> {{ session('locale') ?? 'Not set' }}
                </div>
                <div>
                    <strong>Config Locale:</strong> {{ config('app.locale') }}
                </div>
                <div>
                    <strong>Available Locales:</strong> {{ implode(', ', config('app.available_locales')) }}
                </div>
            </div>
        </div>

        <!-- Language Switcher -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Language Switcher</h2>
            @include('components.advanced-language-switcher')
        </div>

        <!-- Test Language Switcher -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Test Language Switcher (Direct)</h2>
            @include('components.test-language-switcher')
        </div>

        <!-- JavaScript Language Switcher -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">JavaScript Language Switcher (AJAX)</h2>
            @include('components.js-language-switcher')
        </div>

        <!-- Test Messages -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Test Messages</h2>
            <div class="space-y-2">
                <p><strong>Welcome:</strong> {{ __('messages.welcome') }}</p>
                <p><strong>Language:</strong> {{ __('messages.language') }}</p>
                <p><strong>Dashboard:</strong> {{ __('messages.dashboard') }}</p>
                <p><strong>Settings:</strong> {{ __('messages.settings') }}</p>
            </div>
        </div>

        <!-- Debug Info -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <div class="space-y-2 text-sm">
                <p><strong>Session ID:</strong> {{ session()->getId() }}</p>
                <p><strong>Request URL:</strong> {{ request()->fullUrl() }}</p>
                <p><strong>Request Method:</strong> {{ request()->method() }}</p>
                <p><strong>User Agent:</strong> {{ request()->userAgent() }}</p>
            </div>
        </div>
    </div>
</body>
</html>
