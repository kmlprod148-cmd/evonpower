@extends('layouts.app')

@section('title', 'Recharge Mobile Enhanced - En Cours')

@push('styles')
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #3b82f6;
            --secondary-dark: #2563eb;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --warning: #f59e0b;
            --warning-dark: #d97706;
            --success: #10b981;
            --success-dark: #059669;
            --credit-green: #22c55e;
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
            margin: 0;
            padding: 0;
        }

        .mobile-charging-container {
            max-width: 100%;
            margin: 0;
            padding: 16px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Mobile */
        .mobile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .mobile-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0 0 8px 0;
            text-align: center;
        }

        .mobile-header p {
            font-size: 14px;
            color: var(--gray-600);
            margin: 0;
            text-align: center;
        }

        /* Charging Status Card */
        .charging-status-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        /* Animation de chargement principale */
        .charging-animation {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 24px;
        }

        .charging-icon {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: pulse 2s infinite;
            box-shadow: 0 0 40px rgba(16, 185, 129, 0.4);
        }

        .charging-icon i {
            font-size: 48px;
            color: white;
            animation: bounce 1.5s infinite;
        }

        .charging-icon::before {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border: 3px solid transparent;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 2s linear infinite;
        }

        .charging-icon::after {
            content: '';
            position: absolute;
            top: -16px;
            left: -16px;
            right: -16px;
            bottom: -16px;
            border: 2px solid transparent;
            border-top: 2px solid rgba(16, 185, 129, 0.3);
            border-radius: 50%;
            animation: spin 3s linear infinite reverse;
        }

        /* Timer Display */
        .timer-display {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 2px solid #0ea5e9;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .timer-display h3 {
            font-size: 18px;
            font-weight: 600;
            color: #0369a1;
            margin: 0 0 12px 0;
        }

        .timer-value {
            font-size: 36px;
            font-weight: 700;
            color: #0c4a6e;
            font-family: 'Courier New', monospace;
            margin: 0;
        }

        /* Cost Display */
        .cost-display {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 2px solid var(--primary);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .cost-display h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0 0 12px 0;
        }

        .cost-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
        }

        /* Credit Balance */
        .credit-balance {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
        }

        .credit-balance h4 {
            font-size: 16px;
            font-weight: 600;
            color: #92400e;
            margin: 0 0 8px 0;
        }

        .credit-balance-value {
            font-size: 24px;
            font-weight: 700;
            color: #92400e;
            margin: 0;
        }

        /* Control Buttons */
        .control-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .btn-stop {
            flex: 1;
            background: linear-gradient(135deg, var(--danger), var(--danger-dark));
            color: white;
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
        }

        .btn-stop:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .btn-stop:active {
            transform: translateY(0);
        }

        .btn-pause {
            flex: 1;
            background: linear-gradient(135deg, var(--warning), var(--warning-dark));
            color: white;
            border: none;
            border-radius: 16px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
        }

        .btn-pause:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        /* Progress Bar */
        .progress-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 6px;
            transition: width 0.5s ease;
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        /* Status Indicators */
        .status-indicators {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .status-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }

        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            font-size: 18px;
            color: white;
        }

        .status-icon.active {
            background: linear-gradient(135deg, var(--success), var(--success-dark));
            animation: pulse 2s infinite;
        }

        .status-icon.inactive {
            background: #d1d5db;
        }

        .status-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-600);
            text-align: center;
        }

        /* Animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
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
            .mobile-charging-container {
                padding: 12px;
            }
            
            .charging-animation {
                width: 100px;
                height: 100px;
            }
            
            .charging-icon i {
                font-size: 40px;
            }
            
            .timer-value {
                font-size: 28px;
            }
            
            .cost-value {
                font-size: 28px;
            }
        }

        /* Toast Notifications */
        .toast-notification {
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            z-index: 1000;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }

        .toast-notification.show {
            transform: translateY(0);
        }
    </style>
@endpush

