<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - {{ config('app.name', 'EVON') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="{{ asset("vendor/fontawesome/css/all.min.css") }}" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="text-center max-w-md mx-auto px-4">
        <div class="w-24 h-24 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
            <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Erreur</h1>
        <p class="text-lg text-gray-600 mb-8">
            {{ $message ?? 'Une erreur est survenue. Veuillez réessayer plus tard.' }}
        </p>
        <div class="flex gap-4 justify-center">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition duration-200">
                <i class="fas fa-home mr-2"></i>
                Retour au tableau de bord
            </a>
            <button onclick="javascript:history.back()" class="inline-flex items-center px-6 py-3 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </button>
        </div>
        @if(config('app.debug') && isset($code))
            <div class="mt-8 p-4 bg-gray-100 rounded-lg text-left">
                <p class="text-sm text-gray-600">
                    <strong>Code d'erreur:</strong> {{ $code }}
                </p>
            </div>
        @endif
    </div>
</body>
</html>

