@extends('layouts.app')

@section('page-title', 'Paiement en Ligne par Carte')

@push('head')
<script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                Paiement en Ligne par Carte
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Recharge de crédit #{{ $recharge->reference }} - {{ number_format($recharge->amount, 2) }} {{ $recharge->currency }}
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Formulaire de paiement -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 flex items-center space-x-2">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span>Informations de Carte</span>
                    </h2>

                    <!-- Formulaire Stripe Elements -->
                    <form id="payment-form">
                        <div id="card-element" class="mb-4 p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900">
                            <!-- Stripe Elements va créer les champs ici -->
                        </div>

                        <!-- Erreurs -->
                        <div id="card-errors" role="alert" class="mb-4 text-sm text-red-600 dark:text-red-400 hidden"></div>

                        <!-- Bouton de paiement -->
                        <button type="submit" id="submit-button" 
                                class="w-full py-3 px-6 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-green-500/30 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="button-text">Payer {{ number_format($recharge->amount, 2) }} {{ $recharge->currency }}</span>
                            <span id="spinner" class="hidden">
                                <svg class="animate-spin h-5 w-5 inline-block ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </form>

                    <!-- Sécurité -->
                    <div class="mt-6 flex items-center justify-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        <span>Paiement sécurisé avec 3D Secure</span>
                    </div>
                </div>
            </div>

            <!-- Résumé -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 sticky top-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Résumé</h3>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Référence</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">#{{ $recharge->reference }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Montant</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($recharge->amount, 2) }} {{ $recharge->currency }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Méthode</span>
                            <span class="font-medium text-green-600 dark:text-green-400">Carte en ligne</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">Total</span>
                            <span class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($recharge->amount, 2) }} {{ $recharge->currency }}</span>
                        </div>
                    </div>

                    <!-- Cartes acceptées -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Cartes acceptées :</p>
                        <div class="flex items-center space-x-2">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa" class="h-4">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/1200px-MasterCard_Logo.svg.png" alt="Mastercard" class="h-4">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/Amex_logo_2018.svg/1200px-Amex_logo_2018.svg.png" alt="American Express" class="h-4">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stripe = Stripe('{{ $publishableKey }}');
    const elements = stripe.elements();
    
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a',
            },
        },
    });

    cardElement.mount('#card-element');

    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');
    const cardErrors = document.getElementById('card-errors');

    // Gestion des erreurs de carte avec feedback amélioré
    cardElement.on('change', function(event) {
        if (event.error) {
            cardErrors.textContent = event.error.message;
            cardErrors.classList.remove('hidden');
            submitButton.disabled = true;
        } else {
            cardErrors.classList.add('hidden');
            // Vérifier si la carte est complète avant d'activer le bouton
            submitButton.disabled = !event.complete;
        }
    });

    // Gestion de l'état de chargement de la carte
    cardElement.on('ready', function() {
        console.log('Stripe Elements chargé');
    });

    // Gestion du focus
    cardElement.on('focus', function() {
        cardErrors.classList.add('hidden');
    });

    // Soumission du formulaire
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Désactiver le bouton et afficher le statut
        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        spinner.classList.remove('hidden');
        cardErrors.classList.add('hidden');
        
        // Afficher le message de statut
        const statusDiv = document.getElementById('payment-status');
        const statusMessage = document.getElementById('status-message');
        if (statusDiv && statusMessage) {
            statusDiv.classList.remove('hidden');
            statusMessage.textContent = 'Traitement du paiement...';
        }

        try {
            // Créer la méthode de paiement
            const { paymentMethod, error: pmError } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });

            if (pmError) {
                throw pmError;
            }

            // Envoyer au serveur pour confirmation
            const response = await fetch('{{ route("credit-recharge.online-card.process", $recharge->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    payment_method_id: paymentMethod.id,
                    payment_intent_id: '{{ $paymentIntentId }}',
                }),
            });

            const data = await response.json();

            if (data.requires_action) {
                // 3D Secure requis - confirmer avec le client_secret
                // Afficher un message informatif
                buttonText.textContent = 'Authentification 3D Secure en cours...';
                
                const { error: confirmError, paymentIntent } = await stripe.confirmCardPayment(data.client_secret);
                
                if (confirmError) {
                    throw confirmError;
                }

                // Si le paiement a réussi après 3D Secure
                if (paymentIntent && paymentIntent.status === 'succeeded') {
                    buttonText.textContent = 'Finalisation du paiement...';
                    
                    // Vérifier à nouveau avec le serveur
                    const verifyResponse = await fetch('{{ route("credit-recharge.online-card.process", $recharge->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            payment_method_id: paymentMethod.id,
                            payment_intent_id: '{{ $paymentIntentId }}',
                        }),
                    });

                    const verifyData = await verifyResponse.json();

                    if (verifyData.success) {
                        // Démarrer le polling pour s'assurer que le paiement est bien traité
                        startStatusPolling({{ $recharge->id }});
                        
                        // Rediriger après un court délai pour permettre au serveur de traiter
                        setTimeout(() => {
                            window.location.href = verifyData.redirect_url;
                        }, 1000);
                    } else {
                        throw new Error(verifyData.message || 'Erreur lors de la confirmation du paiement');
                    }
                } else {
                    // Si le statut n'est pas succeeded, démarrer le polling
                    if (paymentIntent && paymentIntent.status === 'processing') {
                        startStatusPolling({{ $recharge->id }});
                        buttonText.textContent = 'Traitement du paiement en cours...';
                    } else {
                        throw new Error('Le paiement n\'a pas pu être confirmé après 3D Secure');
                    }
                }
            } else if (data.success) {
                // Paiement réussi immédiatement
                buttonText.textContent = 'Paiement réussi ! Redirection...';
                
                // Démarrer le polling pour s'assurer que tout est bien traité
                startStatusPolling({{ $recharge->id }});
                
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 500);
            } else if (data.requires_payment_method && data.can_retry) {
                // Méthode de paiement refusée mais peut réessayer
                throw new Error(data.message || 'La méthode de paiement a été refusée. Veuillez essayer une autre carte.');
            } else {
                throw new Error(data.message || 'Erreur lors du paiement');
            }

        } catch (error) {
            // Gestion d'erreur améliorée avec détails
            let errorMessage = 'Une erreur est survenue lors du paiement.';
            
            if (error.message) {
                errorMessage = error.message;
            } else if (error.type) {
                switch(error.type) {
                    case 'card_error':
                        errorMessage = error.message || 'Erreur de carte. Veuillez vérifier vos informations.';
                        break;
                    case 'validation_error':
                        errorMessage = error.message || 'Erreur de validation. Veuillez vérifier vos informations.';
                        break;
                    case 'api_error':
                        errorMessage = 'Erreur technique. Veuillez réessayer dans quelques instants.';
                        break;
                    default:
                        errorMessage = error.message || 'Une erreur est survenue.';
                }
            }

            cardErrors.textContent = errorMessage;
            cardErrors.classList.remove('hidden');
            
            // Réactiver le bouton
            submitButton.disabled = false;
            buttonText.classList.remove('hidden');
            spinner.classList.add('hidden');

            // Log de l'erreur pour le débogage
            console.error('Erreur de paiement:', error);
        }
    });

    // Système de polling pour vérifier le statut si le paiement est en attente
    let pollingInterval = null;
    
    function startStatusPolling(rechargeId) {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }

        let attempts = 0;
        const maxAttempts = 30; // 30 tentatives = 1 minute (2 secondes par tentative)
        
        pollingInterval = setInterval(async () => {
            attempts++;
            
            if (attempts > maxAttempts) {
                clearInterval(pollingInterval);
                return;
            }

            try {
                const response = await fetch(`{{ route('credit-recharge.online-card.status', $recharge->id) }}`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success && data.is_completed) {
                    clearInterval(pollingInterval);
                    // Rediriger vers la page de succès
                    window.location.href = data.redirect_url || '{{ route('credit-recharge.online-card.success', $recharge->id) }}';
                } else if (data.success && data.recharge_status === 'failed') {
                    clearInterval(pollingInterval);
                    // Afficher l'erreur
                    cardErrors.textContent = 'Le paiement a échoué. Veuillez réessayer.';
                    cardErrors.classList.remove('hidden');
                    submitButton.disabled = false;
                    buttonText.classList.remove('hidden');
                    spinner.classList.add('hidden');
                }
            } catch (error) {
                console.error('Erreur lors de la vérification du statut:', error);
            }
        }, 2000); // Vérifier toutes les 2 secondes
    }

    // Nettoyer l'intervalle si l'utilisateur quitte la page
    window.addEventListener('beforeunload', function() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });
});
</script>
@endsection

