<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Translation Test - {{ app()->getLocale() }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Translation Test - {{ app()->getLocale() }}</h1>
        
        <!-- Language Switcher -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('messages.language') }}</h2>
            @include('components.direct-language-switcher')
        </div>

        <!-- Current Status -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('messages.current') }} {{ __('messages.status') }}</h2>
            <div class="grid grid-cols-2 gap-4">
                <div><strong>{{ __('messages.language') }}:</strong> {{ app()->getLocale() }}</div>
                <div><strong>{{ __('messages.welcome') }}:</strong> {{ __('messages.welcome') }}</div>
                <div><strong>{{ __('messages.dashboard') }}:</strong> {{ __('messages.dashboard') }}</div>
                <div><strong>{{ __('messages.settings') }}:</strong> {{ __('messages.settings') }}</div>
            </div>
        </div>

        <!-- Navigation Test -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('messages.navigation') }}</h2>
            <div class="space-y-2">
                <p><strong>{{ __('messages.dashboard') }}:</strong> {{ __('messages.dashboard') }}</p>
                <p><strong>{{ __('messages.go_to_dashboard') }}:</strong> {{ __('messages.go_to_dashboard') }}</p>
                <p><strong>{{ __('messages.login') }}:</strong> {{ __('messages.login') }}</p>
                <p><strong>{{ __('messages.logout') }}:</strong> {{ __('messages.logout') }}</p>
                <p><strong>{{ __('messages.profile') }}:</strong> {{ __('messages.profile') }}</p>
            </div>
        </div>

        <!-- Actions Test -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('messages.actions') }}</h2>
            <div class="space-y-2">
                <p><strong>{{ __('messages.view') }}:</strong> {{ __('messages.view') }}</p>
                <p><strong>{{ __('messages.edit') }}:</strong> {{ __('messages.edit') }}</p>
                <p><strong>{{ __('messages.delete') }}:</strong> {{ __('messages.delete') }}</p>
                <p><strong>{{ __('messages.create') }}:</strong> {{ __('messages.create') }}</p>
                <p><strong>{{ __('messages.save') }}:</strong> {{ __('messages.save') }}</p>
                <p><strong>{{ __('messages.cancel') }}:</strong> {{ __('messages.cancel') }}</p>
            </div>
        </div>

        <!-- Status Test -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('messages.status') }}</h2>
            <div class="space-y-2">
                <p><strong>{{ __('messages.online') }}:</strong> {{ __('messages.online') }}</p>
                <p><strong>{{ __('messages.offline') }}:</strong> {{ __('messages.offline') }}</p>
                <p><strong>{{ __('messages.active') }}:</strong> {{ __('messages.active') }}</p>
                <p><strong>{{ __('messages.inactive') }}:</strong> {{ __('messages.inactive') }}</p>
                <p><strong>{{ __('messages.pending') }}:</strong> {{ __('messages.pending') }}</p>
            </div>
        </div>

        <!-- Debug Info -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <div class="space-y-2 text-sm">
                <p><strong>App Locale:</strong> {{ app()->getLocale() }}</p>
                <p><strong>Session Locale:</strong> {{ session('locale') ?? 'Not set' }}</p>
                <p><strong>Config Locale:</strong> {{ config('app.locale') }}</p>
                <p><strong>Available Locales:</strong> {{ implode(', ', config('app.available_locales')) }}</p>
                <p><strong>Request URL:</strong> {{ request()->fullUrl() }}</p>
            </div>
        </div>
    </div>
</body>
</html>
