@extends('layouts.app')

@section('title', 'Diagnostic API SteVe')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            Diagnostic API SteVe
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Vérifiez l'état de la connexion avec le serveur OCPP SteVe.
        </p>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">État de la connexion</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Configuration actuelle: {{ config('steve.api_url', 'Non configuré') }}</p>
            </div>
            <form action="{{ route('steve-api.diagnostic.test') }}" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Lancer le diagnostic
                </button>
            </form>
        </div>

        @if(isset($results))
            <div class="space-y-4">
                @foreach($results as $key => $result)
                    <div class="border rounded-md p-4 {{ $result['status'] === 'success' ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' : ($result['status'] === 'error' ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' : 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800') }}">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                @if($result['status'] === 'success')
                                    <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif($result['status'] === 'error')
                                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="ml-3 w-full">
                                <h3 class="text-sm font-medium {{ $result['status'] === 'success' ? 'text-green-800 dark:text-green-300' : ($result['status'] === 'error' ? 'text-red-800 dark:text-red-300' : 'text-yellow-800 dark:text-yellow-300') }}">
                                    {{ ucfirst(str_replace('_', ' ', $key)) }}
                                </h3>
                                <div class="mt-2 text-sm {{ $result['status'] === 'success' ? 'text-green-700 dark:text-green-400' : ($result['status'] === 'error' ? 'text-red-700 dark:text-red-400' : 'text-yellow-700 dark:text-yellow-400') }}">
                                    <p>{{ $result['message'] }}</p>
                                    @if(isset($result['http_code']))
                                        <p class="mt-1 text-xs opacity-75">Code HTTP: {{ $result['http_code'] }}</p>
                                    @endif
                                    @if(isset($result['note']))
                                        <p class="mt-1 text-xs opacity-75">Note: {{ $result['note'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>Cliquez sur le bouton pour lancer le diagnostic de connexion.</p>
            </div>
        @endif
    </div>

    <div class="flex justify-end">
        <a href="{{ route('steve-charging-points.index') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
            &larr; Retour aux points de charge SteVe
        </a>
    </div>
</div>
@endsection

