@extends('layouts.app')

@section('title', __('Demande de Retrait') . ' #' . $id)
@section('page-title', __('Détails Demande de Retrait'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-orange-100 dark:bg-orange-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-money-bill-wave text-2xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Demande #') }}{{ $id }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Détails de la demande de retrait') }}</p>
                </div>
            </div>
            <a href="{{ route('withdrawal-requests.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                {{ __('Retour') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="text-center py-8">
            <div class="bg-gray-100 dark:bg-gray-700 rounded-full p-6 inline-flex mb-4">
                <i class="fas fa-search text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Demande #') }}{{ $id }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Les détails de cette demande de retrait seront affichés ici.') }}
            </p>
            <a href="{{ route('withdrawal-requests.index') }}"
               class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-list mr-2"></i>
                {{ __('Voir toutes les demandes') }}
            </a>
        </div>
    </div>
</div>
@endsection