@section('content')
<div class="mobile-charging-container">
    <!-- Header -->
    <div class="mobile-header">
        <h1>🔋 Recharge en Cours</h1>
        <p>Votre véhicule se recharge actuellement</p>
    </div>

    <!-- Charging Status -->
    <div class="charging-status-card">
        <div class="charging-animation">
            <div class="charging-icon">
                <i class="fas fa-bolt"></i>
            </div>
        </div>
        <h2 style="font-size: 20px; font-weight: 600; color: var(--gray-800); margin: 0 0 8px 0;">
            Recharge Active
        </h2>
        <p style="font-size: 14px; color: var(--gray-600); margin: 0;">
            {{ $chargingPoint->name }} • {{ $chargingPoint->power_level }}kW
        </p>
    </div>

    <!-- Timer Display -->
    <div class="timer-display">
        <h3>⏱️ Temps Écoulé</h3>
        <p class="timer-value" id="timer">00:00:00</p>
    </div>

    <!-- Cost Display -->
    <div class="cost-display">
        <h3>💰 Coût Actuel</h3>
        <p class="cost-value" id="current-cost">0.00 EUR</p>
    </div>

    <!-- Credit Balance (if credit payment) -->
    @if($reservation->payment_type === 'credit')
    <div class="credit-balance">
        <h4>💳 Solde Restant</h4>
        <p class="credit-balance-value" id="credit-balance">{{ number_format($user->credit->balance ?? 0, 2) }} EUR</p>
    </div>
    @endif

    <!-- Progress Bar -->
    <div class="progress-container">
        <h4 style="font-size: 16px; font-weight: 600; color: var(--gray-800); margin: 0 0 12px 0; text-align: center;">
            Progression
        </h4>
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
        </div>
        <p style="font-size: 14px; color: var(--gray-600); margin: 0; text-align: center;">
            <span id="progress-text">0%</span> complété
        </p>
    </div>

    <!-- Status Indicators -->
    <div class="status-indicators">
        <div class="status-item">
            <div class="status-icon active">
                <i class="fas fa-plug"></i>
            </div>
            <span class="status-label">Connecté</span>
        </div>
        <div class="status-item">
            <div class="status-icon active">
                <i class="fas fa-bolt"></i>
            </div>
            <span class="status-label">En Charge</span>
        </div>
        <div class="status-item">
            <div class="status-icon active">
                <i class="fas fa-shield-alt"></i>
            </div>
            <span class="status-label">Sécurisé</span>
        </div>
    </div>

    <!-- Control Buttons -->
    <div class="control-buttons">
        <button class="btn-pause" onclick="pauseCharging()">
            <i class="fas fa-pause mr-2"></i>
            Pause
        </button>
        <button class="btn-stop" onclick="stopCharging()">
            <i class="fas fa-stop mr-2"></i>
            Arrêter
        </button>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<script>
    // Charging state
    let chargingState = {
        startTime: new Date('{{ $startTime }}'),
        isActive: true,
        isPaused: false,
        pausedTime: 0,
        ratePerMinute: {{ $ratePerMinute }},
        sessionId: '{{ $sessionId }}',
        reservationId: '{{ $reservationId }}'
    };

    // Timer functionality
    function updateTimer() {
        if (!chargingState.isActive || chargingState.isPaused) return;

        const now = new Date();
        const elapsed = now - chargingState.startTime - chargingState.pausedTime;
        const hours = Math.floor(elapsed / 3600000);
        const minutes = Math.floor((elapsed % 3600000) / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);

        const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        document.getElementById('timer').textContent = timeString;

        // Update cost
        const totalMinutes = elapsed / 60000;
        const currentCost = totalMinutes * chargingState.ratePerMinute;
        document.getElementById('current-cost').textContent = currentCost.toFixed(2) + ' EUR';

        // Update progress (assuming 30 minutes max duration)
        const maxDuration = 30; // minutes
        const progress = Math.min((totalMinutes / maxDuration) * 100, 100);
        document.getElementById('progress-fill').style.width = progress + '%';
        document.getElementById('progress-text').textContent = Math.round(progress) + '%';

        // Update credit balance if applicable
        @if($reservation->payment_type === 'credit')
        const userBalance = {{ $user->credit->balance ?? 0 }};
        const remainingBalance = userBalance - currentCost;
        document.getElementById('credit-balance').textContent = remainingBalance.toFixed(2) + ' EUR';
        
        // Change color if balance is low
        const creditElement = document.getElementById('credit-balance');
        if (remainingBalance < 10) {
            creditElement.style.color = '#dc2626';
        } else if (remainingBalance < 50) {
            creditElement.style.color = '#f59e0b';
        } else {
            creditElement.style.color = '#92400e';
        }
        @endif
    }

    // Pause charging
    function pauseCharging() {
        if (chargingState.isPaused) {
            // Resume
            chargingState.isPaused = false;
            chargingState.startTime = new Date();
            document.querySelector('.btn-pause').innerHTML = '<i class="fas fa-pause mr-2"></i>Pause';
            showToast('Recharge reprise', 'success');
        } else {
            // Pause
            chargingState.isPaused = true;
            chargingState.pausedTime += new Date() - chargingState.startTime;
            document.querySelector('.btn-pause').innerHTML = '<i class="fas fa-play mr-2"></i>Reprendre';
            showToast('Recharge mise en pause', 'warning');
        }
    }

    // Stop charging
    async function stopCharging() {
        if (!confirm('Êtes-vous sûr de vouloir arrêter la recharge ?')) {
            return;
        }

        const now = new Date();
        const elapsed = now - chargingState.startTime - chargingState.pausedTime;
        const totalMinutes = elapsed / 60000;
        const finalCost = totalMinutes * chargingState.ratePerMinute;

        try {
            showToast('Arrêt de la recharge en cours...', 'info');

            const response = await fetch('/api/charging/stop', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    session_id: chargingState.sessionId,
                    final_cost: finalCost,
                    duration_minutes: totalMinutes
                })
            });

            const result = await response.json();

            if (result.success) {
                showToast('Recharge arrêtée avec succès', 'success');
                setTimeout(() => {
                    window.location.href = '/charging/complete-mobile?cost=' + finalCost + '&duration=' + totalMinutes + '&power={{ $chargingPoint->power_level }}';
                }, 2000);
            } else {
                showToast(result.message || 'Erreur lors de l\'arrêt', 'error');
            }
        } catch (error) {
            console.error('Error stopping charge:', error);
            showToast('Erreur de connexion', 'error');
        }
    }

    // Toast notification system
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        toast.innerHTML = `
            <div class="${colors[type]} text-white p-4 rounded-xl shadow-lg">
                <div class="flex items-center">
                    <i class="${icons[type]} mr-3"></i>
                    <span class="font-medium">${message}</span>
                </div>
            </div>
        `;

        document.getElementById('toast-container').appendChild(toast);

        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Hide toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Start timer
        setInterval(updateTimer, 1000);
        updateTimer();

        // Add entrance animations
        const elements = document.querySelectorAll('.mobile-header, .charging-status-card, .timer-display, .cost-display, .credit-balance, .progress-container, .status-indicators, .control-buttons');
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'all 0.6s ease-out';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Handle page visibility change
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden, pause timer
            if (!chargingState.isPaused) {
                chargingState.isPaused = true;
                chargingState.pausedTime += new Date() - chargingState.startTime;
            }
        } else {
            // Page is visible, resume timer
            if (chargingState.isPaused) {
                chargingState.isPaused = false;
                chargingState.startTime = new Date();
            }
        }
    });
</script>
@endsection
