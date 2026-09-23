<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Evon Charge') }}</title>

    <!-- Fonts -->
    {{-- Fonts preconnect removed --}}
    {{-- Fonts removed - using system fonts --}}

    <!-- Scripts -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" type="module" defer></script>
    @inertiaHead
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @inertia
    </div>
</body>
</html>
