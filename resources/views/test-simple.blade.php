@extends('layouts.test-simple')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">🎨 Test du Design Restauré</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-900 mb-2">✅ Header</h3>
                    <p class="text-blue-700 text-sm">Le header avec logo de marque est visible en haut</p>
                </div>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="font-semibold text-green-900 mb-2">✅ Sidebar</h3>
                    <p class="text-green-700 text-sm">La sidebar avec navigation est fonctionnelle</p>
                </div>
                
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="font-semibold text-purple-900 mb-2">✅ Marque</h3>
                    <p class="text-purple-700 text-sm">L'intégration de marque fonctionne correctement</p>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <h2 class="text-lg font-semibold mb-3">📋 Informations de Test</h2>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li><strong>Nom de marque:</strong> {{ \App\Helpers\Brand::name() }}</li>
                    <li><strong>Logo:</strong> {{ \App\Helpers\Brand::logo() }}</li>
                    <li><strong>Favicon:</strong> {{ \App\Helpers\Brand::favicon() }}</li>
                    <li><strong>Couleur primaire:</strong> {{ \App\Helpers\Brand::primaryColor() }}</li>
                    <li><strong>Couleur secondaire:</strong> {{ \App\Helpers\Brand::secondaryColor() }}</li>
                </ul>
            </div>
            
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-yellow-900 mb-2">🎯 Prochaines Étapes</h2>
                <p class="text-yellow-800 text-sm">
                    Le design de base fonctionne ! Vous pouvez maintenant utiliser les layouts complets :
                </p>
                <ul class="mt-2 text-sm text-yellow-800">
                    <li>• <code class="bg-yellow-100 px-1 rounded">@extends('layouts.modern')</code> - Layout moderne complet</li>
                    <li>• <code class="bg-yellow-100 px-1 rounded">@extends('layouts.sidebar')</code> - Layout avec sidebar</li>
                    <li>• <code class="bg-yellow-100 px-1 rounded">@extends('layouts.app')</code> - Layout classique</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
