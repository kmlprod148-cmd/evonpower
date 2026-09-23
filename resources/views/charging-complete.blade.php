@extends('layouts.app')

@section('title', 'Recharge Terminée')

@push('styles')
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #3b82f6;
            --secondary-dark: #2563eb;
            --success: #10b981;
            --success-dark: #059669;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .complete-container {
            max-width: 480px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Animation de succès */
        .success-animation {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
        }

        .success-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
            animation: successPulse 2s infinite;
        }

        .success-circle::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--success), var(--primary), var(--success));
            z-index: -1;
            animation: rotate 3s linear infinite;
        }

        .success-icon {
            font-size: 48px;
            color: white;
            animation: checkmark 0.6s ease-in-out;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            margin-bottom: 20px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .card-body {
            padding: 20px;
        }

        /* Summary grid */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .summary-item {
            text-align: center;
            padding: 15px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--gray-200);
        }

        .summary-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .summary-label {
            font-size: 12px;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Cost breakdown */
        .cost-breakdown {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
        }

        .cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .cost-item:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 56px;
            width: 100%;
            margin-bottom: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--gray-500) 0%, var(--gray-600) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
        }

        /* Credit balance */
        .credit-balance {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: white;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            margin: 15px 0;
        }

        .credit-amount {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .credit-label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Animations */
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in {
            animation: slideIn 0.6s ease-out;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .complete-container {
                padding: 15px;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
@endpush

@section('content')
<div class="complete-container">
    <!-- Header avec animation de succès -->
    <div class="card slide-in">
        <div class="card-header">
            <div class="success-animation">
                <div class="success-circle">
                    <i class="fas fa-check success-icon"></i>
                </div>
            </div>
            <h1 class="text-2xl font-bold mb-2">Recharge Terminée</h1>
            <p class="opacity-90">Votre véhicule a été rechargé avec succès</p>
        </div>
    </div>

    <!-- Résumé de la session -->
    <div class="card slide-in">
        <div class="card-body">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Résumé de la Session</h2>
            
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value" id="session-id">{{ $sessionId ?? 'CH-' . rand(100000, 999999) }}</div>
                    <div class="summary-label">ID Session</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="duration">{{ $duration ?? '00:00' }}</div>
                    <div class="summary-label">Durée</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="energy-delivered">{{ $energyDelivered ?? '0' }} kWh</div>
                    <div class="summary-label">Énergie</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="final-cost">{{ number_format($finalCost ?? 0, 2) }} EUR</div>
                    <div class="summary-label">Coût Total</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Détail du coût -->
    <div class="card slide-in">
        <div class="card-body">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Détail du Coût</h3>
            
            <div class="cost-breakdown">
                <div class="cost-item">
                    <span>Tarif par minute:</span>
                    <span id="rate-per-minute">{{ number_format($ratePerMinute ?? 2.00, 2) }} EUR</span>
                </div>
                <div class="cost-item">
                    <span>Durée de recharge:</span>
                    <span id="charging-duration">{{ $duration ?? '00:00' }}</span>
                </div>
                <div class="cost-item">
                    <span>Coût total:</span>
                    <span id="total-cost">{{ number_format($finalCost ?? 0, 2) }} EUR</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Solde restant -->
    <div class="card slide-in">
        <div class="card-body">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Solde Restant</h3>
            
            <div class="credit-balance">
                <div class="credit-amount" id="remaining-balance">{{ number_format($remainingBalance ?? 0, 2) }} EUR</div>
                <div class="credit-label">Solde disponible</div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card slide-in">
        <div class="card-body">
            <a href="{{ route('transactions.history') }}" class="btn btn-primary">
                <i class="fas fa-history"></i>
                Voir l'Historique
            </a>
            <a href="{{ route('wallet.refill') }}" class="btn btn-secondary">
                <i class="fas fa-plus"></i>
                Recharger le Solde
            </a>
        </div>
    </div>
</div>

<script>
    // Animation d'entrée
    document.addEventListener('DOMContentLoaded', function() {
        // Ajouter un délai pour l'animation de succès
        setTimeout(() => {
            const successIcon = document.querySelector('.success-icon');
            if (successIcon) {
                successIcon.style.animation = 'checkmark 0.6s ease-in-out';
            }
        }, 500);

        // Confetti effect (optionnel)
        if (typeof confetti !== 'undefined') {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    });

    // Auto-redirect après 30 secondes (optionnel)
    setTimeout(() => {
        if (confirm('Voulez-vous retourner à l\'accueil ?')) {
            window.location.href = '/';
        }
    }, 30000);
</script>
@endsection
