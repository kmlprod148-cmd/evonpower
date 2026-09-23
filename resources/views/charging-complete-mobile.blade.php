@extends('layouts.app')

@section('title', 'Recharge Terminée - Mobile')

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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
        }

        .mobile-complete-container {
            max-width: 100%;
            margin: 0;
            padding: 16px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Success Animation */
        .success-animation {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 32px;
        }

        .success-icon {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #ffffff, #f0f9ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: successPulse 2s infinite;
            box-shadow: 0 0 40px rgba(255, 255, 255, 0.4);
        }

        .success-icon i {
            font-size: 48px;
            color: var(--success);
            animation: successBounce 1.5s infinite;
        }

        .success-icon::before {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border: 3px solid transparent;
            border-top: 3px solid var(--success);
            border-radius: 50%;
            animation: successSpin 2s linear infinite;
        }

        /* Completion Card */
        .completion-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 32px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .completion-card h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0 0 16px 0;
        }

        .completion-card p {
            font-size: 16px;
            color: var(--gray-600);
            margin: 0 0 24px 0;
        }

        /* Summary Card */
        .summary-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .summary-item:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 18px;
            color: var(--gray-800);
        }

        .summary-label {
            font-size: 14px;
            color: var(--gray-600);
        }

        .summary-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .summary-total {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 2px solid var(--success);
            border-radius: 16px;
            padding: 16px;
            margin-top: 16px;
        }

        .summary-total .summary-value {
            font-size: 24px;
            color: var(--success-dark);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: var(--gray-700);
            border: 2px solid var(--gray-300);
            border-radius: 16px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: white;
            border-color: var(--gray-400);
            transform: translateY(-2px);
        }

        /* Animations */
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes successBounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes successSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .mobile-complete-container {
                padding: 12px;
            }
            
            .success-animation {
                width: 100px;
                height: 100px;
            }
            
            .success-icon i {
                font-size: 40px;
            }
            
            .completion-card h1 {
                font-size: 24px;
            }
        }
    </style>
@endpush

@section('content')
<div class="mobile-complete-container">
    <!-- Success Animation -->
    <div class="success-animation">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
    </div>

    <!-- Completion Card -->
    <div class="completion-card">
        <h1>🎉 Recharge Terminée !</h1>
        <p>Votre véhicule a été rechargé avec succès. Merci d'avoir utilisé nos services.</p>
    </div>

    <!-- Summary Card -->
    <div class="summary-card">
        <h3 style="font-size: 18px; font-weight: 600; color: var(--gray-800); margin: 0 0 16px 0; text-align: center;">
            📊 Résumé de la Recharge
        </h3>
        
        <div class="summary-item">
            <span class="summary-label">⏱️ Durée</span>
            <span class="summary-value" id="duration">{{ request('duration', '30') }} minutes</span>
        </div>
        
        <div class="summary-item">
            <span class="summary-label">⚡ Puissance</span>
            <span class="summary-value">{{ request('power', '22') }}kW</span>
        </div>
        
        <div class="summary-item">
            <span class="summary-label">🔌 Connecteur</span>
            <span class="summary-value">Type 2</span>
        </div>
        
        <div class="summary-total">
            <div class="summary-item">
                <span class="summary-label">💰 Coût Total</span>
                <span class="summary-value" id="total-cost">{{ request('cost', '60.00') }} EUR</span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="/dashboard" class="btn-primary">
            <i class="fas fa-home"></i>
            Retour au Tableau de Bord
        </a>
        
        <a href="/transactions" class="btn-secondary">
            <i class="fas fa-receipt"></i>
            Voir l'Historique
        </a>
        
        <a href="/charging-points" class="btn-secondary">
            <i class="fas fa-map-marker-alt"></i>
            Trouver une Autre Borne
        </a>
    </div>
</div>

<script>
    // Initialize animations
    document.addEventListener('DOMContentLoaded', function() {
        // Add entrance animations
        const elements = document.querySelectorAll('.success-animation, .completion-card, .summary-card, .action-buttons');
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.6s ease-out';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 200);
        });

        // Add confetti effect
        setTimeout(() => {
            createConfetti();
        }, 1000);
    });

    // Simple confetti effect
    function createConfetti() {
        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
        const confettiCount = 50;

        for (let i = 0; i < confettiCount; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.borderRadius = '50%';
                confetti.style.pointerEvents = 'none';
                confetti.style.zIndex = '1000';
                confetti.style.animation = 'fall 3s linear forwards';

                document.body.appendChild(confetti);

                setTimeout(() => {
                    confetti.remove();
                }, 3000);
            }, i * 50);
        }

        // Add fall animation
        if (!document.getElementById('confetti-animation')) {
            const style = document.createElement('style');
            style.id = 'confetti-animation';
            style.textContent = `
                @keyframes fall {
                    0% {
                        transform: translateY(-100vh) rotate(0deg);
                        opacity: 1;
                    }
                    100% {
                        transform: translateY(100vh) rotate(360deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
</script>
@endsection
