@extends('layouts.public')

@section('title', 'Réservation de la borne')

@push('styles')
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        /* ===== MOBILE-FIRST RESPONSIVE DESIGN ===== */
        
        /* Reset and Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }
        
        /* ===== MOBILE CONTAINER (320px - 768px) ===== */
        .mobile-container {
            width: 100%;
            max-width: 100vw;
            margin: 0 auto;
            padding: 0;
            background: transparent;
        }
        
        /* ===== HEADER SECTION ===== */
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .header-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 16px;
        }
        
        .header-icon {
            font-size: 48px;
            margin-bottom: 16px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes costUpdate {
            0% { 
                background-color: #f0fdf4;
                transform: scale(1);
            }
            50% { 
                background-color: #dcfce7;
                transform: scale(1.02);
            }
            100% { 
                background-color: #f0fdf4;
                transform: scale(1);
            }
        }
        
        .cost-value {
            transition: all 0.3s ease;
        }
        
        .cost-value.updating {
            animation: costUpdate 0.6s ease-in-out;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            background: #f8fafc;
            border-radius: 24px 24px 0 0;
            margin-top: -20px;
            position: relative;
            z-index: 3;
            min-height: calc(100vh - 120px);
            padding: 24px 16px 40px;
        }
        
        /* ===== STEP INDICATOR ===== */
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 32px;
            gap: 8px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: #3b82f6;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .step.completed {
            background: #10b981;
            color: white;
        }
        
        .step.pending {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .step-connector {
            width: 20px;
            height: 2px;
            background: #e5e7eb;
            transition: background 0.3s ease;
        }
        
        .step-connector.active {
            background: #3b82f6;
        }
        
        /* ===== SECTION STYLES ===== */
        .section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-icon {
            font-size: 20px;
            color: #3b82f6;
        }
        
        /* ===== TYPE SELECTION ===== */
        .type-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .type-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .type-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .type-card:hover::before {
            left: 100%;
        }
        
        .type-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
        }
        
        .type-card.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .type-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
            background: #f3f4f6;
            border-color: #d1d5db;
            color: #9ca3af;
        }
        
        .type-card.disabled .type-icon {
            color: #9ca3af !important;
        }
        
        .type-card.disabled .type-title {
            color: #9ca3af;
        }
        
        .type-card.disabled .type-desc {
            color: #9ca3af;
        }
        
        .type-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
        }
        
        .type-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .type-desc {
            font-size: 14px;
            color: #6b7280;
        }
        
        /* ===== VALUE SELECTION ===== */
        .value-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .value-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .value-card:hover {
            border-color: #3b82f6;
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .value-card.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .value-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
            background: #f3f4f6;
            border-color: #d1d5db;
            color: #9ca3af;
        }
        
        .value-card.disabled .value-number {
            color: #9ca3af;
        }
        
        .value-card.disabled .value-unit {
            color: #9ca3af;
        }
        
        .value-number {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .value-unit {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* ===== CUSTOM INPUT ===== */
        .custom-input-group {
            margin-top: 16px;
        }
        
        .custom-input-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .custom-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .custom-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: scale(1.02);
        }
        
        /* ===== TIME SELECTION ===== */
        .time-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .time-slot {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .time-slot:hover {
            border-color: #3b82f6;
            background: #f8fafc;
            transform: scale(1.05);
        }
        
        .time-slot.selected {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1e40af;
            font-weight: 600;
        }
        
        .time-slot.unavailable {
            opacity: 0.4;
            cursor: not-allowed;
            background: #fef2f2;
            border-color: #fecaca;
        }
        
        /* ===== COST DISPLAY ===== */
        .cost-section {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .cost-title {
            font-size: 18px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cost-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .cost-row:last-child {
            border-bottom: none;
        }
        
        .cost-label {
            font-size: 14px;
            color: #374151;
        }
        
        .cost-value {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .cost-total {
            font-size: 18px;
            font-weight: 700;
            color: #166534;
            border-top: 2px solid #bbf7d0;
            padding-top: 12px;
            margin-top: 12px;
        }
        
        /* ===== SUBMIT BUTTON ===== */
        .submit-section {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 20px 16px;
            border-top: 1px solid #e5e7eb;
            margin: 0 -16px -40px;
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .submit-btn:hover:not(:disabled)::before {
            left: 100%;
        }
        
        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .submit-btn:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .submit-btn .btn-icon {
            margin-right: 8px;
        }
        
        /* ===== ALERT SYSTEM ===== */
        .alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .alert-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .alert-content {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin: 20px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .alert-overlay.show .alert-content {
            transform: scale(1);
        }
        
        .alert-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .alert-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .alert-message {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .alert-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .alert-btn:hover {
            background: #2563eb;
            transform: scale(1.05);
        }
        
        /* ===== LOADING STATES ===== */
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
        }
        
        .spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* ===== TABLET STYLES (768px+) ===== */
        @media (min-width: 768px) {
            .mobile-container {
                max-width: 600px;
                margin: 20px auto;
                padding: 0 20px;
            }
            
            .header-section {
                border-radius: 16px 16px 0 0;
            }
            
            .main-content {
                border-radius: 0 0 16px 16px;
                margin-top: 0;
            }
            
            .type-grid {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }
            
            .value-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 16px;
            }
            
            .time-grid {
                grid-template-columns: repeat(6, 1fr);
                gap: 12px;
            }
        }
        
        /* ===== DESKTOP STYLES (1024px+) ===== */
        @media (min-width: 1024px) {
            .mobile-container {
                max-width: 800px;
            }
            
            .type-grid {
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            
            .value-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
            }
            
            .time-grid {
                grid-template-columns: repeat(8, 1fr);
                gap: 16px;
            }
        }
        
        /* ===== ACCESSIBILITY ===== */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* ===== DARK MODE SUPPORT ===== */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            }
            
            .main-content {
                background: #1f2937;
                color: #f9fafb;
            }
            
            .section {
                background: #374151;
                border-color: #4b5563;
            }
            
            .section-title {
                color: #f9fafb;
            }
        }
    </style>
@endpush

@section('content')
<div class="mobile-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-charging-station"></i>
            </div>
            <h1 class="header-title">{{ $chargingPoint->name ?? 'Borne de Recharge' }}</h1>
            <p class="header-subtitle">{{ $chargingPoint->address ?? 'Adresse non disponible' }}</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" id="step-1">1</div>
            <div class="step-connector"></div>
            <div class="step pending" id="step-2">2</div>
            <div class="step-connector"></div>
            <div class="step pending" id="step-3">3</div>
        </div>

        <!-- Type Selection -->
        <div class="section" id="type-section">
            <h2 class="section-title">
                <i class="fas fa-bolt section-icon"></i>
                Type de Réservation
            </h2>
            <div class="type-grid">
                <div class="type-card" data-type="kwh" onclick="window.selectType('kwh')">
                    <i class="fas fa-bolt type-icon" style="color: #3b82f6;"></i>
                    <div class="type-title">Par Énergie</div>
                    <div class="type-desc">kWh</div>
                </div>
                <div class="type-card" data-type="minute" onclick="window.selectType('minute')">
                    <i class="fas fa-clock type-icon" style="color: #10b981;"></i>
                    <div class="type-title">Par Durée</div>
                    <div class="type-desc">Minutes</div>
                </div>
            </div>
        </div>

        <!-- Value Selection -->
        <div class="section" id="value-section" style="display: none;">
            <h2 class="section-title">
                <i class="fas fa-calculator section-icon"></i>
                Quantité ou Durée
            </h2>
            <div class="value-grid">
                <div class="value-card" data-value="10" onclick="window.selectValue(10)">
                    <div class="value-number">10</div>
                    <div class="value-unit" id="unit-10">min</div>
                </div>
                <div class="value-card" data-value="20" onclick="window.selectValue(20)">
                    <div class="value-number">20</div>
                    <div class="value-unit" id="unit-20">min</div>
                </div>
                <div class="value-card" data-value="30" onclick="window.selectValue(30)">
                    <div class="value-number">30</div>
                    <div class="value-unit" id="unit-30">min</div>
                </div>
                <div class="value-card" data-value="60" onclick="window.selectValue(60)">
                    <div class="value-number">60</div>
                    <div class="value-unit" id="unit-60">min</div>
                </div>
            </div>
            
            <!-- Custom Input -->
            <div class="custom-input-group">
                <label class="custom-input-label">Ou saisissez une valeur personnalisée</label>
                <input type="number" id="custom-value" class="custom-input" placeholder="Entrez une valeur" onchange="window.selectCustomValue()">
            </div>
            
            <!-- Limit Info -->
            <div id="limit-info" class="custom-input-group" style="display: none;">
                <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; text-align: center;">
                    <i class="fas fa-info-circle" style="color: #d97706; margin-right: 8px;"></i>
                    <span id="limit-text" style="color: #92400e; font-size: 14px; font-weight: 500;"></span>
                </div>
            </div>
        </div>

        <!-- Time Selection -->
        <div class="section" id="time-section" style="display: none;">
            <h2 class="section-title">
                <i class="fas fa-clock section-icon"></i>
                Heure de Début
            </h2>
            <div class="time-grid">
                @for($hour = 6; $hour <= 22; $hour++)
                <div class="time-slot" data-time="{{ sprintf('%02d:00', $hour) }}" onclick="window.selectTime('{{ sprintf('%02d:00', $hour) }}')">
                    {{ sprintf('%02d:00', $hour) }}
                </div>
                @endfor
            </div>
            
            <!-- Custom Time Input -->
            <div class="custom-input-group">
                <label class="custom-input-label">Ou saisissez une heure personnalisée</label>
                <input type="time" id="custom-time" class="custom-input" min="06:00" max="22:00" onchange="window.selectCustomTime()">
            </div>
        </div>

        <!-- Cost Display -->
        <div class="cost-section" id="cost-section" style="display: none;">
            <h3 class="cost-title">
                <i class="fas fa-calculator"></i>
                Estimation en Temps Réel
            </h3>
            
            <!-- Calculation Details -->
            <div class="cost-row" id="calculation-details" style="display: none;">
                <span class="cost-label" id="calculation-label">Tarif de base:</span>
                <span class="cost-value" id="calculation-amount">0.00 {{ $currency ?? 'EUR' }}</span>
            </div>
            
            <!-- Base Cost -->
            <div class="cost-row">
                <span class="cost-label">Tarif de base:</span>
                <span class="cost-value" id="base-cost">0.00 {{ $currency ?? 'EUR' }}</span>
            </div>
            
            <!-- Activation Fee -->
            <div class="cost-row">
                <span class="cost-label">Frais d'activation:</span>
                <span class="cost-value" id="activation-fee">{{ number_format($pricingPlan->activation_fee ?? 0, 2) }} {{ $currency ?? 'EUR' }}</span>
            </div>
            
            <!-- Subtotal -->
            <div class="cost-row" style="border-top: 1px solid #bbf7d0; padding-top: 8px;">
                <span class="cost-label" style="font-weight: 600;">Sous-total HT:</span>
                <span class="cost-value" id="subtotal-ht" style="font-weight: 600;">0.00 {{ $currency ?? 'EUR' }}</span>
            </div>
            
            <!-- VAT -->
            <div class="cost-row">
                <span class="cost-label">TVA ({{ number_format($pricingPlan->vatRate->rate ?? 0, 1) }}%):</span>
                <span class="cost-value" id="vat-cost">0.00 {{ $currency ?? 'EUR' }}</span>
            </div>
            
            <!-- Total -->
            <div class="cost-row cost-total">
                <span class="cost-label">Total TTC:</span>
                <span class="cost-value" id="total-cost">0.00 {{ $currency ?? 'EUR' }}</span>
            </div>
            
            <!-- Real-time indicator -->
            <div class="real-time-indicator" style="text-align: center; margin-top: 12px; padding: 8px; background: rgba(16, 185, 129, 0.1); border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.2);">
                <i class="fas fa-sync-alt fa-spin" style="color: #10b981; margin-right: 6px;"></i>
                <span style="color: #065f46; font-size: 12px; font-weight: 500;">Mise à jour en temps réel</span>
            </div>
        </div>
    </div>

    <!-- Submit Section -->
    <div class="submit-section">
        <button id="submit-btn" class="submit-btn" onclick="window.submitReservation()" disabled>
            <i class="fas fa-check btn-icon"></i>
            Réserver la Borne
        </button>
    </div>
</div>

<!-- Alert Overlay -->
<div class="alert-overlay" id="alert-overlay">
    <div class="alert-content">
        <div class="alert-icon" id="alert-icon"></div>
        <h3 class="alert-title" id="alert-title"></h3>
        <p class="alert-message" id="alert-message"></p>
        <button class="alert-btn" id="alert-btn" onclick="hideAlert()">OK</button>
    </div>
</div>

<script>
// ===== MOBILE-FIRST JAVASCRIPT =====

// Global state
let currentStep = 1;
let selectedType = null;
let selectedValue = null;
let selectedTime = null;

// Pricing data
const pricingData = {
    activationFee: {{ $pricingPlan->activation_fee ?? 0 }},
    pricePerKwh: {{ $pricingPlan->price_per_kwh ?? 0 }},
    pricePerMinute: {{ $pricingPlan->price_per_minute ?? 0 }},
    fixedPrice: {{ $pricingPlan->fixed_price ?? $pricingPlan->base_rate ?? 0 }},
    vatRate: {{ $pricingPlan->vatRate->rate ?? 0 }},
    currency: '{{ $currency ?? 'EUR' }}',
    rateType: '{{ $pricingPlan->rate_type ?? "mixed" }}',
    maxDuration: {{ $pricingPlan->max_duration ?? 0 }},
    chargingPointPower: {{ $chargingPoint->power_output ?? 0 }}
};

// Define all functions first and make them globally accessible
function selectType(type) {
    console.log('Type selected:', type);
    
    // Check if type is allowed
    const typeCard = document.querySelector(`.type-card[data-type="${type}"]`);
    if (typeCard && typeCard.classList.contains('disabled')) {
        console.log('Type not allowed:', type);
        showAlert('Type Non Autorisé', 'Ce type de réservation n\'est pas autorisé par ce plan tarifaire.', 'warning');
        return;
    }
    
    selectedType = type;
    
    // Update visual selection
    document.querySelectorAll('.type-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`.type-card[data-type="${type}"]`).classList.add('selected');
    
    // Update units
    const unit = type === 'kwh' ? 'kWh' : 'min';
    document.querySelectorAll('[id^="unit-"]').forEach(el => {
        el.textContent = unit;
    });
    
    // Show next section with smooth animation
    showNextSection('value-section');
    currentStep = 2;
    updateStepIndicator();
    
    // Haptic feedback (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

// Define all other functions
function selectValue(value) {
    console.log('Value selected:', value);
    
    // Validate value against plan limits
    const validation = validateReservationValue(value, selectedType);
    if (!validation.valid) {
        showAlert('Valeur Non Autorisée', validation.message, 'warning');
        return;
    }
    
    selectedValue = value;
    
    // Update visual selection
    document.querySelectorAll('.value-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`.value-card[data-value="${value}"]`).classList.add('selected');
    
    // Clear custom input
    const customInput = document.getElementById('custom-value');
    if (customInput) {
        customInput.value = '';
        customInput.style.borderColor = '';
        customInput.style.backgroundColor = '';
    }
    
    // Update cost calculation
    updateCost();
    
    // Show next section with smooth animation
    showNextSection('time-section');
    currentStep = 3;
    updateStepIndicator();
    
    // Haptic feedback (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

function selectCustomValue() {
    const customInput = document.getElementById('custom-value');
    const value = parseFloat(customInput.value);
    
    if (!value || value <= 0) {
        customInput.style.borderColor = '#ef4444';
        customInput.style.backgroundColor = '#fef2f2';
        showAlert('Valeur Invalide', 'Veuillez saisir une valeur numérique positive.', 'warning');
        return;
    }
    
    // Validate value against plan limits
    const validation = validateReservationValue(value, selectedType);
    if (!validation.valid) {
        customInput.style.borderColor = '#ef4444';
        customInput.style.backgroundColor = '#fef2f2';
        showAlert('Valeur Non Autorisée', validation.message, 'warning');
        return;
    }
    
    selectedValue = value;
    
    // Clear other selections
    document.querySelectorAll('.value-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Update cost calculation
    updateCost();
    
    // Show next section with smooth animation
    showNextSection('time-section');
    currentStep = 3;
    updateStepIndicator();
    
    // Haptic feedback (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

function selectTime(time) {
    console.log('Time selected:', time);
    
    selectedTime = time;
    
    // Update visual selection
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    document.querySelector(`.time-slot[data-time="${time}"]`).classList.add('selected');
    
    // Clear custom time input
    const customTimeInput = document.getElementById('custom-time');
    if (customTimeInput) {
        customTimeInput.value = '';
    }
    
    // Enable submit button
    const submitBtn = document.getElementById('submit-btn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.add('enabled');
    }
    
    // Haptic feedback (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

function selectCustomTime() {
    const customTimeInput = document.getElementById('custom-time');
    const time = customTimeInput.value;
    
    if (!time) {
        showAlert('Heure Invalide', 'Veuillez saisir une heure valide.', 'warning');
        return;
    }
    
    selectedTime = time;
    
    // Clear other selections
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    
    // Enable submit button
    const submitBtn = document.getElementById('submit-btn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.add('enabled');
    }
    
    // Haptic feedback (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

function submitReservation() {
    console.log('Submitting reservation...');
    
    // Validate all required fields
    if (!selectedType || !selectedValue || !selectedTime) {
        showAlert('Données Incomplètes', 'Veuillez remplir tous les champs requis.', 'warning');
        return;
    }
    
    // For fixed plans, only time is required
    if (pricingData.rateType === 'fixed') {
        if (!selectedTime) {
            showAlert('Heure Requise', 'Veuillez sélectionner une heure de début.', 'warning');
            return;
        }
    } else {
        // For other plans, all fields are required
        if (!selectedType || !selectedValue || !selectedTime) {
            showAlert('Données Incomplètes', 'Veuillez remplir tous les champs requis.', 'warning');
            return;
        }
    }
    
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin btn-icon"></i>Réservation en cours...';
    
    // Prepare reservation data
    const reservationData = {
        charging_point_id: {{ $chargingPoint->id }},
        type: selectedType,
        value: selectedValue,
        time: selectedTime,
        _token: '{{ csrf_token() }}'
    };
    
    console.log('Reservation data:', reservationData);
    
    // Submit reservation
    fetch('{{ route("reservations.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(reservationData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Reservation response:', data);
        
        if (data.success) {
            showAlert('Réservation Confirmée', 'Votre réservation a été créée avec succès !', 'success');
            
            // Redirect to success page or dashboard
            setTimeout(() => {
                window.location.href = '{{ route("dashboard") }}';
            }, 2000);
        } else {
            showAlert('Erreur de Réservation', data.message || 'Une erreur est survenue lors de la création de la réservation.', 'error');
        }
    })
    .catch(error => {
        console.error('Reservation error:', error);
        showAlert('Erreur de Connexion', 'Une erreur est survenue. Veuillez vérifier votre connexion internet et réessayer.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check btn-icon"></i>Réserver la Borne';
    });
}

// Make all functions globally accessible
window.selectType = selectType;
window.selectValue = selectValue;
window.selectCustomValue = selectCustomValue;
window.selectTime = selectTime;
window.selectCustomTime = selectCustomTime;
window.submitReservation = submitReservation;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Mobile reservation page loaded');
    updateStepIndicator();
    
    // Add touch feedback for better mobile experience
    addTouchFeedback();
    
    // Auto-select reservation type based on pricing plan
    autoSelectReservationType();
    
    // Apply plan limits to reservation options
    applyPlanLimits();
    
    // Setup real-time input monitoring
    setupRealTimeInputMonitoring();
});

// Auto-select reservation type based on pricing plan
function autoSelectReservationType() {
    console.log('Auto-selecting reservation type based on plan:', pricingData.rateType);
    
    // Check if this is a fixed pricing plan
    if (pricingData.rateType === 'fixed') {
        console.log('Fixed pricing plan detected - disabling type selection');
        disableTypeSelectionForFixedPlan();
        return;
    }
    
    // First, disable non-allowed types
    updateTypeAvailability();
    
    let autoSelectType = null;
    
    // Determine allowed type based on rate_type
    switch (pricingData.rateType) {
        case 'time':
        case 'minute':
            autoSelectType = 'minute';
            break;
        case 'energy':
        case 'kwh':
            autoSelectType = 'kwh';
            break;
        case 'mixed':
        case 'both':
            // For mixed plans, prefer the one with higher price
            if (pricingData.pricePerKwh > pricingData.pricePerMinute) {
                autoSelectType = 'kwh';
            } else {
                autoSelectType = 'minute';
            }
            break;
        default:
            // Fallback: check which pricing is available
            if (pricingData.pricePerKwh > 0 && pricingData.pricePerMinute > 0) {
                autoSelectType = pricingData.pricePerKwh > pricingData.pricePerMinute ? 'kwh' : 'minute';
            } else if (pricingData.pricePerKwh > 0) {
                autoSelectType = 'kwh';
            } else if (pricingData.pricePerMinute > 0) {
                autoSelectType = 'minute';
            }
    }
    
    if (autoSelectType) {
        console.log('Auto-selecting type:', autoSelectType);
        
        // Simulate click on the appropriate type card
        const typeCard = document.querySelector(`.type-card[data-type="${autoSelectType}"]`);
        if (typeCard && !typeCard.classList.contains('disabled')) {
            // Add visual feedback
            typeCard.style.transform = 'scale(0.95)';
            setTimeout(() => {
                typeCard.style.transform = '';
                selectType(autoSelectType);
            }, 200);
        }
    } else {
        console.log('No auto-selection possible, user must choose manually');
    }
}

// Disable type selection for fixed pricing plans
function disableTypeSelectionForFixedPlan() {
    const typeSection = document.getElementById('type-section');
    const valueSection = document.getElementById('value-section');
    
    if (typeSection) {
        // Hide the entire type selection section
        typeSection.style.display = 'none';
        
        // Add a message explaining fixed pricing
        const fixedPricingMessage = document.createElement('div');
        fixedPricingMessage.className = 'section';
        fixedPricingMessage.innerHTML = `
            <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-radius: 12px; border: 1px solid #bbf7d0;">
                <i class="fas fa-tag" style="font-size: 32px; color: #10b981; margin-bottom: 12px;"></i>
                <h3 style="color: #166534; margin-bottom: 8px;">Prix Fixe</h3>
                <p style="color: #374151; margin-bottom: 0;">Cette borne utilise un tarif fixe. Aucune sélection de type n'est nécessaire.</p>
            </div>
        `;
        
        // Insert the message before the value section
        if (valueSection) {
            valueSection.parentNode.insertBefore(fixedPricingMessage, valueSection);
        }
        
        // Set a default type for fixed plans (this will be handled in the backend)
        selectedType = 'fixed';
        
        // Show value section directly (skip type selection)
        showNextSection('value-section');
        currentStep = 2;
        updateStepIndicator();
        
        // Update cost for fixed pricing
        updateCostForFixedPlan();
    }
}

// Update type availability based on pricing plan
function updateTypeAvailability() {
    const kwhCard = document.querySelector('.type-card[data-type="kwh"]');
    const minuteCard = document.querySelector('.type-card[data-type="minute"]');
    
    // Check which types are allowed based on rate_type and pricing
    const kwhAllowed = (pricingData.rateType === 'energy' || pricingData.rateType === 'kwh' || 
                       pricingData.rateType === 'mixed' || pricingData.rateType === 'both') && 
                       pricingData.pricePerKwh > 0;
    
    const minuteAllowed = (pricingData.rateType === 'time' || pricingData.rateType === 'minute' || 
                          pricingData.rateType === 'mixed' || pricingData.rateType === 'both') && 
                          pricingData.pricePerMinute > 0;
    
    // Update kWh card
    if (kwhCard) {
        if (kwhAllowed) {
            kwhCard.classList.remove('disabled');
            kwhCard.style.opacity = '1';
            kwhCard.style.pointerEvents = 'auto';
        } else {
            kwhCard.classList.add('disabled');
            kwhCard.style.opacity = '0.5';
            kwhCard.style.pointerEvents = 'none';
        }
    }
    
    // Update minute card
    if (minuteCard) {
        if (minuteAllowed) {
            minuteCard.classList.remove('disabled');
            minuteCard.style.opacity = '1';
            minuteCard.style.pointerEvents = 'auto';
        } else {
            minuteCard.classList.add('disabled');
            minuteCard.style.opacity = '0.5';
            minuteCard.style.pointerEvents = 'none';
        }
    }
    
    console.log('Type availability updated:', {
        kwhAllowed,
        minuteAllowed,
        rateType: pricingData.rateType
    });
}

// Apply plan limits to reservation options
function applyPlanLimits() {
    console.log('Applying plan limits:', {
        maxDuration: pricingData.maxDuration,
        rateType: pricingData.rateType,
        chargingPointPower: pricingData.chargingPointPower
    });
    
    // Apply duration limits for minute-based plans
    if (pricingData.rateType === 'minute' || pricingData.rateType === 'time' || pricingData.rateType === 'mixed') {
        applyDurationLimits();
    }
    
    // Apply energy limits for kWh-based plans
    if (pricingData.rateType === 'kwh' || pricingData.rateType === 'energy' || pricingData.rateType === 'mixed') {
        applyEnergyLimits();
    }
    
    // Update limit info display
    updateLimitInfo();
}

// Apply duration limits based on plan max_duration
function applyDurationLimits() {
    if (!pricingData.maxDuration) return;
    
    console.log('Applying duration limits:', pricingData.maxDuration + ' minutes');
    
    // Disable value cards that exceed the limit
    document.querySelectorAll('.value-card[data-value]').forEach(card => {
        const value = parseInt(card.dataset.value);
        if (value > pricingData.maxDuration) {
            card.classList.add('disabled');
            card.style.opacity = '0.5';
            card.style.pointerEvents = 'none';
            card.title = `Durée maximale autorisée: ${pricingData.maxDuration} minutes`;
            
            // Add visual indicator
            const indicator = document.createElement('div');
            indicator.className = 'limit-indicator';
            indicator.innerHTML = '<i class="fas fa-ban"></i>';
            indicator.style.cssText = `
                position: absolute;
                top: 5px;
                right: 5px;
                color: #ef4444;
                font-size: 12px;
                background: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            `;
            card.style.position = 'relative';
            card.appendChild(indicator);
        }
    });
    
    // Update custom input max value
    const customInput = document.getElementById('custom-value');
    if (customInput) {
        customInput.max = pricingData.maxDuration;
        customInput.placeholder = `Max: ${pricingData.maxDuration} min`;
    }
}

// Apply energy limits based on plan max_duration and charging point power
function applyEnergyLimits() {
    if (!pricingData.maxDuration || !pricingData.chargingPointPower) return;
    
    // Calculate max energy based on max duration and power output
    const maxEnergyKwh = pricingData.chargingPointPower * (pricingData.maxDuration / 60);
    
    console.log('Applying energy limits:', {
        maxDuration: pricingData.maxDuration,
        powerOutput: pricingData.chargingPointPower,
        maxEnergy: maxEnergyKwh.toFixed(2) + ' kWh'
    });
    
    // For energy plans, we need to convert the value cards to energy equivalents
    if (pricingData.rateType === 'kwh' || pricingData.rateType === 'energy') {
        document.querySelectorAll('.value-card[data-value]').forEach(card => {
            const durationValue = parseInt(card.dataset.value);
            const energyEquivalent = (durationValue / 60) * pricingData.chargingPointPower;
            
            if (energyEquivalent > maxEnergyKwh) {
                card.classList.add('disabled');
                card.style.opacity = '0.5';
                card.style.pointerEvents = 'none';
                card.title = `Énergie maximale autorisée: ${maxEnergyKwh.toFixed(2)} kWh`;
                
                // Add visual indicator
                const indicator = document.createElement('div');
                indicator.className = 'limit-indicator';
                indicator.innerHTML = '<i class="fas fa-ban"></i>';
                indicator.style.cssText = `
                    position: absolute;
                    top: 5px;
                    right: 5px;
                    color: #ef4444;
                    font-size: 12px;
                    background: white;
                    border-radius: 50%;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                `;
                card.style.position = 'relative';
                card.appendChild(indicator);
            }
        });
        
        // Update custom input max value for energy
        const customInput = document.getElementById('custom-value');
        if (customInput) {
            customInput.max = maxEnergyKwh;
            customInput.placeholder = `Max: ${maxEnergyKwh.toFixed(2)} kWh`;
        }
    }
}

// Update limit info display
function updateLimitInfo() {
    const limitTextElement = document.getElementById('limit-text');
    const limitInfoElement = document.getElementById('limit-info');
    
    if (limitTextElement && limitInfoElement) {
        let limitText = '';
        
        if (pricingData.maxDuration) {
            limitText += `Durée maximale: ${pricingData.maxDuration} minutes`;
        }
        
        if (pricingData.chargingPointPower && pricingData.maxDuration) {
            const maxEnergy = pricingData.chargingPointPower * (pricingData.maxDuration / 60);
            if (limitText) limitText += ' • ';
            limitText += `Énergie maximale: ${maxEnergy.toFixed(2)} kWh`;
        }
        
        if (limitText) {
            limitTextElement.textContent = limitText;
            limitInfoElement.style.display = 'block';
        } else {
            limitInfoElement.style.display = 'none';
        }
    }
}

// Validate reservation value against plan limits
function validateReservationValue(value, type) {
    if (type === 'minute') {
        return validateDurationLimit(value);
    } else if (type === 'kwh') {
        return validateEnergyLimit(value);
    }
    return { valid: true };
}

// Validate duration limit
function validateDurationLimit(durationMinutes) {
    if (pricingData.maxDuration && durationMinutes > pricingData.maxDuration) {
        return {
            valid: false,
            message: `La durée ne peut pas dépasser ${pricingData.maxDuration} minutes selon ce plan tarifaire.`
        };
    }
    return { valid: true };
}

// Validate energy limit
function validateEnergyLimit(energyKwh) {
    if (pricingData.maxDuration && pricingData.chargingPointPower) {
        const maxEnergyKwh = pricingData.chargingPointPower * (pricingData.maxDuration / 60);
        if (energyKwh > maxEnergyKwh) {
            return {
                valid: false,
                message: `La quantité d'énergie ne peut pas dépasser ${maxEnergyKwh.toFixed(2)} kWh selon ce plan tarifaire.`
            };
        }
    }
    return { valid: true };
}

// Add touch feedback to interactive elements
function addTouchFeedback() {
    const interactiveElements = document.querySelectorAll('.type-card, .value-card, .time-slot, .submit-btn');
    
    interactiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        element.addEventListener('touchend', function() {
            this.style.transform = '';
        });
    });
}

// Update step indicator
function updateStepIndicator() {
    for (let i = 1; i <= 3; i++) {
        const step = document.getElementById(`step-${i}`);
        if (i < currentStep) {
            step.className = 'step completed';
        } else if (i === currentStep) {
            step.className = 'step active';
        } else {
            step.className = 'step pending';
        }
    }
}

// Functions are already defined above and made globally accessible

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Mobile reservation page loaded');
    updateStepIndicator();
    
    // Add touch feedback for better mobile experience
    addTouchFeedback();
    
    // Auto-select reservation type based on pricing plan
    autoSelectReservationType();
    
    // Apply plan limits to reservation options
    applyPlanLimits();
    
    // Setup real-time input monitoring
    setupRealTimeInputMonitoring();
});

// All functions are already defined above
    
    // Real-time validation and cost update
    if (value && value > 0) {
        // Validate against plan limits
        const validation = validateReservationValue(value, selectedType);
        if (!validation.valid) {
            showAlert('Limite Dépassée', validation.message, 'error');
            document.getElementById('custom-value').value = '';
            selectedValue = null;
            updateCost(); // Reset cost display
            return;
        }
        
        console.log('Custom value selected:', value);
        selectedValue = value;
        
        // Clear other selections
        document.querySelectorAll('.value-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Real-time cost update
        updateCost();
        
        // Show next section
        showNextSection('time-section');
        currentStep = 3;
        updateStepIndicator();
        
        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    } else if (value === 0 || !value) {
        // Reset if value is cleared
        selectedValue = null;
        updateCost();
    }
}

// Real-time input monitoring for custom value
function setupRealTimeInputMonitoring() {
    const customInput = document.getElementById('custom-value');
    if (customInput) {
        // Monitor input changes in real-time
        customInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            
            if (value && value > 0) {
                // Real-time validation
                const validation = validateReservationValue(value, selectedType);
                
                if (!validation.valid) {
                    // Show warning but don't clear input
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                } else {
                    // Valid input
                    this.style.borderColor = '#10b981';
                    this.style.backgroundColor = '#f0fdf4';
                    
                    // Update cost in real-time
                    selectedValue = value;
                    updateCost();
                }
            } else {
                // Reset styling and cost
                this.style.borderColor = '';
                this.style.backgroundColor = '';
                selectedValue = null;
                updateCost();
            }
        });
        
        // Monitor focus events
        customInput.addEventListener('focus', function() {
            this.style.borderColor = '#3b82f6';
            this.style.backgroundColor = '#eff6ff';
        });
        
        customInput.addEventListener('blur', function() {
            if (this.style.borderColor !== '#ef4444') {
                this.style.borderColor = '';
                this.style.backgroundColor = '';
            }
        });
    }
}

// Time selection with mobile-optimized UX
function selectTime(time) {
    console.log('Time selected:', time);
    selectedTime = time;
    
    // Update visual selection
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    document.querySelector(`.time-slot[data-time="${time}"]`).classList.add('selected');
    
    // Clear custom time
    document.getElementById('custom-time').value = '';
    
    // Enable submit button
    enableSubmitButton();
    
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

// Custom time selection
function selectCustomTime() {
    const time = document.getElementById('custom-time').value;
    if (time) {
        console.log('Custom time selected:', time);
        selectedTime = time;
        
        // Clear other selections
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('selected');
        });
        
        // Enable submit button
        enableSubmitButton();
        
        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    }
}

// Show next section with smooth animation
function showNextSection(sectionId) {
    const section = document.getElementById(sectionId);
    section.style.display = 'block';
    section.style.opacity = '0';
    section.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        section.style.transition = 'all 0.3s ease';
        section.style.opacity = '1';
        section.style.transform = 'translateY(0)';
    }, 100);
}

