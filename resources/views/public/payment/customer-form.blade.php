@extends('layouts.guest')

@section('title', __('Vos Informations'))

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-lg mx-auto px-4">
        <!-- Progress -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center text-sm font-bold">1</div>
                <span class="ml-2 text-sm font-medium text-green-600">{{ __('Informations') }}</span>
            </div>
            <div class="w-12 h-0.5 bg-gray-300 mx-3"></div>
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-sm font-bold">2</div>
                <span class="ml-2 text-sm font-medium text-gray-500">{{ __('Paiement') }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h1 class="text-xl font-bold text-gray-900 mb-2">{{ __('Vos coordonnées') }}</h1>
            <p class="text-sm text-gray-500 mb-6">{{ __('Ces informations sont utilisées pour la confirmation de votre session.') }}</p>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-4">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('public.payment.customer.store', $chargingPoint->id) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Nom complet') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }} <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Téléphone') }}</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <button type="submit"
                        class="w-full px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                    {{ __('Continuer') }} <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('public.payment.station', $chargingPoint->id) }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>
</div>
@endsection
