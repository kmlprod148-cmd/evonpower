<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      @if(app()->getLocale() === 'ar') dir="rtl" @endif 
      class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="balance-api-url" content="{{ route('api.user.balance') }}">
    @endauth
    <title>@yield('title', config('app.name', 'EVON') . ' - Electric Vehicle Charging Management')</title>
    <!-- Favicon -->
    @php
        $faviconUrl = \App\Helpers\Brand::favicon();
        $faviconType = str_ends_with($faviconUrl, '.svg') ? 'image/svg+xml' : 'image/x-icon';
    @endphp
    <link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @stack('meta')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" type="module" defer></script>
    <!-- Correctif global pour les erreurs dataset -->
    <script src="{{ asset('js/global-dataset-fix.js') }}"></script>
    <!-- Correctif pour les requêtes API de notifications -->
    <script src="{{ asset('js/notification-api-fix-v2.js') }}"></script>
    <!-- Script de rafraîchissement automatique du token CSRF -->
    <script src="{{ asset('js/csrf-refresh.js') }}"></script>
    <script src="{{ asset('js/vendor/alpine.min.js') }}" defer></script>
    {{-- Mise à jour solde client après paiement par crédit (pages offer-reservation, etc.) --}}
    @auth
    <script defer src="{{ asset('js/wallet-balance-update.js') }}?v={{ file_exists(public_path('js/wallet-balance-update.js')) ? filemtime(public_path('js/wallet-balance-update.js')) : time() }}"></script>
    @endauth
    @stack('styles')
</head>
<body class="h-full bg-gray-50">
    <div id="app" class="h-full">
        @yield('content')
    </div>
    
    @stack('scripts')
</body>
</html>