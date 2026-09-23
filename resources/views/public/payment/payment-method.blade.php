@extends('layouts.guest')

@section('title', __('Choisir le paiement'))

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-lg mx-auto px-4">
        <!-- Progress -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-check text-xs"></i>
                </div>
                <span class="ml-2 text-sm font-medium text-green-600">{{ __('Informations') }}</span>
            </div>
            <div class="w-12 h-0.5 bg-green-400 mx-3"></div>
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center text-sm font-bold">2</div>
                <span class="ml-2 text-sm font-medium text-green-600">{{ __('Paiement') }}</span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h1 class="text-xl font-bold text-gray-900 mb-6">{{ __('Choisissez votre mode de paiement') }}</h1>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-4">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('public.payment.initiate', $chargingPoint->id) }}" class="space-y-4">
                @csrf

                <!-- Reservation type + value -->
                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-700">{{ __('Paramètres de recharge') }}</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Type') }}</label>
                            <select name="reservation_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="kwh">{{ __('kWh') }}</option>
                                <option value="minute">{{ __('Minutes') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Quantité') }}</label>
                            <input type="number" name="reservation_value" value="10" min="1" step="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Heure de début') }}</label>
                        <input type="time" name="start_time" value="{{ now()->format('H:i') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <input type="hidden" name="estimated_amount" value="0">
                    <input type="hidden" name="currency" value="{{ $chargingPoint->pricingPlan?->currency ?? 'EUR' }}">
                    <input type="hidden" name="pricing_plan_id" value="{{ $chargingPoint->pricingPlan?->id ?? '' }}">
                </div>

                <!-- Payment Methods -->
                <div class="space-y-3">
                    <label class="flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-400 transition-colors has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                        <input type="radio" name="payment_method" value="stripe" checked class="text-green-600">
                        <div class="flex items-center gap-3">
                            <i class="fab fa-stripe text-2xl text-blue-600"></i>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ __('Carte bancaire') }}</p>
                                <p class="text-xs text-gray-500">Visa, Mastercard, etc.</p>
                            </div>
                        </div>
                    </label>
                    <label class="flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-400 transition-colors has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                        <input type="radio" name="payment_method" value="cmi" class="text-green-600">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-university text-2xl text-orange-500"></i>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">CMI</p>
                                <p class="text-xs text-gray-500">{{ __('Paiement CMI (Maroc)') }}</p>
                            </div>
                        </div>
                    </label>
                </div>

                <button type="submit"
                        class="w-full px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                    <i class="fas fa-lock mr-2"></i>{{ __('Payer et démarrer') }}
                </button>
            </form>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('public.payment.customer', $chargingPoint->id) }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>
</div>
@endsection
