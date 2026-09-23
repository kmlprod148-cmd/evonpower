@extends('layouts.public')

@section('title', 'Service Temporairement Indisponible')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-50 flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 text-center">
        <!-- Icône d'erreur -->
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
        </div>

        <!-- Titre -->
        <h1 class="text-2xl font-bold text-gray-900 mb-4">
            Service Temporairement Indisponible
        </h1>

        <!-- Message -->
        <p class="text-gray-600 mb-6">
            {{ $message ?? 'Le serveur est temporairement indisponible. Veuillez réessayer dans quelques minutes.' }}
        </p>

        @if(isset($details))
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <p class="text-sm text-gray-500">{{ $details }}</p>
        </div>
        @endif

        <!-- Actions -->
        <div class="space-y-3">
            <button onclick="window.location.reload()" 
                    class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
                <i class="fas fa-redo mr-2"></i>
                Réessayer
            </button>
            
            <button onclick="window.history.back()" 
                    class="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </button>
        </div>

        <!-- Informations de contact -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500 mb-2">Besoin d'aide ?</p>
            <div class="flex justify-center space-x-4 text-sm">
                <a href="tel:+212522345678" class="text-indigo-600 hover:text-indigo-700">
                    <i class="fas fa-phone mr-1"></i>
                    +212 5 22 34 56 78
                </a>
                <a href="mailto:support@evonpower.com" class="text-indigo-600 hover:text-indigo-700">
                    <i class="fas fa-envelope mr-1"></i>
                    support@evonpower.com
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-retry après 30 secondes
setTimeout(() => {
    if (confirm('Voulez-vous réessayer maintenant ?')) {
        window.location.reload();
    }
}, 30000);
</script>
@endsection
