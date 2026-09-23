@extends('layouts.guest')

@section('title', __('Paiement réussi'))

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-lg mx-auto px-4 text-center">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-4xl text-green-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Paiement réussi !') }}</h1>
            <p class="text-gray-500 mb-6">{{ __('Votre session de recharge est en cours de démarrage.') }}</p>

            <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('Station') }}</span>
                    <span class="font-medium text-gray-900">{{ $chargingPoint->name ?? '–' }}</span>
                </div>
                @if ($reservation)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('Réservation #') }}</span>
                    <span class="font-medium text-gray-900">{{ $reservation->id }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('Statut') }}</span>
                    <span class="font-medium text-green-600">{{ ucfirst($reservation->status ?? '–') }}</span>
                </div>
                @endif
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-700">{{ __('La borne va démarrer automatiquement dans quelques instants. Si elle ne démarre pas sous 60 secondes, contactez le support.') }}</p>
                </div>
            </div>

            <a href="{{ route('public.payment.station', $chargingPoint->id) }}"
               class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                <i class="fas fa-home mr-2"></i>{{ __('Retour à la station') }}
            </a>
        </div>
    </div>
</div>
@endsection
