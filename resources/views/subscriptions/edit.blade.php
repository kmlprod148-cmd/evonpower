@extends('layouts.app')
@section('title', __('Abonnement'))
@section('page-title', __('Abonnement'))
@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Abonnement') }}</h1>
            <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
        <i class="fas fa-credit-card text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Fonctionnalité bientôt disponible.') }}</p>
    </div>
</div>
@endsection
