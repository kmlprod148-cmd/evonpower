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

        /* ============================================
           AUTHENTICATION REQUIRED PANEL
           ============================================ */
        .auth-required-panel {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            animation: slideIn 0.3s ease;
        }

        .auth-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .auth-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
            font-size: 20px;
        }

        .auth-content h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .auth-content p {
            margin: 0;
            font-size: 14px;
            color: var(--gray-600);
        }

        .auth-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }

        .auth-btn {
            flex: 1;
            padding: 12px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .login-btn {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-2px);
        }

        .register-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .register-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
        }

        .auth-benefits {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            padding: 12px;
        }

        .auth-benefits h5 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .auth-benefits ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .auth-benefits li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
            font-size: 13px;
            color: var(--gray-700);
        }

        .auth-benefits li i {
            color: #f59e0b;
            width: 16px;
        }

        .payment-option.auth-required {
            border: 2px solid #f59e0b;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }

        .payment-option.auth-required:hover {
            border-color: #d97706;
            background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
        }

        .payment-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-top: 8px;
            padding: 4px 8px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
        }

        .payment-badge i {
            font-size: 8px;
        }

        @keyframes slideIn {
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
           RESERVATION RESTORED PANEL
           ============================================ */
        .reservation-restored-panel {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 1px solid #10b981;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            animation: slideIn 0.3s ease;
        }

        .restored-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .restored-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
            font-size: 20px;
        }

        .restored-content h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .restored-content p {
            margin: 0;
            font-size: 14px;
            color: var(--gray-600);
        }

        .restored-actions {
            display: flex;
            justify-content: center;
        }

        .restored-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .restored-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
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
                    <p>Choisissez votre mode de paiement</p>
                </div>
            </div>
            <div class="card-body">
                <div class="payment-grid">
                    <!-- Credit Payment - Only for authenticated clients -->
                    @if(auth()->check() && auth()->user()->hasRole('user'))
                    <div class="payment-option credit-option" onclick="selectPayment('credit')" data-method="credit">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-wallet" style="color: white;"></i>
                        </div>
                        <div class="payment-name">Paiement par Solde</div>
                        <div class="payment-desc" id="user-balance-display-mobile">Crédit: {{ number_format(auth()->user()->getOrCreateWallet()->balance ?? 0, 2) }} EUR</div>
                        <div class="payment-badge">
                            <i class="fas fa-bolt"></i>
                            <span>Instantané</span>
                        </div>
                    </div>
                    @else
                    <!-- Authentication Required for Credit Payment -->
                    <div class="payment-option auth-required" onclick="showAuthRequired()" data-method="auth-required">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-user-shield" style="color: white;"></i>
                        </div>
                        <div class="payment-name">Paiement par Solde</div>
                        <div class="payment-desc">Identification requise</div>
                        <div class="payment-badge">
                            <i class="fas fa-lock"></i>
                            <span>Connexion</span>
                        </div>
                    </div>
                    @endif
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
                    <div class="payment-option" onclick="selectPayment('offline')" data-method="offline">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                            <i class="fas fa-cash-register" style="color: white;"></i>
                        </div>
                        <div class="payment-name">Sur Place</div>
                        <div class="payment-desc">Espèces/Carte</div>
                        <div class="payment-badge">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Physique</span>
                        </div>
                    </div>
                </div>
                
                <!-- Authentication Required Panel -->
                <div id="auth-required-panel" class="auth-required-panel" style="display: none;">
                    <div class="auth-header">
                        <div class="auth-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="auth-content">
                            <h4>Identification Requise</h4>
                            <p>Connectez-vous pour utiliser le paiement par solde</p>
                        </div>
                    </div>
                    <div class="auth-actions">
                        <a href="{{ route('login', ['redirect' => urlencode(url()->current()), 'reservation_data' => base64_encode(json_encode([
                            'charging_point_id' => $chargingPoint->id,
                            'pricing_plan_id' => $pricingPlan->id,
                            'payment_method' => 'credit',
                            'timestamp' => time()
                        ]))]) }}" class="auth-btn login-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            Se connecter
                        </a>
                        <a href="{{ route('register', ['redirect' => urlencode(url()->current()), 'reservation_data' => base64_encode(json_encode([
                            'charging_point_id' => $chargingPoint->id,
                            'pricing_plan_id' => $pricingPlan->id,
                            'payment_method' => 'credit',
                            'timestamp' => time()
                        ]))]) }}" class="auth-btn register-btn">
                            <i class="fas fa-user-plus"></i>
                            S'inscrire
                        </a>
                    </div>
                    <div class="auth-benefits">
                        <h5>Avantages du compte client :</h5>
                        <ul>
                            <li><i class="fas fa-wallet"></i> Paiement par solde instantané</li>
                            <li><i class="fas fa-history"></i> Historique des transactions</li>
                            <li><i class="fas fa-percentage"></i> Tarifs préférentiels</li>
                            <li><i class="fas fa-shield-alt"></i> Paiements sécurisés</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Reservation Restored Panel -->
                <div id="reservation-restored-panel" class="reservation-restored-panel" style="display: none;">
                    <div class="restored-header">
                        <div class="restored-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="restored-content">
                            <h4>Réservation Restaurée</h4>
                            <p>Votre réservation a été restaurée. Complétez votre paiement par crédit.</p>
                        </div>
                    </div>
                    <div class="restored-actions">
                        <button onclick="hideReservationRestored()" class="restored-btn">
                            <i class="fas fa-times"></i>
                            Fermer
                        </button>
                    </div>
                </div>
                
                <div class="info-box info">
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

        <!-- Time Selection -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="card-title-group">
                    <h3>Heure de Début</h3>
                    <p>Planifiez votre recharge</p>
                </div>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <label class="input-label">
                        <i class="fas fa-clock"></i>
                        Sélectionnez l'heure de début
                    </label>
                    <div class="input-wrapper">
                        <input type="time" id="start-time" min="06:00" max="22:00" onchange="validateTime()">
                        <button class="btn-secondary" onclick="setCurrentTime()">
                            <i class="fas fa-clock"></i>
                            Maintenant
                        </button>
                    </div>
                    <div class="input-help">
                        <i class="fas fa-info-circle"></i>
                        <span>Heures disponibles: 06:00 - 22:00</span>
                    </div>
                    <div class="input-error" id="time-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span id="time-error-text"></span>
                    </div>
                </div>

                <div class="info-box success">
                    <i class="fas fa-bolt"></i>
                    <div>
                        <strong>Recharge immédiate</strong><br>
                        Votre recharge commencera immédiatement après le paiement
                    </div>
                </div>
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

    </div>

    <script>
        // State Management
        const state = {
            paymentMethod: null,
            reservationType: null,
            duration: null,
            startTime: null,
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
            
            // Check if user is authenticated and restore reservation state
            @if(auth()->check())
            restoreReservationState();
            @endif
        });

        // Payment Method Selection
        function selectPayment(method) {
            // Check if authentication is required for credit payment
            if (method === 'credit') {
                // This should only be called for authenticated users
                state.paymentMethod = method;
                
                // Update UI
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                document.querySelector(`[data-method="${method}"]`).classList.add('selected');
                
                showMessage('Paiement par solde sélectionné', 'success');
                updateButtonStates();
                updateSummary();
            } else {
                state.paymentMethod = method;
                
                // Update UI
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                document.querySelector(`[data-method="${method}"]`).classList.add('selected');
                
                showMessage('Méthode de paiement sélectionnée', 'success');
                updateButtonStates();
                updateSummary();
            }
        }

        // Show Authentication Required Panel
        function showAuthRequired() {
            // Save current reservation state before showing auth panel
            saveReservationState();
            
            const authPanel = document.getElementById('auth-required-panel');
            if (authPanel) {
                authPanel.style.display = 'block';
                
                // Scroll to the panel
                authPanel.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                showMessage('Identification requise pour le paiement par solde', 'warning');
            }
        }

        // Save current reservation state to localStorage
        function saveReservationState() {
            const reservationState = {
                charging_point_id: {{ $chargingPoint->id }},
                pricing_plan_id: {{ $pricingPlan->id }},
                payment_method: 'credit',
                reservation_type: state.reservationType,
                duration: state.duration,
                start_time: state.startTime,
                prices: state.prices,
                timestamp: Date.now(),
                url: window.location.href
            };
            
            localStorage.setItem('pending_reservation', JSON.stringify(reservationState));
            console.log('Reservation state saved:', reservationState);
        }

        // Restore reservation state from localStorage
        function restoreReservationState() {
            const savedState = localStorage.getItem('pending_reservation');
            if (savedState) {
                try {
                    const reservationData = JSON.parse(savedState);
                    
                    // Check if the data is recent (within 1 hour)
                    const oneHourAgo = Date.now() - (60 * 60 * 1000);
                    if (reservationData.timestamp > oneHourAgo) {
                        // Restore the state
                        state.reservationType = reservationData.reservation_type;
                        state.duration = reservationData.duration;
                        state.startTime = reservationData.start_time;
                        state.paymentMethod = reservationData.payment_method;
                        
                        // Update UI
                        if (reservationData.reservation_type) {
                            selectReservationType(reservationData.reservation_type);
                        }
                        if (reservationData.duration) {
                            selectDuration(reservationData.duration);
                        }
                        if (reservationData.payment_method === 'credit') {
                            selectPayment('credit');
                        }
                        
                        showMessage('Réservation restaurée - Complétez votre paiement par crédit', 'success');
                        showReservationRestored();
                        
                        // Clear the saved state
                        localStorage.removeItem('pending_reservation');
                    } else {
                        // Clear expired data
                        localStorage.removeItem('pending_reservation');
                    }
                } catch (error) {
                    console.error('Error restoring reservation state:', error);
                    localStorage.removeItem('pending_reservation');
                }
            }
        }

        // Hide Authentication Required Panel
        function hideAuthRequired() {
            const authPanel = document.getElementById('auth-required-panel');
            if (authPanel) {
                authPanel.style.display = 'none';
            }
        }

        // Show Reservation Restored Panel
        function showReservationRestored() {
            const restoredPanel = document.getElementById('reservation-restored-panel');
            if (restoredPanel) {
                restoredPanel.style.display = 'block';
                
                // Scroll to the panel
                restoredPanel.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    hideReservationRestored();
                }, 5000);
            }
        }

        // Hide Reservation Restored Panel
        function hideReservationRestored() {
            const restoredPanel = document.getElementById('reservation-restored-panel');
            if (restoredPanel) {
                restoredPanel.style.display = 'none';
            }
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
            timeInput.value = `${hours}:${minutes}`;
            state.startTime = timeInput.value;
            validateTime();
            updateSummary();
        }

        function validateTime() {
            const timeInput = document.getElementById('start-time');
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
            } else if (state.paymentMethod === 'cmi' || state.paymentMethod === 'stripe') {
                reserveBtn.disabled = true;
                checkoutBtn.disabled = !isValid;
            } else {
                reserveBtn.disabled = true;
                checkoutBtn.disabled = true;
            }
        }

        // Submit Reservation
        function submitReservation() {
            if (!validateForm()) return;
            
            showMessage('Réservation en cours...', 'success');
            
            // Prepare form data
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('type', state.reservationType === 'kwh' ? 'energy' : 'duration');
            formData.append('value', state.duration);
            formData.append('time', state.startTime || 'immediate');
            formData.append('customer_name', 'Client Mobile');
            formData.append('customer_email', '');
            formData.append('customer_phone', '');
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
            fetch('/reservations/store/{{ $chargingPoint->id }}', {
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

        // Process Payment
        function processPayment() {
            if (!validateForm()) return;
            
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
    </script>
</body>
</html>