// Show cost section
function showCostSection() {
    const costSection = document.getElementById('cost-section');
    costSection.style.display = 'block';
    costSection.style.opacity = '0';
    costSection.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        costSection.style.transition = 'all 0.3s ease';
        costSection.style.opacity = '1';
        costSection.style.transform = 'translateY(0)';
    }, 100);
}

// Update cost calculation with real-time estimation
function updateCost() {
    console.log('Updating cost calculation...', {
        selectedType,
        selectedValue,
        rateType: pricingData.rateType
    });
    
    if (!selectedType || !selectedValue) {
        resetCostDisplay();
        return;
    }
    
    let baseCost = 0;
    let calculationDetails = '';
    
    // Calculate base cost based on plan type and selected values
    if (pricingData.rateType === 'fixed') {
        // Fixed pricing plan
        baseCost = pricingData.fixedPrice;
        calculationDetails = `Prix fixe: ${baseCost.toFixed(2)} ${pricingData.currency}`;
    } else if (selectedType === 'kwh') {
        // Energy-based calculation
        baseCost = selectedValue * pricingData.pricePerKwh;
        calculationDetails = `Énergie (${selectedValue} kWh × ${pricingData.pricePerKwh.toFixed(2)} ${pricingData.currency}/kWh)`;
    } else if (selectedType === 'minute') {
        // Time-based calculation
        baseCost = selectedValue * pricingData.pricePerMinute;
        calculationDetails = `Durée (${selectedValue} min × ${pricingData.pricePerMinute.toFixed(2)} ${pricingData.currency}/min)`;
    }
    
    // Calculate additional costs
    const subtotal = baseCost + pricingData.activationFee;
    const vat = subtotal * (pricingData.vatRate / 100);
    const total = subtotal + vat;
    
    // Update display with animation
    updateCostDisplayWithAnimation(calculationDetails, baseCost, subtotal, vat, total);
    
    // Show cost section if not already visible
    showCostSection();
}

