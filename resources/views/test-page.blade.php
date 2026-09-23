@extends("layouts.app")

@section("title", "Test Page")

@section("content")
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Test de page</h1>
            <p class="text-gray-600 mb-4">Si vous voyez cette page, le layout fonctionne correctement.</p>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-800 font-medium">✅ Page de test fonctionnelle</p>
                <p class="text-green-700 text-sm">Le problème n'est pas dans le layout de base.</p>
            </div>
        </div>
    </div>
</div>
@endsection