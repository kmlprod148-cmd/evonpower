@extends('layouts.app')

@section('title', 'Paiement de la Réservation #' . $reservation->id)
@section('page-title', 'Paiement de la Réservation #' . $reservation->id)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Paiement de la Réservation #{{ $reservation->id }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            {{ $reservation->chargingPoint->name ?? 'Borne inconnue' }} - 
            {{ $reservation->start_time ? $reservation->start_time->format('d/m/Y H:i') : 'N/A' }}
        </p>
    </div>

    <!-- Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Reservation Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Résumé de la Réservation</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Point de charge</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $reservation->chargingPoint->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Date de début</p>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $reservation->start_time ? $reservation->start_time->format('d/m/Y H:i') : 'N/A' }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Montant à payer</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($estimatedCost, 2) }} EUR
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Votre solde actuel</p>
                <p class="text-lg font-semibold {{ $hasSufficientBalance ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $formattedBalance }}
                </p>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Choisissez votre méthode de paiement</h2>
        
        <div class="space-y-4">
            <!-- Payment with Wallet Balance -->
            @if($hasSufficientBalance)
            <form method="POST" action="{{ route('payment.reservations.pay.prepaid-credit', $reservation->id) }}" class="payment-method-card">
                @csrf
                <div class="border-2 border-green-500 rounded-lg p-4 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Payer avec mon solde</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Solde disponible: {{ $formattedBalance }}
                                </p>
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                            Payer maintenant
                        </button>
                    </div>
                </div>
            </form>
            @else
            <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50 opacity-60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-500 dark:text-gray-400">Payer avec mon solde</h3>
                            <p class="text-sm text-red-600 dark:text-red-400">
                                Solde insuffisant. Rechargez votre wallet ou payez par carte.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('credit-recharge.index') }}" class="px-6 py-2 bg-gray-400 text-white font-medium rounded-lg cursor-not-allowed">
                        Solde insuffisant
                    </a>
                </div>
            </div>
            @endif

            <!-- CMI Payment -->
            @if(isset($availableMethods['cmi']) && $availableMethods['cmi']['enabled'])
            <form method="POST" action="{{ route('payment.reservation.cmi', $reservation->id) }}" class="payment-method-card">
                @csrf
                <div class="border-2 border-blue-500 rounded-lg p-4 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">CMI (Maroc)</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Paiement sécurisé par carte bancaire via CMI
                                </p>
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            Payer avec CMI
                        </button>
                    </div>
                </div>
            </form>
            @endif

            <!-- Stripe Payment -->
            @if(isset($availableMethods['stripe']) && $availableMethods['stripe']['enabled'])
            <form method="POST" action="{{ route('payment.reservation.stripe', $reservation->id) }}" class="payment-method-card">
                @csrf
                <div class="border-2 border-purple-500 rounded-lg p-4 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Stripe (International)</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Paiement sécurisé par carte bancaire via Stripe
                                </p>
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors">
                            Payer avec Stripe
                        </button>
                    </div>
                </div>
            </form>
            @endif

            @if((!isset($availableMethods['cmi']) || !$availableMethods['cmi']['enabled']) && 
                (!isset($availableMethods['stripe']) || !$availableMethods['stripe']['enabled']) && 
                !$hasSufficientBalance)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-lg">
                <p class="text-yellow-700 dark:text-yellow-300">
                    <strong>Attention:</strong> Aucune méthode de paiement en ligne n'est disponible. 
                    Veuillez recharger votre wallet ou contacter le support.
                </p>
            </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between">
        <a href="{{ route('reservations.show', $reservation->id) }}" 
           class="px-6 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            Retour aux détails
        </a>
        @if(!$hasSufficientBalance)
        <a href="{{ route('credit-recharge.index') }}" 
           class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
            Recharger mon wallet
        </a>
        @endif
    </div>
</div>
@endsection

