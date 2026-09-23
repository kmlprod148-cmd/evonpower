@extends('layouts.app')

@section('title', 'Recharge Enhanced - En Cours')

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

        .charging-container {
            max-width: 480px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Animation de chargement principale */
        .charging-animation {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
        }

        .charging-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
            animation: pulse 2s infinite;
        }

        .charging-circle::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--secondary), var(--primary));
            z-index: -1;
            animation: rotate 3s linear infinite;
        }

        .charging-icon {
            font-size: 48px;
            color: white;
            animation: bounce 1.5s infinite;
        }

        .charging-progress {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            overflow: hidden;
        }

        .charging-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 3px;
            animation: progress 3s ease-in-out infinite;
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .card-body {
            padding: 20px;
        }

        /* Status indicators */
        .status-active {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .status-pulse {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: pulse-dot 1.5s infinite;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .info-item {
            text-align: center;
            padding: 15px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--gray-200);
        }

        .info-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .info-label {
            font-size: 12px;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Timer */
        .timer-display {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
            text-align: center;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
        }

        /* Cost calculation */
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

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
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
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
            .charging-container {
                padding: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .timer-display {
                font-size: 28px;
            }
        }

        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid var(--gray-300);
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
@endpush

@section('content')
<div class="charging-container">
    <!-- Header avec animation de chargement -->
    <div class="card slide-in">
        <div class="card-header">
            <div class="charging-animation">
                <div class="charging-circle">
                    <i class="fas fa-bolt charging-icon"></i>
                </div>
                <div class="charging-progress">
                    <div class="charging-progress-bar"></div>
                </div>
            </div>
            <h1 class="text-2xl font-bold mb-2">Recharge en Cours</h1>
            <p class="opacity-90">Votre véhicule se recharge avec succès</p>
        </div>
    </div>

    <!-- Status et informations de session -->
    <div class="card slide-in">
        <div class="card-body">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Session Active</h2>
                <div class="status-active">
                    <div class="status-pulse"></div>
                    <span>En cours</span>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-value" id="session-id">{{ $sessionId ?? 'CH-' . rand(100000, 999999) }}</div>
                    <div class="info-label">ID Session</div>
                </div>
                <div class="info-item">
                    <div class="info-value" id="connector-type">{{ $connector->type ?? 'Type2' }}</div>
                    <div class="info-label">Connecteur</div>
                </div>
                <div class="info-item">
                    <div class="info-value" id="power-level">{{ $powerLevel ?? '22' }} kW</div>
                    <div class="info-label">Puissance</div>
                </div>
                <div class="info-item">
                    <div class="info-value" id="voltage">{{ $voltage ?? '400' }}V</div>
                    <div class="info-label">Tension</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timer et durée de recharge -->
    <div class="card slide-in">
        <div class="card-body">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Durée de Recharge</h3>
            <div class="timer-display" id="charging-timer">00:00:00</div>
            <div class="text-center text-gray-600 text-sm">
                <div>Début: <span id="start-time">{{ $startTime ?? now()->format('H:i') }}</span></div>
                <div>Durée estimée: <span id="estimated-duration">{{ $estimatedDuration ?? '30' }} min</span></div>
            </div>
        </div>
    </div>

    <!-- Solde crédit et calcul de coût -->
    <div class="card slide-in">
        <div class="card-body">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Paiement par Solde</h3>
            
            <!-- Solde actuel -->
            <div class="credit-balance">
                <div class="credit-amount" id="current-balance">{{ number_format($user->credit->balance ?? 0, 2) }} EUR</div>
                <div class="credit-label">Solde disponible</div>
            </div>

            <!-- Calcul de coût en temps réel -->
            <div class="cost-breakdown">
                <h4 class="font-semibold text-gray-900 mb-3">Calcul du coût</h4>
                <div class="cost-item">
                    <span>Tarif par minute:</span>
                    <span id="rate-per-minute">{{ number_format($ratePerMinute ?? 2.00, 2) }} EUR</span>
                </div>
                <div class="cost-item">
                    <span>Durée écoulée:</span>
                    <span id="elapsed-time">00:00</span>
                </div>
                <div class="cost-item">
                    <span>Coût actuel:</span>
                    <span id="current-cost">0.00 EUR</span>
                </div>
                <div class="cost-item">
                    <span>Coût estimé total:</span>
                    <span id="estimated-total-cost">0.00 EUR</span>
                </div>
            </div>

            <!-- Vérification du solde -->
            <div id="balance-warning" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 mt-3">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    <span class="text-red-800 text-sm">
                        <strong>Attention:</strong> Votre solde sera insuffisant pour terminer la recharge estimée.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="card slide-in">
        <div class="card-body">
            <button id="stop-charging-btn" class="btn btn-danger" onclick="stopCharging()">
                <i class="fas fa-stop"></i>
                Arrêter la Recharge
            </button>
            <button id="view-details-btn" class="btn btn-secondary" onclick="viewDetails()">
                <i class="fas fa-info-circle"></i>
                Voir les Détails
            </button>
        </div>
    </div>

    <!-- Messages et notifications -->
    <div id="messages" class="fixed top-4 right-4 z-50 space-y-2"></div>
</div>

<script>
    // État de la recharge
    const chargingState = {
        isActive: true,
        startTime: new Date('{{ $startTime ?? now() }}'),
        currentTime: new Date(),
        ratePerMinute: {{ $ratePerMinute ?? 2.00 }},
        userBalance: {{ $user->credit->balance ?? 0 }},
        estimatedDuration: {{ $estimatedDuration ?? 30 }}, // en minutes
        sessionId: '{{ $sessionId ?? "CH-" . rand(100000, 999999) }}'
    };

    // Éléments DOM
    const elements = {
        timer: document.getElementById('charging-timer'),
        elapsedTime: document.getElementById('elapsed-time'),
        currentCost: document.getElementById('current-cost'),
        estimatedTotalCost: document.getElementById('estimated-total-cost'),
        currentBalance: document.getElementById('current-balance'),
        balanceWarning: document.getElementById('balance-warning'),
        stopBtn: document.getElementById('stop-charging-btn'),
        messages: document.getElementById('messages')
    };

    // Timer principal
    let chargingTimer;
    let costUpdateInterval;

    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        startChargingTimer();
        startCostCalculation();
        checkBalanceSufficiency();
        
        // Mise à jour du solde toutes les 30 secondes
        setInterval(updateCreditBalance, 30000);
    });

    // Démarrer le timer de recharge
    function startChargingTimer() {
        chargingTimer = setInterval(updateTimer, 1000);
        updateTimer(); // Appel immédiat
    }

    // Mettre à jour le timer
    function updateTimer() {
        const now = new Date();
        const elapsed = now - chargingState.startTime;
        
        const hours = Math.floor(elapsed / (1000 * 60 * 60));
        const minutes = Math.floor((elapsed % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((elapsed % (1000 * 60)) / 1000);
        
        const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        const elapsedString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        elements.timer.textContent = timeString;
        elements.elapsedTime.textContent = elapsedString;
        
        chargingState.currentTime = now;
    }

    // Démarrer le calcul de coût
    function startCostCalculation() {
        costUpdateInterval = setInterval(calculateCost, 1000);
        calculateCost(); // Appel immédiat
    }

    // Calculer le coût en temps réel
    function calculateCost() {
        const now = new Date();
        const elapsed = now - chargingState.startTime;
        const elapsedMinutes = elapsed / (1000 * 60);
        
        const currentCost = elapsedMinutes * chargingState.ratePerMinute;
        const estimatedTotalCost = chargingState.estimatedDuration * chargingState.ratePerMinute;
        
        elements.currentCost.textContent = `${currentCost.toFixed(2)} EUR`;
        elements.estimatedTotalCost.textContent = `${estimatedTotalCost.toFixed(2)} EUR`;
        
        // Vérifier si le solde est suffisant
        checkBalanceSufficiency();
    }

    // Vérifier la suffisance du solde
    function checkBalanceSufficiency() {
        const estimatedTotalCost = chargingState.estimatedDuration * chargingState.ratePerMinute;
        
        if (estimatedTotalCost > chargingState.userBalance) {
            elements.balanceWarning.classList.remove('hidden');
            elements.stopBtn.style.background = 'linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%)';
            elements.stopBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Arrêter (Solde insuffisant)';
        } else {
            elements.balanceWarning.classList.add('hidden');
            elements.stopBtn.style.background = 'linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%)';
            elements.stopBtn.innerHTML = '<i class="fas fa-stop"></i> Arrêter la Recharge';
        }
    }

    // Mettre à jour le solde de crédit
    function updateCreditBalance() {
        fetch('/api/credit/balance')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    chargingState.userBalance = data.balance;
                    elements.currentBalance.textContent = `${data.formatted_balance}`;
                    checkBalanceSufficiency();
                }
            })
            .catch(error => {
                console.error('Erreur lors de la mise à jour du solde:', error);
            });
    }

    // Arrêter la recharge
    function stopCharging() {
        if (!confirm('Êtes-vous sûr de vouloir arrêter la recharge ?')) {
            return;
        }

        // Afficher l'état de chargement
        elements.stopBtn.classList.add('loading');
        elements.stopBtn.disabled = true;

        // Calculer le coût final
        const now = new Date();
        const elapsed = now - chargingState.startTime;
        const elapsedMinutes = elapsed / (1000 * 60);
        const finalCost = elapsedMinutes * chargingState.ratePerMinute;

        // Arrêter les timers
        clearInterval(chargingTimer);
        clearInterval(costUpdateInterval);

        // Envoyer la requête d'arrêt
        fetch('/api/charging/stop', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                session_id: chargingState.sessionId,
                final_cost: finalCost,
                duration_minutes: elapsedMinutes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Recharge arrêtée avec succès', 'success');
                
                // Rediriger vers la page de confirmation
                setTimeout(() => {
                    window.location.href = `/charging/complete?session=${chargingState.sessionId}&cost=${finalCost.toFixed(2)}`;
                }, 2000);
            } else {
                showMessage('Erreur lors de l\'arrêt de la recharge', 'error');
                elements.stopBtn.classList.remove('loading');
                elements.stopBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage('Erreur de connexion', 'error');
            elements.stopBtn.classList.remove('loading');
            elements.stopBtn.disabled = false;
        });
    }

    // Voir les détails
    function viewDetails() {
        const details = {
            sessionId: chargingState.sessionId,
            startTime: chargingState.startTime.toLocaleString(),
            currentTime: new Date().toLocaleString(),
            elapsedMinutes: Math.floor((new Date() - chargingState.startTime) / (1000 * 60)),
            currentCost: parseFloat(elements.currentCost.textContent),
            userBalance: chargingState.userBalance,
            ratePerMinute: chargingState.ratePerMinute
        };

        alert(`Détails de la session:
ID: ${details.sessionId}
Début: ${details.startTime}
Durée écoulée: ${details.elapsedMinutes} minutes
Coût actuel: ${details.currentCost} EUR
Solde disponible: ${details.userBalance} EUR
Tarif: ${details.ratePerMinute} EUR/min`);
    }

    // Afficher un message
    function showMessage(text, type = 'info') {
        const message = document.createElement('div');
        message.className = `p-4 rounded-lg shadow-lg max-w-sm ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            type === 'warning' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        
        message.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
                <span>${text}</span>
            </div>
        `;
        
        elements.messages.appendChild(message);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            message.remove();
        }, 5000);
    }

    // Gestion des erreurs
    window.addEventListener('error', function(e) {
        console.error('Erreur JavaScript:', e.error);
        showMessage('Une erreur est survenue', 'error');
    });

    // Gestion de la perte de connexion
    window.addEventListener('online', function() {
        showMessage('Connexion rétablie', 'success');
        updateCreditBalance();
    });

    window.addEventListener('offline', function() {
        showMessage('Connexion perdue', 'warning');
    });
</script>
@endsection