// Reset cost display
function resetCostDisplay() {
    document.getElementById('base-cost').textContent = `0.00 ${pricingData.currency}`;
    document.getElementById('vat-cost').textContent = `0.00 ${pricingData.currency}`;
    document.getElementById('total-cost').textContent = `0.00 ${pricingData.currency}`;
    
    // Hide cost section
    const costSection = document.getElementById('cost-section');
    if (costSection) {
        costSection.style.display = 'none';
    }
}

// Update cost display with smooth animation
function updateCostDisplayWithAnimation(calculationDetails, baseCost, subtotal, vat, total) {
    // Show calculation details for non-fixed plans
    const calculationDetailsElement = document.getElementById('calculation-details');
    if (calculationDetailsElement) {
        if (pricingData.rateType === 'fixed') {
            calculationDetailsElement.style.display = 'none';
        } else {
            calculationDetailsElement.style.display = 'flex';
            const calculationLabel = document.getElementById('calculation-label');
            const calculationAmount = document.getElementById('calculation-amount');
            
            if (calculationLabel) {
                calculationLabel.textContent = calculationDetails + ':';
            }
            if (calculationAmount) {
                animateValueUpdate('calculation-amount', baseCost);
            }
        }
    }
    
    // Animate cost updates
    animateValueUpdate('base-cost', baseCost);
    animateValueUpdate('vat-cost', vat);
    animateValueUpdate('total-cost', total);
    
    // Update subtotal (activation fee + base cost)
    const subtotalElement = document.getElementById('subtotal-ht');
    if (subtotalElement) {
        animateValueUpdate('subtotal-ht', subtotal);
    }
    
    // Add visual feedback for real-time updates
    addRealTimeUpdateFeedback();
}

