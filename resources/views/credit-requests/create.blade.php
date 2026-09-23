@extends('layouts.app')

@section('title', __('Nouvelle Demande de Crédit'))
@section('page-title', __('Nouvelle Demande de Crédit'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-hand-holding-usd text-2xl text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Demande de Crédit') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Demandez un remboursement ou un crédit à un propriétaire de borne') }}</p>
                </div>
            </div>
            <a href="{{ route('credit-requests.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-2xl">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('credit-requests.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Propriétaire') }} <span class="text-red-500">*</span></label>
                    <select name="owner_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">{{ __('Sélectionner un propriétaire') }}</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" {{ old('owner_id') == $owner->id ? 'selected' : '' }}>{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($recentReservations->count() > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Réservation concernée') }}</label>
                    <select name="reservation_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">{{ __('Aucune réservation spécifique') }}</option>
                        @foreach ($recentReservations as $res)
                            <option value="{{ $res->id }}" {{ old('reservation_id') == $res->id ? 'selected' : '' }}>
                                #{{ $res->id }} — {{ $res->chargingPoint?->name ?? '–' }} ({{ $res->created_at?->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Montant demandé (€)') }} <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" value="{{ old('amount') }}" required step="0.01" min="10" max="10000"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Minimum 10 €, maximum 10 000 €') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Motif') }} <span class="text-red-500">*</span></label>
                    <textarea name="reason" required rows="4" placeholder="{{ __('Expliquez la raison de votre demande...') }}"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('reason') }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-paper-plane mr-2"></i>{{ __('Envoyer la demande') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
