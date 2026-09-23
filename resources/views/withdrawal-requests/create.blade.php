@extends('layouts.app')

@section('title', __('Nouvelle Demande de Retrait'))
@section('page-title', __('Nouvelle Demande de Retrait'))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-orange-100 dark:bg-orange-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-money-bill-wave text-2xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Nouvelle Demande de Retrait') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Soumettez une demande de retrait de vos fonds') }}</p>
                </div>
            </div>
            <a href="{{ route('withdrawal-requests.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                {{ __('Retour') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach ($errors->all() as $error)
                    <li><i class="fas fa-exclamation-circle mr-1"></i> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('withdrawal-requests.store') }}" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-info-circle text-primary-500 mr-2"></i>
                {{ __('Détails du Retrait') }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Montant') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="amount" id="amount" value="{{ old('amount') }}"
                               step="0.01" min="10" required
                               class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 pr-16">
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400">EUR</span>
                    </div>
                    @error('amount')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Notes') }}
                    </label>
                    <textarea name="notes" id="notes" rows="3"
                              class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                              placeholder="{{ __('Raison du retrait (optionnel)') }}">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-university text-indigo-500 mr-2"></i>
                {{ __('Coordonnées Bancaires') }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Banque') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" required
                           class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label for="account_holder" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Titulaire') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="account_holder" id="account_holder" value="{{ old('account_holder') }}" required
                           class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div class="sm:col-span-2">
                    <label for="iban" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('IBAN') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="iban" id="iban" value="{{ old('iban') }}" required
                           class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono"
                           placeholder="FR76 XXXX XXXX XXXX XXXX XXXX XXX">
                </div>
                <div>
                    <label for="swift" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('BIC/SWIFT') }}
                    </label>
                    <input type="text" name="swift" id="swift" value="{{ old('swift') }}"
                           class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500 font-mono">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('withdrawal-requests.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                {{ __('Annuler') }}
            </a>
            <button type="submit"
                    class="inline-flex items-center px-6 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                <i class="fas fa-paper-plane mr-2"></i>
                {{ __('Soumettre la demande') }}
            </button>
        </div>
    </form>
</div>
@endsection
