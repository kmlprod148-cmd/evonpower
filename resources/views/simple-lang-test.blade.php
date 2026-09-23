<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Language Test - {{ app()->getLocale() }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Simple Language Test - {{ app()->getLocale() }}</h1>
        
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

        <!-- Language Switcher -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Language Switcher</h2>
            <div class="flex space-x-4">
                <a href="/lang-switch/fr" 
                   class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 {{ app()->getLocale() == 'fr' ? 'bg-blue-700' : '' }}">
                    Français (FR)
                </a>
                <a href="/lang-switch/en" 
                   class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 {{ app()->getLocale() == 'en' ? 'bg-green-700' : '' }}">
                    English (EN)
                </a>
                <a href="/lang-switch/ar" 
                   class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 {{ app()->getLocale() == 'ar' ? 'bg-red-700' : '' }}">
                    العربية (AR)
                </a>
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
</body>
</html>
