@extends('layouts.public')

@section('title', 'Méthodes de Paiement')

@push('styles')
<style>
    .payment-methods-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem;
    }

    .payment-method-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border: 2px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .payment-method-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        border-color: #3b82f6;
    }

    .payment-method-card.selected {
        border-color: #3b82f6;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .payment-method-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .payment-method-card.selected::before {
        transform: scaleX(1);
    }

    .payment-method-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .payment-method-logo {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .payment-method-info h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .payment-method-info p {
        color: #64748b;
        margin: 0.5rem 0 0 0;
        font-size: 0.9rem;
    }

    .payment-method-features {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .feature-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .continue-btn {
        width: 100%;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 2rem;
        position: relative;
        overflow: hidden;
    }

    .continue-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
    }

    .continue-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .loading-spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid #ffffff;
        border-top: 2px solid transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 0.5rem;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .selected-indicator {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 24px;
        height: 24px;
        background: #3b82f6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .payment-method-card.selected .selected-indicator {
        opacity: 1;
        transform: scale(1);
    }
</style>
@endpush

@section('content')
<div class="payment-methods-container">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Choisissez votre méthode de paiement</h1>
        <p class="text-gray-600">Sélectionnez une méthode de paiement sécurisée pour finaliser votre réservation</p>
                    </div>

    <form id="payment-method-form">
        <div id="payment-methods-list">
            <!-- Payment methods will be loaded here -->
</div>

        <button type="submit" id="continue-btn" class="continue-btn" disabled>
            <span class="loading-spinner"></span>
            <span class="btn-text">Continuer vers le paiement</span>
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payment-method-form');
    const continueBtn = document.getElementById('continue-btn');
    const loadingSpinner = document.querySelector('.loading-spinner');
    const btnText = document.querySelector('.btn-text');
let selectedMethod = null;

    // Load payment methods
    loadPaymentMethods();

    function loadPaymentMethods() {
        fetch('/api/payment-methods')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderPaymentMethods(data.payment_methods);
                }
            })
            .catch(error => {
                console.error('Error loading payment methods:', error);
                showError('Erreur lors du chargement des méthodes de paiement');
            });
    }

    function renderPaymentMethods(methods) {
        const container = document.getElementById('payment-methods-list');
        container.innerHTML = '';

        methods.forEach(method => {
            const methodCard = createMethodCard(method);
            container.appendChild(methodCard);
        });
    }

    function createMethodCard(method) {
        const card = document.createElement('div');
        card.className = 'payment-method-card';
        card.dataset.methodId = method.id;
        card.dataset.methodSlug = method.slug;

        const logo = getMethodLogo(method.provider);
        const features = getMethodFeatures(method.provider);

        card.innerHTML = `
            <div class="selected-indicator">✓</div>
            <div class="payment-method-header">
                <div class="payment-method-logo">${logo}</div>
                <div class="payment-method-info">
                    <h3>${method.name}</h3>
                    <p>Paiement sécurisé via ${method.provider}</p>
                </div>
            </div>
            <div class="payment-method-features">
                ${features.map(feature => `<span class="feature-badge">${feature}</span>`).join('')}
            </div>
        `;

        card.addEventListener('click', () => selectMethod(card, method));
        return card;
    }

    function getMethodLogo(provider) {
        const logos = {
            'CMI': 'CMI',
            'Stripe': 'S',
            'PayPal': 'PP',
            'default': '💳'
        };
        return logos[provider] || logos.default;
    }

    function getMethodFeatures(provider) {
        const features = {
            'CMI': ['Sécurisé', 'Rapide', 'Carte bancaire'],
            'Stripe': ['Sécurisé', 'International', 'Carte bancaire', 'Apple Pay'],
            'PayPal': ['Sécurisé', 'PayPal', 'Compte PayPal'],
            'default': ['Sécurisé', 'Paiement en ligne']
        };
        return features[provider] || features.default;
    }

    function selectMethod(card, method) {
        // Remove previous selection
        document.querySelectorAll('.payment-method-card').forEach(c => {
            c.classList.remove('selected');
        });

        // Select current method
        card.classList.add('selected');
        selectedMethod = method;
        continueBtn.disabled = false;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!selectedMethod) {
            showError('Veuillez sélectionner une méthode de paiement');
            return;
        }

        processPayment();
    });

    function processPayment() {
        showLoading(true);

        const reservationId = getReservationId(); // Get from URL or session
        
        fetch('/api/payments/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                reservation_id: reservationId,
                payment_method: selectedMethod.slug
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.redirect_data.redirect_url) {
                    // Redirect to payment gateway
                    window.location.href = data.redirect_data.redirect_url;
                } else if (data.redirect_data.client_secret) {
                    // Handle Stripe payment
                    handleStripePayment(data.redirect_data);
                }
            } else {
                showError(data.message || 'Erreur lors de la création du paiement');
            }
        })
        .catch(error => {
            console.error('Payment error:', error);
            showError('Erreur lors du traitement du paiement');
        })
        .finally(() => {
            showLoading(false);
        });
    }

    function handleStripePayment(data) {
        // Implement Stripe payment handling
        // This would typically involve Stripe.js
        console.log('Stripe payment:', data);
    }

    function getReservationId() {
        // Get reservation ID from URL parameters or session
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('reservation_id') || sessionStorage.getItem('reservation_id');
    }

    function showLoading(show) {
        if (show) {
            loadingSpinner.style.display = 'inline-block';
            btnText.textContent = 'Traitement...';
            continueBtn.disabled = true;
        } else {
            loadingSpinner.style.display = 'none';
            btnText.textContent = 'Continuer vers le paiement';
            continueBtn.disabled = false;
        }
    }

    function showError(message) {
        // Implement error display
        alert(message); // Replace with proper error display
    }
});
</script>
@endsection
