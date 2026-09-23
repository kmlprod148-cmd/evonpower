@extends('layouts.modern')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <h1 class="text-2xl font-bold mb-4">🎨 Test du Design Restauré</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Carte de test 1 -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">✅ Header Restauré</h3>
                    <p class="text-blue-100">Le header avec le logo de marque est maintenant visible en haut de la page.</p>
                </div>
                
                <!-- Carte de test 2 -->
                <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">✅ Sidebar Restaurée</h3>
                    <p class="text-green-100">La sidebar avec navigation et logo de marque est fonctionnelle.</p>
                </div>
                
                <!-- Carte de test 3 -->
                <div class="bg-gradient-to-r from-orange-500 to-red-600 rounded-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">✅ Design Responsive</h3>
                    <p class="text-orange-100">Le design s'adapte automatiquement aux différentes tailles d'écran.</p>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">📋 Fonctionnalités Testées</h2>
                <ul class="space-y-2">
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Logo de marque intégré dans le header
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Nom de marque personnalisable
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Sidebar rétractable avec bouton de contrôle
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Navigation mobile responsive
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Variables CSS de marque intégrées
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Support du mode sombre
                    </li>
                </ul>
            </div>
            
            <div class="mt-8 bg-blue-50 dark:bg-blue-900 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-blue-900 dark:text-blue-100">🎯 Instructions d'Utilisation</h2>
                <div class="text-blue-800 dark:text-blue-200">
                    <p class="mb-2"><strong>Pour utiliser ce layout dans vos vues :</strong></p>
                    <code class="bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded text-sm">@extends('layouts.modern')</code>
                    <p class="mt-2">ou</p>
                    <code class="bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded text-sm">@extends('layouts.sidebar')</code>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
