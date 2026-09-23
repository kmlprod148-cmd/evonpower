<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.dashboard') }} - {{ app()->getLocale() }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="{{ asset("vendor/fontawesome/css/all.min.css") }}" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">{{ __('messages.dashboard') }}</h1>
                </div>
                
                <!-- Language Switcher -->
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">{{ __('messages.language') }}:</span>
                    <div class="flex space-x-2">
                        <a href="/lang-switch/fr" 
                           class="px-3 py-1 text-sm rounded border transition-colors duration-200 {{ app()->getLocale() == 'fr' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🇫🇷 FR
                        </a>
                        <a href="/lang-switch/en" 
                           class="px-3 py-1 text-sm rounded border transition-colors duration-200 {{ app()->getLocale() == 'en' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🇺🇸 EN
                        </a>
                        <a href="/lang-switch/ar" 
                           class="px-3 py-1 text-sm rounded border transition-colors duration-200 {{ app()->getLocale() == 'ar' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            🇸🇦 AR
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Current Status Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        {{ __('messages.current_status') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">{{ __('messages.app_locale') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ app()->getLocale() }}</dd>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">{{ __('messages.session_locale') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ session('locale') ?? 'Not set' }}</dd>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">{{ __('messages.config_locale') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ config('app.locale') }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Messages Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        {{ __('messages.test_messages') }}
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <i class="fas fa-home text-blue-500 mr-3"></i>
                            <span class="text-gray-700"><strong>{{ __('messages.dashboard') }}:</strong> {{ __('messages.dashboard') }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-cog text-gray-500 mr-3"></i>
                            <span class="text-gray-700"><strong>{{ __('messages.settings') }}:</strong> {{ __('messages.settings') }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-user text-green-500 mr-3"></i>
                            <span class="text-gray-700"><strong>{{ __('messages.profile') }}:</strong> {{ __('messages.profile') }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-language text-purple-500 mr-3"></i>
                            <span class="text-gray-700"><strong>{{ __('messages.language') }}:</strong> {{ __('messages.language') }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-handshake text-orange-500 mr-3"></i>
                            <span class="text-gray-700"><strong>{{ __('messages.welcome') }}:</strong> {{ __('messages.welcome') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Test -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        {{ __('messages.navigation_test') }}
                    </h3>
                    <div class="flex flex-wrap gap-4">
                        <a href="/dashboard" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            {{ __('messages.dashboard') }}
                        </a>
                        <a href="/settings" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-cog mr-2"></i>
                            {{ __('messages.settings') }}
                        </a>
                        <a href="/profile" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-user mr-2"></i>
                            {{ __('messages.profile') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
