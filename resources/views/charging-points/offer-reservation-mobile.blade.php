<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#10b981">
    <meta name="description" content="Réservez votre borne de recharge électrique en quelques clics">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Réservation - {{ $chargingPoint->name ?? 'Borne de Recharge' }}</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="{{ asset("vendor/fontawesome/css/all.min.css") }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}"></noscript>
    
    <style>
        /* ============================================
           MOBILE-FIRST RESPONSIVE DESIGN SYSTEM
           ============================================ */
        
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #3b82f6;
            --secondary-dark: #2563eb;
            --accent: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-900: #111827;
            --mobile-padding: 12px;
            --mobile-gap: 8px;
            --touch-target: 44px;
            --border-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.2);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: rgba(0,0,0,0.05);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-size: 14px;
            color: var(--gray-900);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* Container System */
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: var(--mobile-padding);
        }

        @media (min-width: 640px) {
            .container { max-width: 640px; }
        }

        @media (min-width: 768px) {
            .container { max-width: 768px; }
        }

        /* ============================================
           HEADER COMPONENT
           ============================================ */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 14px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 16px;
        }

        .header-content {
            flex: 1;
            text-align: center;
            width: 100%;
        }

        .header h1 {
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 6px 0;
            line-height: 1.3;
        }

        .header-address {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
            opacity: 0.95;
        }

        .qr-container {
            background: white;
            padding: 10px;
            border-radius: 10px;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
        }

        .qr-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* ============================================
           FLOATING COST CARD
           ============================================ */
        .cost-card {
            position: sticky;
            top: 8px;
            z-index: 50;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #a7f3d0;
            border-radius: var(--border-radius);
            padding: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 16px;
            backdrop-filter: blur(10px);
        }

        .cost-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cost-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .cost-amount {
            text-align: right;
        }

        .cost-total {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .cost-subtitle {
            font-size: 10px;
            color: var(--gray-600);
        }

        .cost-breakdown {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #d1fae5;
            font-size: 11px;
        }

        .cost-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .cost-toggle {
            text-align: center;
            margin-top: 8px;
        }

        .cost-toggle button {
            background: none;
            border: none;
            color: var(--primary-dark);
            font-size: 11px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .cost-toggle button:hover {
            background: rgba(16, 185, 129, 0.1);
        }

        /* ============================================
           CARD COMPONENT
           ============================================ */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 16px;
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border-bottom: 1px solid var(--gray-100);
        }

        .card-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .card-title-group h3 {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 2px 0;
            color: var(--gray-900);
        }

        .card-title-group p {
            font-size: 11px;
            margin: 0;
            color: var(--gray-600);
        }

        .card-body {
            padding: 14px;
        }

        /* ============================================
           PAYMENT METHODS
           ============================================ */
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .payment-option {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: var(--touch-target);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .payment-option:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .payment-option.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, #f0fdf4 0%, #d1fae5 100%);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 6px;
            font-size: 18px;
        }

        .payment-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        .payment-desc {
            font-size: 10px;
            color: var(--gray-600);
        }

        /* ============================================
           RESERVATION TYPE
           ============================================ */
        .reservation-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .reservation-option {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .reservation-option:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .reservation-option.selected {
            border-color: var(--secondary);
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .reservation-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .reservation-option.kwh .reservation-icon {
            color: var(--secondary);
        }

        .reservation-option.minute .reservation-icon {
            color: var(--primary);
        }

        .reservation-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .reservation-unit {
            font-size: 12px;
            color: var(--gray-600);
            margin-bottom: 6px;
        }

        .reservation-price {
            font-size: 11px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        /* ============================================
           DURATION BUTTONS
           ============================================ */
        .duration-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .duration-btn {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: var(--touch-target);
        }

        .duration-btn:hover {
            border-color: var(--secondary);
            transform: translateY(-1px);
        }

        .duration-btn.selected {
            border-color: var(--secondary);
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .duration-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .duration-label {
            font-size: 11px;
            color: var(--gray-600);
            margin-top: 2px;
        }

        /* ============================================
           FORM INPUTS
           ============================================ */
        .input-group {
            margin-bottom: 16px;
        }

        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .input-wrapper {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        input[type="number"],
        input[type="time"] {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            min-height: var(--touch-target);
            transition: all 0.2s;
        }

        input[type="number"]:focus,
        input[type="time"]:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-secondary {
            padding: 12px 16px;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            min-height: var(--touch-target);
            white-space: nowrap;
        }

        .btn-secondary:hover {
            background: var(--secondary-dark);
            transform: translateY(-1px);
        }

        .input-help {
            font-size: 10px;
            color: var(--gray-600);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .input-error {
            font-size: 10px;
            color: var(--error);
            margin-top: 6px;
            display: none;
            align-items: center;
            gap: 4px;
        }

        .input-error.show {
            display: flex;
        }

        /* ============================================
           INFO BOXES
           ============================================ */
        .info-box {
            padding: 12px;
            border-radius: 8px;
            font-size: 11px;
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .info-box.success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
        }

        .info-box.warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .info-box.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .info-box.info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }

        /* ============================================
           SUMMARY CARD
           ============================================ */
        .summary-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #93c5fd;
            border-radius: var(--border-radius);
            padding: 14px;
            margin-bottom: 16px;
        }

        .summary-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .summary-label {
            color: var(--gray-600);
        }

        .summary-value {
            font-weight: 600;
            color: var(--gray-900);
        }

        /* ============================================
           ACTION BUTTONS
           ============================================ */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }

        .btn-primary {
            flex: 1;
            padding: 14px 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
            min-height: var(--touch-target);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-payment {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
        }

        /* ============================================
           MESSAGES
           ============================================ */
        .message {
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 11px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideDown 0.3s ease;
        }

        .message.success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
        }

        .message.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================
           STATION INFO
           ============================================ */
        .station-info {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #93c5fd;
            border-radius: var(--border-radius);
            padding: 14px;
            margin-bottom: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            font-size: 11px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            color: var(--gray-600);
        }

        .info-value {
            font-weight: 600;
            color: var(--gray-900);
        }

        /* ============================================
           LOADING STATE
           ============================================ */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ============================================
           UTILITIES
           ============================================ */
        .hidden {
            display: none !important;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }

        /* ============================================
           RESPONSIVE BREAKPOINTS
           ============================================ */
        @media (min-width: 375px) {
            .duration-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 480px) {
            :root {
                --mobile-padding: 16px;
            }

            .header {
                flex-direction: row;
                justify-content: space-between;
                padding: 16px;
            }

            .header-content {
                text-align: left;
            }

            .header-address {
                justify-content: flex-start;
            }
        }

        @media (min-width: 640px) {
            body {
                font-size: 15px;
            }

            .header h1 {
                font-size: 20px;
            }

            .card-header {
                padding: 16px;
            }

            .card-body {
                padding: 16px;
            }
        }

        @media (max-width: 374px) {
            .payment-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .payment-grid .payment-option:last-child {
                grid-column: 1 / -1;
            }
        }

        /* ============================================
           ACCESSIBILITY
           ============================================ */
        *:focus-visible {
            outline: 2px solid var(--secondary);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ============================================
           TOUCH OPTIMIZATIONS
           ============================================ */
        @media (hover: none) and (pointer: coarse) {
            .payment-option:active,
            .reservation-option:active,
            .duration-btn:active,
            .btn-primary:active,
            .btn-secondary:active {
                transform: scale(0.97);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1>{{ $chargingPoint->name ?? 'Station de Recharge' }}</h1>
                <div class="header-address">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ $chargingPoint->address ?? 'Adresse non disponible' }}</span>
                </div>
            </div>
            <div class="qr-container">
                <img src="{{ $qrCode }}" alt="QR Code de la borne" loading="lazy" decoding="async">
            </div>
        </header>

        <!-- Messages -->
        <div id="messages"></div>

        <!-- Floating Cost Card -->
        <div class="cost-card">
            <div class="cost-header">
                <div class="cost-label">
                    <i class="fas fa-calculator"></i>
                    <span>Estimation</span>
                </div>
                <div class="cost-amount">
                    <div class="cost-total" id="total-cost">0.00 {{ $currency ?? 'EUR' }}</div>
                    <div class="cost-subtitle">Base</div>
                </div>
            </div>
            <div class="cost-breakdown hidden" id="cost-breakdown">
                <div class="cost-row">
                    <span>Base:</span>
                    <span id="base-amount">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="cost-row">
                    <span>Activation:</span>
                    <span>{{ number_format($pricingPlan->activation_fee ?? 2.50, 2) }} {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="cost-row">
                    <span>Sous-total (HT):</span>
                    <span id="subtotal">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="cost-row">
                    <span>TVA (20%):</span>
                    <span id="vat-amount">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
            </div>
            <div class="cost-toggle">
                <button onclick="toggleBreakdown()">
                    <i class="fas fa-chevron-down"></i>
                    <span id="toggle-text">Détails</span>
                </button>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon" style="background: #dcfce7; color: #059669;">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="card-title-group">
                    <h3>Méthode de Paiement</h3>
                    <p>Choisissez votre mode de paiement préféré</p>
                </div>
            </div>
            <div class="card-body">
                <div class="payment-grid">
                    @php
                        // Vérifier si l'utilisateur est un client (pas admin, integrator, operator, partner)
                        $isClient = false;
                        if (Auth::check()) {
                            $user = Auth::user();
                            $systemRoles = ['admin', 'integrator', 'operator', 'partner'];
                            $hasSystemRole = false;
                            foreach ($systemRoles as $role) {
                                if ($user->hasRole($role)) {
                                    $hasSystemRole = true;
                                    break;
                                }
                            }
                            $isClient = !$hasSystemRole;
                        }
                    @endphp
                    @if(auth()->check() && $isClient)
                    <div class="payment-option" onclick="selectPayment('credit')" data-method="credit">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-wallet" style="color: white;"></i>
                        </div>
                        <div class="payment-name">Solde</div>
                        <div class="payment-desc" id="user-balance-display-mobile">{{ number_format(auth()->user()->getOrCreateWallet()->balance ?? 0, 2) }} EUR</div>
                    </div>
                    @else
                    <div class="payment-option" onclick="showAuthRequired()" data-method="auth-required" style="opacity: 0.7;">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-user-shield" style="color: white;"></i>
                        </div>
                        <div class="payment-name">Solde</div>
                        <div class="payment-desc">Connexion requise</div>
                    </div>
                    @endif
                    <div class="payment-option" onclick="selectPayment('offline')" data-method="offline">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-cash-register" style="color: white;"></i>
                        </div>
                        <div class="payment-name">Sur Place</div>
                        <div class="payment-desc">Espèces/Carte</div>
                    </div>
                    <div class="payment-option" onclick="selectPayment('cmi')" data-method="cmi">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <i class="fas fa-university" style="color: white;"></i>
                        </div>
                        <div class="payment-name">CMI</div>
                        <div class="payment-desc">Sécurisé</div>
                    </div>
                    <div class="payment-option" onclick="selectPayment('stripe')" data-method="stripe">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <i class="fab fa-stripe" style="color: white;"></i>
                        </div>
                        <div class="payment-name">Stripe</div>
                        <div class="payment-desc">International</div>
                    </div>
                </div>
                
                <!-- Credit Balance Check -->
                <div id="credit-info-mobile" class="info-box success hidden" style="margin-top: 12px;">
                    <i class="fas fa-wallet"></i>
                    <div>
                        <strong>Solde disponible:</strong> <span id="available-balance-mobile">0.00 EUR</span><br>
                        <small>Le montant sera déduit de votre solde instantanément</small>
                    </div>
                </div>

                <!-- Insufficient Balance Warning -->
                <div id="insufficient-balance-mobile" class="info-box error hidden" style="margin-top: 12px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Solde insuffisant</strong><br>
                        <small>Votre solde: <span id="current-balance-mobile">0.00</span> EUR | Requis: <span id="required-amount-mobile">0.00</span> EUR</small>
                    </div>
                </div>
                
                <div class="info-box info" style="margin-top: 12px;">
                    <i class="fas fa-shield-alt"></i>
                    <span>Paiements sécurisés et cryptés</span>
                </div>
            </div>
        </div>

        <!-- Station Info -->
        <div class="station-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Puissance:</span>
                    <span class="info-value">{{ $chargingPoint->power ?? 'N/A' }} kW</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Partenaire:</span>
                    <span class="info-value">{{ $chargingPoint->partner->name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Plan tarifaire:</span>
                    <span class="info-value" style="color: #3b82f6;">{{ $pricingPlan->name ?? 'Standard' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Type:</span>
                    <span class="info-value">{{ ucfirst($pricingPlan->rate_type ?? 'Mixte') }}</span>
                </div>
            </div>
        </div>

        <!-- Reservation Type -->
        @if($showReservationType)
        <div class="card">
            <div class="card-header">
                <div class="card-icon" style="background: #dbeafe; color: #2563eb;">
                    <i class="fas fa-charging-station"></i>
                </div>
                <div class="card-title-group">
                    <h3>Type de Réservation</h3>
                    <p>Mode de facturation du plan</p>
                </div>
            </div>
            <div class="card-body">
                <div class="reservation-grid">
                    @if($reservationType === 'kwh' || $reservationType === 'mixed')
                    <div class="reservation-option kwh {{ $reservationType === 'kwh' ? 'selected' : '' }}" onclick="selectReservationType('kwh')" data-type="kwh">
                        <div class="reservation-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="reservation-title">Par Énergie</div>
                        <div class="reservation-unit">kWh</div>
                        <div class="reservation-price">{{ number_format($pricingPlan->price_per_kwh ?? 0.45, 2) }} {{ $currency ?? 'EUR' }}/kWh</div>
                    </div>
                    @endif
                    @if($reservationType === 'minute' || $reservationType === 'mixed')
                    <div class="reservation-option minute {{ $reservationType === 'minute' ? 'selected' : '' }}" onclick="selectReservationType('minute')" data-type="minute">
                        <div class="reservation-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="reservation-title">Par Durée</div>
                        <div class="reservation-unit">Minutes</div>
                        <div class="reservation-price">{{ number_format($pricingPlan->price_per_minute ?? 0.25, 2) }} {{ $currency ?? 'EUR' }}/min</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Duration Selection -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon" style="background: #f3e8ff; color: #7c3aed;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="card-title-group">
                    <h3>Durée ou Quantité</h3>
                    <p>Sélectionnez votre durée de charge</p>
                </div>
            </div>
            <div class="card-body">
                <div class="duration-grid">
                    <div class="duration-btn" onclick="selectDuration(10)" data-duration="10">
                        <div class="duration-value">10 min</div>
                        <div class="duration-label">Rapide</div>
                    </div>
                    <div class="duration-btn" onclick="selectDuration(20)" data-duration="20">
                        <div class="duration-value">20 min</div>
                        <div class="duration-label">Standard</div>
                    </div>
                    <div class="duration-btn" onclick="selectDuration(30)" data-duration="30">
                        <div class="duration-value">30 min</div>
                        <div class="duration-label">Complet</div>
                    </div>
                    <div class="duration-btn" onclick="selectDuration(40)" data-duration="40">
                        <div class="duration-value">40 min</div>
                        <div class="duration-label">Étendue</div>
                    </div>
                    <div class="duration-btn" onclick="selectDuration(50)" data-duration="50">
                        <div class="duration-value">50 min</div>
                        <div class="duration-label">Longue</div>
                    </div>
                    <div class="duration-btn" onclick="selectDuration(60)" data-duration="60">
                        <div class="duration-value">60 min</div>
                        <div class="duration-label">Maximale</div>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Ou saisissez une valeur personnalisée</label>
                    <div class="input-wrapper">
                        <input type="number" id="custom-value" placeholder="Ex: 15, 25..." min="10" max="480" step="10" oninput="handleCustomValue()">
                        <span style="color: var(--gray-600); font-size: 13px; font-weight: 600;">min</span>
                    </div>
                    <div class="input-help">
                        <i class="fas fa-info-circle"></i>
                        <span>Valeurs par intervalles de 10 minutes</span>
                    </div>
                    <div class="input-error" id="value-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="value-error-text"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Immediate Start Mode -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon" style="background: #dcfce7; color: #16a34a;">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="card-title-group">
                    <h3>Démarrage Immédiat</h3>
                    <p>Votre recharge commencera immédiatement après le paiement</p>
                </div>
            </div>
            <div class="card-body">
                <div class="info-box success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Mode de démarrage immédiat activé</strong><br>
                        La recharge commencera automatiquement via l'API Steve après validation du paiement
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon" style="background: #e0f2fe; color: #0ea5e9;">
                    <i class="fas fa-user"></i>
                </div>
                <div class="card-title-group">
                    <h3>Informations client</h3>
                    <p>Vos coordonnées pour la réservation</p>
                </div>
            </div>
            <div class="card-body">
                <form id="customer-info-form">
                    <div class="input-group">
                        <label class="input-label" for="customer_name">Nom Complet</label>
                        <div class="input-wrapper">
                            <input type="text" id="customer_name" name="customer_name" placeholder="Votre nom" value="{{ auth()->check() ? auth()->user()->name : '' }}">
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="customer_email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="customer_email" name="customer_email" placeholder="votre@email.com" value="{{ auth()->check() ? auth()->user()->email : '' }}">
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="customer_phone">Téléphone</label>
                        <div class="input-wrapper">
                            <input type="tel" id="customer_phone" name="customer_phone" placeholder="+212 6.." value="{{ auth()->check() ? auth()->user()->phone ?? '' : '' }}">
                        </div>
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="customer_address">Adresse Complète</label>
                        <div class="input-wrapper">
                            <input type="text" id="customer_address" name="customer_address" placeholder="Votre adresse">
                        </div>
                    </div>
                    @if(!auth()->check())
                    <div style="display: flex; align-items: center; margin-top: 12px; gap: 8px;">
                        <input type="checkbox" id="register_account" name="register_account" value="1" style="width: 18px; height: 18px; border-radius: 4px; border: 2px solid var(--gray-200);">
                        <label for="register_account" style="font-size: 12px; color: var(--gray-900); font-weight: 500;">
                            Créer un compte pour un accès plus rapide la prochaine fois
                        </label>
                    </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- Reservation Summary -->
        <div class="summary-card">
            <h4 class="summary-title">Résumé de la réservation</h4>
            <div class="summary-row">
                <span class="summary-label">Type:</span>
                <span class="summary-value" id="summary-type">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Durée/Quantité:</span>
                <span class="summary-value" id="summary-value">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Heure de début:</span>
                <span class="summary-value" id="summary-time">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Montant estimé:</span>
                <span class="summary-value" style="color: #059669;" id="summary-amount">0.00 {{ $currency ?? 'EUR' }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Statut:</span>
                <span class="summary-value" style="color: #d97706;">En attente</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-primary" id="reserve-btn" onclick="submitReservation()" disabled>
                <i class="fas fa-check"></i>
                Confirmer la Réservation
            </button>
            <button class="btn-primary btn-payment" id="checkout-btn" onclick="processPayment()" disabled>
                <i class="fas fa-credit-card"></i>
                Payer Maintenant
            </button>
        </div>


        <!-- Module Crédit (Admin/Intégrateur uniquement) -->
        @if(isset($creditData) && $creditData)
        <div class="card" style="margin-top: 16px;">
            <div class="card-header" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white;">
                <div class="card-icon" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="card-title-group">
                    <h3 style="color: white;">Gestion des Crédits</h3>
                    <p style="color: rgba(255,255,255,0.9);">Ajouter du crédit et consulter l'historique</p>
                </div>
            </div>

            <!-- Onglets -->
            <div style="border-bottom: 1px solid var(--gray-200); display: flex;">
                <button onclick="switchCreditTab('add')" id="credit-tab-add-mobile" class="credit-tab-button active" style="flex: 1; padding: 12px; border: none; background: none; border-bottom: 2px solid #8b5cf6; color: #8b5cf6; font-weight: 600; font-size: 12px;">
                    <i class="fas fa-plus-circle"></i> Ajouter
                </button>
                <button onclick="switchCreditTab('history')" id="credit-tab-history-mobile" class="credit-tab-button" style="flex: 1; padding: 12px; border: none; background: none; border-bottom: 2px solid transparent; color: var(--gray-600); font-weight: 600; font-size: 12px;">
                    <i class="fas fa-history"></i> Historique
                </button>
            </div>

            <!-- Contenu onglet "Ajouter du crédit" -->
            <div id="credit-tab-content-add-mobile" class="credit-tab-content" style="padding: 14px;">
                <form id="add-credit-form-offer-mobile" style="display: flex; flex-direction: column; gap: 12px;">
                    @csrf
                    
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--gray-900); margin-bottom: 6px;">
                            Utilisateur <span style="color: #ef4444;">*</span>
                        </label>
                        <select name="user_id" id="credit_user_id_mobile" required
                            style="width: 100%; padding: 10px; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 13px;">
                            <option value="">Sélectionnez un utilisateur</option>
                            @foreach($creditData['users'] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--gray-900); margin-bottom: 6px;">
                                Montant (EUR) <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="number" name="amount" id="credit_amount_mobile" step="0.01" min="0.01" required
                                style="width: 100%; padding: 10px; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 13px;"
                                placeholder="0.00">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: var(--gray-900); margin-bottom: 6px;">
                                Type <span style="color: #ef4444;">*</span>
                            </label>
                            <select name="type" id="credit_type_mobile" required
                                style="width: 100%; padding: 10px; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 13px;">
                                <option value="manuel">Manuel</option>
                                <option value="bonus">Bonus</option>
                                <option value="automatique">Automatique</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--gray-900); margin-bottom: 6px;">
                            Description/Commentaire
                        </label>
                        <textarea name="commentaire" id="credit_commentaire_mobile" rows="2"
                            style="width: 100%; padding: 10px; border: 2px solid var(--gray-200); border-radius: 8px; font-size: 13px; resize: vertical;"
                            placeholder="Description du crédit (optionnel)"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 8px;">
                        <button type="button" onclick="resetCreditFormMobile()"
                            style="flex: 1; padding: 10px; border: 2px solid var(--gray-200); border-radius: 8px; background: white; color: var(--gray-700); font-weight: 600; font-size: 12px;">
                            Réinitialiser
                        </button>
                        <button type="submit"
                            style="flex: 1; padding: 10px; background: #8b5cf6; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 12px;">
                            <i class="fas fa-check"></i> Ajouter
                        </button>
                    </div>
                </form>

                <!-- Message de résultat -->
                <div id="credit-form-result-mobile" style="margin-top: 12px; display: none;"></div>
            </div>

            <!-- Contenu onglet "Historique" -->
            <div id="credit-tab-content-history-mobile" class="credit-tab-content hidden" style="padding: 14px;">
                <!-- Statistiques -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px;">
                    <div style="background: #f0fdf4; padding: 10px; border-radius: 8px; text-align: center;">
                        <p style="font-size: 10px; color: var(--gray-600); margin: 0 0 4px 0;">Total crédits</p>
                        <p style="font-size: 14px; font-weight: 700; color: #059669; margin: 0;">
                            {{ number_format($creditData['statistics']['total_credits'] ?? 0, 2) }} EUR
                        </p>
                    </div>
                    <div style="background: #eff6ff; padding: 10px; border-radius: 8px; text-align: center;">
                        <p style="font-size: 10px; color: var(--gray-600); margin: 0 0 4px 0;">Transactions</p>
                        <p style="font-size: 14px; font-weight: 700; color: #2563eb; margin: 0;">
                            {{ $creditData['statistics']['total_transactions'] ?? 0 }}
                        </p>
                    </div>
                    <div style="background: #f3e8ff; padding: 10px; border-radius: 8px; text-align: center;">
                        <p style="font-size: 10px; color: var(--gray-600); margin: 0 0 4px 0;">Par type</p>
                        <div style="font-size: 9px; color: var(--gray-700);">
                            @foreach($creditData['statistics']['by_type'] ?? [] as $type => $total)
                                <div>{{ ucfirst($type) }}: {{ number_format($total, 2) }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Tableau de l'historique -->
                <div style="overflow-x: auto;">
                    <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                        <thead style="background: var(--gray-50);">
                            <tr>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: var(--gray-600);">Date</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: var(--gray-600);">Utilisateur</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: var(--gray-600);">Montant</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: var(--gray-600);">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($creditData['history']->items() as $transaction)
                                <tr style="border-top: 1px solid var(--gray-200);">
                                    <td style="padding: 8px; color: var(--gray-900);">
                                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td style="padding: 8px; color: var(--gray-900);">
                                        {{ $transaction->user->name ?? 'N/A' }}
                                    </td>
                                    <td style="padding: 8px; font-weight: 600; color: #059669;">
                                        +{{ number_format($transaction->amount, 2) }} EUR
                                    </td>
                                    <td style="padding: 8px;">
                                        <span style="padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600;
                                            @if($transaction->type == 'bonus') background: #fef3c7; color: #92400e;
                                            @elseif($transaction->type == 'automatique') background: #dbeafe; color: #1e40af;
                                            @else background: var(--gray-100); color: var(--gray-800);
                                            @endif">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding: 16px; text-align: center; color: var(--gray-500); font-size: 11px;">
                                        Aucune transaction de crédit trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($creditData['history']->hasPages())
                    <div style="margin-top: 12px; text-align: center;">
                        {{ $creditData['history']->links() }}
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <script>
        // State Management
        const state = {
            paymentMethod: null,
            reservationType: null,
            duration: null,
            startTime: null,
            userBalance: {{ auth()->check() && $isClient ? (float)(auth()->user()->wallet->balance ?? auth()->user()->getOrCreateWallet()->balance ?? 0) : 0 }},
            prices: {
                perKwh: {{ $pricingPlan->price_per_kwh ?? 0.45 }},
                perMinute: {{ $pricingPlan->price_per_minute ?? 0.25 }},
                activation: {{ $pricingPlan->activation_fee ?? 2.50 }},
                vatRate: 0.20
            }
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setCurrentTime();
            updateButtonStates();
            
            // Set default start time if not set
            if (!state.startTime) {
                state.startTime = 'immediate';
            }
            
            // Auto-select reservation type if only one option is available
            @if($showReservationType && $reservationType !== 'mixed')
            selectReservationType('{{ $reservationType }}');
            @endif

            // Load user balance if authenticated and is client (user, client, super_admin)
            @if(auth()->check() && $isClient)
            loadUserCreditBalance();
            @endif
        });

        // Payment Method Selection
        function selectPayment(method) {
            state.paymentMethod = method;
            
            // Update UI
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            document.querySelector(`[data-method="${method}"]`).classList.add('selected');
            
            // Check credit balance if credit payment selected
            if (method === 'credit') {
                checkCreditBalance();
            } else {
                hideCreditInfo();
            }
            
            showMessage('Méthode de paiement sélectionnée', 'success');
            updateButtonStates();
            updateSummary();
        }

        // Reservation Type Selection
        function selectReservationType(type) {
            state.reservationType = type;
            
            // Update UI
            document.querySelectorAll('.reservation-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            document.querySelector(`[data-type="${type}"]`).classList.add('selected');
            
            showMessage(`Facturation par ${type === 'kwh' ? 'énergie' : 'durée'} sélectionnée`, 'success');
            updateButtonStates();
            calculateCost();
            updateSummary();
        }

        // Duration Selection
        function selectDuration(duration) {
            state.duration = duration;
            
            // Update UI
            document.querySelectorAll('.duration-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            document.querySelector(`[data-duration="${duration}"]`).classList.add('selected');
            
            // Clear custom input
            document.getElementById('custom-value').value = '';
            
            showMessage(`${duration} minutes sélectionnées`, 'success');
            updateButtonStates();
            calculateCost();
            updateSummary();
        }

        // Custom Value Handler
        function handleCustomValue() {
            const input = document.getElementById('custom-value');
            const value = parseInt(input.value);
            const errorDiv = document.getElementById('value-error');
            const errorText = document.getElementById('value-error-text');
            
            // Clear duration buttons
            document.querySelectorAll('.duration-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            if (value && (value < 10 || value > 480 || value % 10 !== 0)) {
                errorText.textContent = 'Valeur entre 10 et 480 minutes, par intervalles de 10';
                errorDiv.classList.add('show');
                state.duration = null;
            } else if (value) {
                errorDiv.classList.remove('show');
                state.duration = value;
                showMessage(`${value} minutes sélectionnées`, 'success');
                calculateCost();
                updateSummary();
            } else {
                errorDiv.classList.remove('show');
                state.duration = null;
            }
            
            updateButtonStates();
        }

        // Time Functions
        function setCurrentTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const timeInput = document.getElementById('start-time');
            
            // Vérifier que l'élément existe avant de le modifier
            if (timeInput) {
                timeInput.value = `${hours}:${minutes}`;
                state.startTime = timeInput.value;
                validateTime();
                updateSummary();
            } else {
                // Si l'élément n'existe pas, définir l'heure actuelle dans l'état
                state.startTime = `${hours}:${minutes}`;
                // Élément start-time non trouvé, heure définie dans l'état (normal pour certaines vues)
            }
        }

        function validateTime() {
            const timeInput = document.getElementById('start-time');
            
            // Vérifier que l'élément existe avant de l'utiliser
            if (!timeInput) {
                // Élément start-time non trouvé pour la validation (normal pour certaines vues)
                return;
            }
            
            const time = timeInput.value;
            const errorDiv = document.getElementById('time-error');
            const errorText = document.getElementById('time-error-text');
            
            if (!time) {
                errorDiv.classList.remove('show');
                return true;
            }
            
            const [hours] = time.split(':').map(Number);
            
            if (hours < 6 || hours >= 22) {
                errorText.textContent = 'Heures disponibles: 06:00 - 22:00';
                errorDiv.classList.add('show');
                state.startTime = null;
                return false;
            }
            
            errorDiv.classList.remove('show');
            state.startTime = time;
            updateSummary();
            return true;
        }

        // Cost Calculation
        function calculateCost() {
            if (!state.reservationType || !state.duration) {
                return;
            }

            let baseAmount = 0;
            
            if (state.reservationType === 'kwh') {
                // Estimate: 50kW charger, efficiency ~80%
                const estimatedKwh = (state.duration / 60) * 50 * 0.8;
                baseAmount = estimatedKwh * state.prices.perKwh;
            } else {
                baseAmount = state.duration * state.prices.perMinute;
            }

            const subtotal = baseAmount + state.prices.activation;
            const vat = subtotal * state.prices.vatRate;
            const total = subtotal + vat;

            // Update UI
            document.getElementById('base-amount').textContent = `${baseAmount.toFixed(2)} {{ $currency ?? 'EUR' }}`;
            document.getElementById('subtotal').textContent = `${subtotal.toFixed(2)} {{ $currency ?? 'EUR' }}`;
            document.getElementById('vat-amount').textContent = `${vat.toFixed(2)} {{ $currency ?? 'EUR' }}`;
            document.getElementById('total-cost').textContent = `${total.toFixed(2)} {{ $currency ?? 'EUR' }}`;
            document.getElementById('summary-amount').textContent = `${total.toFixed(2)} {{ $currency ?? 'EUR' }}`;
            
            // Vérifier le solde si paiement par crédit sélectionné
            if (state.paymentMethod === 'credit') {
                checkCreditBalance();
            }
        }

        // Calculate Estimated Amount for form submission
        function calculateEstimatedAmount() {
            if (!state.reservationType || !state.duration) {
                return 0;
            }

            let baseAmount = 0;
            
            if (state.reservationType === 'kwh') {
                // Estimate: 50kW charger, efficiency ~80%
                const estimatedKwh = (state.duration / 60) * 50 * 0.8;
                baseAmount = estimatedKwh * state.prices.perKwh;
            } else {
                baseAmount = state.duration * state.prices.perMinute;
            }

            const subtotal = baseAmount + state.prices.activation;
            const vat = subtotal * state.prices.vatRate;
            return subtotal + vat;
        }

        // Toggle Breakdown
        function toggleBreakdown() {
            const breakdown = document.getElementById('cost-breakdown');
            const toggleText = document.getElementById('toggle-text');
            const icon = document.querySelector('.cost-toggle i');
            
            if (breakdown.classList.contains('hidden')) {
                breakdown.classList.remove('hidden');
                toggleText.textContent = 'Masquer';
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                breakdown.classList.add('hidden');
                toggleText.textContent = 'Détails';
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        // Update Summary
        function updateSummary() {
            document.getElementById('summary-type').textContent = 
                state.reservationType ? (state.reservationType === 'kwh' ? 'Par Énergie (kWh)' : 'Par Durée (min)') : '-';
            
            document.getElementById('summary-value').textContent = 
                state.duration ? `${state.duration} min` : '-';
            
            document.getElementById('summary-time').textContent = 
                state.startTime || '-';
        }

        // Update Button States
        function updateButtonStates() {
            const reserveBtn = document.getElementById('reserve-btn');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            const isValid = state.paymentMethod && state.reservationType && state.duration && state.startTime;
            
            if (state.paymentMethod === 'offline') {
                reserveBtn.disabled = !isValid;
                checkoutBtn.disabled = true;
            } else if (state.paymentMethod === 'credit') {
                // Pour le crédit, vérifier le solde
                const totalCost = calculateEstimatedAmount();
                const hasSufficientBalance = state.userBalance >= totalCost;
                reserveBtn.disabled = !isValid || !hasSufficientBalance;
                checkoutBtn.disabled = !isValid || !hasSufficientBalance;
            } else if (state.paymentMethod === 'cmi' || state.paymentMethod === 'stripe') {
                reserveBtn.disabled = true;
                checkoutBtn.disabled = !isValid;
            } else {
                reserveBtn.disabled = true;
                checkoutBtn.disabled = true;
            }
        }

        // Load User Credit Balance
        async function loadUserCreditBalance() {
            @php
                $isClient = false;
                if (Auth::check()) {
                    $user = Auth::user();
                    $systemRoles = ['admin', 'integrator', 'operator', 'partner'];
                    $hasSystemRole = false;
                    foreach ($systemRoles as $role) {
                        if ($user->hasRole($role)) {
                            $hasSystemRole = true;
                            break;
                        }
                    }
                    $isClient = !$hasSystemRole;
                }
            @endphp
            @if(!auth()->check() || !$isClient)
            return;
            @endif
            
            try {
                const response = await fetch('/credits/balance/api', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    const balanceElements = document.querySelectorAll('#user-balance-display-mobile');
                    balanceElements.forEach(element => {
                        if (element) element.textContent = data.formatted_balance;
                    });
                    state.userBalance = data.balance;
                    
                    // Check balance if credit payment is selected
                    if (state.paymentMethod === 'credit') {
                        checkCreditBalance();
                    }
                } else {
                    const balanceElement = document.getElementById('user-balance-display-mobile');
                    if (balanceElement) {
                        balanceElement.textContent = 'Erreur';
                    }
                    state.userBalance = 0;
                }
            } catch (error) {
                console.error('Error loading credit balance:', error);
                const balanceElement = document.getElementById('user-balance-display-mobile');
                if (balanceElement) {
                    balanceElement.textContent = 'Erreur';
                }
                state.userBalance = 0;
            }
        }

        // Check Credit Balance
        function checkCreditBalance() {
            if (!state.paymentMethod || state.paymentMethod !== 'credit') {
                hideCreditInfo();
                return;
            }

            const totalCost = calculateEstimatedAmount();
            const userBalance = state.userBalance || 0;
            
            // Update balance display
            const availableBalanceEl = document.getElementById('available-balance-mobile');
            const currentBalanceEl = document.getElementById('current-balance-mobile');
            const requiredAmountEl = document.getElementById('required-amount-mobile');
            
            if (availableBalanceEl) availableBalanceEl.textContent = userBalance.toFixed(2) + ' EUR';
            if (currentBalanceEl) currentBalanceEl.textContent = userBalance.toFixed(2);
            if (requiredAmountEl) requiredAmountEl.textContent = totalCost.toFixed(2);
            
            if (userBalance >= totalCost) {
                // Solde suffisant
                document.getElementById('credit-info-mobile')?.classList.remove('hidden');
                document.getElementById('insufficient-balance-mobile')?.classList.add('hidden');
            } else {
                // Solde insuffisant
                document.getElementById('credit-info-mobile')?.classList.add('hidden');
                document.getElementById('insufficient-balance-mobile')?.classList.remove('hidden');
            }
            
            updateButtonStates();
        }

        function hideCreditInfo() {
            document.getElementById('credit-info-mobile')?.classList.add('hidden');
            document.getElementById('insufficient-balance-mobile')?.classList.add('hidden');
        }

        // Submit Reservation
        function submitReservation() {
            if (!validateForm()) return;
            
            // Si paiement par crédit, utiliser processCreditPayment
            if (state.paymentMethod === 'credit') {
                processCreditPayment();
                return;
            }
            
            showMessage('Réservation en cours...', 'success');
            
            // Prepare form data
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('type', state.reservationType === 'kwh' ? 'energy' : 'duration');
            formData.append('value', state.duration);
            formData.append('time', state.startTime || 'immediate');
            const customerName = document.getElementById('customer_name')?.value || 'Client Mobile';
            const customerEmail = document.getElementById('customer_email')?.value || '';
            const customerPhone = document.getElementById('customer_phone')?.value || '';
            const customerAddress = document.getElementById('customer_address')?.value || '';
            const registerAccount = document.getElementById('register_account')?.checked ? '1' : '0';

            formData.append('customer_name', customerName);
            formData.append('customer_email', customerEmail);
            formData.append('customer_phone', customerPhone);
            formData.append('customer_address', customerAddress);
            if(registerAccount === '1') formData.append('register_account', '1');
            
            formData.append('payment_method', state.paymentMethod);
            // formData.append('debug_test', '1'); // Test temporaire désactivé
            
            // Debug: Log the data being sent
            console.log('Sending data:', {
                type: state.reservationType === 'kwh' ? 'energy' : 'duration',
                value: state.duration,
                time: state.startTime || 'immediate',
                customer_name: 'Client Mobile',
                customer_email: '',
                customer_phone: '',
                payment_method: state.paymentMethod,
                reservationType: state.reservationType,
                duration: state.duration,
                startTime: state.startTime
            });
            
            // Submit reservation
            fetch('{{ route("reservations.store", ["chargingPoint" => $chargingPoint->id]) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    return response.json().then(errorData => {
                        console.error('Server error:', errorData);
                        throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                console.log('Reservation ID:', data.reservation_id);
                console.log('Redirect URL:', data.redirect_url);
                console.log('Success:', data.success);
                
                if (data.success) {
                    showMessage('Réservation confirmée avec succès!', 'success');
                    // Redirect to thank you page after 2 seconds
                    setTimeout(() => {
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else if (data.reservation_id) {
                            window.location.href = '{{ route("reservations.thank-you", ":id") }}'.replace(':id', data.reservation_id);
                        } else {
                            console.error('No reservation ID or redirect URL found:', data);
                            showMessage('Erreur: ID de réservation manquant', 'error');
                        }
                    }, 2000);
                } else {
                    showMessage(data.message || 'Erreur lors de la réservation', 'error');
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                showMessage('Erreur lors de la réservation: ' + error.message, 'error');
            });
        }

        // Process Credit Payment
        async function processCreditPayment() {
            if (!state.paymentMethod || state.paymentMethod !== 'credit') {
                showMessage('Méthode de paiement par crédit non sélectionnée', 'error');
                return;
            }

            const totalCost = calculateEstimatedAmount();
            const userBalance = state.userBalance || 0;

            if (userBalance < totalCost) {
                showMessage('Solde insuffisant pour cette transaction', 'error');
                return;
            }

            // Vérifier que les détails de réservation sont complets
            if (!state.reservationType || !state.duration) {
                showMessage('Veuillez compléter tous les détails de réservation avant de procéder au paiement', 'warning');
                return;
            }

            try {
                // Show loading state
                const btn = document.getElementById('reserve-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
                }

                // Préparer les données de réservation avec paiement par crédit
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append('type', state.reservationType === 'kwh' ? 'energy' : 'duration');
                formData.append('value', state.duration);
                formData.append('time', 'immediate');
                const customerName = document.getElementById('customer_name')?.value || '{{ auth()->check() ? auth()->user()->name : "Client Mobile" }}';
                const customerEmail = document.getElementById('customer_email')?.value || '{{ auth()->check() ? auth()->user()->email : "" }}';
                const customerPhone = document.getElementById('customer_phone')?.value || '';
                const customerAddress = document.getElementById('customer_address')?.value || '';
                const registerAccount = document.getElementById('register_account')?.checked ? '1' : '0';

                formData.append('customer_name', customerName);
                formData.append('customer_email', customerEmail);
                formData.append('customer_phone', customerPhone);
                formData.append('customer_address', customerAddress);
                if(registerAccount === '1') formData.append('register_account', '1');
                
                formData.append('payment_method', 'credit');

                // Soumettre la réservation avec paiement par crédit
                const response = await fetch('{{ route("reservations.store", ["chargingPoint" => $chargingPoint->id]) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    // Mettre à jour le solde utilisateur si disponible
                    if (result.remaining_balance !== undefined) {
                        state.userBalance = result.remaining_balance;
                        const formatted = result.remaining_balance.toFixed(2) + ' EUR';
                        const balanceElement = document.getElementById('user-balance-display-mobile');
                        if (balanceElement) {
                            balanceElement.textContent = formatted;
                        }
                        document.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                            detail: { balance: result.remaining_balance, formatted: formatted }
                        }));
                        checkCreditBalance();
                    }
                    
                    showMessage(result.message || 'Réservation créée et payée avec succès !', 'success');
                    
                    // Rediriger immédiatement vers la page de confirmation (paiement crédit déjà traité)
                    const thankYouUrl = result.redirect_url || (result.reservation_id && '{{ route("reservations.thank-you", ":id") }}'.replace(':id', result.reservation_id));
                    if (thankYouUrl) {
                        setTimeout(() => { window.location.href = thankYouUrl; }, 500);
                    }
                } else {
                    showMessage(result.message || 'Erreur lors du paiement par crédit', 'error');
                }

            } catch (error) {
                console.error('Erreur lors du paiement par crédit:', error);
                showMessage('Erreur de connexion lors du paiement', 'error');
            } finally {
                // Remove loading state
                const btn = document.getElementById('reserve-btn');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check"></i> Confirmer la Réservation';
                }
            }
        }

        // Process Payment
        function processPayment() {
            if (!validateForm()) return;
            
            // Si paiement par crédit, utiliser processCreditPayment
            if (state.paymentMethod === 'credit') {
                processCreditPayment();
                return;
            }
            
            showMessage('Redirection vers le paiement...', 'success');
            
            // Prepare payment data
            const paymentData = {
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                charging_point_id: '{{ $chargingPoint->id }}',
                pricing_plan_id: '{{ $pricingPlan->id }}',
                reservation_type: state.reservationType,
                reservation_value: state.duration,
                start_time: state.startTime,
                payment_method: state.paymentMethod,
                estimated_amount: calculateEstimatedAmount(),
                currency: '{{ $currency ?? "EUR" }}'
            };
            
            // Redirect to payment gateway
            if (state.paymentMethod === 'cmi') {
                // Redirect to CMI payment
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("payment.cmi.initiate") }}';
                
                Object.keys(paymentData).forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = paymentData[key];
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            } else if (state.paymentMethod === 'stripe') {
                // Redirect to Stripe payment
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("payment.stripe.initiate.public") }}';
                
                Object.keys(paymentData).forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = paymentData[key];
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            } else {
                // Fallback for other payment methods
                showMessage('Méthode de paiement non supportée', 'error');
            }
        }

        // Form Validation
        function validateForm() {
            const errors = [];
            
            if (!state.paymentMethod) {
                errors.push('Veuillez sélectionner une méthode de paiement');
            }
            if (!state.reservationType) {
                errors.push('Veuillez sélectionner un type de réservation');
            }
            if (!state.duration) {
                errors.push('Veuillez sélectionner une durée');
            }
            // L'heure de début est optionnelle, on peut utiliser 'immediate'
            // if (!state.startTime) {
            //     errors.push('Veuillez sélectionner une heure de début');
            // }
            
            if (errors.length > 0) {
                showMessage(errors.join('. '), 'error');
                return false;
            }
            
            return true;
        }

        // Show Message
        function showMessage(text, type = 'success') {
            const messagesDiv = document.getElementById('messages');
            const message = document.createElement('div');
            message.className = `message ${type}`;
            message.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${text}</span>
            `;
            
            messagesDiv.innerHTML = '';
            messagesDiv.appendChild(message);
            
            setTimeout(() => {
                message.remove();
            }, 5000);
        }

        // Touch Feedback
        document.addEventListener('touchstart', function(e) {
            if (e.target.matches('.payment-option, .reservation-option, .duration-btn, .btn-primary, .btn-secondary')) {
                e.target.style.transform = 'scale(0.97)';
            }
        });

        document.addEventListener('touchend', function(e) {
            if (e.target.matches('.payment-option, .reservation-option, .duration-btn, .btn-primary, .btn-secondary')) {
                e.target.style.transform = '';
            }
        });

        // ============================================
        // Module Crédit - Fonctions de gestion
        // ============================================
        
        // Gestion des onglets crédit
        function switchCreditTab(tab) {
            // Masquer tous les contenus
            document.querySelectorAll('.credit-tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Désactiver tous les onglets
            document.querySelectorAll('.credit-tab-button').forEach(button => {
                button.classList.remove('active');
                button.style.borderBottomColor = 'transparent';
                button.style.color = 'var(--gray-600)';
            });
            
            // Afficher le contenu sélectionné
            const contentId = 'credit-tab-content-' + tab + '-mobile';
            document.getElementById(contentId).classList.remove('hidden');
            
            // Activer l'onglet sélectionné
            const activeTab = document.getElementById('credit-tab-' + tab + '-mobile');
            activeTab.classList.add('active');
            activeTab.style.borderBottomColor = '#8b5cf6';
            activeTab.style.color = '#8b5cf6';
        }

        // Réinitialiser le formulaire de crédit
        function resetCreditFormMobile() {
            document.getElementById('add-credit-form-offer-mobile').reset();
            const resultDiv = document.getElementById('credit-form-result-mobile');
            if (resultDiv) {
                resultDiv.style.display = 'none';
                resultDiv.innerHTML = '';
            }
        }

        // Gestion du formulaire d'ajout de crédit
        @if(isset($creditData) && $creditData)
        document.getElementById('add-credit-form-offer-mobile').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const resultDiv = document.getElementById('credit-form-result-mobile');
            const originalButtonText = submitButton.innerHTML;
            
            // Désactiver le bouton
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
            
            try {
                const response = await fetch('{{ route("credits.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 10px; font-size: 11px;">
                            <div style="display: flex; align-items: center; gap: 8px; color: #166534;">
                                <i class="fas fa-check-circle"></i>
                                <strong>${data.message}</strong>
                            </div>
                            <p style="margin: 6px 0 0 0; color: #166534;">
                                Nouveau solde: ${parseFloat(data.data.new_balance).toFixed(2)} EUR
                            </p>
                        </div>
                    `;
                    resultDiv.style.display = 'block';
                    
                    // Réinitialiser le formulaire
                    resetCreditFormMobile();
                    
                    // Recharger la page après 2 secondes pour mettre à jour l'historique
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    resultDiv.innerHTML = `
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px; font-size: 11px;">
                            <div style="display: flex; align-items: center; gap: 8px; color: #991b1b;">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>${data.message || 'Erreur lors de l\'ajout du crédit'}</strong>
                            </div>
                        </div>
                    `;
                    resultDiv.style.display = 'block';
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px; font-size: 11px;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #991b1b;">
                            <i class="fas fa-exclamation-circle"></i>
                            <strong>Erreur: ${error.message}</strong>
                        </div>
                    </div>
                `;
                resultDiv.style.display = 'block';
            } finally {
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
        @endif

        // Show Authentication Required
        function showAuthRequired() {
            showMessage('Vous devez être connecté pour utiliser le paiement par solde. Veuillez vous connecter ou créer un compte.', 'warning', 5000);
            
            // Optionnel: Rediriger vers la page de connexion après un délai
            setTimeout(() => {
                if (confirm('Souhaitez-vous être redirigé vers la page de connexion ?')) {
                    window.location.href = '/login';
                }
            }, 2000);
        }
    </script>
</body>
</html>
