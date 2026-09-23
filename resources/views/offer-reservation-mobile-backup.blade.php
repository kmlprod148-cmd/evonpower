@extends('layouts.public')

@section('title', 'Réservation de la borne')

@push('styles')
    <style>
        /* Mobile-first design - Enhanced for better mobile targeting with performance optimizations */
        body {
            background-color: #ffffff;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            /* Performance optimizations */
            will-change: transform;
            transform: translateZ(0);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .mobile-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
            background: #ffffff;
            min-height: 100vh;
        }

        /* Header mobile optimisé - Enhanced for mobile targeting */
        .mobile-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1.5rem 1rem;
            position: relative;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.3);
            border-bottom: 4px solid #059669;
            backdrop-filter: blur(10px);
            border-radius: 0 0 24px 24px;
        }

        .mobile-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .mobile-header-info {
            flex: 1;
            min-width: 0;
        }

        .mobile-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 0.25rem 0;
            line-height: 1.2;
        }

        .mobile-header p {
            font-size: 0.875rem;
            margin: 0;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Mobile CTA Badge */
        .mobile-cta-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            margin-top: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            animation: mobilePulse 2s infinite;
        }

        @keyframes mobilePulse {

            0%,
            100% {
                opacity: 0.8;
            }

            50% {
                opacity: 1;
            }
        }

        .mobile-qr-code {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 8px;
            padding: 4px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .mobile-qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Back button */
        .mobile-back-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }

        .mobile-back-btn:active {
            transform: scale(0.95);
            background: rgba(255, 255, 255, 0.3);
        }

        /* Content sections */
        .mobile-content {
            padding: 1rem;
            background: #f8fafc;
            min-height: calc(100vh - 120px);
        }

        .mobile-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mobile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .mobile-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Station info */
        .mobile-station-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .mobile-station-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .mobile-station-item:last-child {
            border-bottom: none;
        }

        .mobile-station-label {
            color: #64748b;
            font-weight: 500;
        }

        .mobile-station-value {
            color: #1e293b;
            font-weight: 600;
        }

        /* Reservation type selection */
        .mobile-reservation-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .mobile-reservation-type {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            touch-action: manipulation;
            position: relative;
            overflow: hidden;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            /* Performance optimizations */
            will-change: transform, box-shadow, border-color;
            transform: translateZ(0);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            /* Touch optimizations */
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-reservation-type::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .mobile-reservation-type:hover::before {
            left: 100%;
        }

        .mobile-reservation-type:active {
            transform: scale(0.95);
        }

        .mobile-reservation-type.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            animation: mobileSelectedPulse 0.6s ease-out;
        }

        @keyframes mobileSelectedPulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.03);
            }

            100% {
                transform: scale(1);
            }
        }

        .mobile-reservation-type.disabled {
            opacity: 0.5;
            pointer-events: none;
            filter: grayscale(100%);
        }

        .mobile-reservation-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .mobile-reservation-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 0.25rem 0;
        }

        .mobile-reservation-subtitle {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }

        /* Duration/Energy selection */
        .mobile-duration-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .mobile-duration-btn {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            touch-action: manipulation;
            position: relative;
            overflow: hidden;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            /* Performance optimizations */
            will-change: transform, box-shadow, border-color;
            transform: translateZ(0);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            /* Touch optimizations */
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-duration-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.3s ease;
        }

        .mobile-duration-btn:active::after {
            width: 150px;
            height: 150px;
        }

        .mobile-duration-btn:active {
            transform: scale(0.95);
        }

        .mobile-duration-btn.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            animation: mobileDurationBounce 0.5s ease-out;
        }

        @keyframes mobileDurationBounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: scale(1);
            }

            40% {
                transform: scale(1.05);
            }

            60% {
                transform: scale(1.02);
            }
        }

        .mobile-duration-btn.disabled {
            opacity: 0.5;
            pointer-events: none;
            filter: grayscale(100%);
        }

        .mobile-duration-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 0.25rem 0;
        }

        .mobile-duration-label {
            font-size: 0.75rem;
            color: #64748b;
            margin: 0;
        }

        /* Custom input */
        .mobile-custom-input {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .mobile-custom-input label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .mobile-custom-input input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            background: white;
            transition: border-color 0.2s ease;
        }

        .mobile-custom-input input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .mobile-custom-input .input-help {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Time selection */
        .mobile-time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .mobile-time-slot {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem 0.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            touch-action: manipulation;
        }

        .mobile-time-slot:active {
            transform: scale(0.98);
        }

        .mobile-time-slot.selected {
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .mobile-time-slot.available {
            border-color: #10b981;
        }

        .mobile-time-slot.unavailable {
            opacity: 0.5;
            background: #fef2f2;
            border-color: #ef4444;
            pointer-events: none;
        }

        .mobile-time-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 0.25rem 0;
        }

        .mobile-time-status {
            font-size: 0.75rem;
            color: #64748b;
            margin: 0;
        }

        /* Cost calculation */
        .mobile-cost-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
        }

        .mobile-cost-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .mobile-cost-header h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .mobile-cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 0.875rem;
        }

        .mobile-cost-item:not(:last-child) {
            border-bottom: 1px solid #d1fae5;
        }

        .mobile-cost-label {
            color: #374151;
        }

        .mobile-cost-value {
            font-weight: 600;
            color: #059669;
        }

        .mobile-cost-total {
            background: #dcfce7;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.75rem;
        }

        .mobile-cost-total .mobile-cost-item {
            border-bottom: none;
            font-size: 1rem;
        }

        .mobile-cost-total .mobile-cost-label {
            font-weight: 700;
            color: #1e293b;
        }

        .mobile-cost-total .mobile-cost-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #059669;
        }

        /* Reserve button */
        .mobile-reserve-btn {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            touch-action: manipulation;
            box-shadow: 0 8px 32px rgba(16, 185, 129, 0.4);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }

        .mobile-reserve-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .mobile-reserve-btn:hover::before {
            left: 100%;
        }

        .mobile-reserve-btn:active {
            transform: scale(0.95);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }

        .mobile-reserve-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .mobile-reserve-btn:disabled:active {
            transform: none;
        }

        /* Loading state */
        .mobile-reserve-btn.loading {
            pointer-events: none;
            opacity: 0.8;
            animation: mobileLoadingPulse 1.5s infinite;
        }

        @keyframes mobileLoadingPulse {

            0%,
            100% {
                opacity: 0.8;
            }

            50% {
                opacity: 1;
            }
        }

        /* Success state */
        .mobile-reserve-btn.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            animation: mobileSuccessPulse 0.8s ease-out;
        }

        @keyframes mobileSuccessPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* Success alert text styling for mobile */
        .success-title {
            color: #059669 !important;
            font-weight: 700 !important;
            font-size: 1.25rem !important;
            text-shadow: 0 1px 2px rgba(5, 150, 105, 0.2);
        }

        .success-message {
            color: #047857 !important;
            font-weight: 500 !important;
            line-height: 1.5 !important;
            font-size: 1rem !important;
        }

        .success-body {
            color: #065f46 !important;
            font-size: 0.875rem !important;
            line-height: 1.4 !important;
        }

        /* Mobile Payment Methods Styles */
        .mobile-payment-method-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            min-height: 80px;
        }

        .mobile-payment-method-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        .mobile-payment-method-card.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
        }

        .mobile-payment-method-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .mobile-payment-method-card.selected::before {
            transform: scaleX(1);
        }

        .mobile-payment-method-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .mobile-payment-method-logo {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
        }

        .mobile-payment-method-info h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .mobile-payment-method-info p {
            color: #64748b;
            margin: 0.25rem 0 0 0;
            font-size: 0.85rem;
        }

        .mobile-payment-method-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .mobile-feature-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .mobile-selected-indicator {
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

        .mobile-payment-method-card.selected .mobile-selected-indicator {
            opacity: 1;
            transform: scale(1);
        }

        /* Enhanced mobile payment method card styles */
        .payment-method-card .payment-method-features {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .payment-method-card .payment-method-features span {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
        }

        /* CMI specific mobile styles */
        .payment-method-card[data-provider="cmi"]:hover {
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .payment-method-card[data-provider="cmi"].selected {
            border-color: #059669;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        }

        /* Stripe specific mobile styles */
        .payment-method-card[data-provider="stripe"]:hover {
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .payment-method-card[data-provider="stripe"].selected {
            border-color: #059669;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        }

        /* Limit info */
        .mobile-limit-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Missing selection highlight */
        .mobile-missing-selection {
            animation: pulse-red 2s infinite;
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3) !important;
        }

        @keyframes pulse-red {

            0%,
            100% {
                box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3);
            }

            50% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0.1);
            }
        }

        /* Custom time input */
        .mobile-time-input {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .mobile-time-input input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            background: white;
        }

        .mobile-time-input input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .mobile-now-btn {
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .mobile-now-btn:active {
            transform: scale(0.98);
            background: #2563eb;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .mobile-duration-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .mobile-time-slots {
                grid-template-columns: repeat(2, 1fr);
            }

            .mobile-station-info {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 481px) and (max-width: 768px) {
            .mobile-duration-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .mobile-time-slots {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Enhanced mobile optimizations for better targeting */
        @media (max-width: 768px) {
            .mobile-optimized {
                font-size: 16px;
                line-height: 1.5;
            }

            .touch-target {
                min-height: 56px;
                min-width: 56px;
                padding: 16px;
            }

            .mobile-card {
                border-radius: 20px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            }

            .mobile-button {
                padding: 24px 36px;
                border-radius: 20px;
                font-size: 16px;
                font-weight: 600;
                min-height: 64px;
            }

            /* Enhanced mobile interactions with better feedback */
            .mobile-reservation-type:active {
                transform: scale(0.95);
                background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }

            .mobile-duration-btn:active {
                transform: scale(0.95);
                background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }

            .mobile-reserve-btn:active {
                transform: scale(0.98);
                box-shadow: 0 4px 20px rgba(59, 130, 246, 0.6);
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            }

            /* Improved mobile typography */
            .mobile-header h1 {
                font-size: 1.5rem;
                font-weight: 800;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .mobile-card h3 {
                font-size: 1.25rem;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 1rem;
            }

            /* Better spacing for mobile */
            .mobile-content {
                padding: 1rem;
            }

            .mobile-card {
                margin-bottom: 2rem;
            }
        }

        /* iPhone specific optimizations */
        @@supports (-webkit-touch-callout: none) {
            .mobile-container {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }

            .mobile-header {
                padding-top: calc(1rem + env(safe-area-inset-top));
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #0f172a;
                color: #f1f5f9;
            }

            .mobile-content {
                background: #0f172a;
            }

            .mobile-card {
                background: #1e293b;
                border-color: #334155;
            }

            .mobile-card h3 {
                color: #f1f5f9;
            }
        }
    </style>
@endpush

@section('content')
    <div class="mobile-container">
        <!-- Mobile Header -->
        <div class="mobile-header">

            <div class="mobile-header-content">
                <div class="mobile-header-info">
                    <h1>{{ $chargingPoint->name ?? 'Borne de Recharge' }}</h1>
                    <p>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>{{ $chargingPoint->address ?? optional($chargingPoint->location)->address ?? 'Adresse non disponible' }}</span>
                    </p>
                    <!-- Mobile-specific call-to-action -->
                    <div class="mobile-cta-badge">
                        <i class="fas fa-bolt"></i>
                        <span>Rechargez en toute simplicité</span>
                    </div>
                </div>
                <div class="mobile-qr-code">
                    <img src="{{ $qrCode }}" alt="QR Code" />
                </div>
            </div>
        </div>

        <!-- Payment Methods Section - OPTIONAL -->
        <div class="mobile-card" id="payment-methods-section">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <div class="app-card-header">
                    <div class="app-card-icon bg-orange-100 text-orange-600">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div>
                        <h3 class="app-card-title">Méthodes de Paiement</h3>
                        <p class="app-card-subtitle">Choisissez votre mode de paiement</p>
                    </div>
                    <span class="app-badge app-badge-warning">OPTIONNEL</span>
                </div>
            <p class="text-sm text-gray-600 mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Vous pouvez choisir une méthode de paiement maintenant ou plus tard
            </p>

            <!-- Static Payment Methods with Logos - Compact Version -->
            <div class="grid grid-cols-2 gap-2 mb-4">
                <!-- CMI Payment Method -->
                <div class="payment-method-card" data-provider="cmi" onclick="selectPaymentMethod('cmi')">
                    <div class="flex items-center justify-between p-2 border-2 border-gray-200 rounded-lg hover:border-blue-500 transition-all duration-300 cursor-pointer">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg flex items-center justify-center mr-2">
                                <i class="fas fa-university text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800">CMI</h4>
                                <p class="text-xs text-gray-600">Sécurisé</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stripe Payment Method -->
                <div class="payment-method-card" data-provider="stripe" onclick="selectPaymentMethod('stripe')">
                    <div class="flex items-center justify-between p-2 border-2 border-gray-200 rounded-lg hover:border-purple-500 transition-all duration-300 cursor-pointer">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-purple-600 to-purple-800 rounded-lg flex items-center justify-center mr-2">
                                <i class="fab fa-stripe text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800">Stripe</h4>
                                <p class="text-xs text-gray-600">International</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="text-xs text-gray-500 text-center mb-4">
                <i class="fas fa-shield-alt mr-1"></i>
                Paiements sécurisés et cryptés
            </div>

            <!-- Dynamic Payment Methods (fallback) -->
            <div id="paymentMethodsContainer" class="grid grid-cols-1 gap-3" style="display: none;">
                <!-- Payment methods will be loaded here -->
            </div>

            <div class="mt-3 flex items-center justify-between text-sm">
                <div class="text-gray-600 flex items-center">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Paiements sécurisés
                </div>
                <div class="text-blue-600 flex items-center">
                    <i class="fas fa-clock mr-1"></i>
                    Paiement à la borne
                </div>
            </div>
        </div>

        <!-- Enhanced Cost Calculation - FLOATING FOR ALWAYS VISIBLE -->
        <div class="fixed top-4 left-4 right-4 z-40 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-2 border border-green-200 shadow-lg backdrop-blur-sm" id="cost-calculation-card">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-calculator text-green-600 mr-1 text-xs"></i>
                    <h4 class="text-xs font-semibold text-gray-900">Estimation</h4>
                    <div id="cost-loading" class="ml-1 hidden">
                        <div class="w-2 h-2 border-2 border-green-600 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-bold text-green-700" id="total-ttc">0.00 {{ $currency ?? 'EUR' }}</div>
                    <div class="text-xs text-gray-500" id="calculation-label">Base</div>
                </div>
            </div>

            <!-- Compact Cost Breakdown (initially hidden, shown on hover/click) -->
            <div id="detailed-breakdown" class="hidden mt-1 space-y-0.5 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600" id="calculation-label-detail">Base:</span>
                    <span class="font-semibold text-green-600" id="calculation-amount">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Activation:</span>
                    <span class="font-semibold text-green-600" id="activation-fee-display">{{ number_format($pricingPlan->activation_fee ?? 0, 2) }} {{ $currency ?? 'EUR' }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-semibold">Sous-total (HT):</span>
                    <span class="font-semibold text-green-600" id="subtotal-ht">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-gray-600">TVA ({{ number_format($pricingPlan->vatRate->rate ?? 0, 1) }}%):</span>
                    <span class="font-semibold text-green-600" id="vat-amount">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
            </div>

            <!-- Toggle button for detailed breakdown -->
            <div class="mt-1 text-center">
                <button id="toggle-breakdown" class="text-xs text-green-600 hover:text-green-800 transition-colors">
                    <i class="fas fa-chevron-down mr-1"></i>
                    Détails
                </button>
            </div>
        </div>

        <!-- Mobile Content -->
        <div class="mobile-content" style="padding-top: 120px;">
            <!-- Station Info Card -->
            <div class="mobile-card">
                <h3>
                    <i class="fas fa-info-circle text-blue-600"></i>
                    Informations de la borne
                </h3>
                <div class="mobile-station-info">
                    <div class="mobile-station-item">
                        <span class="mobile-station-label">Puissance:</span>
                        <span class="mobile-station-value">{{ $chargingPoint->power_output ?? 'N/A' }} kW</span>
                    </div>
                    <div class="mobile-station-item">
                        <span class="mobile-station-label">Partenaire:</span>
                        <span class="mobile-station-value">{{ optional($chargingPoint->partner)->name ?? 'N/A' }}</span>
                    </div>
                    <div class="mobile-station-item">
                        <span class="mobile-station-label">Plan tarifaire:</span>
                        <span class="mobile-station-value text-blue-600">{{ $pricingPlan->name ?? 'Plan Standard' }}</span>
                    </div>
                    <div class="mobile-station-item">
                        <span class="mobile-station-label">Type:</span>
                        <span class="mobile-station-value">{{ ucfirst($pricingPlan->rate_type) }}</span>
                    </div>
                </div>
            </div>

            <!-- Reservation Type Selection -->
            <div class="app-card app-fade-in">
                <div class="app-card-header">
                    <div class="app-card-icon bg-blue-100 text-blue-600">
                        <i class="fas fa-charging-station"></i>
                    </div>
                    <div>
                        <h3 class="app-card-title">Type de réservation</h3>
                        <p class="app-card-subtitle">Choisissez votre mode de facturation</p>
                    </div>
                </div>

                <!-- Plan Information Card -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-3 mb-3 border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-semibold text-blue-900 text-sm">{{ $pricingPlan->name }}</h5>
                            <p class="text-xs text-blue-700">
                                @if($pricingPlan->rate_type === 'per_kw' || $pricingPlan->rate_type === 'energy')
                                    Facturation par énergie (kWh)
                                @elseif($pricingPlan->rate_type === 'per_min' || $pricingPlan->rate_type === 'time')
                                    Facturation par durée (minutes)
                                @elseif($pricingPlan->rate_type === 'mixed' || $pricingPlan->rate_type === 'both')
                                    Facturation mixte (énergie + durée)
                                @else
                                    {{ ucfirst($pricingPlan->rate_type) }}
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-blue-900">
                                @if($pricingPlan->price_per_kwh > 0)
                                    {{ number_format($pricingPlan->price_per_kwh, 2) }} {{ $currency ?? 'EUR' }}/kWh
                                @endif
                                @if($pricingPlan->price_per_kwh > 0 && $pricingPlan->price_per_minute > 0)
                                    <br>
                                @endif
                                @if($pricingPlan->price_per_minute > 0)
                                    {{ number_format($pricingPlan->price_per_minute, 2) }} {{ $currency ?? 'EUR' }}/min
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Limit Information -->
                <div id="limit-info" class="mobile-limit-info">
                    <i class="fas fa-info-circle"></i>
                    <span id="limit-text">Limites du plan tarifaire</span>
                </div>

                <div class="mobile-reservation-types">
                    <div class="mobile-reservation-type" data-type="kwh">
                        <div class="mobile-reservation-icon text-blue-600">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="mobile-reservation-title">Par Énergie</div>
                        <div class="mobile-reservation-subtitle">kWh</div>
                        @if($pricingPlan->price_per_kwh > 0)
                            <div class="text-xs text-green-600 font-semibold mt-1">
                                {{ number_format($pricingPlan->price_per_kwh, 2) }} {{ $currency ?? 'EUR' }}/kWh
                            </div>
                        @endif
                    </div>
                    <div class="mobile-reservation-type" data-type="minute">
                        <div class="mobile-reservation-icon text-green-600">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="mobile-reservation-title">Par Durée</div>
                        <div class="mobile-reservation-subtitle">Minutes</div>
                        @if($pricingPlan->price_per_minute > 0)
                            <div class="text-xs text-green-600 font-semibold mt-1">
                                {{ number_format($pricingPlan->price_per_minute, 2) }} {{ $currency ?? 'EUR' }}/min
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Duration/Energy Selection -->
            <div class="app-card app-fade-in">
                <div class="app-card-header">
                    <div class="app-card-icon bg-green-100 text-green-600">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div>
                        <h3 class="app-card-title">Durée ou Quantité</h3>
                        <p class="app-card-subtitle">Sélectionnez la durée ou l'énergie souhaitée</p>
                    </div>
                </div>

                <!-- Quick Selection Buttons -->
                <div class="mobile-duration-grid">
                    <div class="mobile-duration-btn" data-duration="10">
                        <div class="mobile-duration-value">10 min</div>
                        <div class="mobile-duration-label">Rapide</div>
                    </div>
                    <div class="mobile-duration-btn" data-duration="20">
                        <div class="mobile-duration-value">20 min</div>
                        <div class="mobile-duration-label">Standard</div>
                    </div>
                    <div class="mobile-duration-btn" data-duration="30">
                        <div class="mobile-duration-value">30 min</div>
                        <div class="mobile-duration-label">Complet</div>
                    </div>
                    <div class="mobile-duration-btn" data-duration="40">
                        <div class="mobile-duration-value">40 min</div>
                        <div class="mobile-duration-label">Étendue</div>
                    </div>
                    <div class="mobile-duration-btn" data-duration="50">
                        <div class="mobile-duration-value">50 min</div>
                        <div class="mobile-duration-label">Longue</div>
                    </div>
                    <div class="mobile-duration-btn" data-duration="60">
                        <div class="mobile-duration-value">1 heure</div>
                        <div class="mobile-duration-label">Maximale</div>
                    </div>
                </div>

                <!-- Custom Input -->
                <div class="mobile-custom-input">
                    <label for="custom-value">Ou saisissez une valeur personnalisée</label>
                    <div class="mobile-time-input">
                        <input type="number" id="custom-value" min="10" step="10" 
                               placeholder="Ex: 10, 20, 30...">
                        <span id="value-unit" class="text-sm text-gray-600 font-medium">min</span>
                    </div>
                    <div class="input-help">
                        <i class="fas fa-info-circle"></i>
                        Valeurs par intervalles de 10 minutes
                    </div>
                </div>
            </div>

            <!-- Time Selection -->
            <div class="app-card app-fade-in">
                <div class="app-card-header">
                    <div class="app-card-icon bg-purple-100 text-purple-600">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h3 class="app-card-title">Heure de Début</h3>
                        <p class="app-card-subtitle">Choisissez votre créneau de recharge</p>
                    </div>
                </div>

                <!-- Custom Time Input -->
                <div class="mobile-custom-input">
                    <label for="custom-time">Ou saisissez une heure personnalisée</label>
                    <div class="mobile-time-input">
                        <input type="time" id="custom-time" 
                               min="06:00" max="22:00"
                               value="{{ date('H:i') }}">
                        <button type="button" id="use-current-time" class="mobile-now-btn">
                            <i class="fas fa-clock"></i>
                            Maintenant
                        </button>
                    </div>
                    <div class="input-help">
                        <i class="fas fa-info-circle"></i>
                        Heures disponibles: 06:00 - 22:00
                    </div>
                </div>

                <!-- Time Slots Grid -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Ou choisissez parmi les créneaux disponibles:</h4>
                    <div class="mobile-time-slots">
                        @foreach($timeSlots as $slot)
                            <div class="mobile-time-slot {{ $slot['available'] ? 'available' : 'unavailable' }}"
                                 data-time="{{ $slot['time'] }}"
                                 data-available="{{ $slot['available'] ? 'true' : 'false' }}">
                                <div class="mobile-time-value">{{ $slot['time'] }}</div>
                                <div class="mobile-time-status">{{ $slot['available'] ? 'Disponible' : 'Occupé' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>


            <!-- Reserve Button -->
            <button id="reserve-btn" class="app-btn app-btn-success w-full" disabled>
                <i class="fas fa-check mr-2"></i>
                Réserver la borne
            </button>
        </div>
    </div>

    <!-- Hidden form for submission -->
    <form id="reservation-form" action="{{ route('reservations.store', $chargingPoint->id) }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="charging_point_id" value="{{ $chargingPoint->id }}">
        <input type="hidden" name="pricing_plan_id" value="{{ $pricingPlan->id }}">
        <input type="hidden" name="user_id" value="{{ auth()->id() }}">
        <input type="hidden" name="reservation_type" id="form-reservation-type">
        <input type="hidden" name="reservation_value" id="form-reservation-value">
        <input type="hidden" name="start_time" id="form-start-time">
        <input type="hidden" name="estimated_amount" id="form-estimated-amount">
        <input type="hidden" name="currency" value="{{ $currency ?? 'EUR' }}">
        <input type="hidden" name="price_per_kwh" value="{{ $pricingPlan->price_per_kwh ?? 0 }}">
        <input type="hidden" name="price_per_minute" value="{{ $pricingPlan->price_per_minute ?? 0 }}">
        <input type="hidden" name="activation_fee" value="{{ $pricingPlan->activation_fee ?? 0 }}">
        <input type="hidden" name="vat_rate" value="{{ $pricingPlan->vatRate->rate ?? 0 }}">
        <input type="hidden" name="admin_commission" id="form-admin-commission">
        <input type="hidden" name="integrator_commission" id="form-integrator-commission">
        <input type="hidden" name="partner_commission" id="form-partner-commission">
        <input type="hidden" name="business_profile_id" value="{{ $chargingPoint->business_profile_id ?? null }}">
        <input type="hidden" name="commission_plan_id" value="{{ $pricingPlan->commission_plan_id ?? null }}">
    </form>

    <!-- Custom Alert Overlay -->
    <div id="customAlertOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 shadow-xl" id="alertContainer">
            <div id="customAlertIcon" class="flex justify-center mb-4">
                <i id="customAlertIconClass" class="text-3xl"></i>
            </div>
            <h3 id="customAlertTitle" class="text-lg font-semibold text-center mb-2"></h3>
            <p id="customAlertMessage" class="text-gray-600 text-center mb-4"></p>
            <div id="customAlertBodyContent" class="text-sm text-gray-500 text-center mb-4"></div>
            <div class="flex justify-center space-x-3">
                <button id="customAlertPrimaryBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"></button>
                <button id="customAlertSecondaryBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors hidden"></button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedReservationType = null;
            let selectedReservationValue = null;
            let selectedTimeSlot = null;

            const reserveButton = document.getElementById('reserve-btn');

            if (!reserveButton) {
                console.error('Reserve button not found!');
                return;
            }

            // Mobile-optimized debug function
            window.debugReservationButton = function() {
                console.log('=== MOBILE RESERVATION DEBUG ===');
                console.log('Button disabled:', reserveButton.disabled);
                console.log('Selected type:', selectedReservationType);
                console.log('Selected value:', selectedReservationValue);
                console.log('Selected time:', selectedTimeSlot);
                console.log('===============================');
            };

            // Function to highlight missing selections
            window.highlightMissingSelections = function() {
                const missingSelections = [];
                if (!selectedReservationType) missingSelections.push('mobile-reservation-type');
                if (!selectedReservationValue) missingSelections.push('mobile-duration-btn');
                if (!selectedTimeSlot) missingSelections.push('mobile-time-slot');

                // Remove previous highlights
                document.querySelectorAll('.mobile-missing-selection').forEach(el => {
                    el.classList.remove('mobile-missing-selection');
                });

                // Add highlights to missing sections
                missingSelections.forEach(selector => {
                    document.querySelectorAll(`.${selector}`).forEach(el => {
                        el.classList.add('mobile-missing-selection');
                    });
                });
            };

            // Function to clear highlights
            function clearHighlights() {
                document.querySelectorAll('.mobile-missing-selection').forEach(el => {
                    el.classList.remove('mobile-missing-selection');
                });
            }

            const activationFee = parseFloat('{{ $pricingPlan->activation_fee ?? 0 }}');
            const pricePerKwh = parseFloat('{{ $pricingPlan->price_per_kwh ?? 0 }}');
            const pricePerMinute = parseFloat('{{ $pricingPlan->price_per_minute ?? 0 }}');
            const vatRate = parseFloat('{{ $pricingPlan->vatRate->rate ?? 0 }}');
            const currency = '{{ $currency ?? 'EUR' }}';

            // Pricing plan data from backend
            const pricingPlan = @json($pricingPlan);
            const chargingPoint = @json($chargingPoint);

            // Determine allowed reservation types based on pricing plan rate_type
            function getAllowedReservationTypes() {
                const rateType = pricingPlan.rate_type;

                console.log('🔍 Mobile - Analyzing plan rate type:', rateType);

                switch (rateType) {
                    case 'time':
                    case 'minute':
                    case 'per_min':
                        console.log('✅ Mobile - Time-based plan detected - only minute type allowed');
                        return ['minute'];
                    case 'energy':
                    case 'kwh':
                    case 'per_kw':
                        console.log('✅ Mobile - Energy-based plan detected - only kWh type allowed');
                        return ['kwh'];
                    case 'mixed':
                    case 'both':
                        console.log('✅ Mobile - Mixed plan detected - both types allowed');
                        return ['kwh', 'minute'];
                    case 'fixed':
                    default:
                        // For fixed plans, check which pricing is actually available
                        const allowedTypes = [];
                        if (pricingPlan.price_per_kwh > 0) {
                            allowedTypes.push('kwh');
                            console.log('✅ Mobile - kWh pricing available:', pricingPlan.price_per_kwh);
                        }
                        if (pricingPlan.price_per_minute > 0) {
                            allowedTypes.push('minute');
                            console.log('✅ Mobile - Minute pricing available:', pricingPlan.price_per_minute);
                        }

                        if (allowedTypes.length === 0) {
                            console.warn('⚠️ Mobile - No pricing found, defaulting to both types');
                            return ['kwh', 'minute'];
                        }

                        console.log('✅ Mobile - Fixed plan - allowed types:', allowedTypes);
                        return allowedTypes;
                }
            }

            // Get the recommended reservation type based on pricing plan
            function getRecommendedReservationType() {
                const rateType = pricingPlan.rate_type;
                const allowedTypes = getAllowedReservationTypes();

                console.log('🔍 Mobile Enhanced Plan Analysis:', {
                    rateType: rateType,
                    allowedTypes: allowedTypes,
                    pricePerKwh: pricingPlan.price_per_kwh,
                    pricePerMinute: pricingPlan.price_per_minute,
                    maxDuration: pricingPlan.max_duration,
                    maxEnergy: pricingPlan.max_energy,
                    chargingPointPower: chargingPoint.power_output
                });

                // If only one type is allowed, return it
                if (allowedTypes.length === 1) {
                    console.log('✅ Mobile - Single type allowed:', allowedTypes[0]);
                    return allowedTypes[0];
                }

                // Enhanced logic for mixed plans
                if (rateType === 'mixed' || rateType === 'both') {
                    // Calculate cost efficiency based on typical usage
                    const typicalSessionDuration = 60; // 1 hour typical session
                    const typicalEnergyConsumption = chargingPoint.power_output ? 
                        (chargingPoint.power_output * typicalSessionDuration / 60) : 20; // kWh

                    const costPerMinute = pricingPlan.price_per_minute * typicalSessionDuration;
                    const costPerKwh = pricingPlan.price_per_kwh * typicalEnergyConsumption;

                    console.log('💰 Mobile Cost Analysis:', {
                        costPerMinute: costPerMinute,
                        costPerKwh: costPerKwh,
                        typicalDuration: typicalSessionDuration,
                        typicalEnergy: typicalEnergyConsumption
                    });

                    // Choose the more cost-effective option
                    const recommendedType = costPerKwh <= costPerMinute ? 'kwh' : 'minute';
                    console.log('✅ Mobile - Mixed plan - cost-effective selection:', recommendedType);
                    return recommendedType;
                }

                // For specific rate types, return the corresponding type
                switch(rateType) {
                    case 'time':
                    case 'minute':
                    case 'per_min':
                        console.log('✅ Mobile - Time-based plan selected: minute');
                        return 'minute';
                    case 'energy':
                    case 'kwh':
                    case 'per_kw':
                        console.log('✅ Mobile - Energy-based plan selected: kwh');
                        return 'kwh';
                    case 'fixed':
                        // For fixed plans, check which pricing is available and more attractive
                        if (pricingPlan.price_per_kwh > 0 && pricingPlan.price_per_minute > 0) {
                            // Compare prices and choose the more attractive one
                            const kwhPrice = pricingPlan.price_per_kwh;
                            const minutePrice = pricingPlan.price_per_minute;

                            // If kWh price is significantly lower, prefer kWh
                            if (kwhPrice < minutePrice * 0.8) {
                                console.log('✅ Mobile - Fixed plan - kWh more attractive');
                                return 'kwh';
                            } else if (minutePrice < kwhPrice * 0.8) {
                                console.log('✅ Mobile - Fixed plan - minute more attractive');
                                return 'minute';
                            } else {
                                // Prices are similar, prefer kWh for energy-based charging
                                console.log('✅ Mobile - Fixed plan - similar prices, prefer kWh');
                                return 'kwh';
                            }
                        } else if (pricingPlan.price_per_kwh > 0 && allowedTypes.includes('kwh')) {
                            console.log('✅ Mobile - Fixed plan - only kWh available');
                            return 'kwh';
                        } else if (pricingPlan.price_per_minute > 0 && allowedTypes.includes('minute')) {
                            console.log('✅ Mobile - Fixed plan - only minute available');
                            return 'minute';
                        }
                        break;
                }

                // Default to first allowed type
                console.log('✅ Mobile - Default selection:', allowedTypes[0]);
                return allowedTypes[0];
            }

            const allowedReservationTypes = getAllowedReservationTypes();
            const maxDurationMinutes = pricingPlan.max_duration || 180;
            const chargingPointPowerOutput = chargingPoint.power_output || pricingPlan.max_power_output || null;

            // Calculate max energy based on max duration and power output
            function getMaxEnergyKwh() {
                if (pricingPlan.max_energy) {
                    return pricingPlan.max_energy;
                }

                if (maxDurationMinutes && chargingPointPowerOutput) {
                    return chargingPointPowerOutput * (maxDurationMinutes / 60);
                }
                return null;
            }

            const maxEnergyKwh = getMaxEnergyKwh();

            // Validation functions for limits
            function validateDurationLimit(durationMinutes) {
                if (maxDurationMinutes && durationMinutes > maxDurationMinutes) {
                    return {
                        valid: false,
                        message: `La durée ne peut pas dépasser ${maxDurationMinutes} minutes selon ce plan tarifaire.`
                    };
                }
                return { valid: true };
            }

            function validateEnergyLimit(energyKwh) {
                if (maxEnergyKwh && energyKwh > maxEnergyKwh) {
                    return {
                        valid: false,
                        message: `La quantité d'énergie ne peut pas dépasser ${maxEnergyKwh.toFixed(2)} kWh selon ce plan tarifaire.`
                    };
                }
                return { valid: true };
            }

            function validateReservationValue(value, type) {
                if (type === 'minute') {
                    return validateDurationLimit(value);
                } else if (type === 'kwh') {
                    return validateEnergyLimit(value);
                }
                return { valid: true };
            }

            // Hide/show reservation options based on pricing plan
            function updateReservationOptionsVisibility() {
                document.querySelectorAll('.mobile-reservation-type[data-type]').forEach(option => {
                    const reservationType = option.dataset.type;
                    if (allowedReservationTypes.includes(reservationType)) {
                        option.style.display = 'block';
                        option.style.opacity = '1';
                        option.style.pointerEvents = 'auto';
                        option.classList.remove('disabled');
                    } else {
                        option.style.display = 'none';
                        option.style.opacity = '0.3';
                        option.style.pointerEvents = 'none';
                        option.classList.add('disabled');
                    }
                });

                // Clear any existing selection if the selected type is not allowed
                if (selectedReservationType && !allowedReservationTypes.includes(selectedReservationType)) {
                    selectedReservationType = null;
                    document.querySelectorAll('.mobile-reservation-type[data-type]').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    updateReserveButtonState();
                }

                // Auto-select recommended type if no selection exists
                if (!selectedReservationType) {
                    const recommendedType = getRecommendedReservationType();
                    const recommendedOption = document.querySelector(`.mobile-reservation-type[data-type="${recommendedType}"]`);
                    if (recommendedOption && allowedReservationTypes.includes(recommendedType)) {
                        // Auto-select the recommended type
                        recommendedOption.classList.add('selected');
                        selectedReservationType = recommendedType;

                        // Update unit in custom input
                        const valueUnit = document.getElementById('value-unit');
                        if (valueUnit) {
                            valueUnit.textContent = selectedReservationType === 'kwh' ? 'kWh' : 'min';
                        }

                        updateReserveButtonState();
                        updateCostEstimation();
                    }
                }
            }

            // Update duration buttons visibility based on max duration limit
            function updateDurationButtonsVisibility() {
                if (maxDurationMinutes) {
                    document.querySelectorAll('.mobile-duration-btn').forEach(btn => {
                        const durationValue = parseInt(btn.dataset.duration);
                        if (durationValue > maxDurationMinutes) {
                            btn.style.opacity = '0.3';
                            btn.style.filter = 'grayscale(100%)';
                            btn.style.pointerEvents = 'none';
                            btn.classList.add('disabled');
                            btn.title = `Durée maximale autorisée: ${maxDurationMinutes} minutes`;
                        } else {
                            btn.style.opacity = '1';
                            btn.style.filter = 'none';
                            btn.style.pointerEvents = 'auto';
                            btn.classList.remove('disabled');
                            btn.title = '';
                        }
                    });
                }
            }

            // Update limit information display
            function updateLimitInfo() {
                const limitTextElement = document.getElementById('limit-text');
                const limitInfoElement = document.getElementById('limit-info');

                if (limitTextElement && limitInfoElement) {
                    let limitText = '';

                    if (maxDurationMinutes) {
                        limitText += `Durée maximale: ${maxDurationMinutes} minutes`;
                    }

                    if (maxEnergyKwh) {
                        if (limitText) limitText += ' • ';
                        limitText += `Énergie maximale: ${maxEnergyKwh.toFixed(2)} kWh`;
                    }

                    if (limitText) {
                        limitTextElement.textContent = limitText;
                        limitInfoElement.style.display = 'block';
                    } else {
                        limitInfoElement.style.display = 'none';
                    }
                }
            }

            // Initialize reservation options visibility
            updateReservationOptionsVisibility();
            updateDurationButtonsVisibility();
            updateLimitInfo();

            // Toggle breakdown functionality
            document.getElementById('toggle-breakdown').addEventListener('click', function() {
                const breakdown = document.getElementById('detailed-breakdown');
                const icon = this.querySelector('i');

                if (breakdown.classList.contains('hidden')) {
                    breakdown.classList.remove('hidden');
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                    this.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Masquer';
                } else {
                    breakdown.classList.add('hidden');
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                    this.innerHTML = '<i class="fas fa-chevron-down mr-1"></i> Détails';
                }
            });

            // Commission rates
            const adminCommissionRate = 0.05;
            const integratorCommissionRate = 0.03;
            const partnerCommissionRate = 0.02;

            // Optimized mobile selection logic with debouncing and performance improvements
            let selectionDebounceTimer = null;
            let lastSelectedType = null;

            // Performance optimization cache
            const selectionCache = {
                reservationTypes: new Map(),
                durationButtons: new Map(),
                paymentMethods: new Map()
            };

            // Utility function for optimized DOM updates
            function batchDOMUpdates(updates) {
                requestAnimationFrame(() => {
                    updates.forEach(update => update());
                });
            }

            // Optimized element selection with caching
            function getCachedElements(selector, cacheKey) {
                if (!selectionCache[cacheKey].has(selector)) {
                    const elements = document.querySelectorAll(selector);
                    selectionCache[cacheKey].set(selector, Array.from(elements));
                }
                return selectionCache[cacheKey].get(selector);
            }

            // Clear cache when DOM changes
            function clearSelectionCache() {
                selectionCache.reservationTypes.clear();
                selectionCache.durationButtons.clear();
                selectionCache.paymentMethods.clear();
            }

            // Enhanced touch feedback for mobile
            function addTouchFeedback(element) {
                element.style.transform = 'scale(0.95)';
                element.style.transition = 'transform 0.1s ease';
                setTimeout(() => {
                    element.style.transform = '';
                    element.style.transition = '';
                }, 100);
            }

            // Optimized event listener with passive option for better performance
            function addOptimizedEventListener(element, event, handler, options = {}) {
                const defaultOptions = {
                    passive: false, // Changed to false to allow preventDefault
                    capture: false
                };
                element.addEventListener(event, handler, { ...defaultOptions, ...options });
            }

            // Initialize performance optimizations
            function initializePerformanceOptimizations() {
                // Pre-cache all interactive elements
                getCachedElements('.mobile-reservation-type[data-type]', 'reservationTypes');
                getCachedElements('.mobile-duration-btn', 'durationButtons');
                getCachedElements('.payment-method-card', 'paymentMethods');

                // Add performance monitoring
                if (window.performance && window.performance.mark) {
                    window.performance.mark('mobile-selection-optimized');
                }

                console.log('🚀 Mobile - Performance optimizations initialized');
            }

            // Initialize optimizations when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializePerformanceOptimizations);
            } else {
                initializePerformanceOptimizations();
            }

            // Debounced selection handler with optimized performance
            function debouncedSelectReservationType(element, reservationType) {
                if (selectionDebounceTimer) {
                    clearTimeout(selectionDebounceTimer);
                }

                selectionDebounceTimer = setTimeout(() => {
                    if (lastSelectedType === reservationType) return;

                    if (!allowedReservationTypes.includes(reservationType)) {
                        console.warn(`Reservation type '${reservationType}' is not allowed by pricing plan rate_type '${pricingPlan.rate_type}'`);
                        return;
                    }

                    // Use cached elements for better performance
                    const allOptions = getCachedElements('.mobile-reservation-type[data-type]', 'reservationTypes');

                    // Batch DOM updates for better performance
                    batchDOMUpdates([
                        () => {
                            // Remove all selections in one operation
                            allOptions.forEach(opt => opt.classList.remove('selected'));
                        },
                        () => {
                            // Add selection to current element
                            element.classList.add('selected');
                            selectedReservationType = reservationType;
                            lastSelectedType = reservationType;
                        },
                        () => {
                            clearHighlights();

                            // Update unit in custom input
                            const valueUnit = document.getElementById('value-unit');
                            if (valueUnit) {
                                valueUnit.textContent = selectedReservationType === 'kwh' ? 'kWh' : 'min';
                            }

                            updateReserveButtonState();
                            updateCostEstimation();
                        }
                    ]);
                }, 50); // 50ms debounce
            }

            // Event delegation for better performance with optimized touch handling
            addOptimizedEventListener(document, 'click', function(e) {
                const reservationTypeElement = e.target.closest('.mobile-reservation-type[data-type]');
                if (reservationTypeElement) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Add enhanced touch feedback
                    addTouchFeedback(reservationTypeElement);

                    const reservationType = reservationTypeElement.dataset.type;
                    debouncedSelectReservationType(reservationTypeElement, reservationType);
                }
            });

            // Optimized duration selection with debouncing and performance improvements
            let durationDebounceTimer = null;
            let lastSelectedDuration = null;

            function debouncedSelectDuration(element, durationValue) {
                if (durationDebounceTimer) {
                    clearTimeout(durationDebounceTimer);
                }

                durationDebounceTimer = setTimeout(() => {
                    if (lastSelectedDuration === durationValue) return;

                    if (selectedReservationType === 'minute') {
                        const validation = validateDurationLimit(durationValue);
                        if (!validation.valid) {
                            showCustomAlert({
                                type: 'error',
                                title: 'Durée maximale dépassée',
                                message: validation.message,
                                primaryBtn: {
                                    text: 'Compris',
                                    action: () => hideCustomAlert()
                                }
                            });
                            return;
                        }
                    }

                    // Use cached elements for better performance
                    const allDurationBtns = getCachedElements('.mobile-duration-btn', 'durationButtons');

                    // Batch DOM updates for better performance
                    batchDOMUpdates([
                        () => {
                            // Remove all duration selections in one operation
                            allDurationBtns.forEach(btn => btn.classList.remove('selected'));
                        },
                        () => {
                            // Add selection to current element
                            element.classList.add('selected');
                            selectedReservationValue = durationValue;
                            lastSelectedDuration = durationValue;
                        },
                        () => {
                            clearHighlights();
                            updateReserveButtonState();
                            updateCostEstimation();
                        }
                    ]);
                }, 50); // 50ms debounce
            }

            // Event delegation for duration buttons with optimized touch handling
            addOptimizedEventListener(document, 'click', function(e) {
                const durationBtn = e.target.closest('.mobile-duration-btn');
                if (durationBtn) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Add enhanced touch feedback
                    addTouchFeedback(durationBtn);

                    const durationValue = parseInt(durationBtn.dataset.duration);
                    debouncedSelectDuration(durationBtn, durationValue);
                }
            });

            // Custom value input with mobile optimization
            const customValueInput = document.getElementById('custom-value');
            if (customValueInput) {
                customValueInput.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    if (value && value > 0) {
                        if (selectedReservationType === 'minute' && value % 10 !== 0) {
                            showCustomAlert({
                                type: 'warning',
                                title: 'Intervalle invalide',
                                message: 'Les durées doivent être par intervalles de 10 minutes (10, 20, 30, etc.).',
                                primaryBtn: {
                                    text: 'Compris',
                                    action: () => hideCustomAlert()
                                }
                            });
                            const roundedValue = Math.round(value / 10) * 10;
                            this.value = roundedValue;
                            selectedReservationValue = roundedValue;
                            updateReserveButtonState();
                            updateCostEstimation();
                            return;
                        }

                        const validation = validateReservationValue(value, selectedReservationType);
                        if (!validation.valid) {
                            showCustomAlert({
                                type: 'error',
                                title: 'Limite dépassée',
                                message: validation.message,
                                primaryBtn: {
                                    text: 'Compris',
                                    action: () => hideCustomAlert()
                                }
                            });
                            this.value = '';
                            selectedReservationValue = null;
                            updateReserveButtonState();
                            updateCostEstimation();
                            return;
                        }

                        selectedReservationValue = value;
                        clearHighlights();

                        updateReserveButtonState();
                        updateCostEstimation();
                    }
                });
            }

            // Custom time input handling
            const customTimeInput = document.getElementById('custom-time');
            const useCurrentTimeBtn = document.getElementById('use-current-time');

            if (customTimeInput) {
                customTimeInput.addEventListener('change', function() {
                    const selectedTime = this.value;
                    if (selectedTime) {
                        const timeParts = selectedTime.split(':');
                        const hours = parseInt(timeParts[0]);
                        const minutes = parseInt(timeParts[1]);

                        if (hours < 6 || hours > 22 || (hours === 22 && minutes > 0)) {
                            showCustomAlert({
                                type: 'error',
                                title: 'Heure invalide',
                                message: 'L\'heure doit être entre 06:00 et 22:00.',
                                primaryBtn: {
                                    text: 'Compris',
                                    action: () => hideCustomAlert()
                                }
                            });
                            this.value = '';
                            selectedTimeSlot = null;
                            updateReserveButtonState();
                            return;
                        }

                        document.querySelectorAll('.mobile-time-slot').forEach(s => {
                            s.classList.remove('selected');
                        });

                        selectedTimeSlot = selectedTime;
                        clearHighlights();

                        updateReserveButtonState();

                        this.style.borderColor = '#3b82f6';
                        this.style.backgroundColor = '#eff6ff';
                    }
                });
            }

            // "Maintenant" (Now) button
            if (useCurrentTimeBtn) {
                useCurrentTimeBtn.addEventListener('click', function() {
                    const now = new Date();
                    const currentTime = now.toTimeString().slice(0, 5);

                    const timeParts = currentTime.split(':');
                    const hours = parseInt(timeParts[0]);
                    const minutes = parseInt(timeParts[1]);

                    if (hours < 6 || hours > 22 || (hours === 22 && minutes > 0)) {
                        showCustomAlert({
                            type: 'warning',
                            title: 'Heure actuelle non disponible',
                            message: `L'heure actuelle (${currentTime}) est en dehors des heures de service (06:00 - 22:00). Veuillez choisir une heure disponible.`,
                            primaryBtn: {
                                text: 'Compris',
                                action: () => hideCustomAlert()
                            }
                        });
                        return;
                    }

                    if (customTimeInput) {
                        customTimeInput.value = currentTime;
                        customTimeInput.dispatchEvent(new Event('change'));
                    }

                    document.querySelectorAll('.mobile-time-slot').forEach(s => {
                        s.classList.remove('selected');
                    });
                });
            }

            // Time slot selection with mobile touch feedback
            document.querySelectorAll('.mobile-time-slot').forEach(slot => {
                slot.addEventListener('click', function() {
                    if (this.dataset.available === 'true') {
                        document.querySelectorAll('.mobile-time-slot').forEach(s => {
                            s.classList.remove('selected');
                        });
                        this.classList.add('selected');
                        selectedTimeSlot = this.dataset.time;

                        clearHighlights();

                        if (customTimeInput) {
                            customTimeInput.value = '';
                            customTimeInput.style.borderColor = '';
                            customTimeInput.style.backgroundColor = '';
                        }

                        updateReserveButtonState();
                    }
                });
            });

            function updateCostEstimation() {
                if (!selectedReservationType || !selectedReservationValue) {
                    const calculationLabelElement = document.getElementById('calculation-label');
                    const calculationAmountElement = document.getElementById('calculation-amount');
                    const subtotalHTElement = document.getElementById('subtotal-ht');
                    const vatAmountElement = document.getElementById('vat-amount');
                    const totalTTCElement = document.getElementById('total-ttc');
                    const detailedBreakdownElement = document.getElementById('detailed-breakdown');
                    const estimationInfoElement = document.getElementById('estimation-info');

                    if (calculationLabelElement) calculationLabelElement.textContent = 'Tarif de base:';
                    if (calculationAmountElement) calculationAmountElement.textContent = `0.00 ${currency}`;
                    if (subtotalHTElement) subtotalHTElement.textContent = `0.00 ${currency}`;
                    if (vatAmountElement) vatAmountElement.textContent = `0.00 ${currency}`;
                    if (totalTTCElement) totalTTCElement.textContent = `0.00 ${currency}`;

                    // Hide detailed breakdown and estimation info
                    if (detailedBreakdownElement) detailedBreakdownElement.classList.add('hidden');
                    if (estimationInfoElement) estimationInfoElement.classList.add('hidden');
                    return;
                }

                let baseAmount = 0;
                let calculationLabel = 'Tarif de base:';
                let breakdownDetails = '';

                if (selectedReservationType === 'kwh') {
                    baseAmount = selectedReservationValue * pricePerKwh;
                    calculationLabel = `Tarif énergie (${selectedReservationValue} kWh × ${pricePerKwh.toFixed(2)} ${currency}/kWh):`;
                    breakdownDetails = `${selectedReservationValue} kWh × ${pricePerKwh.toFixed(2)} ${currency}/kWh`;
                } else if (selectedReservationType === 'minute') {
                    baseAmount = selectedReservationValue * pricePerMinute;
                    calculationLabel = `Tarif temps (${selectedReservationValue} min × ${pricePerMinute.toFixed(2)} ${currency}/min):`;
                    breakdownDetails = `${selectedReservationValue} min × ${pricePerMinute.toFixed(2)} ${currency}/min`;
                }

                const subtotalHT = baseAmount + activationFee;
                const vatAmount = subtotalHT * (vatRate / 100);
                const totalTTC = subtotalHT + vatAmount;

                const calculationLabelElement = document.getElementById('calculation-label');
                const calculationAmountElement = document.getElementById('calculation-amount');
                const subtotalHTElement = document.getElementById('subtotal-ht');
                const vatAmountElement = document.getElementById('vat-amount');
                const totalTTCElement = document.getElementById('total-ttc');
                const detailedBreakdownElement = document.getElementById('detailed-breakdown');
                const breakdownDetailsElement = document.getElementById('breakdown-details');
                const breakdownAmountElement = document.getElementById('breakdown-amount');
                const estimationInfoElement = document.getElementById('estimation-info');
                const estimationTextElement = document.getElementById('estimation-text');

                if (calculationLabelElement) calculationLabelElement.textContent = calculationLabel;
                if (calculationAmountElement) calculationAmountElement.textContent = `${baseAmount.toFixed(2)} ${currency}`;
                if (subtotalHTElement) subtotalHTElement.textContent = `${subtotalHT.toFixed(2)} ${currency}`;
                if (vatAmountElement) vatAmountElement.textContent = `${vatAmount.toFixed(2)} ${currency}`;
                if (totalTTCElement) totalTTCElement.textContent = `${totalTTC.toFixed(2)} ${currency}`;

                // Show detailed breakdown
                if (detailedBreakdownElement) {
                    detailedBreakdownElement.classList.remove('hidden');
                }
                if (breakdownDetailsElement) {
                    breakdownDetailsElement.textContent = breakdownDetails;
                }
                if (breakdownAmountElement) {
                    breakdownAmountElement.textContent = `${baseAmount.toFixed(2)} ${currency}`;
                }

                // Show estimation info
                if (estimationInfoElement) {
                    estimationInfoElement.classList.remove('hidden');
                }
                if (estimationTextElement) {
                    const typeName = selectedReservationType === 'kwh' ? 'énergie' : 'durée';
                    estimationTextElement.textContent = `Estimation basée sur la ${typeName} sélectionnée`;
                }

                // Update hidden form fields
                if (document.getElementById('form-estimated-amount')) {
                    document.getElementById('form-estimated-amount').value = totalTTC.toFixed(2);
                }
                if (document.getElementById('form-admin-commission')) {
                    document.getElementById('form-admin-commission').value = (totalTTC * adminCommissionRate).toFixed(2);
                }
                if (document.getElementById('form-integrator-commission')) {
                    document.getElementById('form-integrator-commission').value = (totalTTC * integratorCommissionRate).toFixed(2);
                }
                if (document.getElementById('form-partner-commission')) {
                    document.getElementById('form-partner-commission').value = (totalTTC * partnerCommissionRate).toFixed(2);
                }
            }

            // Check reservation limits based on plan (Mobile)
            function checkReservationLimits() {
                const planLimits = {
                    maxDuration: pricingPlan.max_duration || null,
                    maxEnergy: pricingPlan.max_energy || null,
                    maxReservations: pricingPlan.max_reservations || null,
                    currentReservations: pricingPlan.current_reservations || 0
                };

                console.log('🔍 Mobile - Checking reservation limits:', planLimits);

                // Check if reservation limit is reached
                if (planLimits.maxReservations && planLimits.currentReservations >= planLimits.maxReservations) {
                    console.log('❌ Mobile - Reservation limit reached:', planLimits.currentReservations, '/', planLimits.maxReservations);
                    return {
                        exceeded: true,
                        type: 'reservation_limit',
                        message: `Limite de réservations atteinte (${planLimits.currentReservations}/${planLimits.maxReservations})`
                    };
                }

                // Check duration limit for minute-based reservations
                if (selectedReservationType === 'minute' && planLimits.maxDuration && selectedReservationValue > planLimits.maxDuration) {
                    console.log('❌ Mobile - Duration limit exceeded:', selectedReservationValue, '>', planLimits.maxDuration);
                    return {
                        exceeded: true,
                        type: 'duration_limit',
                        message: `Durée maximale dépassée (${selectedReservationValue} > ${planLimits.maxDuration} minutes)`
                    };
                }

                // Check energy limit for kWh-based reservations
                if (selectedReservationType === 'kwh' && planLimits.maxEnergy && selectedReservationValue > planLimits.maxEnergy) {
                    console.log('❌ Mobile - Energy limit exceeded:', selectedReservationValue, '>', planLimits.maxEnergy);
                    return {
                        exceeded: true,
                        type: 'energy_limit',
                        message: `Énergie maximale dépassée (${selectedReservationValue} > ${planLimits.maxEnergy} kWh)`
                    };
                }

                return { exceeded: false };
            }

            // Show limit warning message (Mobile)
            function showLimitWarning(message, type) {
                // Remove existing warning if any
                hideLimitWarning();

                const warningDiv = document.createElement('div');
                warningDiv.id = 'mobile-limit-warning';
                warningDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-3 mb-4';
                warningDiv.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-red-800 font-medium text-sm">${message}</span>
                    </div>
                `;

                // Insert before the reserve button
                const reserveButton = document.getElementById('mobile-reserve-btn');
                if (reserveButton) {
                    reserveButton.parentNode.insertBefore(warningDiv, reserveButton);
                }
            }

            // Hide limit warning message (Mobile)
            function hideLimitWarning() {
                const existingWarning = document.getElementById('mobile-limit-warning');
                if (existingWarning) {
                    existingWarning.remove();
                }
            }

            function updateReserveButtonState() {
                const isReservationTypeValid = selectedReservationType && allowedReservationTypes.includes(selectedReservationType);
                const limitCheck = checkReservationLimits();

                if (isReservationTypeValid && selectedReservationValue && !limitCheck.exceeded) {
                    reserveButton.disabled = false;
                    reserveButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    reserveButton.textContent = 'Réserver maintenant';

                    // Hide limit warning if it exists
                    hideLimitWarning();
                } else {
                    reserveButton.disabled = true;
                    reserveButton.classList.add('opacity-50', 'cursor-not-allowed');

                    if (limitCheck.exceeded) {
                        reserveButton.textContent = limitCheck.message;
                        showLimitWarning(limitCheck.message, limitCheck.type);
                    } else if (!isReservationTypeValid) {
                        reserveButton.textContent = 'Sélectionnez un type valide';
                    } else if (!selectedReservationValue) {
                        reserveButton.textContent = 'Sélectionnez une valeur';
                    }
                }
            }

            // Mobile-optimized reservation button click handler
            reserveButton.addEventListener('click', function(e) {
                if (this.disabled) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }

                const missingSelections = [];
                if (!selectedReservationType) missingSelections.push('type de réservation');
                if (!selectedReservationValue) missingSelections.push('valeur/durée');
                if (!selectedTimeSlot) missingSelections.push('créneau horaire');

                if (missingSelections.length > 0) {
                    const message = missingSelections.length === 1 
                        ? `Veuillez sélectionner un ${missingSelections[0]}.`
                        : `Veuillez sélectionner: ${missingSelections.join(', ')}.`;

                    highlightMissingSelections();

                    showCustomAlert({
                        type: 'warning',
                        title: 'Sélection requise',
                        message: message,
                        primaryBtn: {
                            text: 'Compris',
                            action: () => {
                                hideCustomAlert();
                                setTimeout(() => {
                                    document.querySelectorAll('.mobile-missing-selection').forEach(el => {
                                        el.classList.remove('mobile-missing-selection');
                                    });
                                }, 3000);
                            }
                        }
                    });
                    return;
                }

                if (!allowedReservationTypes.includes(selectedReservationType)) {
                    showCustomAlert({
                        type: 'error',
                        title: 'Type de réservation invalide',
                        message: `Le type de réservation "${selectedReservationType}" n'est pas autorisé par ce plan tarifaire (${pricingPlan.rate_type}).`,
                        primaryBtn: {
                            text: 'Compris',
                            action: () => hideCustomAlert()
                        }
                    });
                    return;
                }

                const finalValidation = validateReservationValue(selectedReservationValue, selectedReservationType);
                if (!finalValidation.valid) {
                    showCustomAlert({
                        type: 'error',
                        title: 'Limite dépassée',
                        message: finalValidation.message,
                        primaryBtn: {
                            text: 'Compris',
                            action: () => hideCustomAlert()
                        }
                    });
                    return;
                }

                // Prepare reservation data - format for API
                const reservationData = {
                    type: selectedReservationType === 'kwh' ? 'energy' : 'duration',
                    value: selectedReservationValue,
                    time: 'immediate',
                    customer_name: 'Utilisateur Public',
                    customer_email: 'public@example.com',
                    customer_phone: '0000000000'
                };

                // Show loading state
                reserveButton.disabled = true;
                reserveButton.classList.add('loading');
                reserveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création de la réservation...';

                // Submit reservation (toujours sur le même host que la page)
                const reservationUrl = '{{ url('/api/reservations/store/' . $chargingPoint->id) }}';

                // API route doesn't require CSRF token

                fetch(reservationUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(reservationData)
                })
                .then(response => {
                    if (response.status === 419) {
                        console.error('CSRF token mismatch detected');
                        alert('Erreur de sécurité: Token CSRF expiré. Veuillez recharger la page et réessayer.');
                        window.location.reload();
                        return;
                    }

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            let errorMessage = 'Erreur serveur: La réponse n\'est pas au format JSON.';

                            if (text.includes('<!DOCTYPE html>')) {
                                const titleMatch = text.match(/<title>(.*?)<\/title>/i);
                                if (titleMatch) {
                                    errorMessage = `Erreur serveur: ${titleMatch[1]}`;
                                }

                                if (text.includes('TokenMismatchException')) {
                                    errorMessage = 'Erreur de sécurité: Token CSRF expiré. Veuillez recharger la page.';
                                } else if (text.includes('MethodNotAllowedHttpException')) {
                                    errorMessage = 'Erreur: Méthode HTTP non autorisée.';
                                } else if (text.includes('NotFoundHttpException')) {
                                    errorMessage = 'Erreur: Page non trouvée.';
                                } else if (text.includes('Fatal error') || text.includes('Parse error')) {
                                    errorMessage = 'Erreur serveur: Erreur fatale PHP.';
                                }
                            }

                            alert(errorMessage + ' Veuillez réessayer.');
                            throw new Error('Non-JSON response received');
                        });
                    }

                    return response.json();
                })
                .then(data => {
                    if (data.success === true || data.reservation) {
                        showCustomAlert({
                            type: 'success',
                            title: 'Réservation Confirmée!',
                            message: data.message || 'Votre réservation a été créée avec succès. Nous vous remercions de votre confiance.',
                            bodyContent: 'Vous allez être redirigé vers la page de confirmation...',
                            primaryBtn: {
                                text: 'Continuer',
                                action: () => {
                                    hideCustomAlert();
                                    const redirectUrl = data.redirect_url || '/reservations/thank-you/' + (data.reservation?.id || data.reservation_id);
                                    window.location.href = redirectUrl;
                                }
                            }
                        });

                        setTimeout(() => {
                            if (document.getElementById('customAlertOverlay').style.display !== 'none') {
                                hideCustomAlert();
                                const redirectUrl = data.redirect_url || '/reservations/thank-you/' + (data.reservation?.id || data.reservation_id);
                                window.location.href = redirectUrl;
                            }
                        }, 3000);

                        return;
                    } else {
                        if (data.error === 'limit_exceeded') {
                            showCustomAlert({
                                type: 'error',
                                title: 'Limite dépassée',
                                message: data.message || 'La durée de réservation ne peut pas dépasser la limite autorisée.',
                                bodyContent: 'Veuillez réduire la durée ou la quantité d\'énergie de votre réservation.',
                                primaryBtn: {
                                    text: 'Compris',
                                    action: () => hideCustomAlert()
                                }
                            });
                        } else {
                            showCustomAlert({
                                type: 'error',
                                title: 'Erreur de réservation',
                                message: data.message || 'Erreur lors de la création de la réservation.',
                                primaryBtn: {
                                    text: 'OK',
                                    action: () => hideCustomAlert()
                                }
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Reservation error:', error);

                    let errorMessage = 'Une erreur est survenue lors de la réservation.';
                    let errorTitle = 'Erreur de connexion';
                    let bodyContent = 'Veuillez vérifier votre connexion internet et réessayer.';

                    if (error.message === 'Non-JSON response received') {
                        errorTitle = 'Erreur serveur';
                        errorMessage = 'Le serveur a retourné une réponse invalide.';
                        bodyContent = 'Veuillez réessayer ou contacter le support technique.';
                    } else if (error.name === 'SyntaxError') {
                        errorTitle = 'Erreur de communication';
                        errorMessage = 'Erreur de communication avec le serveur.';
                        bodyContent = 'Veuillez vérifier votre connexion et réessayer.';
                    } else if (error.name === 'TypeError') {
                        errorTitle = 'Erreur de réseau';
                        errorMessage = 'Erreur de réseau.';
                        bodyContent = 'Veuillez vérifier votre connexion internet et réessayer.';
                    }

                    showCustomAlert({
                        type: 'error',
                        title: errorTitle,
                        message: errorMessage,
                        bodyContent: bodyContent,
                        primaryBtn: {
                            text: 'Réessayer',
                            action: () => hideCustomAlert()
                        }
                    });
                })
                .finally(() => {
                    reserveButton.disabled = false;
                    reserveButton.classList.remove('loading');
                    reserveButton.innerHTML = '<i class="fas fa-check"></i> Réserver la borne';
                });
            });
        });
        </script>

        <!-- Custom Alert Functions -->
        <script>
        function showCustomAlert(options) {
            const overlay = document.getElementById('customAlertOverlay');
            const icon = document.getElementById('customAlertIcon');
            const iconClass = document.getElementById('customAlertIconClass');
            const title = document.getElementById('customAlertTitle');
            const message = document.getElementById('customAlertMessage');
            const bodyContent = document.getElementById('customAlertBodyContent');
            const primaryBtn = document.getElementById('customAlertPrimaryBtn');
            const secondaryBtn = document.getElementById('customAlertSecondaryBtn');

            icon.className = `flex justify-center mb-4 ${options.type}`;

            // Styliser le conteneur selon le type
            const alertContainer = document.getElementById('alertContainer');
            if (alertContainer) {
                if (options.type === 'success') {
                    alertContainer.className = 'bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg p-6 max-w-sm w-full mx-4 shadow-xl';
                } else {
                    alertContainer.className = 'bg-white rounded-lg p-6 max-w-sm w-full mx-4 shadow-xl';
                }
            }

            switch(options.type) {
                case 'success':
                    iconClass.className = 'fas fa-check-circle text-green-600 text-4xl';
                    iconClass.style.color = '#059669';
                    iconClass.style.animation = 'mobileSuccessPulse 0.8s ease-out';
                    break;
                case 'error':
                    iconClass.className = 'fas fa-exclamation-triangle text-red-500 text-3xl';
                    break;
                case 'warning':
                    iconClass.className = 'fas fa-exclamation-circle text-yellow-500 text-3xl';
                    break;
                default:
                    iconClass.className = 'fas fa-info-circle text-blue-500 text-3xl';
            }

            title.textContent = options.title || '';
            message.textContent = options.message || '';
            bodyContent.innerHTML = options.bodyContent || '';

            // Styliser le titre et le message pour les alertes de succès
            if (options.type === 'success') {
                title.className = 'text-lg font-semibold text-center mb-2 success-title';
                message.className = 'text-gray-600 text-center mb-4 success-message';
                bodyContent.className = 'text-sm text-gray-500 text-center mb-4 success-body';
            }

            if (options.primaryBtn) {
                primaryBtn.textContent = options.primaryBtn.text;
                primaryBtn.onclick = options.primaryBtn.action;
                primaryBtn.style.display = 'block';

                // Styliser le bouton selon le type
                if (options.type === 'success') {
                    primaryBtn.className = 'px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1';
                } else {
                    primaryBtn.className = 'px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors';
                }
            } else {
                primaryBtn.style.display = 'none';
            }

            if (options.secondaryBtn) {
                secondaryBtn.textContent = options.secondaryBtn.text;
                secondaryBtn.onclick = options.secondaryBtn.action;
                secondaryBtn.style.display = 'block';
            } else {
                secondaryBtn.style.display = 'none';
            }

            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function hideCustomAlert() {
            const overlay = document.getElementById('customAlertOverlay');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Close alert on overlay click
        document.getElementById('customAlertOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCustomAlert();
            }
        });

        // Close alert with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideCustomAlert();
            }
        });

        // Payment Methods Management
        let selectedPaymentMethod = null;

            // Restore selections after scroll or page interactions (Mobile)
            function restoreMobileSelections() {
                // Only restore if we have selections to restore
                if (!selectedReservationType && !selectedPaymentMethod && !selectedReservationValue) {
                    return;
                }

                console.log('🔄 Mobile - Restoring selections...');

                // Restore reservation type selection
                if (selectedReservationType) {
                    const selectedOption = document.querySelector(`.mobile-reservation-type[data-type="${selectedReservationType}"]`);
                    if (selectedOption && !selectedOption.classList.contains('selected')) {
                        selectedOption.classList.add('selected');
                        console.log('✅ Mobile - Restored reservation type selection:', selectedReservationType);
                    }
                }

                // Restore payment method selection
                if (selectedPaymentMethod) {
                    const selectedPaymentCard = document.querySelector(`[data-provider="${selectedPaymentMethod}"]`);
                    if (selectedPaymentCard && !selectedPaymentCard.classList.contains('selected')) {
                        selectedPaymentCard.classList.add('selected');
                        console.log('✅ Mobile - Restored payment method selection:', selectedPaymentMethod);
                    }
                }

                // Restore duration selection
                if (selectedReservationValue) {
                    const selectedDurationBtn = document.querySelector(`.mobile-duration-btn[data-duration="${selectedReservationValue}"]`);
                    if (selectedDurationBtn && !selectedDurationBtn.classList.contains('selected')) {
                        selectedDurationBtn.classList.add('selected');
                        console.log('✅ Mobile - Restored duration selection:', selectedReservationValue);
                    }
                }
            }

            // Force pre-selection of reservation type based on plan (Mobile)
            function forceMobileReservationTypePreselection() {
                console.log('🚀 Mobile - Forcing reservation type pre-selection...');

                // Get allowed types and recommended type
                const allowedTypes = getAllowedReservationTypes();
                const recommendedType = getRecommendedReservationType();

                console.log('📋 Mobile - Allowed types:', allowedTypes);
                console.log('🎯 Mobile - Recommended type:', recommendedType);

                // Wait for DOM to be ready with longer timeout
                setTimeout(() => {
                    // Hide non-allowed reservation type options
                    document.querySelectorAll('.mobile-reservation-type[data-type]').forEach(option => {
                        const type = option.dataset.type;
                        if (!allowedTypes.includes(type)) {
                            console.log('🚫 Mobile - Hiding non-allowed type:', type);
                            option.style.display = 'none';
                        } else {
                            console.log('✅ Mobile - Showing allowed type:', type);
                            option.style.display = 'block';
                        }
                    });

                    // Clear any existing selection
                    document.querySelectorAll('.mobile-reservation-type[data-type]').forEach(opt => {
                        opt.classList.remove('selected');
                    });

                    const recommendedOption = document.querySelector(`.mobile-reservation-type[data-type="${recommendedType}"]`);
                    console.log('🔍 Mobile - Looking for option:', recommendedType, 'Found:', !!recommendedOption);

                    // Force selection regardless of allowed types (plan-based selection)
                    if (recommendedOption) {
                // Force selection
                recommendedOption.classList.add('selected');
                selectedReservationType = recommendedType;

                console.log('🎯 Mobile - Forced selection:', selectedReservationType);

                // Update UI elements
                const valueUnit = document.getElementById('mobile-value-unit');

                // If only one type is allowed, hide the entire type selection section
                if (allowedTypes.length === 1) {
                    const typeSelectionSection = document.querySelector('.mobile-reservation-type-selection');
                    if (typeSelectionSection) {
                        console.log('🎯 Mobile - Only one type allowed - hiding type selection section');
                        typeSelectionSection.style.display = 'none';

                        // Show a message that the type is automatically selected
                        const autoSelectMessage = document.createElement('div');
                        autoSelectMessage.className = 'bg-green-50 border border-green-200 rounded-lg p-4 mb-4';
                        autoSelectMessage.innerHTML = `
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 mr-3"></i>
                                <div>
                                    <span class="text-green-800 font-semibold text-base">
                                        Type de réservation : ${recommendedType === 'kwh' ? 'kWh (Énergie)' : 'Minutes (Temps)'}
                                    </span>
                                    <p class="text-green-700 text-sm mt-1">
                                        Sélectionné automatiquement selon le plan de tarification
                                    </p>
                                </div>
                            </div>
                        `;

                        // Insert the message before the duration selection
                        const durationSection = document.querySelector('.mobile-duration-selection');
                        if (durationSection) {
                            durationSection.parentNode.insertBefore(autoSelectMessage, durationSection);
                        }
                    }
                }
                if (valueUnit) {
                    valueUnit.textContent = selectedReservationType === 'kwh' ? 'kWh' : 'min';
                }

                // Update all related functions
                updateMobileReservationTypeInfo();
                updateMobileReserveButtonState();
                updateMobileCostEstimation();

                // Visual feedback
                recommendedOption.style.transform = 'scale(1.05)';
                recommendedOption.style.boxShadow = '0 8px 25px rgba(59, 130, 246, 0.3)';
                setTimeout(() => {
                    recommendedOption.style.transform = '';
                    recommendedOption.style.boxShadow = '';
                }, 500);

                return true;
            }

            return false;
        }

        // Load payment methods on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Force pre-selection with longer delay
            setTimeout(() => {
                forceMobileReservationTypePreselection();
            }, 500);
            // Load payment methods with delay to ensure server is ready
            setTimeout(() => {
                loadPaymentMethods();
            }, 500);

            // Add scroll and interaction listeners to restore selections (Mobile) with debouncing
            let mobileRestoreTimeout;
            function debouncedMobileRestore() {
                clearTimeout(mobileRestoreTimeout);
                mobileRestoreTimeout = setTimeout(restoreMobileSelections, 100);
            }

            window.addEventListener('scroll', debouncedMobileRestore, { passive: true });
            window.addEventListener('resize', debouncedMobileRestore, { passive: true });

            // Only restore on specific interactions, not all clicks/touches
            document.addEventListener('click', function(e) {
                // Only restore if clicking on non-interactive elements
                if (!e.target.closest('.mobile-reservation-type, .payment-method-card, .mobile-duration-btn, button, input, select')) {
                    debouncedMobileRestore();
                }
            });

            document.addEventListener('touchstart', function(e) {
                // Only restore if touching non-interactive elements
                if (!e.target.closest('.mobile-reservation-type, .payment-method-card, .mobile-duration-btn, button, input, select')) {
                    debouncedMobileRestore();
                }
            }, { passive: true });

            // Restore selections periodically but less frequently (Mobile)
            setInterval(restoreMobileSelections, 5000);
        });

        async function loadPaymentMethods(retryCount = 0) {
            const maxRetries = 3;
            const retryDelay = 1000; // 1 second

            console.log(`🔄 Mobile - Loading payment methods... (attempt ${retryCount + 1}/${maxRetries + 1})`);

            try {
                const response = await fetch('/api/payment-methods', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'Cache-Control': 'no-cache'
                    }
                });

                console.log('📡 Mobile - API Response status:', response.status);

                if (!response.ok) {
                    // Try to get error details from response
                    let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                    try {
                        const errorData = await response.json();
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                        if (errorData.debug) {
                            console.error('🔍 Mobile - Debug info:', errorData.debug);
                        }
                    } catch (parseError) {
                        console.warn('Mobile - Could not parse error response:', parseError);
                    }
                    throw new Error(errorMessage);
                }

                const paymentMethods = await response.json();
                console.log('✅ Mobile - Payment methods loaded:', paymentMethods);
                renderPaymentMethods(paymentMethods);

            } catch (error) {
                console.error(`❌ Mobile - Error loading payment methods (attempt ${retryCount + 1}):`, error);

                if (retryCount < maxRetries) {
                    console.log(`🔄 Mobile - Retrying in ${retryDelay}ms...`);
                    setTimeout(() => {
                        loadPaymentMethods(retryCount + 1);
                    }, retryDelay);
                } else {
                    console.error('❌ Mobile - Max retries reached, showing error');
                    showPaymentError(`Impossible de charger les méthodes de paiement après ${maxRetries + 1} tentatives: ${error.message}`);
                }
            }
        }

        function renderPaymentMethods(paymentMethods) {
            console.log('🎨 Mobile - Rendering payment methods:', paymentMethods);
            const container = document.getElementById('paymentMethodsContainer');
            console.log('📦 Mobile - Container found:', container);

            if (!container) {
                console.error('❌ Mobile - Payment methods container not found!');
                return;
            }

            container.innerHTML = '';
            console.log('🧹 Mobile - Container cleared');

            paymentMethods.forEach((method, index) => {
                console.log(`🃏 Mobile - Creating card ${index + 1} for method:`, method);
                const methodCard = createPaymentMethodCard(method);
                container.appendChild(methodCard);
            });

            console.log('✅ Mobile - Payment methods rendered successfully');
        }

        function createPaymentMethodCard(method) {
            console.log('🃏 Mobile - Creating payment method card for:', method);

            const card = document.createElement('div');
            card.className = 'mobile-payment-method-card';
            card.onclick = () => selectPaymentMethod(method.id);

            const features = getPaymentMethodFeatures(method.provider);
            console.log('🏷️ Mobile - Features for', method.provider, ':', features);

            const featuresHtml = features.map(feature => 
                `<span class="mobile-feature-badge">${feature}</span>`
            ).join('');

            // Get provider-specific styling
            const providerStyle = getProviderStyle(method.provider);
            console.log('🎨 Mobile - Provider style for', method.provider, ':', providerStyle);

            card.innerHTML = `
                <div class="mobile-payment-method-header">
                    <div class="mobile-payment-method-logo ${providerStyle.bg}">
                        ${getPaymentMethodLogo(method.provider)}
                    </div>
                    <div class="mobile-payment-method-info">
                        <h4>${method.name}</h4>
                        <p>${getProviderDescription(method.provider)}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Optionnel</div>
                    </div>
                </div>
                <div class="mobile-payment-method-features">
                    ${featuresHtml}
                </div>
                <div class="text-xs text-gray-500 flex items-center mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    ${getProviderInfo(method.provider)}
                </div>
                <div class="mobile-selected-indicator">
                    <i class="fas fa-check"></i>
                </div>
            `;

            console.log('✅ Mobile - Payment method card created successfully');
            return card;
        }

        function getPaymentMethodLogo(provider) {
            switch(provider) {
                case 'cmi':
                    return '<i class="fas fa-credit-card"></i>';
                case 'stripe':
                    return '<i class="fab fa-stripe"></i>';
                default:
                    return '<i class="fas fa-wallet"></i>';
            }
        }

        function getPaymentMethodFeatures(provider) {
            switch(provider) {
                case 'cmi':
                    return ['Sécurisé', 'Rapide', 'Local'];
                case 'stripe':
                    return ['International', 'Sécurisé', 'Cartes'];
                default:
                    return ['Paiement', 'Sécurisé'];
            }
        }

        // Get provider-specific styling
        function getProviderStyle(provider) {
            switch(provider.toLowerCase()) {
                case 'cmi':
                    return {
                        bg: 'bg-gradient-to-br from-blue-500 to-blue-600',
                        border: 'border-blue-500'
                    };
                case 'stripe':
                    return {
                        bg: 'bg-gradient-to-br from-purple-500 to-purple-600',
                        border: 'border-purple-500'
                    };
                default:
                    return {
                        bg: 'bg-gradient-to-br from-gray-500 to-gray-600',
                        border: 'border-gray-500'
                    };
            }
        }

        // Get provider description
        function getProviderDescription(provider) {
            switch(provider.toLowerCase()) {
                case 'cmi':
                    return 'Paiement local sécurisé au Maroc';
                case 'stripe':
                    return 'Paiement international avec cartes bancaires';
                default:
                    return 'Méthode de paiement sécurisée';
            }
        }

        // Get provider info
        function getProviderInfo(provider) {
            switch(provider.toLowerCase()) {
                case 'cmi':
                    return 'Paiement instantané, sans frais supplémentaires';
                case 'stripe':
                    return 'Accepte Visa, Mastercard, American Express';
                default:
                    return 'Paiement sécurisé et rapide';
            }
        }

        // Optimized payment method selection with debouncing and performance improvements
        let paymentDebounceTimer = null;
        let lastSelectedPayment = null;

        function selectPaymentMethod(provider) {
            console.log('🎯 Mobile - Selecting payment method:', provider);

            if (paymentDebounceTimer) {
                clearTimeout(paymentDebounceTimer);
            }

            paymentDebounceTimer = setTimeout(() => {
                if (lastSelectedPayment === provider) return;

                // Use cached elements for better performance
                const allPaymentCards = getCachedElements('.payment-method-card', 'paymentMethods');

                // Batch DOM updates for better performance
                batchDOMUpdates([
                    () => {
                        // Remove all payment method selections in one operation
                        allPaymentCards.forEach(card => card.classList.remove('selected'));
                    },
                    () => {
                        // Find and select the current method
                        const selectedCard = document.querySelector(`[data-provider="${provider}"]`);
                        if (selectedCard) {
                            selectedCard.classList.add('selected');
                            selectedPaymentMethod = provider;
                            lastSelectedPayment = provider;

                            console.log('✅ Mobile - Payment method selected:', provider);

                            // Add visual feedback with optimized animation
                            selectedCard.style.transform = 'scale(1.02)';
                            selectedCard.style.transition = 'transform 0.2s ease';
                            setTimeout(() => {
                                selectedCard.style.transform = '';
                                selectedCard.style.transition = '';
                            }, 200);

                            // Update reserve button state
                            updateReserveButtonState();
                        } else {
                            console.warn('⚠️ Mobile - Payment method card not found for:', provider);
                        }
                    }
                ]);
            }, 50); // 50ms debounce
        }

        // Make selectPaymentMethod globally available for onclick handlers
        window.selectPaymentMethod = selectPaymentMethod;

        // Payment method testing and API key validation
        async function testPaymentMethods() {
            console.log('🧪 Testing payment methods and API keys...');

            const testButton = document.getElementById('test-payment-methods-btn');
            const resultsContainer = document.getElementById('payment-test-results');
            const cmiStatus = document.getElementById('cmi-status');
            const stripeStatus = document.getElementById('stripe-status');
            const steveStatus = document.getElementById('steve-status'); // Add steveStatus here since it's referenced later

            // Ensure elements exist before manipulating them
            if (testButton) {
                // Show loading state
                testButton.disabled = true;
                testButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Test en cours...';
            }

            if (resultsContainer) {
                resultsContainer.classList.remove('hidden');
            }

            const testResults = {
                cmi: { status: 'unknown', hasApiKey: false, error: null, health: 'unknown' },
                stripe: { status: 'unknown', hasApiKey: false, error: null, health: 'unknown' },
                steve: { status: 'unknown', hasApiKey: false, error: null, health: 'unknown' }
            };

            try {
                // Test CMI payment method
                const cmiTest = await testPaymentMethod('cmi');
                testResults.cmi = cmiTest;
                updateTestResult('cmi', cmiTest, cmiStatus);

                // Test Stripe payment method
                const stripeTest = await testPaymentMethod('stripe');
                testResults.stripe = stripeTest;
                updateTestResult('stripe', stripeTest, stripeStatus);

                // Test SteVe API
                const steveTest = await testPaymentMethod('steve');
                testResults.steve = steveTest;
                updateTestResult('steve', steveTest, steveStatus);

                // Check if any API keys are missing
                const missingKeys = [];
                if (!testResults.cmi.hasApiKey) missingKeys.push('CMI');
                if (!testResults.stripe.hasApiKey) missingKeys.push('Stripe');
                if (!testResults.steve.hasApiKey) missingKeys.push('SteVe');

                if (missingKeys.length > 0) {
                    await sendAdminAlert({
                        type: 'warning',
                        title: 'Clés API Manquantes',
                        message: `Les clés API suivantes sont manquantes : ${missingKeys.join(', ')}. Veuillez configurer les clés API dans les paramètres d'administration.`,
                        paymentMethods: missingKeys,
                        timestamp: new Date().toISOString()
                    });

                    // Show user notification
                    showCustomAlert({
                        type: 'warning',
                        title: 'Clés API Manquantes',
                        message: `Les méthodes de paiement suivantes nécessitent une configuration : ${missingKeys.join(', ')}. L'administrateur a été notifié.`,
                        primaryBtn: {
                            text: 'Compris',
                            action: () => hideCustomAlert()
                        }
                    });
                }

                console.log('✅ Payment method testing completed:', testResults);
                return testResults;

            } catch (error) {
                console.error('❌ Error testing payment methods:', error);
                await sendAdminAlert({
                    type: 'error',
                    title: 'Erreur Test Paiements',
                    message: `Erreur lors du test des méthodes de paiement : ${error.message}`,
                    error: error.message,
                    timestamp: new Date().toISOString()
                });

                // Show error to user
                showCustomAlert({
                    type: 'error',
                    title: 'Erreur de Test',
                    message: 'Une erreur est survenue lors du test des méthodes de paiement.',
                    primaryBtn: {
                        text: 'OK',
                        action: () => hideCustomAlert()
                    }
                });

                throw error;
            } finally {
                // Reset button state
                if (testButton) {
                    testButton.disabled = false;
                    testButton.innerHTML = '<i class="fas fa-vial mr-2"></i>Tester';
                }
            }
        }

        // Update test result display with proactive information
        function updateTestResult(provider, result, statusElement) {
            let icon, text, className;

            if (result.status === 'success') {
                if (result.hasApiKey) {
                    // Check health status for additional indicators
                    if (result.health === 'healthy') {
                        icon = '<i class="fas fa-check-circle text-green-500 mr-1"></i>';
                        text = 'API Key OK';
                        className = 'text-sm text-green-600 font-medium';
                    } else if (result.health === 'degraded') {
                        icon = '<i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>';
                        text = 'API Key OK (Performance dégradée)';
                        className = 'text-sm text-yellow-600 font-medium';
                    } else if (result.health === 'slow') {
                        icon = '<i class="fas fa-clock text-orange-500 mr-1"></i>';
                        text = 'API Key OK (Lente)';
                        className = 'text-sm text-orange-600 font-medium';
                    } else {
                        icon = '<i class="fas fa-check-circle text-green-500 mr-1"></i>';
                        text = 'API Key OK';
                        className = 'text-sm text-green-600 font-medium';
                    }
                } else {
                    icon = '<i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>';
                    text = 'API Key Manquante';
                    className = 'text-sm text-yellow-600 font-medium';
                }
            } else {
                if (result.health === 'unreachable') {
                    icon = '<i class="fas fa-unlink text-red-500 mr-1"></i>';
                    text = 'Inaccessible';
                    className = 'text-sm text-red-600 font-medium';
                } else {
                    icon = '<i class="fas fa-times-circle text-red-500 mr-1"></i>';
                    text = 'Erreur';
                    className = 'text-sm text-red-600 font-medium';
                }
            }

            statusElement.innerHTML = icon + text;
            statusElement.className = className;

            // Update overview status
            updateOverviewStatus(provider, result);

            // Add proactive alerts if any
            if (result.proactiveAlerts && result.proactiveAlerts.length > 0) {
                const alertIcon = result.proactiveAlerts.some(alert => alert.severity === 'high') 
                    ? '<i class="fas fa-exclamation-circle text-red-500 ml-2" title="Alertes proactives détectées"></i>'
                    : '<i class="fas fa-info-circle text-blue-500 ml-2" title="Suggestions disponibles"></i>';
                statusElement.innerHTML += alertIcon;
            }

            // Log proactive alerts for debugging
            if (result.proactiveAlerts && result.proactiveAlerts.length > 0) {
                console.log(`🔍 Proactive alerts for ${provider}:`, result.proactiveAlerts);
            }
        }

        // Update overview status display
        function updateOverviewStatus(provider, result) {
            const overviewElement = document.getElementById(`${provider}-overview`);
            if (!overviewElement) return;

            let statusText, statusClass;

            if (result.status === 'success') {
                if (result.hasApiKey) {
                    if (result.health === 'healthy') {
                        statusText = '✓';
                        statusClass = 'text-green-600';
                    } else if (result.health === 'degraded') {
                        statusText = '⚠';
                        statusClass = 'text-yellow-600';
                    } else if (result.health === 'slow') {
                        statusText = '🐌';
                        statusClass = 'text-orange-600';
                    } else {
                        statusText = '✓';
                        statusClass = 'text-green-600';
                    }
                } else {
                    statusText = '🔑';
                    statusClass = 'text-yellow-600';
                }
            } else {
                if (result.health === 'unreachable') {
                    statusText = '❌';
                    statusClass = 'text-red-600';
                } else {
                    statusText = '⚠';
                    statusClass = 'text-red-600';
                }
            }

            overviewElement.textContent = statusText;
            overviewElement.className = `text-xs font-medium ${statusClass}`;
        }

        // Test individual payment method with proactive detection
        async function testPaymentMethod(provider) {
            console.log(`🔍 Testing ${provider} payment method with proactive detection...`);

            const startTime = Date.now();

            try {
                const response = await fetch(`/api/payment-methods/test/${provider}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        provider: provider,
                        test: true,
                        proactive: true,
                        healthCheck: true
                    })
                });

                const result = await response.json();
                const responseTime = Date.now() - startTime;

                if (response.ok) {
                    console.log(`✅ ${provider} test successful:`, result);

                    // Proactive health monitoring
                    const healthStatus = analyzeHealthStatus(result, responseTime);

                    return {
                        status: 'success',
                        hasApiKey: result.hasApiKey || false,
                        error: null,
                        details: result,
                        health: healthStatus,
                        responseTime: responseTime,
                        proactiveAlerts: result.proactiveAlerts || []
                    };
                } else {
                    console.warn(`⚠️ ${provider} test failed:`, result);

                    // Proactive error analysis
                    const proactiveAlerts = analyzeProactiveErrors(result, provider);

                    return {
                        status: 'error',
                        hasApiKey: false,
                        error: result.message || 'Test failed',
                        details: result,
                        health: 'unhealthy',
                        responseTime: responseTime,
                        proactiveAlerts: proactiveAlerts
                    };
                }

            } catch (error) {
                console.error(`❌ ${provider} test error:`, error);

                // Proactive network error analysis
                const proactiveAlerts = analyzeNetworkErrors(error, provider);

                return {
                    status: 'error',
                    hasApiKey: false,
                    error: error.message,
                    details: null,
                    health: 'unreachable',
                    responseTime: Date.now() - startTime,
                    proactiveAlerts: proactiveAlerts
                };
            }
        }

        // Analyze health status based on response time and API health
        function analyzeHealthStatus(result, responseTime) {
            if (responseTime > 5000) return 'slow';
            if (responseTime > 2000) return 'degraded';
            if (result.health && result.health.status === 'healthy') return 'healthy';
            if (result.health && result.health.status === 'warning') return 'warning';
            return 'unknown';
        }

        // Analyze proactive errors and suggest solutions
        function analyzeProactiveErrors(result, provider) {
            const alerts = [];

            if (result.error && result.error.includes('API key')) {
                alerts.push({
                    type: 'configuration',
                    severity: 'high',
                    message: `Clé API ${provider} manquante ou invalide`,
                    solution: 'Vérifier la configuration des clés API dans les paramètres d\'administration'
                });
            }

            if (result.error && result.error.includes('network')) {
                alerts.push({
                    type: 'network',
                    severity: 'medium',
                    message: `Problème de connectivité avec ${provider}`,
                    solution: 'Vérifier la connectivité réseau et les paramètres de proxy'
                });
            }

            if (result.error && result.error.includes('rate limit')) {
                alerts.push({
                    type: 'rate_limit',
                    severity: 'medium',
                    message: `Limite de taux atteinte pour ${provider}`,
                    solution: 'Attendre ou augmenter les limites de taux dans la configuration'
                });
            }

            return alerts;
        }

        // Analyze network errors and suggest solutions
        function analyzeNetworkErrors(error, provider) {
            const alerts = [];

            if (error.message.includes('Failed to fetch')) {
                alerts.push({
                    type: 'network',
                    severity: 'high',
                    message: `Impossible de se connecter à ${provider}`,
                    solution: 'Vérifier la connectivité internet et les paramètres de firewall'
                });
            }

            if (error.message.includes('timeout')) {
                alerts.push({
                    type: 'timeout',
                    severity: 'medium',
                    message: `Timeout de connexion avec ${provider}`,
                    solution: 'Augmenter le timeout ou vérifier la performance du serveur'
                });
            }

            return alerts;
        }

        // Send admin alert for missing API keys
        async function sendAdminAlert(alertData) {
            console.log('🚨 Sending admin alert:', alertData);

            try {
                const response = await fetch('/api/admin/alerts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(alertData)
                });

                if (response.ok) {
                    console.log('✅ Admin alert sent successfully');
                    return true;
                } else {
                    console.error('❌ Failed to send admin alert:', response.status);
                    return false;
                }

            } catch (error) {
                console.error('❌ Error sending admin alert:', error);
                return false;
            }
        }

        // Proactive monitoring system
        class ProactiveMonitoring {
            constructor() {
                this.monitoringInterval = null;
                this.healthStatus = {
                    cmi: { status: 'unknown', lastCheck: null, consecutiveFailures: 0 },
                    stripe: { status: 'unknown', lastCheck: null, consecutiveFailures: 0 },
                    steve: { status: 'unknown', lastCheck: null, consecutiveFailures: 0 }
                };
                this.alertThreshold = 3; // Number of consecutive failures before alert
            }

            // Start continuous monitoring
            startMonitoring(intervalMinutes = 5) {
                console.log('🔄 Starting proactive monitoring...');

                this.monitoringInterval = setInterval(async () => {
                    await this.performHealthCheck();
                }, intervalMinutes * 60 * 1000);

                // Initial health check
                setTimeout(() => this.performHealthCheck(), 5000);
            }

            // Stop monitoring
            stopMonitoring() {
                if (this.monitoringInterval) {
                    clearInterval(this.monitoringInterval);
                    this.monitoringInterval = null;
                    console.log('⏹️ Proactive monitoring stopped');
                }
            }

            // Perform health check on all APIs
            async performHealthCheck() {
                console.log('🔍 Performing proactive health check...');

                const providers = ['cmi', 'stripe', 'steve'];
                const results = {};

                for (const provider of providers) {
                    try {
                        const result = await testPaymentMethod(provider);
                        results[provider] = result;

                        // Update health status
                        this.updateHealthStatus(provider, result);

                    } catch (error) {
                        console.error(`❌ Health check failed for ${provider}:`, error);
                        this.updateHealthStatus(provider, { status: 'error', error: error.message });
                    }
                }

                // Check for proactive alerts
                await this.checkProactiveAlerts(results);

                return results;
            }

            // Update health status for a provider
            updateHealthStatus(provider, result) {
                const currentStatus = this.healthStatus[provider];

                if (result.status === 'success') {
                    currentStatus.status = 'healthy';
                    currentStatus.consecutiveFailures = 0;
                } else {
                    currentStatus.status = 'unhealthy';
                    currentStatus.consecutiveFailures++;
                }

                currentStatus.lastCheck = new Date().toISOString();
                this.healthStatus[provider] = currentStatus;
            }

            // Check for proactive alerts
            async checkProactiveAlerts(results) {
                const alerts = [];

                for (const [provider, result] of Object.entries(results)) {
                    const healthStatus = this.healthStatus[provider];

                    // Check for consecutive failures
                    if (healthStatus.consecutiveFailures >= this.alertThreshold) {
                        alerts.push({
                            type: 'consecutive_failures',
                            severity: 'high',
                            provider: provider,
                            message: `${provider.toUpperCase()} a échoué ${healthStatus.consecutiveFailures} fois consécutives`,
                            solution: 'Vérifier la configuration et la connectivité'
                        });
                    }

                    // Check for performance issues
                    if (result.health === 'slow' || result.health === 'degraded') {
                        alerts.push({
                            type: 'performance',
                            severity: 'medium',
                            provider: provider,
                            message: `Performance dégradée détectée pour ${provider.toUpperCase()}`,
                            solution: 'Vérifier la charge du serveur et les paramètres de performance'
                        });
                    }

                    // Check for API key issues
                    if (!result.hasApiKey) {
                        alerts.push({
                            type: 'configuration',
                            severity: 'high',
                            provider: provider,
                            message: `Clé API manquante pour ${provider.toUpperCase()}`,
                            solution: 'Configurer les clés API dans les paramètres d\'administration'
                        });
                    }
                }

                // Send alerts if any
                if (alerts.length > 0) {
                    await this.sendProactiveAlerts(alerts);
                }
            }

            // Send proactive alerts
            async sendProactiveAlerts(alerts) {
                console.log('🚨 Sending proactive alerts:', alerts);

                try {
                    await sendAdminAlert({
                        type: 'proactive_monitoring',
                        title: 'Alertes Proactives - Monitoring Système',
                        message: `${alerts.length} problème(s) détecté(s) par le monitoring proactif`,
                        alerts: alerts,
                        timestamp: new Date().toISOString(),
                        healthStatus: this.healthStatus
                    });
                } catch (error) {
                    console.error('❌ Failed to send proactive alerts:', error);
                }
            }

            // Get current health status
            getHealthStatus() {
                return this.healthStatus;
            }
        }

        // Initialize proactive monitoring
        const proactiveMonitoring = new ProactiveMonitoring();

        // Make test functions globally available
        window.testPaymentMethods = testPaymentMethods;
        window.testPaymentMethod = testPaymentMethod;
        window.proactiveMonitoring = proactiveMonitoring;

        // Start monitoring when page loads
        document.addEventListener('DOMContentLoaded', () => {
            // Start proactive monitoring after a delay
            setTimeout(() => {
                proactiveMonitoring.startMonitoring(5); // Check every 5 minutes
            }, 10000); // Start after 10 seconds
        });

        function showPaymentError(message) {
            showCustomAlert({
                type: 'error',
                title: 'Erreur de Paiement',
                message: message,
                primaryBtn: {
                    text: 'OK',
                    action: () => hideCustomAlert()
                }
            });
        }
        </script>
    @endpush
@endsection