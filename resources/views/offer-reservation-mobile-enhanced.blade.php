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
           ENHANCED MOBILE-FIRST RESPONSIVE DESIGN
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
            --credit-green: #16a34a;
            --credit-light: #dcfce7;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-900: #111827;
            --mobile-padding: 16px;
            --mobile-gap: 12px;
            --touch-target: 48px;
            --border-radius: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.2);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.25);
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
            font-size: 15px;
            color: var(--gray-900);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
            line-height: 1.5;
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
           ENHANCED HEADER COMPONENT
           ============================================ */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            flex: 1;
            text-align: center;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header p {
            font-size: 14px;
            margin: 0;
            opacity: 0.9;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .status-badge.available {
            background: rgba(16, 185, 129, 0.3);
        }

        .status-badge.charging {
            background: rgba(245, 158, 11, 0.3);
        }

        /* ============================================
           ENHANCED CARD COMPONENT
           ============================================ */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .card-title-group h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 4px 0;
            color: var(--gray-900);
        }

        .card-title-group p {
            font-size: 13px;
            margin: 0;
            color: var(--gray-600);
        }

        .card-body {
            padding: 20px;
        }

        /* ============================================
           ENHANCED PAYMENT METHODS
           ============================================ */
        .payment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .payment-option {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .payment-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .payment-option:hover::before {
            left: 100%;
        }

        .payment-option.selected {
            border-color: var(--primary);
            background: var(--credit-light);
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }

        .payment-option.credit-option {
            border-color: var(--credit-green);
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }

        .payment-option.credit-option.selected {
            border-color: var(--credit-green);
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .payment-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 20px;
            position: relative;
        }

        .payment-option.credit-option .payment-icon {
            background: linear-gradient(135deg, var(--credit-green), #15803d);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .payment-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--gray-900);
        }

        .payment-desc {
            font-size: 12px;
            color: var(--gray-600);
            margin-bottom: 8px;
        }

        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--primary);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payment-option.credit-option .payment-badge {
            background: var(--credit-green);
        }

        /* ============================================
           CLIENT AUTHENTICATION PANEL
           ============================================ */
        .client-auth-panel {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            display: none;
        }

        .client-auth-panel.hidden {
            display: none !important;
        }

        .client-auth-panel.show {
            display: block;
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

        /* ============================================
           CREDIT SYSTEM INTEGRATION
           ============================================ */
        .credit-info {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            display: none;
        }

        .credit-info.show {
            display: block;
            animation: slideIn 0.3s ease;
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

        .credit-balance {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .credit-balance-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--credit-green);
        }

        .credit-balance-amount {
            font-size: 18px;
            font-weight: 700;
            color: var(--credit-green);
        }

        .credit-benefits {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
            margin-top: 12px;
        }

        .credit-benefit {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--gray-600);
        }

        .credit-benefit i {
            color: var(--credit-green);
            font-size: 14px;
        }

        .insufficient-balance {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            display: none;
        }

        .insufficient-balance.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        .insufficient-balance-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .insufficient-balance-header i {
            color: var(--error);
            font-size: 16px;
        }

        .insufficient-balance-header span {
            font-size: 14px;
            font-weight: 600;
            color: var(--error);
        }

        .insufficient-balance-content {
            font-size: 13px;
            color: var(--gray-600);
            margin-bottom: 12px;
        }

        .insufficient-balance-details {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--gray-600);
        }

        .recharge-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 8px;
        }

        .recharge-link:hover {
            text-decoration: underline;
        }

        /* ============================================
           ENHANCED BUTTONS
           ============================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: var(--touch-target);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-tertiary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-tertiary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-credit {
            background: linear-gradient(135deg, var(--credit-green) 0%, #15803d 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .btn-credit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: var(--shadow-sm) !important;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-group .btn {
            flex: 1;
        }

        /* ============================================
           ENHANCED RESPONSIVE DESIGN
           ============================================ */
        @media (max-width: 480px) {
            .container {
                padding: 12px;
            }
            
            .header {
                padding: 16px;
                margin-bottom: 16px;
            }
            
            .header h1 {
                font-size: 18px;
            }
            
            .card-body {
                padding: 16px;
            }
            
            .payment-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .payment-option {
                padding: 12px;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 15px;
            }
        }

        @media (min-width: 640px) {
            .payment-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .credit-benefits {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 768px) {
            .container {
                padding: 24px;
            }
            
            .payment-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* ============================================
           LOADING AND ANIMATION STATES
           ============================================ */
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
            border: 2px solid transparent;
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* ============================================
           ACCESSIBILITY IMPROVEMENTS
           ============================================ */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
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
            border: 0;
        }

        /* Focus styles for better accessibility */
        .payment-option:focus,
        .btn:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* ============================================
           ENHANCED DURATION SELECTION STYLES
           ============================================ */
        
        .mobile-duration-btn {
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .mobile-duration-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .mobile-duration-btn:hover::before {
            left: 100%;
        }

        .mobile-duration-btn.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.1));
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
            transform: translateY(-2px);
        }

        .mobile-duration-btn.selected .duration-icon {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .mobile-duration-btn.selected .duration-number {
            color: var(--primary);
            text-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .mobile-duration-btn.selected .duration-badge {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .mobile-duration-btn.selected .duration-price {
            color: var(--primary);
            font-weight: 700;
        }

        /* Custom duration input enhancements */
        #mobile-custom-duration {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
        }

        #mobile-custom-duration:focus {
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        /* Enhanced info section */
        .info-section {
            background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248,250,252,0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        /* Animation for selection indicator */
        @keyframes checkmark {
            0% { transform: scale(0) rotate(0deg); opacity: 0; }
            50% { transform: scale(1.2) rotate(180deg); opacity: 1; }
            100% { transform: scale(1) rotate(360deg); opacity: 1; }
        }

        .mobile-duration-btn.selected .selection-indicator {
            animation: checkmark 0.6s ease-out;
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Pulse animation for active state */
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        }

        .mobile-duration-btn.selected {
            animation: pulse-glow 2s infinite;
        }

        /* Enhanced hover effects */
        .mobile-duration-btn:hover {
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }

        .mobile-duration-btn:hover .duration-icon {
            transform: scale(1.05) rotate(5deg);
        }

        /* Custom scrollbar for better mobile experience */
        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
            border-radius: 2px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.2);
            border-radius: 2px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0,0,0,0.3);
        }

        /* ============================================
           ENHANCED ANIMATIONS AND EFFECTS
           ============================================ */
        
        /* Progress Ring Animation */
        @keyframes progressRing {
            from {
                stroke-dashoffset: 283;
            }
            to {
                stroke-dashoffset: 0;
            }
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        /* Glow Pulse Animation */
        @keyframes glowPulse {
            0%, 100% {
                box-shadow: 0 0 5px rgba(59, 130, 246, 0.5);
            }
            50% {
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.8), 0 0 30px rgba(59, 130, 246, 0.6);
            }
        }

        /* Shimmer Animation */
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }

        .animate-shimmer {
            animation: shimmer 3s ease-in-out infinite;
        }

        /* Enhanced Button Hover Effects */
        .mobile-duration-btn:hover {
            animation: float 2s ease-in-out infinite;
        }

        .mobile-duration-btn:hover .duration-icon {
            animation: glowPulse 1.5s ease-in-out infinite;
        }

        /* Custom Duration Section Animations */
        .custom-duration-section {
            position: relative;
            overflow: hidden;
        }

        .custom-duration-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            animation: rotate 10s linear infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .custom-duration-section:hover::before {
            opacity: 1;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Enhanced Input Focus Effects */
        .enhanced-input:focus {
            animation: glowPulse 2s ease-in-out infinite;
        }

        /* Particle Animation */
        @keyframes particleFloat {
            0% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-20px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Price Calculation Animation */
        @keyframes priceCalculate {
            0% {
                opacity: 0.5;
                transform: scale(0.9);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Enhanced Hover Effects */
        @keyframes cardLift {
            0% {
                transform: translateY(0) scale(1);
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            100% {
                transform: translateY(-8px) scale(1.02);
                box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            }
        }

        /* Dynamic Price Update Animation */
        @keyframes priceUpdate {
            0% {
                color: #10b981;
                transform: scale(1);
            }
            50% {
                color: #059669;
                transform: scale(1.1);
            }
            100% {
                color: inherit;
                transform: scale(1);
            }
        }

        /* Enhanced Fluid Animations */
        @keyframes fluidFloat {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            33% {
                transform: translateY(-3px) rotate(1deg);
            }
            66% {
                transform: translateY(-6px) rotate(-1deg);
            }
        }

        @keyframes fluidPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
        }

        @keyframes fluidGlow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
            }
            50% {
                box-shadow: 0 0 40px rgba(59, 130, 246, 0.6), 0 0 60px rgba(59, 130, 246, 0.4);
            }
        }

        @keyframes fluidShimmer {
            0% {
                transform: translateX(-100%) skewX(-15deg);
            }
            100% {
                transform: translateX(200%) skewX(-15deg);
            }
        }

        /* Enhanced Button States */
        .mobile-duration-btn {
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mobile-duration-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s ease;
        }

        .mobile-duration-btn:hover::before {
            left: 100%;
        }

        .mobile-duration-btn:hover {
            animation: fluidFloat 3s ease-in-out infinite;
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .mobile-duration-btn.selected {
            animation: fluidGlow 2s ease-in-out infinite;
            transform: translateY(-4px) scale(1.05);
        }

        /* Ripple Effect Animation */
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Enhanced Fluid Transitions */
        .mobile-duration-btn * {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Enhanced Focus States */
        .mobile-duration-btn:focus {
            outline: none;
            animation: fluidGlow 1s ease-in-out;
        }

        /* Enhanced Active States */
        .mobile-duration-btn:active {
            transform: translateY(-2px) scale(0.98);
            transition: all 0.1s ease;
        }

        .particle {
            animation: particleFloat 3s ease-in-out infinite;
        }

        /* Enhanced Selection States */
        .mobile-duration-btn.selected {
            animation: glowPulse 2s ease-in-out infinite;
            transform: scale(1.05);
        }

        .mobile-duration-btn.selected .duration-icon {
            animation: float 1s ease-in-out infinite;
        }

        /* Responsive Enhancements */
        @media (max-width: 640px) {
            .mobile-duration-btn {
                padding: 1rem;
            }
            
            .mobile-duration-btn .duration-icon {
                width: 3rem;
                height: 3rem;
            }
            
            .mobile-duration-btn .duration-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Enhanced Header -->
        <div class="header">
            <div class="header-content">
                <h1>{{ $chargingPoint->name ?? 'Borne de Recharge' }}</h1>
                <p>Réservation de session de recharge</p>
                <div class="status-badge available">
                    <i class="fas fa-check-circle"></i>
                    <span>Disponible</span>
                </div>
            </div>
        </div>

        <!-- Enhanced Duration Selection -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon" style="background: #e0e7ff; color: #6366f1;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="card-title-group">
                    <h3>1. Choisissez votre durée</h3>
                    <p>Sélectionnez d'abord la durée de votre session</p>
                </div>
            </div>
            <div class="card-body">
                @php
                    $maxDuration = $pricingPlan->max_duration ?? 120;
                    $minDuration = $pricingPlan->min_charge_duration ?? 10;
                    $durationOptions = [
                        ['value' => 15, 'label' => 'Rapide', 'color' => 'blue', 'icon' => 'fas fa-bolt', 'gradient' => 'from-blue-500 to-cyan-500'],
                        ['value' => 30, 'label' => 'Standard', 'color' => 'green', 'icon' => 'fas fa-clock', 'gradient' => 'from-green-500 to-emerald-500'],
                        ['value' => 60, 'label' => '1 heure', 'color' => 'purple', 'icon' => 'fas fa-hourglass-half', 'gradient' => 'from-purple-500 to-violet-500'],
                        ['value' => 120, 'label' => '2 heures', 'color' => 'red', 'icon' => 'fas fa-battery-full', 'gradient' => 'from-red-500 to-pink-500']
                    ];
                    $filteredOptions = array_filter($durationOptions, function($option) use ($maxDuration, $minDuration) {
                        return $option['value'] >= $minDuration && $option['value'] <= $maxDuration;
                    });
                @endphp
                
                <!-- Enhanced Fluid Duration Selection Grid -->
                <div class="grid grid-cols-2 gap-6 mb-8">
                    @foreach($filteredOptions as $option)
                    <button class="mobile-duration-btn group relative bg-white/90 backdrop-blur-sm border-2 border-gray-200/50 rounded-3xl p-6 text-center hover:border-{{ $option['color'] }}-400 hover:shadow-2xl hover:shadow-{{ $option['color'] }}-200/50 transition-all duration-500 cursor-pointer overflow-hidden" 
                            data-duration="{{ $option['value'] }}" 
                            onmouseenter="showMobileDurationTooltip(event, {{ $option['value'] }})" 
                            onmouseleave="hideMobileDurationTooltip()"
                            onclick="selectMobileDuration({{ $option['value'] }})">
                        
                        <!-- Fluid Background Gradient -->
                        <div class="absolute inset-0 bg-gradient-to-br {{ $option['gradient'] }} opacity-0 group-hover:opacity-10 rounded-3xl transition-all duration-700"></div>
                        
                        <!-- Enhanced Shimmer Effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1200 ease-out" style="animation: fluidShimmer 2s ease-in-out infinite;"></div>
                        
                        <!-- Enhanced Floating Particles -->
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-700">
                            <div class="absolute top-3 left-3 w-2 h-2 bg-{{ $option['color'] }}-400 rounded-full animate-ping" style="animation-duration: 2s;"></div>
                            <div class="absolute top-6 right-4 w-1.5 h-1.5 bg-{{ $option['color'] }}-300 rounded-full animate-pulse" style="animation-delay: 0.7s; animation-duration: 1.5s;"></div>
                            <div class="absolute bottom-4 left-4 w-1 h-1 bg-{{ $option['color'] }}-500 rounded-full animate-bounce" style="animation-delay: 1.2s; animation-duration: 2s;"></div>
                            <div class="absolute bottom-6 right-6 w-1.5 h-1.5 bg-{{ $option['color'] }}-400 rounded-full animate-ping" style="animation-delay: 0.3s; animation-duration: 1.8s;"></div>
                        </div>
                        
                        <!-- Enhanced Icon with Fluid Animation -->
                        <div class="relative z-10 w-16 h-16 mx-auto mb-4 bg-gradient-to-br {{ $option['gradient'] }} rounded-3xl flex items-center justify-center shadow-2xl group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 group-hover:shadow-3xl">
                            <i class="{{ $option['icon'] }} text-white text-2xl group-hover:scale-110 transition-transform duration-500"></i>
                            <!-- Enhanced Icon Glow -->
                            <div class="absolute inset-0 bg-gradient-to-br {{ $option['gradient'] }} rounded-3xl blur-md opacity-0 group-hover:opacity-60 transition-opacity duration-500"></div>
                        </div>
                        
                        <!-- Enhanced Duration Number with Fluid Typography -->
                        <div class="relative z-10 text-4xl font-black text-gray-900 mb-2 group-hover:text-{{ $option['color'] }}-600 transition-all duration-500 group-hover:scale-110">
                            {{ $option['value'] }}
                            <!-- Enhanced Number Glow -->
                            <div class="absolute inset-0 text-{{ $option['color'] }}-600 blur-sm opacity-0 group-hover:opacity-40 transition-opacity duration-500">{{ $option['value'] }}</div>
                        </div>
                        
                        <!-- Enhanced Unit Styling -->
                        <div class="relative z-10 text-sm font-bold text-gray-600 mb-3 group-hover:text-{{ $option['color'] }}-500 transition-colors duration-500">minutes</div>
                        
                        <!-- Enhanced Label Badge with Fluid Animation -->
                        <div class="relative z-10 inline-flex items-center px-5 py-3 rounded-full text-sm font-bold bg-{{ $option['color'] }}-100 text-{{ $option['color'] }}-700 mb-4 group-hover:bg-{{ $option['color'] }}-200 group-hover:scale-110 transition-all duration-500 shadow-lg">
                            <i class="{{ $option['icon'] }} mr-2 text-sm group-hover:animate-spin" style="animation-duration: 2s;"></i>
                            {{ $option['label'] }}
                        </div>
                        
                        <!-- Enhanced Price Display with Fluid Animation -->
                        <div class="relative z-10 text-base font-bold text-gray-800 group-hover:text-{{ $option['color'] }}-600 transition-all duration-500 group-hover:scale-110">
                            <span class="price-display" data-duration="{{ $option['value'] }}">Calculé</span> EUR
                            <!-- Enhanced Price Glow -->
                            <div class="absolute inset-0 text-{{ $option['color'] }}-600 blur-sm opacity-0 group-hover:opacity-50 transition-opacity duration-500">
                                <span class="price-display" data-duration="{{ $option['value'] }}">Calculé</span> EUR
                            </div>
                        </div>
                        
                        <!-- Enhanced Selection Indicator -->
                        <div class="absolute top-4 right-4 w-8 h-8 bg-white/90 backdrop-blur-sm border-2 border-gray-300 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-500 group-hover:scale-125 group-hover:border-{{ $option['color'] }}-500">
                            <i class="fas fa-check text-sm text-gray-600 group-hover:text-{{ $option['color'] }}-600 transition-colors duration-500"></i>
                        </div>
                        
                        <!-- Enhanced Progress Ring -->
                        <div class="absolute inset-0 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" stroke="currentColor" stroke-width="3" fill="none" class="text-{{ $option['color'] }}-200" opacity="0.4"/>
                                <circle cx="50" cy="50" r="45" stroke="currentColor" stroke-width="3" fill="none" class="text-{{ $option['color'] }}-500" stroke-dasharray="283" stroke-dashoffset="283" style="animation: progressRing 3s ease-in-out forwards;"/>
                            </svg>
                        </div>
                    </button>
                    @endforeach
                </div>
                
                <!-- Enhanced Fluid Custom Duration Section -->
                <div class="custom-duration-section bg-gradient-to-br from-gray-50/80 via-blue-50/80 to-indigo-50/80 backdrop-blur-md rounded-3xl p-6 border-2 border-gray-200/50 relative overflow-hidden shadow-2xl">
                    <!-- Enhanced Background Pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 left-0 w-24 h-24 bg-blue-500 rounded-full -translate-x-12 -translate-y-12 animate-pulse" style="animation-duration: 3s;"></div>
                        <div class="absolute bottom-0 right-0 w-20 h-20 bg-indigo-500 rounded-full translate-x-10 translate-y-10 animate-bounce" style="animation-duration: 2.5s;"></div>
                        <div class="absolute top-1/2 left-1/2 w-16 h-16 bg-purple-500 rounded-full -translate-x-8 -translate-y-8 animate-ping" style="animation-duration: 4s;"></div>
                        <div class="absolute top-1/4 right-1/4 w-12 h-12 bg-cyan-500 rounded-full animate-pulse" style="animation-duration: 2s; animation-delay: 1s;"></div>
                    </div>
                    
                    <!-- Enhanced Floating Elements -->
                    <div class="absolute top-3 right-3 w-3 h-3 bg-blue-400 rounded-full animate-ping opacity-70" style="animation-duration: 2s;"></div>
                    <div class="absolute bottom-4 left-4 w-2 h-2 bg-indigo-400 rounded-full animate-pulse opacity-70" style="animation-delay: 1.5s; animation-duration: 1.8s;"></div>
                    <div class="absolute top-1/2 right-1/3 w-2.5 h-2.5 bg-purple-400 rounded-full animate-bounce opacity-60" style="animation-delay: 0.8s; animation-duration: 2.2s;"></div>
                    
                    <div class="relative z-10">
                        <!-- Enhanced Header with Fluid Icon -->
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 rounded-3xl flex items-center justify-center mr-4 shadow-2xl relative overflow-hidden">
                                <i class="fas fa-edit text-white text-lg relative z-10"></i>
                                <!-- Enhanced Icon Glow -->
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-purple-400 rounded-3xl blur-md opacity-60"></div>
                                <!-- Icon Shimmer -->
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full animate-shimmer" style="animation-duration: 3s;"></div>
                            </div>
                            <div>
                                <label class="text-lg font-bold text-gray-800">Durée personnalisée</label>
                                <p class="text-sm text-gray-600">Créez votre propre session</p>
                            </div>
                        </div>
                        
                        <!-- Enhanced Fluid Input Section -->
                        <div class="flex gap-4 mb-6">
                            <div class="flex-1 relative group">
                                <input type="number" id="mobile-custom-duration" min="{{ $minDuration }}" max="{{ $maxDuration }}" step="10" 
                                       class="w-full px-6 py-5 border-2 border-gray-300/50 rounded-3xl focus:ring-4 focus:ring-blue-500/30 focus:border-blue-500 text-lg font-semibold transition-all duration-500 bg-white/90 backdrop-blur-sm shadow-xl group-hover:shadow-2xl group-hover:scale-[1.02]" 
                                       placeholder="Ex: 45"
                                       oninput="updateCustomPricePreview()"
                                       onchange="updateCustomPricePreview()">
                                <div class="absolute right-5 top-1/2 transform -translate-y-1/2 text-gray-500 text-base font-bold group-hover:text-blue-600 transition-colors duration-500">
                                    min
                                </div>
                                <!-- Enhanced Input Glow Effect -->
                                <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-blue-500/15 to-indigo-500/15 opacity-0 group-focus-within:opacity-100 transition-opacity duration-500"></div>
                                <!-- Input Shimmer -->
                                <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-focus-within:translate-x-full transition-transform duration-1000 ease-out"></div>
                            </div>
                            <button onclick="selectMobileCustomDuration()" class="px-10 py-5 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 text-white rounded-3xl hover:from-blue-700 hover:via-indigo-700 hover:to-purple-700 transition-all duration-500 shadow-2xl hover:shadow-3xl transform hover:-translate-y-2 hover:scale-110 relative overflow-hidden">
                                <i class="fas fa-check text-2xl relative z-10"></i>
                                <!-- Enhanced Button Shimmer -->
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full hover:translate-x-full transition-transform duration-1000"></div>
                                <!-- Button Glow -->
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-purple-500 rounded-3xl blur-sm opacity-0 hover:opacity-50 transition-opacity duration-500"></div>
                            </button>
                        </div>
                        
                        <!-- Enhanced Fluid Info Section -->
                        <div class="bg-white/95 backdrop-blur-md rounded-3xl p-5 border border-gray-200/50 shadow-xl">
                            <div class="flex items-start">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-2xl flex items-center justify-center mr-4 mt-1 shadow-lg">
                                    <i class="fas fa-info-circle text-blue-600 text-base"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-base text-gray-700 font-bold mb-2">
                                        Valeurs par intervalles de 10 minutes
                                    </p>
                                    <p class="text-sm text-gray-600 mb-3">
                                        Plage autorisée : <span class="font-bold text-blue-600 text-lg">{{ $minDuration }}-{{ $maxDuration }}</span> minutes
                                    </p>
                                    
                                    <!-- Enhanced Dynamic Price Preview -->
                                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-2xl p-4 border border-green-200/50 mb-3 shadow-lg">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <i class="fas fa-calculator text-green-600 mr-3 text-base"></i>
                                                <span class="text-sm text-gray-600 font-semibold">Prix estimé :</span>
                                            </div>
                                            <span class="text-lg font-bold text-green-600" id="custom-price-preview">-- EUR</span>
                                        </div>
                                    </div>
                                    
                                    @if($maxDuration < 120)
                                    <div class="flex items-center bg-orange-50 rounded-2xl p-3 border border-orange-200/50 shadow-md">
                                        <i class="fas fa-exclamation-triangle text-orange-600 mr-3 text-base"></i>
                                        <p class="text-sm text-orange-700 font-semibold">
                                            Limité par le plan tarifaire
                                        </p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Payment Methods -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon" style="background: #dcfce7; color: #059669;">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="card-title-group">
                    <h3>2. Méthode de Paiement</h3>
                    <p>Choisissez votre mode de paiement préféré</p>
                </div>
            </div>
            <div class="card-body">
                <div class="payment-grid">
                    <!-- Credit Payment (Replaces "Sur Place") -->
                    <div class="payment-option credit-option" 
                         onclick="selectPayment('credit')" 
                         data-method="credit"
                         onmouseenter="showMobilePaymentTooltip(event, 'credit')" 
                         onmouseleave="hideMobilePaymentTooltip()">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-wallet" style="color: white;"></i>
                        </div>
                        <div class="payment-name">Paiement par Solde</div>
                        <div class="payment-desc">Crédit disponible</div>
                        <div class="payment-badge">
                            <i class="fas fa-bolt"></i>
                            <span>Instantané</span>
                        </div>
                    </div>

                    <!-- Stripe Payment -->
                    <div class="payment-option" onclick="selectPayment('stripe')" data-method="stripe">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <i class="fab fa-stripe"></i>
                        </div>
                        <div class="payment-name">Stripe</div>
                        <div class="payment-desc">International</div>
                        <div class="payment-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>Sécurisé</span>
                        </div>
                    </div>

                    <!-- CMI Payment -->
                    <div class="payment-option" onclick="selectPayment('cmi')" data-method="cmi">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="payment-name">CMI</div>
                        <div class="payment-desc">Local</div>
                        <div class="payment-badge">
                            <i class="fas fa-home"></i>
                            <span>Maroc</span>
                        </div>
                    </div>

                    <!-- Sur Place Payment (Offline) -->
                    <div class="payment-option" 
                         onclick="selectPayment('offline')" 
                         data-method="offline"
                         onmouseenter="showMobilePaymentTooltip(event, 'offline')" 
                         onmouseleave="hideMobilePaymentTooltip()">
                        <div class="payment-icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div class="payment-name">Sur Place</div>
                        <div class="payment-desc">Espèces/Carte</div>
                        <div class="payment-badge">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Physique</span>
                        </div>
                    </div>

                </div>

                <!-- Client Authentication Panel -->
                <div id="client-auth-panel" class="client-auth-panel hidden">
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

                <!-- Credit Information Panel -->
                <div id="credit-info" class="credit-info">
                    <div class="credit-balance">
                        <div class="credit-balance-label">
                            <i class="fas fa-wallet"></i>
                            <span>Solde disponible</span>
                        </div>
                        <div class="credit-balance-amount" id="available-balance">
                            {{ number_format($user->credit->balance ?? 0, 2) }} EUR
                        </div>
                    </div>
                    
                    <div class="credit-benefits">
                        <div class="credit-benefit">
                            <i class="fas fa-bolt"></i>
                            <span>Paiement instantané</span>
                        </div>
                        <div class="credit-benefit">
                            <i class="fas fa-shield-alt"></i>
                            <span>Sécurisé et traçable</span>
                        </div>
                        <div class="credit-benefit">
                            <i class="fas fa-history"></i>
                            <span>Historique complet</span>
                        </div>
                    </div>
                </div>

                <!-- Insufficient Balance Warning -->
                <div id="insufficient-balance" class="insufficient-balance">
                    <div class="insufficient-balance-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Solde insuffisant</span>
                    </div>
                    <div class="insufficient-balance-content">
                        Votre solde de crédit est insuffisant pour cette transaction.
                    </div>
                    <div class="insufficient-balance-details">
                        <span>Solde actuel: <strong id="current-balance">{{ number_format($user->credit->balance ?? 0, 2) }} EUR</strong></span>
                        <span>Montant requis: <strong id="required-amount">0.00 EUR</strong></span>
                    </div>
                    <a href="{{ route('wallet.refill') }}" class="recharge-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Recharger mon compte</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="btn-group">
            <button class="btn btn-primary" id="reserve-btn" onclick="submitReservation()" disabled>
                <i class="fas fa-check"></i>
                <span>Confirmer</span>
            </button>
            <button class="btn btn-secondary" id="checkout-btn" onclick="processPayment()" disabled>
                <i class="fas fa-credit-card"></i>
                <span>Payer</span>
            </button>
        </div>
        
        <!-- Prominent Details Button -->
        <div style="margin-top: 16px;">
            <button class="btn btn-tertiary" onclick="showReservationDetails()" style="width: 100%; font-size: 16px; padding: 16px; border-radius: 16px; box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);">
                <i class="fas fa-eye" style="margin-right: 8px; font-size: 18px;"></i>
                <span style="font-weight: 600;">Voir les Détails de la Réservation</span>
            </button>
        </div>

    </div>

    <script>
        // Enhanced State Management
        const state = {
            paymentMethod: null,
            duration: null,
            reservationType: 'minute',
            userBalance: {{ $user->credit->balance ?? 0 }},
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            isClient: {{ $isClient ? 'true' : 'false' }},
            prices: {
                perKwh: {{ $pricingPlan->price_per_kwh ?? 0.45 }},
                perMinute: {{ $pricingPlan->price_per_minute ?? 0.25 }},
                activation: {{ $pricingPlan->activation_fee ?? 2.50 }},
                vatRate: 0.20
            },
            planLimits: {
                maxDuration: {{ $pricingPlan->max_duration ?? 120 }},
                minDuration: {{ $pricingPlan->min_charge_duration ?? 10 }}
            }
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateButtonStates();
            initializePaymentMethods();
            updateDurationPrices();
            loadUserCreditBalance();
        });

        // Update Duration Prices with Animation
        function updateDurationPrices() {
            document.querySelectorAll('.mobile-duration-btn').forEach(btn => {
                const duration = parseInt(btn.getAttribute('data-duration'));
                const priceDisplay = btn.querySelector('.price-display');
                if (priceDisplay && duration) {
                    // Add animation class
                    priceDisplay.classList.add('updating');
                    
                    // Calculate price
                    const totalCost = (duration * state.prices.perMinute + state.prices.activation).toFixed(2);
                    
                    // Update with animation
                    setTimeout(() => {
                        priceDisplay.textContent = totalCost;
                        priceDisplay.classList.remove('updating');
                    }, 300);
                }
            });
        }

        // Enhanced Fluid Price Calculation with Smooth Animation
        function calculateAndDisplayPrice(duration, element) {
            if (!element) return;
            
            // Show loading state with fluid animation
            element.textContent = '...';
            element.classList.add('updating');
            element.style.animation = 'fluidPulse 0.8s ease-in-out infinite';
            
            // Calculate price
            const totalCost = (duration * state.prices.perMinute + state.prices.activation).toFixed(2);
            
            // Animate price update with enhanced fluid animation
            setTimeout(() => {
                element.textContent = totalCost;
                element.classList.remove('updating');
                element.style.animation = 'priceUpdate 0.8s ease-in-out';
                
                // Add enhanced success animation
                setTimeout(() => {
                    element.style.animation = 'fluidFloat 2s ease-in-out';
                    setTimeout(() => {
                        element.style.animation = '';
                    }, 2000);
                }, 800);
            }, 300);
        }

        // Enhanced Fluid Selection Animation
        function animateFluidSelection(button) {
            if (!button) return;
            
            // Add fluid selection animation
            button.style.animation = 'fluidGlow 1.5s ease-in-out';
            
            // Add ripple effect
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(59, 130, 246, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
                top: 50%;
                left: 50%;
                width: 100px;
                height: 100px;
                margin-left: -50px;
                margin-top: -50px;
            `;
            
            button.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        }

        // Update Custom Duration Price Preview
        function updateCustomPricePreview() {
            const input = document.getElementById('mobile-custom-duration');
            const preview = document.getElementById('custom-price-preview');
            
            if (!input || !preview) return;
            
            const value = parseInt(input.value);
            
            if (value && value >= state.planLimits.minDuration && value <= state.planLimits.maxDuration && value % 10 === 0) {
                const totalCost = (value * state.prices.perMinute + state.prices.activation).toFixed(2);
                preview.textContent = `${totalCost} EUR`;
                preview.style.animation = 'priceUpdate 0.4s ease-in-out';
                setTimeout(() => {
                    preview.style.animation = '';
                }, 400);
            } else {
                preview.textContent = '-- EUR';
            }
        }

        // Load User Credit Balance
        async function loadUserCreditBalance() {
            // Only load if user is authenticated and is a client
            @if(!auth()->check() || !$isClient)
            console.log('User not authenticated or not a client, skipping credit balance load');
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
                    state.userBalance = data.balance;
                    updateCreditDisplay();
                }
            } catch (error) {
                console.error('Erreur lors du chargement du solde:', error);
            }
        }

        // Enhanced Payment Selection with Authentication Check
        function selectPayment(method) {
            // Toggle selection - if same method is clicked, deselect it
            if (state.paymentMethod === method) {
                state.paymentMethod = null;
                document.querySelectorAll('.payment-option').forEach(option => {
                    option.classList.remove('selected');
                });
                updateButtonStates();
                return;
            }
            
            state.paymentMethod = method;
            
            // Update UI - ensure only one method is selected
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            const selectedOption = document.querySelector(`[data-method="${method}"]`);
            if (selectedOption) {
                selectedOption.classList.add('selected');
            }
            
            // Handle different payment methods
            if (method === 'credit') {
                // Allow credit payment for all users
                if (!state.isAuthenticated || !state.isClient) {
                    // Show authentication panel for non-authenticated users
                    showClientAuthPanel();
                    hideCreditInfo();
                } else {
                    // Show credit info for authenticated clients
                    hideClientAuthPanel();
                    checkCreditBalance();
                }
            } else if (method === 'offline') {
                // Hide both panels for offline payment
                hideClientAuthPanel();
                hideCreditInfo();
                
                showMessage('Paiement Sur Place sélectionné - Configurez votre réservation', 'info');
                // Auto-select minute-based reservation for offline payment
                if (!state.reservationType) {
                    state.reservationType = 'minute';
                }
                // Show reservation details modal for offline payment
                setTimeout(() => {
                    showReservationDetails();
                }, 500);
            } else {
                // Hide both panels for other payment methods (stripe, cmi)
                hideClientAuthPanel();
                hideCreditInfo();
                
                showMessage(`${method.charAt(0).toUpperCase() + method.slice(1)} sélectionné`, 'success');
            }
            
            updateButtonStates();
        }

        // Show Client Authentication Panel
        function showClientAuthPanel() {
            const authPanel = document.getElementById('client-auth-panel');
            if (authPanel) {
                authPanel.classList.remove('hidden');
                authPanel.classList.add('show');
            }
        }

        // Hide Client Authentication Panel
        function hideClientAuthPanel() {
            const authPanel = document.getElementById('client-auth-panel');
            if (authPanel) {
                authPanel.classList.add('hidden');
                authPanel.classList.remove('show');
            }
        }

        // Enhanced Credit Balance Check
        function checkCreditBalance() {
            if (!state.paymentMethod || state.paymentMethod !== 'credit') {
                hideCreditInfo();
                return;
            }

            const totalCost = calculateTotalCost();
            const userBalance = state.userBalance || 0;
            
            // Update balance information
            document.getElementById('available-balance').textContent = userBalance.toFixed(2) + ' EUR';
            document.getElementById('current-balance').textContent = userBalance.toFixed(2) + ' EUR';
            document.getElementById('required-amount').textContent = totalCost.toFixed(2) + ' EUR';
            
            if (userBalance >= totalCost) {
                // Sufficient balance
                document.getElementById('credit-info').classList.add('show');
                document.getElementById('insufficient-balance').classList.remove('show');
                
                // Enable buttons
                document.getElementById('reserve-btn').disabled = false;
                document.getElementById('checkout-btn').disabled = false;
            } else {
                // Insufficient balance
                document.getElementById('credit-info').classList.remove('show');
                document.getElementById('insufficient-balance').classList.add('show');
                
                // Disable buttons
                document.getElementById('reserve-btn').disabled = true;
                document.getElementById('checkout-btn').disabled = true;
            }
        }

        function hideCreditInfo() {
            document.getElementById('credit-info').classList.remove('show');
            document.getElementById('insufficient-balance').classList.remove('show');
        }

        function calculateTotalCost() {
            // Use selected duration or default to 30 minutes
            const duration = state.duration || 30;
            return state.prices.activation + (duration * state.prices.perMinute);
        }

        // Show Reservation Details Modal - Enhanced Mobile Design
        function showReservationDetails() {
            const totalCost = calculateTotalCost();
            const startTime = 'Maintenant'; // Mobile version uses immediate start
            const duration = '30'; // Default duration for mobile
            const reservationType = 'minute'; // Default to minute-based for mobile
            const paymentMethod = state.paymentMethod || 'Non sélectionné';
            
            // Create enhanced modal content with premium mobile design
            const modalContent = `
                <div class="fixed inset-0 bg-gradient-to-br from-black/70 via-black/50 to-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4" id="reservation-details-modal">
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl max-w-sm w-full max-h-[90vh] overflow-hidden border border-white/20">
                        <!-- Header with gradient -->
                        <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 p-5 text-white relative overflow-hidden">
                            <div class="absolute inset-0 bg-black/10"></div>
                            <div class="relative z-10">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-xl font-bold">Détails de la Réservation</h3>
                                    <button onclick="closeReservationDetails()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-full p-2 transition-all duration-200">
                                        <i class="fas fa-times text-lg"></i>
                                    </button>
                                </div>
                                <p class="text-blue-100 text-xs">Vérifiez tous les détails avant de confirmer</p>
                            </div>
                            <!-- Decorative elements -->
                            <div class="absolute -top-3 -right-3 w-16 h-16 bg-white/10 rounded-full"></div>
                            <div class="absolute -bottom-1 -left-1 w-12 h-12 bg-white/5 rounded-full"></div>
                        </div>
                        
                        <!-- Content with enhanced styling -->
                        <div class="p-4 space-y-4 max-h-[60vh] overflow-y-auto">
                            <!-- Charging Point Info Card -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-3">
                                <div class="flex items-center mb-2">
                                    <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-charging-station text-white text-sm"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-sm">Borne de Recharge</h4>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-gray-700 font-medium text-sm">📍 {{ $chargingPoint->name }}</p>
                                    <div class="flex items-center text-xs text-gray-600">
                                        <i class="fas fa-bolt text-yellow-500 mr-1"></i>
                                        <span>{{ $chargingPoint->power_level }}kW - {{ $chargingPoint->voltage }}V</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Billing Type Card -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-3">
                                <div class="flex items-center mb-2">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-calculator text-white text-sm"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-sm">Type de Facturation</h4>
                                </div>
                                <div class="flex items-center text-blue-700">
                                    <i class="fas fa-clock text-blue-500 mr-2"></i>
                                    <span class="font-medium text-sm">Par Durée (minutes)</span>
                                </div>
                            </div>
                            
                            <!-- Duration Card -->
                            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-3">
                                <div class="flex items-center mb-2">
                                    <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-hourglass-half text-white text-sm"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-sm">Durée de Recharge</h4>
                                </div>
                                ${paymentMethod === 'offline' ? `
                                    <div class="space-y-3">
                                        <p class="text-xs text-gray-600 mb-2">Choisissez la durée :</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            ${[15, 30, 60, 120].filter(d => d >= state.planLimits.minDuration && d <= state.planLimits.maxDuration).map(duration => `
                                                <button onclick="selectDurationInModalMobile(${duration})" class="duration-btn-modal-mobile p-2 border-2 border-gray-200 rounded-lg text-center hover:border-purple-500 hover:bg-purple-50 transition-all ${state.duration === duration ? 'border-purple-500 bg-purple-50' : ''}">
                                                    <div class="font-semibold text-gray-800 text-xs">${duration === 60 ? '1 heure' : duration === 120 ? '2 heures' : duration + ' min'}</div>
                                                    <div class="text-xs text-gray-500">${(duration * state.prices.perMinute + state.prices.activation).toFixed(2)} EUR</div>
                                                </button>
                                            `).join('')}
                                        </div>
                                        <div class="mt-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Durée personnalisée :</label>
                                            <div class="flex gap-1">
                                                <input type="number" id="custom-duration-modal-mobile" min="${state.planLimits.minDuration}" max="${state.planLimits.maxDuration}" step="10" 
                                                       class="flex-1 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-purple-500 focus:border-transparent" 
                                                       placeholder="Ex: 45" value="${state.duration && ![15, 30, 60, 120].includes(state.duration) ? state.duration : ''}">
                                                <button onclick="selectCustomDurationInModalMobile()" class="px-2 py-1 bg-purple-600 text-white rounded text-xs hover:bg-purple-700 transition-colors">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ` : `
                                    <div class="flex items-center text-purple-700">
                                        <i class="fas fa-clock text-purple-500 mr-2"></i>
                                        <span class="font-medium">${state.duration || duration} minutes</span>
                                    </div>
                                `}
                            </div>
                            
                            <!-- Start Time Card -->
                            <div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-xl p-3">
                                <div class="flex items-center mb-2">
                                    <div class="w-8 h-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-play text-white text-sm"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-sm">Heure de Début</h4>
                                </div>
                                <div class="flex items-center text-orange-700">
                                    <i class="fas fa-clock text-orange-500 mr-2"></i>
                                    <span class="font-medium text-sm">${startTime}</span>
                                </div>
                            </div>
                            
                            <!-- Payment Method Card -->
                            <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl p-3">
                                <div class="flex items-center mb-2">
                                    <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-credit-card text-white text-sm"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-sm">Mode de Paiement</h4>
                                </div>
                                <div class="flex items-center">
                                    ${paymentMethod === 'credit' ? `
                                        <div class="flex items-center text-green-700">
                                            <i class="fas fa-wallet text-green-500 mr-2"></i>
                                            <span class="font-medium text-sm">Paiement par Solde</span>
                                        </div>
                                    ` : paymentMethod === 'stripe' ? `
                                        <div class="flex items-center text-purple-700">
                                            <i class="fab fa-stripe text-purple-500 mr-2"></i>
                                            <span class="font-medium text-sm">Stripe</span>
                                        </div>
                                    ` : paymentMethod === 'cmi' ? `
                                        <div class="flex items-center text-blue-700">
                                            <i class="fas fa-university text-blue-500 mr-2"></i>
                                            <span class="font-medium text-sm">CMI</span>
                                        </div>
                                    ` : paymentMethod === 'offline' ? `
                                        <div class="flex items-center text-orange-700">
                                            <i class="fas fa-cash-register text-orange-500 mr-2"></i>
                                            <span class="font-medium text-sm">Sur Place</span>
                                        </div>
                                    ` : `
                                        <div class="flex items-center text-red-500">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            <span class="font-medium text-sm">Non sélectionné</span>
                                        </div>
                                    `}
                                </div>
                            </div>
                            
                            <!-- Cost Summary Card -->
                            <div class="bg-gradient-to-r from-emerald-50 to-green-50 border-2 border-emerald-200 rounded-xl p-4 relative overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/5 to-green-500/5"></div>
                                <div class="relative z-10">
                                    <div class="flex items-center mb-3">
                                        <div class="w-10 h-10 bg-gradient-to-r from-emerald-500 to-green-500 rounded-xl flex items-center justify-center mr-3">
                                            <i class="fas fa-euro-sign text-white text-lg"></i>
                                        </div>
                                        <h4 class="font-bold text-gray-800 text-lg">Coût Total</h4>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-3xl font-bold text-emerald-600 mb-2">${totalCost.toFixed(2)} EUR</p>
                                        ${state.paymentMethod === 'credit' ? `
                                            <div class="bg-white/80 rounded-lg p-3 mt-2">
                                                <p class="text-xs text-gray-600 mb-1">Solde disponible</p>
                                                <p class="text-base font-semibold text-gray-800">${state.userBalance.toFixed(2)} EUR</p>
                                                <div class="flex items-center justify-center mt-2">
                                                    ${state.userBalance >= totalCost ? `
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <i class="fas fa-check-circle mr-1"></i>
                                                            Solde suffisant
                                                        </span>
                                                    ` : `
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                                            Solde insuffisant
                                                        </span>
                                                    `}
                                                </div>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Enhanced Footer -->
                        <div class="bg-gray-50/80 backdrop-blur-sm p-4 border-t border-gray-200">
                            <div class="flex gap-2">
                                <button onclick="closeReservationDetails()" class="flex-1 bg-white border-2 border-gray-300 text-gray-700 py-2 px-4 rounded-xl font-semibold hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 shadow-sm text-sm">
                                    <i class="fas fa-times mr-1"></i>
                                    Fermer
                                </button>
                                ${state.paymentMethod ? `
                                    <button onclick="closeReservationDetails(); processPayment();" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-2 px-4 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 text-sm">
                                        <i class="fas fa-credit-card mr-1"></i>
                                        Payer
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('reservation-details-modal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalContent);
        }
        
        // Close Reservation Details Modal
        function closeReservationDetails() {
            const modal = document.getElementById('reservation-details-modal');
            if (modal) {
                modal.remove();
            }
        }

        // Functions for duration selection in modal (Mobile)
        function selectDurationInModalMobile(duration) {
            state.duration = duration;
            
            // Update button states in modal
            document.querySelectorAll('.duration-btn-modal-mobile').forEach(btn => {
                btn.classList.remove('border-purple-500', 'bg-purple-50');
                btn.classList.add('border-gray-200');
            });
            
            // Highlight selected button
            event.target.closest('.duration-btn-modal-mobile').classList.remove('border-gray-200');
            event.target.closest('.duration-btn-modal-mobile').classList.add('border-purple-500', 'bg-purple-50');
            
            // Clear custom input
            document.getElementById('custom-duration-modal-mobile').value = '';
            
            // Update cost display
            updateCostInModalMobile();
            
            showMessage(`${duration} minutes sélectionnées`, 'success');
        }

        function selectCustomDurationInModalMobile() {
            const input = document.getElementById('custom-duration-modal-mobile');
            const value = parseInt(input.value);
            
            if (value && value >= state.planLimits.minDuration && value <= state.planLimits.maxDuration && value % 10 === 0) {
                state.duration = value;
                
                // Clear preset buttons
                document.querySelectorAll('.duration-btn-modal-mobile').forEach(btn => {
                    btn.classList.remove('border-purple-500', 'bg-purple-50');
                    btn.classList.add('border-gray-200');
                });
                
                // Update cost display
                updateCostInModalMobile();
                
                showMessage(`${value} minutes sélectionnées`, 'success');
            } else {
                showMessage(`Veuillez entrer une durée valide (${state.planLimits.minDuration}-${state.planLimits.maxDuration} minutes, multiple de 10)`, 'error');
            }
        }

        function updateCostInModalMobile() {
            // This function will be called to update the cost display in the modal
            // The modal will be refreshed with new cost information
            setTimeout(() => {
                showReservationDetails();
            }, 100);
        }

        // Mobile Floating Info Tooltips for Payment Methods
        function showMobilePaymentTooltip(event, paymentMethod) {
            // Remove existing tooltip
            hideMobilePaymentTooltip();
            
            const tooltip = document.createElement('div');
            tooltip.id = 'mobile-payment-tooltip';
            tooltip.className = 'fixed z-50 bg-white border border-gray-200 rounded-xl shadow-2xl p-3 max-w-xs pointer-events-none';
            tooltip.style.left = (event.pageX + 10) + 'px';
            tooltip.style.top = (event.pageY - 10) + 'px';
            
            let tooltipContent = '';
            
            if (paymentMethod === 'credit') {
                const currentBalance = state.userBalance || 0;
                // Calculer le coût avec TVA
                const subtotalHT = state.duration ? (state.duration * state.prices.perMinute + state.prices.activation) : 0;
                const estimatedCost = subtotalHT * (1 + (state.prices.vatRate || 0));
                const canAfford = currentBalance >= estimatedCost;
                
                tooltipContent = `
                    <div class="space-y-2">
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-gray-100 pb-1">
                            <h4 class="font-bold text-gray-800 text-xs">Paiement par Solde</h4>
                            <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                        </div>
                        
                        <!-- Authentication Status -->
                        <div class="flex items-center">
                            <div class="w-6 h-6 ${state.isAuthenticated && state.isClient ? 'bg-green-100' : 'bg-orange-100'} rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-${state.isAuthenticated && state.isClient ? 'check-circle' : 'exclamation-triangle'} text-${state.isAuthenticated && state.isClient ? 'green' : 'orange'}-600 text-xs"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-xs">${state.isAuthenticated && state.isClient ? 'Compte client actif' : 'Connexion requise'}</p>
                                <p class="text-xs text-gray-600">${state.isAuthenticated && state.isClient ? 'Solde disponible' : 'Redirection vers connexion'}</p>
                            </div>
                        </div>
                        
                        <!-- Current Balance -->
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-green-100 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-wallet text-green-600 text-xs"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-xs">${currentBalance.toFixed(2)} EUR</p>
                                <p class="text-xs text-gray-600">Solde actuel</p>
                            </div>
                        </div>
                        
                        ${state.duration ? `
                            <!-- Estimated Cost -->
                            <div class="flex items-center">
                                <div class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                                    <i class="fas fa-calculator text-blue-600 text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-xs">${estimatedCost.toFixed(2)} EUR</p>
                                    <p class="text-xs text-gray-600">Coût estimé</p>
                                </div>
                            </div>
                            
                            <!-- Affordability Check -->
                            <div class="bg-${canAfford ? 'green' : 'red'}-50 rounded-lg p-2">
                                <div class="flex items-center">
                                    <i class="fas fa-${canAfford ? 'check-circle' : 'exclamation-circle'} text-${canAfford ? 'green' : 'red'}-600 mr-1 text-xs"></i>
                                    <p class="text-xs font-medium text-${canAfford ? 'green' : 'red'}-800">
                                        ${canAfford ? 'Solde suffisant' : 'Solde insuffisant'}
                                    </p>
                                </div>
                                ${!canAfford ? `
                                    <p class="text-xs text-red-600 mt-1">
                                        Manque: ${(estimatedCost - currentBalance).toFixed(2)} EUR
                                    </p>
                                ` : ''}
                            </div>
                        ` : `
                            <div class="bg-yellow-50 rounded-lg p-2">
                                <p class="text-xs text-yellow-800 text-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Sélectionnez d'abord une durée
                                </p>
                            </div>
                        `}
                        
                        <!-- Benefits -->
                        <div class="bg-green-50 rounded-lg p-1">
                            <p class="text-xs text-green-700 text-center">
                                <i class="fas fa-bolt mr-1"></i>
                                Paiement instantané
                            </p>
                        </div>
                    </div>
                `;
            } else if (paymentMethod === 'offline') {
                // Calculer le coût avec TVA
                const subtotalHT = state.duration ? (state.duration * state.prices.perMinute + state.prices.activation) : 0;
                const estimatedCost = subtotalHT * (1 + (state.prices.vatRate || 0));
                
                tooltipContent = `
                    <div class="space-y-2">
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-gray-100 pb-1">
                            <h4 class="font-bold text-gray-800 text-xs">Paiement Sur Place</h4>
                            <div class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse"></div>
                        </div>
                        
                        <!-- Payment Info -->
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-orange-100 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-cash-register text-orange-600 text-xs"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-xs">Espèces / Carte</p>
                                <p class="text-xs text-gray-600">Paiement physique</p>
                            </div>
                        </div>
                        
                        ${state.duration ? `
                            <!-- Estimated Cost -->
                            <div class="flex items-center">
                                <div class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                                    <i class="fas fa-calculator text-blue-600 text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-xs">${estimatedCost.toFixed(2)} EUR</p>
                                    <p class="text-xs text-gray-600">Coût estimé</p>
                                </div>
                            </div>
                        ` : `
                            <div class="bg-yellow-50 rounded-lg p-2">
                                <p class="text-xs text-yellow-800 text-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Sélectionnez d'abord une durée
                                </p>
                            </div>
                        `}
                        
                        <!-- Process Info -->
                        <div class="bg-orange-50 rounded-lg p-2">
                            <h5 class="font-semibold text-orange-800 text-xs mb-1">Processus :</h5>
                            <div class="space-y-1 text-xs text-orange-700">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-orange-200 rounded-full flex items-center justify-center mr-1 text-xs">1</span>
                                    Sélectionnez durée
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-orange-200 rounded-full flex items-center justify-center mr-1 text-xs">2</span>
                                    Confirmez réservation
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 bg-orange-200 rounded-full flex items-center justify-center mr-1 text-xs">3</span>
                                    Payez sur place
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            tooltip.innerHTML = tooltipContent;
            document.body.appendChild(tooltip);
            
            // Position tooltip to stay within viewport (mobile optimized)
            const rect = tooltip.getBoundingClientRect();
            if (rect.right > window.innerWidth) {
                tooltip.style.left = (event.pageX - rect.width - 10) + 'px';
            }
            if (rect.bottom > window.innerHeight) {
                tooltip.style.top = (event.pageY - rect.height - 10) + 'px';
            }
        }

        function hideMobilePaymentTooltip() {
            const tooltip = document.getElementById('mobile-payment-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        }

        // Mobile Duration Selection Functions
        function selectMobileDuration(duration) {
            state.duration = duration;
            state.reservationType = 'minute'; // Auto-set to minute-based
            
            // Update UI - Remove selected state from all buttons with smooth animation
            document.querySelectorAll('.mobile-duration-btn').forEach(btn => {
                btn.classList.remove('selected', 'border-blue-500', 'bg-blue-50', 'border-green-500', 'border-purple-500', 'border-red-500');
                btn.classList.add('border-gray-200');
                btn.style.animation = '';
            });
            
            // Add selected state to the clicked button with enhanced fluid animation
            const selectedBtn = document.querySelector(`[data-duration="${duration}"]`);
            if (selectedBtn) {
                selectedBtn.classList.remove('border-gray-200');
                selectedBtn.classList.add('selected');
                
                // Enhanced fluid visual feedback
                selectedBtn.style.transform = 'scale(0.95)';
                selectedBtn.style.animation = 'fluidPulse 0.8s ease-out';
                
                // Add ripple effect
                animateFluidSelection(selectedBtn);
                
                setTimeout(() => {
                    selectedBtn.style.transform = 'scale(1.05)';
                    selectedBtn.style.animation = 'fluidGlow 2s ease-in-out infinite';
                }, 200);
                
                setTimeout(() => {
                    selectedBtn.style.transform = '';
                }, 400);
                
                // Add specific border color based on duration
                if (duration === 15) {
                    selectedBtn.classList.add('border-blue-500');
                } else if (duration === 30) {
                    selectedBtn.classList.add('border-green-500');
                } else if (duration === 60) {
                    selectedBtn.classList.add('border-purple-500');
                } else if (duration === 120) {
                    selectedBtn.classList.add('border-red-500');
                }
                
                // Update price display with enhanced fluid animation
                const priceDisplay = selectedBtn.querySelector('.price-display');
                if (priceDisplay) {
                    calculateAndDisplayPrice(duration, priceDisplay);
                }
            }
            
            // Clear custom input
            document.getElementById('mobile-custom-duration').value = '';
            
            // Enhanced success message
            showMessage(`⚡ ${duration} minutes sélectionnées`, 'success');
            updateButtonStates();
            updateFloatingBanner();
            
            // Add haptic feedback if available
            if (navigator.vibrate) {
                navigator.vibrate([50, 30, 50]);
            }
        }

        function selectMobileCustomDuration() {
            const input = document.getElementById('mobile-custom-duration');
            const value = parseInt(input.value);
            
            if (value && value >= state.planLimits.minDuration && value <= state.planLimits.maxDuration && value % 10 === 0) {
                state.duration = value;
                state.reservationType = 'minute'; // Auto-set to minute-based
                
                // Clear preset buttons - Remove all selected states
                document.querySelectorAll('.mobile-duration-btn').forEach(btn => {
                    btn.classList.remove('selected', 'border-blue-500', 'bg-blue-50', 'border-green-500', 'border-purple-500', 'border-red-500');
                    btn.classList.add('border-gray-200');
                });
                
                // Add visual feedback to the input
                input.classList.add('border-green-500', 'bg-green-50');
                setTimeout(() => {
                    input.classList.remove('border-green-500', 'bg-green-50');
                }, 1000);
                
                showMessage(`${value} minutes sélectionnées`, 'success');
                updateButtonStates();
                updateFloatingBanner();
            } else {
                showMessage(`Veuillez entrer une durée valide (${state.planLimits.minDuration}-${state.planLimits.maxDuration} minutes, multiple de 10)`, 'error');
                
                // Add error feedback to the input
                input.classList.add('border-red-500', 'bg-red-50');
                setTimeout(() => {
                    input.classList.remove('border-red-500', 'bg-red-50');
                }, 1000);
            }
        }

        // Mobile Duration Tooltips
        function showMobileDurationTooltip(event, duration) {
            // Remove existing tooltip
            hideMobileDurationTooltip();
            
            const totalCost = (duration * state.prices.perMinute + state.prices.activation);
            const energyEstimate = (duration * {{ $chargingPoint->power_level ?? 22 }} / 60).toFixed(1);
            
            const tooltip = document.createElement('div');
            tooltip.id = 'mobile-duration-tooltip';
            tooltip.className = 'fixed z-50 bg-white border border-gray-200 rounded-xl shadow-2xl p-3 max-w-xs pointer-events-none';
            tooltip.style.left = (event.pageX + 10) + 'px';
            tooltip.style.top = (event.pageY - 10) + 'px';
            
            tooltip.innerHTML = `
                <div class="space-y-2">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 pb-1">
                        <h4 class="font-bold text-gray-800 text-xs">Détails de la Réservation</h4>
                        <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    
                    <!-- Duration Info -->
                    <div class="flex items-center">
                        <div class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                            <i class="fas fa-clock text-blue-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-xs">${duration} minutes</p>
                            <p class="text-xs text-gray-600">Durée de recharge</p>
                        </div>
                    </div>
                    
                    <!-- Energy Estimate -->
                    <div class="flex items-center">
                        <div class="w-6 h-6 bg-yellow-100 rounded-lg flex items-center justify-center mr-2">
                            <i class="fas fa-bolt text-yellow-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-xs">~${energyEstimate} kWh</p>
                            <p class="text-xs text-gray-600">Énergie estimée</p>
                        </div>
                    </div>
                    
                    <!-- Charging Point -->
                    <div class="flex items-center">
                        <div class="w-6 h-6 bg-green-100 rounded-lg flex items-center justify-center mr-2">
                            <i class="fas fa-charging-station text-green-600 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-xs">{{ $chargingPoint->name }}</p>
                            <p class="text-xs text-gray-600">{{ $chargingPoint->power_level ?? 22 }}kW - {{ $chargingPoint->voltage ?? 400 }}V</p>
                        </div>
                    </div>
                    
                    <!-- Cost Breakdown -->
                    <div class="bg-gray-50 rounded-lg p-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Durée (${duration} min)</span>
                            <span class="font-medium">${(duration * state.prices.perMinute).toFixed(2)} EUR</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Frais d'activation</span>
                            <span class="font-medium">${state.prices.activation.toFixed(2)} EUR</span>
                        </div>
                        <div class="border-t border-gray-200 pt-1 flex justify-between">
                            <span class="font-bold text-gray-800 text-xs">Total</span>
                            <span class="font-bold text-green-600 text-sm">${totalCost.toFixed(2)} EUR</span>
                        </div>
                    </div>
                    
                    <!-- Payment Info -->
                    <div class="bg-blue-50 rounded-lg p-1">
                        <p class="text-xs text-blue-700 text-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Sélectionnez ensuite votre mode de paiement
                        </p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(tooltip);
            
            // Position tooltip to stay within viewport (mobile optimized)
            const rect = tooltip.getBoundingClientRect();
            if (rect.right > window.innerWidth) {
                tooltip.style.left = (event.pageX - rect.width - 10) + 'px';
            }
            if (rect.bottom > window.innerHeight) {
                tooltip.style.top = (event.pageY - rect.height - 10) + 'px';
            }
        }

        function hideMobileDurationTooltip() {
            const tooltip = document.getElementById('mobile-duration-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        }

        // Enhanced Toast Notification System for Mobile
        function showMessage(message, type = 'info', duration = 4000) {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.toast-notification');
            existingToasts.forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = 'toast-notification fixed top-4 left-4 right-4 z-50 transform transition-all duration-500 translate-y-full';
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            const colors = {
                success: 'bg-gradient-to-r from-green-500 to-emerald-500',
                error: 'bg-gradient-to-r from-red-500 to-rose-500',
                warning: 'bg-gradient-to-r from-yellow-500 to-orange-500',
                info: 'bg-gradient-to-r from-blue-500 to-indigo-500'
            };
            
            toast.innerHTML = `
                <div class="${colors[type] || colors.info} text-white p-4 rounded-xl shadow-2xl border border-white/20 backdrop-blur-sm">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="${icons[type] || icons.info} text-lg"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium">${message}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <button onclick="this.closest('.toast-notification').remove()" class="text-white/80 hover:text-white transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-2 w-full bg-white/20 rounded-full h-1">
                        <div class="bg-white h-1 rounded-full transition-all duration-${duration}" style="width: 100%; animation: shrink ${duration}ms linear forwards;"></div>
                    </div>
                </div>
            `;
            
            // Add CSS animation
            if (!document.getElementById('toast-animations-mobile')) {
                const style = document.createElement('style');
                style.id = 'toast-animations-mobile';
                style.textContent = `
                    @keyframes shrink {
                        from { width: 100%; }
                        to { width: 0%; }
                    }
                    .toast-notification {
                        animation: slideInMobile 0.5s ease-out forwards;
                    }
                    @keyframes slideInMobile {
                        from { transform: translateY(100%); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                    @keyframes slideOutMobile {
                        from { transform: translateY(0); opacity: 1; }
                        to { transform: translateY(100%); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(toast);
            
            // Auto remove
            setTimeout(() => {
                toast.style.animation = 'slideOutMobile 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        function updateButtonStates() {
            const hasPaymentMethod = state.paymentMethod !== null;
            const hasDuration = state.duration !== null;
            
            // Allow all users to proceed with any payment method
            // Only require duration selection for offline payments
            let canProceed = hasPaymentMethod;
            
            if (state.paymentMethod === 'offline') {
                canProceed = hasPaymentMethod && hasDuration;
            }
            
            // For credit payment, show warning if not authenticated but still allow
            if (state.paymentMethod === 'credit' && (!state.isAuthenticated || !state.isClient)) {
                // User can still proceed, but will be prompted to authenticate
                canProceed = hasPaymentMethod;
            }
            
            document.getElementById('reserve-btn').disabled = !canProceed;
            document.getElementById('checkout-btn').disabled = !canProceed;
        }

        function initializePaymentMethods() {
            // No auto-selection - user must manually select a payment method
            // This ensures no payment method is pre-selected
            console.log('Payment methods initialized - no auto-selection');
        }

        // Enhanced Form Submission
        function submitReservation() {
            if (!state.paymentMethod) {
                alert('Veuillez sélectionner une méthode de paiement');
                return;
            }

            // Add loading state
            const btn = document.getElementById('reserve-btn');
            btn.classList.add('loading');
            btn.disabled = true;

            // Simulate API call
            setTimeout(() => {
                btn.classList.remove('loading');
                btn.disabled = false;
                alert('Réservation soumise avec succès !');
            }, 2000);
        }

        // Enhanced Credit Payment Processing for Mobile
        async function processCreditPayment() {
            if (!state.paymentMethod || state.paymentMethod !== 'credit') {
                alert('Méthode de paiement par crédit non sélectionnée');
                return;
            }

            const totalCost = calculateTotalCost();
            const userBalance = state.userBalance || 0;

            if (userBalance < totalCost) {
                alert('Solde insuffisant pour cette transaction');
                return;
            }

            try {
                // Show loading state
                const btn = document.getElementById('checkout-btn');
                btn.classList.add('loading');
                btn.disabled = true;

                // Prepare payment data
                const paymentData = {
                    amount: totalCost,
                    description: 'Paiement session de recharge mobile - {{ $chargingPoint->name ?? "Borne" }}',
                    reference: 'PAY_MOBILE_' + Date.now() + '_{{ $chargingPoint->id ?? "unknown" }}',
                    _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                };

                // Process credit payment
                const response = await fetch('{{ route("charging-points.credit-payment", ["id" => $chargingPoint->id ?? 1]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(paymentData)
                });

                const result = await response.json();

                if (result.success) {
                    // Update user balance
                    const newBalance = result.new_balance ?? result.remaining_balance ?? state.userBalance;
                    state.userBalance = newBalance;
                    const formatted = result.formatted_balance ?? (newBalance.toFixed(2) + ' EUR');
                    updateCreditDisplay();
                    document.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                        detail: { balance: newBalance, formatted: formatted, remaining_balance: newBalance, currency: 'EUR' },
                        bubbles: true
                    }));
                    window.dispatchEvent(new CustomEvent('walletBalanceUpdated', {
                        detail: { balance: newBalance, formatted: formatted, remaining_balance: newBalance, currency: 'EUR' },
                        bubbles: true
                    }));
                    
                    // Show success message
                    showMobileMessage('Paiement traité avec succès !', 'success');
                    
                    // Redirect to charging in progress view
                    setTimeout(() => {
                        window.location.href = '{{ route("charging-points.charging", ["id" => $chargingPoint->id ?? 1]) }}?payment=credit&duration=' + state.duration + '&cost=' + totalCost;
                    }, 2000);
                } else {
                    showMobileMessage(result.message || 'Erreur lors du paiement', 'error');
                }

            } catch (error) {
                console.error('Erreur lors du paiement par crédit:', error);
                showMobileMessage('Erreur de connexion lors du paiement', 'error');
            } finally {
                // Remove loading state
                const btn = document.getElementById('checkout-btn');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }

        // Update Credit Display for Mobile
        function updateCreditDisplay() {
            const balanceElements = document.querySelectorAll('#user-balance, #available-balance, #current-balance');
            balanceElements.forEach(element => {
                element.textContent = state.userBalance.toFixed(2) + ' EUR';
            });
        }

        // Mobile Message Display
        function showMobileMessage(message, type = 'info') {
            // Create mobile-friendly message
            const messageDiv = document.createElement('div');
            messageDiv.className = `fixed top-4 left-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            messageDiv.textContent = message;
            
            document.body.appendChild(messageDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }

        function processPayment() {
            if (!state.paymentMethod) {
                showMobileMessage('Veuillez sélectionner une méthode de paiement', 'error');
                return;
            }

            if (state.paymentMethod === 'credit') {
                // Allow credit payment for all users
                if (!state.isAuthenticated || !state.isClient) {
                    // Redirect to login with return URL
                    const currentUrl = encodeURIComponent(window.location.href);
                    window.location.href = `/login?redirect=${currentUrl}`;
                    return;
                }
                processCreditPayment();
                return;
            }

            if (state.paymentMethod === 'offline') {
                processOfflinePayment();
                return;
            }

            // Handle other payment methods (stripe, cmi)
            const btn = document.getElementById('checkout-btn');
            btn.classList.add('loading');
            btn.disabled = true;

            showMobileMessage(`Traitement du paiement ${state.paymentMethod}...`, 'info');

            // Simulate API call for other methods
            setTimeout(() => {
                btn.classList.remove('loading');
                btn.disabled = false;
                showMobileMessage(`Paiement ${state.paymentMethod} traité avec succès`, 'success');
                
                // Redirect to success page or charging view
                setTimeout(() => {
                    const params = new URLSearchParams({
                        payment: state.paymentMethod,
                        duration: state.duration || 30,
                        cost: calculateTotalCost()
                    });
                    window.location.href = `{{ route("charging-points.charging", ["id" => $chargingPoint->id ?? 1]) }}?${params.toString()}`;
                }, 2000);
            }, 2000);
        }

        // Process Offline Payment for Mobile
        function processOfflinePayment() {
            if (!state.paymentMethod || state.paymentMethod !== 'offline') {
                showMobileMessage('Méthode de paiement offline non sélectionnée', 'error');
                return;
            }

            const totalCost = calculateTotalCost();
            
            try {
                // Show loading state
                const btn = document.getElementById('checkout-btn');
                btn.classList.add('loading');
                btn.disabled = true;

                showMobileMessage('Réservation en cours de traitement...', 'success');
                
                // Simulate processing time
                setTimeout(() => {
                    // Redirect to reservation thank you page
                    const params = new URLSearchParams({
                        duration: state.duration,
                        total_cost: totalCost,
                        payment_method: 'offline'
                    });
                    
                    window.location.href = '{{ route("charging-points.reservation-thank-you", ["id" => $chargingPoint->id ?? 1]) }}?' + params.toString();
                }, 2000);

            } catch (error) {
                console.error('Erreur lors du traitement de la réservation:', error);
                showMobileMessage('Erreur lors du traitement de la réservation', 'error');
            } finally {
                // Remove loading state
                const btn = document.getElementById('checkout-btn');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }

        // Enhanced Touch Interactions
        document.addEventListener('touchstart', function(e) {
            if (e.target.closest('.payment-option')) {
                e.target.closest('.payment-option').style.transform = 'scale(0.98)';
            }
        });

        document.addEventListener('touchend', function(e) {
            if (e.target.closest('.payment-option')) {
                e.target.closest('.payment-option').style.transform = '';
            }
        });

        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // ============================================
        // FLOATING ESTIMATION BANNER FUNCTIONS
        // ============================================

        /**
         * Show the floating estimation banner with animation
         */
        function showFloatingBanner() {
            const banner = document.getElementById('floating-estimation-banner');
            if (banner) {
                banner.classList.remove('translate-y-full', 'opacity-0');
                banner.classList.add('translate-y-0', 'opacity-100');
                
                // Add entrance animation
                banner.style.transform = 'translateY(0) scale(1)';
                banner.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
            }
        }

        /**
         * Hide the floating estimation banner with animation
         */
        function hideFloatingBanner() {
            const banner = document.getElementById('floating-estimation-banner');
            if (banner) {
                banner.classList.add('translate-y-full', 'opacity-0');
                banner.classList.remove('translate-y-0', 'opacity-100');
                
                // Add exit animation
                banner.style.transform = 'translateY(100%) scale(0.95)';
                banner.style.transition = 'all 0.3s ease-in-out';
            }
        }

        /**
         * Update the floating banner with current estimation
         */
        function updateFloatingBanner() {
            if (!state.duration || state.duration <= 0) {
                hideFloatingBanner();
                return;
            }

            const duration = state.duration;
            const perMinutePrice = state.prices.perMinute || 0;
            const activationPrice = state.prices.activation || 0;
            const totalPrice = (duration * perMinutePrice + activationPrice).toFixed(2);

            // Update banner content
            const durationElement = document.getElementById('banner-duration');
            const totalPriceElement = document.getElementById('banner-total-price');
            const perMinuteElement = document.getElementById('banner-per-minute');
            const activationElement = document.getElementById('banner-activation');
            const progressElement = document.getElementById('banner-progress');

            if (durationElement) {
                durationElement.textContent = `${duration} min`;
            }

            if (totalPriceElement) {
                totalPriceElement.textContent = `${totalPrice} EUR`;
            }

            if (perMinuteElement) {
                perMinuteElement.textContent = `${perMinutePrice.toFixed(2)} EUR/min`;
            }

            if (activationElement) {
                activationElement.textContent = `${activationPrice.toFixed(2)} EUR activation`;
            }

            // Update progress bar based on duration (max 120 minutes)
            if (progressElement) {
                const maxDuration = state.planLimits?.maxDuration || 120;
                const progressPercentage = Math.min((duration / maxDuration) * 100, 100);
                progressElement.style.width = `${progressPercentage}%`;
            }

            // Show banner with animation
            showFloatingBanner();

            // Add pulse effect to price
            if (totalPriceElement) {
                totalPriceElement.classList.add('animate-pulse');
                setTimeout(() => {
                    totalPriceElement.classList.remove('animate-pulse');
                }, 1000);
            }
        }

        /**
         * Auto-hide banner after a delay
         */
        let bannerTimeout;
        function scheduleBannerHide() {
            clearTimeout(bannerTimeout);
            bannerTimeout = setTimeout(() => {
                hideFloatingBanner();
            }, 10000); // Hide after 10 seconds
        }
    </script>

    <!-- Floating Estimation Banner -->
    <div id="floating-estimation-banner" class="fixed bottom-4 left-4 right-4 z-50 transform translate-y-full opacity-0 transition-all duration-500 ease-in-out">
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-2xl border border-white/20 backdrop-blur-sm relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-20 h-20 bg-white rounded-full -translate-x-10 -translate-y-10 animate-pulse"></div>
                <div class="absolute bottom-0 right-0 w-16 h-16 bg-white rounded-full translate-x-8 translate-y-8 animate-pulse" style="animation-delay: 1s;"></div>
            </div>
            
            <!-- Shimmer Effect -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full animate-shimmer"></div>
            
            <div class="relative z-10 p-4">
                <div class="flex items-center justify-between">
                    
                    <!-- Right Side - Price Info -->
                    <div class="text-right">
                        <div class="text-white font-black text-2xl" id="banner-total-price">0.00 EUR</div>
                        <div class="text-white/80 text-sm">Estimation totale</div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mt-3 bg-white/20 rounded-full h-2 overflow-hidden">
                    <div id="banner-progress" class="h-full bg-gradient-to-r from-yellow-400 to-orange-400 rounded-full transition-all duration-1000 ease-out" style="width: 0%"></div>
                </div>
                
                <!-- Additional Info -->
                <div class="mt-2 flex items-center justify-between text-white/70 text-xs">
                    <div class="flex items-center">
                        <i class="fas fa-bolt mr-1"></i>
                        <span id="banner-per-minute">0.00 EUR/min</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-plus mr-1"></i>
                        <span id="banner-activation">0.00 EUR activation</span>
                    </div>
                </div>
            </div>
            
            <!-- Close Button -->
            <button onclick="hideFloatingBanner()" class="absolute top-2 right-2 w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-white hover:bg-white/30 transition-colors duration-200">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    </div>
</body>
</html>
