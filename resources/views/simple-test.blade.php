<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Language Test - {{ app()->getLocale() }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                ❌ {{ session('error') }}
            </div>
        @endif

        <h1 class="text-4xl font-bold mb-8 text-center">
            {{ __('messages.welcome') }} - {{ app()->getLocale() }}
        </h1>
        
        <!-- Language Switcher -->
        <div class="bg-white p-6 rounded-lg shadow mb-6 text-center">
            <h2 class="text-2xl font-semibold mb-4">{{ __('messages.language') }}</h2>
            <div class="flex justify-center space-x-4">
                <a href="/lang-switch/fr" 
                   class="px-6 py-3 text-lg rounded-lg transition-colors duration-200 {{ app()->getLocale() == 'fr' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    🇫🇷 Français
                </a>
                <a href="/lang-switch/en" 
                   class="px-6 py-3 text-lg rounded-lg transition-colors duration-200 {{ app()->getLocale() == 'en' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    🇺🇸 English
                </a>
                <a href="/lang-switch/ar" 
                   class="px-6 py-3 text-lg rounded-lg transition-colors duration-200 {{ app()->getLocale() == 'ar' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    🇸🇦 العربية
                </a>
            </div>
        </div>

        <!-- Test Messages -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-2xl font-semibold mb-4">{{ __('messages.test_messages') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 rounded">
                    <strong>{{ __('messages.dashboard') }}:</strong> {{ __('messages.dashboard') }}
                </div>
                <div class="p-4 bg-gray-50 rounded">
                    <strong>{{ __('messages.settings') }}:</strong> {{ __('messages.settings') }}
                </div>
                <div class="p-4 bg-gray-50 rounded">
                    <strong>{{ __('messages.profile') }}:</strong> {{ __('messages.profile') }}
                </div>
                <div class="p-4 bg-gray-50 rounded">
                    <strong>{{ __('messages.language') }}:</strong> {{ __('messages.language') }}
                </div>
                <div class="p-4 bg-gray-50 rounded">
                    <strong>{{ __('messages.welcome') }}:</strong> {{ __('messages.welcome') }}
                </div>
                <div class="p-4 bg-gray-50 rounded">
                    <strong>{{ __('messages.login') }}:</strong> {{ __('messages.login') }}
                </div>
            </div>
        </div>

        <!-- Current Status -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-semibold mb-4">{{ __('messages.current_status') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-blue-50 rounded">
                    <div class="text-sm text-gray-600">{{ __('messages.app_locale') }}</div>
                    <div class="text-2xl font-bold text-blue-600">{{ app()->getLocale() }}</div>
                </div>
                <div class="text-center p-4 bg-green-50 rounded">
                    <div class="text-sm text-gray-600">{{ __('messages.session_locale') }}</div>
                    <div class="text-2xl font-bold text-green-600">{{ session('locale') ?? 'Not set' }}</div>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded">
                    <div class="text-sm text-gray-600">{{ __('messages.config_locale') }}</div>
                    <div class="text-2xl font-bold text-purple-600">{{ config('app.locale') }}</div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
