<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur serveur - Evon Power</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="{{ asset("vendor/fontawesome/css/all.min.css") }}" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="w-24 h-24 mx-auto mb-6 bg-orange-100 rounded-full flex items-center justify-center">
            <i class="fas fa-server text-orange-500 text-3xl"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Erreur serveur</h1>
        <p class="text-lg text-gray-600 mb-8">{{ $message ?? 'Une erreur est survenue. Veuillez réessayer plus tard.' }}</p>
        <a href="javascript:history.back()" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition duration-200">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour
        </a>
    </div>
</body>
</html>
