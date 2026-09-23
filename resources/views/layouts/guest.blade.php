<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        @php
            $faviconUrl = \App\Helpers\Brand::favicon();
            $faviconType = str_ends_with($faviconUrl, '.svg') ? 'image/svg+xml' : 'image/x-icon';
        @endphp
        <link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">

        <!-- Fonts -->
        {{-- Fonts preconnect removed --}}
        {{-- Fonts removed - using system fonts --}}

        <!-- Assets -->
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script src="{{ asset('js/app.js') }}" type="module" defer></script>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gradient-to-br from-gray-50 via-green-50 to-emerald-50">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4 pb-6">
            <!-- Logo en haut -->
            <div class="mb-4 sm:mb-6">
                <a href="/" class="block transition-transform hover:scale-105">
                    <x-application-logo class="w-16 h-16 sm:w-20 sm:h-20 fill-current text-green-600" />
                </a>
            </div>

            <!-- Contenu principal -->
            <div class="w-full sm:max-w-2xl mt-2 sm:mt-4 mb-4">
                {{ $slot }}
            </div>
            
            <!-- Footer -->
            <div class="text-center text-xs sm:text-sm text-gray-600 mt-4">
                <p>&copy; {{ date('Y') }} {{ config('brand.display_name', 'EVON Power') }}. Tous droits réservés.</p>
            </div>
        </div>
    </body>
</html>
