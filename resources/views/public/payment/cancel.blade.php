@extends('layouts.guest')

@section('title', __('Paiement annulé'))

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-lg mx-auto px-4 text-center">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-times text-4xl text-red-500"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Paiement annulé') }}</h1>
            <p class="text-gray-500 mb-8">{{ __('Votre paiement a été annulé. Aucun montant n\'a été débité.') }}</p>

            <a href="{{ route('public.payment.station', $chargingPoint->id) }}"
               class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                <i class="fas fa-redo mr-2"></i>{{ __('Réessayer') }}
            </a>
        </div>
    </div>
</div>
@endsection
