@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">
                Créer un point de charge sur Steve
            </h1>

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-300 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="mb-4 p-4 bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 text-yellow-700 dark:text-yellow-300 rounded">
                    {{ session('warning') }}
                </div>
            @endif

            <form action="{{ route('charging-points.create-steve') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="chargeBoxId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Charge Box ID <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="chargeBoxId" 
                        name="chargeBoxId" 
                        value="{{ old('chargeBoxId') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-gray-100"
                        placeholder="Ex: CP-00123"
                    >
                    @error('chargeBoxId')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="endpointAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Adresse du point de terminaison <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="endpointAddress" 
                        name="endpointAddress" 
                        value="{{ old('endpointAddress') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-gray-100"
                        placeholder="Ex: 192.168.1.100"
                    >
                    @error('endpointAddress')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Notes (optionnel)
                    </label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-gray-100"
                        placeholder="Ex: Créé depuis Laravel"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-4">
                    <a 
                        href="{{ route('charging-points.index') }}" 
                        class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                    >
                        ← Retour à la liste
                    </a>
                    <button 
                        type="submit" 
                        class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors"
                    >
                        Créer le point de charge
                    </button>
                </div>
            </form>

            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-md">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    ℹ️ Informations
                </h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>• Le point de charge sera créé directement sur l'API Steve</li>
                    <li>• Il sera également enregistré dans la base de données locale</li>
                    <li>• L'endpoint utilisé : <code class="bg-gray-200 dark:bg-gray-800 px-1 rounded">POST /api/v1/chargePoints</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