// Add visual feedback for real-time updates
function addRealTimeUpdateFeedback() {
    const costSection = document.getElementById('cost-section');
    if (costSection) {
        // Add a subtle pulse animation
        costSection.style.animation = 'pulse 0.5s ease-in-out';
        
        setTimeout(() => {
            costSection.style.animation = '';
        }, 500);
    }
}

// Animate value updates with smooth transitions
function animateValueUpdate(elementId, targetValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const currentValue = parseFloat(element.textContent.replace(/[^\d.-]/g, '')) || 0;
    const currency = pricingData.currency;
    const duration = 300; // Animation duration in ms
    const steps = 20; // Number of animation steps
    const stepDuration = duration / steps;
    const valueStep = (targetValue - currentValue) / steps;
    
    let currentStep = 0;
    
    const animate = () => {
        currentStep++;
        const animatedValue = currentValue + (valueStep * currentStep);
        element.textContent = `${animatedValue.toFixed(2)} ${currency}`;
        
        if (currentStep < steps) {
            setTimeout(animate, stepDuration);
        } else {
            // Ensure final value is exact
            element.textContent = `${targetValue.toFixed(2)} ${currency}`;
        }
    };
    
    animate();
}

// Update cost for fixed pricing plans
function updateCostForFixedPlan() {
    // For fixed plans, the cost is just the fixed price + activation fee
    const fixedPrice = pricingData.fixedPrice;
    const subtotal = fixedPrice + pricingData.activationFee;
    const vat = subtotal * (pricingData.vatRate / 100);
    const total = subtotal + vat;
    
    document.getElementById('base-cost').textContent = `${fixedPrice.toFixed(2)} ${pricingData.currency}`;
    document.getElementById('vat-cost').textContent = `${vat.toFixed(2)} ${pricingData.currency}`;
    document.getElementById('total-cost').textContent = `${total.toFixed(2)} ${pricingData.currency}`;
    
    // Show cost section
    showCostSection();
}

