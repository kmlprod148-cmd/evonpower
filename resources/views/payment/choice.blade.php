@extends('layouts.app')

@section('title', 'Choisir une méthode de paiement')

@push('styles')
<style>
    .payment-option {
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid #e5e7eb;
    }
    
    .payment-option:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-color: #3b82f6;
    }
    
    .payment-option.selected {
        border-color: #3b82f6;
        background-color: #eff6ff;
    }
    
    .btn-primary {
        background: linear-gradient(to right, #3b82f6, #1d4ed8);
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Choisir une méthode de paiement</h1>
            
            <!-- Informations de la réservation -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Détails de la réservation</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-600">Type:</span>
                        <span class="font-semibold">{{ ucfirst($reservation->reservation_type) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Valeur:</span>
                        <span class="font-semibold">{{ $reservation->reservation_value }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Date:</span>
                        <span class="font-semibold">{{ $reservation->start_time->format('d/m/Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Coût total:</span>
                        <span class="font-semibold text-green-600">{{ number_format($transaction->price_total, 2) }} {{ $transaction->currency }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Méthodes de paiement -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Méthodes de paiement disponibles</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Stripe -->
                    <div class="payment-option p-6 rounded-lg" data-method="stripe">
                        <div class="flex items-center">
                            <i class="fab fa-stripe text-blue-600 text-3xl mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Stripe</h3>
                                <p class="text-gray-600">Carte bancaire sécurisée</p>
                                <div class="mt-2 text-sm text-gray-500">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    Paiement sécurisé SSL
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CMI -->
                    <div class="payment-option p-6 rounded-lg" data-method="cmi">
                        <div class="flex items-center">
                            <i class="fas fa-credit-card text-green-600 text-3xl mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">CMI</h3>
                                <p class="text-gray-600">Paiement en ligne sécurisé</p>
                                <div class="mt-2 text-sm text-gray-500">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    Paiement sécurisé SSL
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bouton de paiement -->
            <div class="text-center">
                <button id="pay-btn" class="btn-primary px-8 py-3 text-lg" disabled>
                    <i class="fas fa-credit-card mr-2"></i>
                    Payer maintenant
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de chargement -->
<div id="loading-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg p-8 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Traitement du paiement en cours...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let selectedPaymentMethod = null;

document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la sélection des méthodes de paiement
    document.querySelectorAll('.payment-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            selectedPaymentMethod = this.dataset.method;
            
            const payBtn = document.getElementById('pay-btn');
            payBtn.disabled = false;
        });
    });
    
    // Gestion du bouton de paiement
    document.getElementById('pay-btn').addEventListener('click', function() {
        if (selectedPaymentMethod === 'stripe') {
            processStripePayment();
        } else if (selectedPaymentMethod === 'cmi') {
            processCMIPayment();
        }
    });
});

function processStripePayment() {
    const loadingModal = document.getElementById('loading-modal');
    loadingModal.classList.remove('hidden');
    
    fetch(`/reservations/{{ $reservation->id }}/pay/stripe`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        loadingModal.classList.add('hidden');
        
        if (data.client_secret) {
            // Rediriger vers Stripe ou utiliser Stripe.js
            window.location.href = `/payment/stripe/redirect?client_secret=${data.client_secret}`;
        } else {
            alert('Erreur lors de l\'initialisation du paiement Stripe: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        loadingModal.classList.add('hidden');
        console.error('Erreur Stripe:', error);
        alert('Erreur lors du paiement Stripe');
    });
}

function processCMIPayment() {
    const loadingModal = document.getElementById('loading-modal');
    loadingModal.classList.remove('hidden');
    
    fetch(`/reservations/{{ $reservation->id }}/pay/cmi`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        loadingModal.classList.add('hidden');
        
        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            alert('Erreur lors de l\'initialisation du paiement CMI: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        loadingModal.classList.add('hidden');
        console.error('Erreur CMI:', error);
        alert('Erreur lors du paiement CMI');
    });
}
</script>
@endpush