// Enable submit button
function enableSubmitButton() {
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = false;
    submitBtn.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
}

// Show alert with mobile-optimized design
function showAlert(title, message, type = 'error') {
    const overlay = document.getElementById('alert-overlay');
    const icon = document.getElementById('alert-icon');
    const titleEl = document.getElementById('alert-title');
    const messageEl = document.getElementById('alert-message');
    
    // Configure icon and colors
    const iconClasses = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-triangle',
        'warning': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle'
    };
    
    icon.className = `alert-icon ${iconClasses[type] || iconClasses['error']}`;
    icon.style.color = type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#ef4444';
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate(100);
    }
}

// Hide alert
function hideAlert() {
    const overlay = document.getElementById('alert-overlay');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

// Submit reservation with mobile-optimized UX
async function submitReservation() {
    const submitBtn = document.getElementById('submit-btn');
    
    // For fixed plans, we don't need selectedValue
    if (pricingData.rateType === 'fixed') {
        if (!selectedTime) {
            showAlert('Sélection Incomplète', 'Veuillez sélectionner un créneau horaire', 'warning');
            return;
        }
    } else {
        if (!selectedType || !selectedValue || !selectedTime) {
            showAlert('Sélection Incomplète', 'Veuillez remplir tous les champs requis', 'warning');
            return;
        }
    }
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin btn-icon"></i>Création de la réservation...';
    
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate(200);
    }
    
    try {
        const reservationData = {
            charging_point_id: {{ $chargingPoint->id }},
            pricing_plan_id: {{ $pricingPlan->id }},
            reservation_type: selectedType,
            reservation_value: pricingData.rateType === 'fixed' ? null : selectedValue,
            start_time: selectedTime,
            payment_type: 'offline',
            guest_email: null,
            guest_phone: null
        };
        
        const response = await fetch('{{ route("reservations.store", $chargingPoint->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(reservationData)
        });
        
        const data = await response.json();
        
        if (data.success === true || data.reservation) {
            showAlert('Réservation Confirmée!', 'Votre réservation a été créée avec succès.', 'success');
            
            // Success haptic feedback
            if (navigator.vibrate) {
                navigator.vibrate([100, 50, 100]);
            }
            
            setTimeout(() => {
                const redirectUrl = data.redirect_url || '/reservations/' + (data.reservation?.id || data.reservation_id) + '/thank-you';
                window.location.href = redirectUrl;
            }, 2000);
        } else {
            showAlert('Erreur de Réservation', data.message || 'Erreur lors de la création de la réservation.', 'error');
        }
    } catch (error) {
        console.error('Reservation error:', error);
        showAlert('Erreur de Connexion', 'Une erreur est survenue. Veuillez vérifier votre connexion internet et réessayer.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check btn-icon"></i>Réserver la Borne';
    }
}

// Handle back button for mobile navigation
window.addEventListener('popstate', function(event) {
    if (currentStep > 1) {
        currentStep--;
        updateStepIndicator();
        
        if (currentStep === 1) {
            document.getElementById('value-section').style.display = 'none';
            document.getElementById('time-section').style.display = 'none';
            document.getElementById('cost-section').style.display = 'none';
        } else if (currentStep === 2) {
            document.getElementById('time-section').style.display = 'none';
            document.getElementById('cost-section').style.display = 'none';
        }
    }
});

// Prevent zoom on double tap for better mobile experience
document.addEventListener('touchstart', function(event) {
    if (event.touches.length > 1) {
        event.preventDefault();
    }
});

let lastTouchEnd = 0;
document.addEventListener('touchend', function(event) {
    const now = (new Date()).getTime();
    if (now - lastTouchEnd <= 300) {
        event.preventDefault();
    }
    lastTouchEnd = now;
}, false);
</script>
@endsection
