@extends('layouts.public')

@section('title', 'Réservation de la borne')

@push('styles')
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reservation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .content {
            padding: 30px;
        }
        
        .option-group {
            margin-bottom: 30px;
        }
        
        .option-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #374151;
        }
        
        .option-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .option-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .option-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .option-card.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .option-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .option-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .option-text {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .option-desc {
            font-size: 14px;
            color: #6b7280;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .input-field {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
        }
        
        .time-slot {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .time-slot:hover {
            border-color: #3b82f6;
            background-color: #f8fafc;
        }
        
        .time-slot.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        .time-slot.unavailable {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #fef2f2;
            border-color: #fecaca;
        }
        
        .cost-display {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .cost-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .cost-total {
            font-size: 20px;
            font-weight: 700;
            color: #166534;
            border-top: 2px solid #bbf7d0;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .submit-btn {
            width: 100%;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover:not(:disabled) {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .submit-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #fef2f2;
            border-bottom: 1px solid #fecaca;
            color: #dc2626;
            padding: 15px;
            text-align: center;
            z-index: 1000;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }
        
        .alert.show {
            transform: translateY(0);
        }
        
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
        }
        
        @media (max-width: 640px) {
            .reservation-container {
                padding: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .option-grid {
                grid-template-columns: 1fr;
            }
            
            .time-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
@endpush

@section('content')
<div class="reservation-container">
    <!-- Header -->
    <div class="card">
        <div class="header">
            <h1>{{ $chargingPoint->name ?? 'Borne de Recharge' }}</h1>
            <p>{{ $chargingPoint->address ?? 'Adresse non disponible' }}</p>
        </div>
    </div>

    <!-- Reservation Form -->
    <div class="card">
        <div class="content">
            <h2 style="text-align: center; margin-bottom: 30px;">
                <i class="fas fa-charging-station" style="color: #3b82f6; margin-right: 10px;"></i>
                Réserver la Borne
            </h2>

            <!-- Type Selection -->
            <div class="option-group">
                <div class="option-title">Type de Réservation</div>
                <div class="option-grid">
                    <div class="option-card" data-type="kwh" onclick="selectType('kwh')">
                        <div class="option-icon" style="color: #3b82f6;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="option-text">Par Énergie</div>
                        <div class="option-desc">kWh</div>
                    </div>
                    <div class="option-card" data-type="minute" onclick="selectType('minute')">
                        <div class="option-icon" style="color: #10b981;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="option-text">Par Durée</div>
                        <div class="option-desc">Minutes</div>
                    </div>
                </div>
            </div>

            <!-- Value Selection -->
            <div class="option-group">
                <div class="option-title">Quantité ou Durée</div>
                <div class="option-grid">
                    <div class="option-card" data-value="10" onclick="selectValue(10)">
                        <div class="option-text">10</div>
                        <div class="option-desc" id="unit-10">min</div>
                    </div>
                    <div class="option-card" data-value="20" onclick="selectValue(20)">
                        <div class="option-text">20</div>
                        <div class="option-desc" id="unit-20">min</div>
                    </div>
                    <div class="option-card" data-value="30" onclick="selectValue(30)">
                        <div class="option-text">30</div>
                        <div class="option-desc" id="unit-30">min</div>
                    </div>
                    <div class="option-card" data-value="60" onclick="selectValue(60)">
                        <div class="option-text">60</div>
                        <div class="option-desc" id="unit-60">min</div>
                    </div>
                </div>
                
                <!-- Custom Input -->
                <div class="input-group">
                    <label class="input-label">Ou saisissez une valeur personnalisée</label>
                    <input type="number" id="custom-value" class="input-field" placeholder="Entrez une valeur" onchange="selectCustomValue()">
                    <small style="color: #6b7280; font-size: 12px;">Valeur personnalisée</small>
                </div>
            </div>

            <!-- Time Selection -->
            <div class="option-group">
                <div class="option-title">Heure de Début</div>
                <div class="time-grid">
                    @for($hour = 6; $hour <= 22; $hour++)
                    <div class="time-slot" data-time="{{ sprintf('%02d:00', $hour) }}" onclick="selectTime('{{ sprintf('%02d:00', $hour) }}')">
                        {{ sprintf('%02d:00', $hour) }}
                    </div>
                    @endfor
                </div>
                
                <!-- Custom Time Input -->
                <div class="input-group">
                    <label class="input-label">Ou saisissez une heure personnalisée</label>
                    <input type="time" id="custom-time" class="input-field" min="06:00" max="22:00" onchange="selectCustomTime()">
                </div>
            </div>

            <!-- Cost Display -->
            <div class="cost-display">
                <h3 style="margin-bottom: 15px; color: #166534;">
                    <i class="fas fa-calculator" style="margin-right: 8px;"></i>
                    Coût Estimé
                </h3>
                <div class="cost-row">
                    <span>Tarif de base:</span>
                    <span id="base-cost">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="cost-row">
                    <span>Frais d'activation:</span>
                    <span>{{ number_format($pricingPlan->activation_fee ?? 0, 2) }} {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="cost-row">
                    <span>TVA ({{ number_format($pricingPlan->vatRate->rate ?? 0, 1) }}%):</span>
                    <span id="vat-cost">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
                <div class="cost-row cost-total">
                    <span>Total:</span>
                    <span id="total-cost">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
            </div>

            <!-- Payment Method Selection -->
            <div class="option-group">
                <div class="option-title">Méthode de Paiement</div>
                <div class="option-grid">
                    <div class="option-card" data-payment="offline" onclick="selectPaymentMethod('offline')">
                        <div class="option-icon" style="color: #6b7280;">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="option-text">Paiement sur Place</div>
                        <div class="option-desc">Espèces ou carte</div>
                    </div>
                    <div class="option-card" data-payment="cmi" onclick="selectPaymentMethod('cmi')">
                        <div class="option-icon" style="color: #3b82f6;">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="option-text">CMI</div>
                        <div class="option-desc">Carte bancaire (Maroc)</div>
                    </div>
                    <div class="option-card" data-payment="stripe" onclick="selectPaymentMethod('stripe')">
                        <div class="option-icon" style="color: #10b981;">
                            <i class="fab fa-stripe"></i>
                        </div>
                        <div class="option-text">Stripe</div>
                        <div class="option-desc">Paiement international</div>
                    </div>
                </div>
            </div>

            <!-- Guest Information (for online payments) -->
            <div id="guest-info" class="option-group" style="display: none;">
                <div class="option-title">Informations de Contact</div>
                <div class="input-group">
                    <label for="guest-email">Email *</label>
                    <input type="email" id="guest-email" placeholder="votre@email.com" required>
                </div>
                <div class="input-group">
                    <label for="guest-phone">Téléphone</label>
                    <input type="tel" id="guest-phone" placeholder="+212 6XX XXX XXX">
                </div>
            </div>

            <!-- Submit Button -->
            <button id="submit-btn" class="submit-btn" onclick="submitReservation()" disabled>
                <i class="fas fa-check" style="margin-right: 8px;"></i>
                Réserver la Borne
            </button>
        </div>
    </div>
</div>

<!-- Alert -->
<div id="alert" class="alert"></div>

<script>
// Global variables
let selectedType = null;
let selectedValue = null;
let selectedTime = null;
let selectedPaymentMethod = null;

// Pricing data
const pricingData = {
    activationFee: {{ $pricingPlan->activation_fee ?? 0 }},
    pricePerKwh: {{ $pricingPlan->price_per_kwh ?? 0 }},
    pricePerMinute: {{ $pricingPlan->price_per_minute ?? 0 }},
    vatRate: {{ $pricingPlan->vatRate->rate ?? 0 }},
    currency: '{{ $currency ?? 'EUR' }}'
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Reservation page loaded');
    updateCost();
    
    // Add event listeners for guest information validation
    document.getElementById('guest-email').addEventListener('input', checkSubmitButton);
    document.getElementById('guest-phone').addEventListener('input', checkSubmitButton);
});

// Type selection
function selectType(type) {
    console.log('Type selected:', type);
    selectedType = type;
    
    // Update visual selection
    document.querySelectorAll('.option-card[data-type]').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`.option-card[data-type="${type}"]`).classList.add('selected');
    
    // Update units
    const unit = type === 'kwh' ? 'kWh' : 'min';
    document.querySelectorAll('[id^="unit-"]').forEach(el => {
        el.textContent = unit;
    });
    
    // Clear value selection
    selectedValue = null;
    document.querySelectorAll('.option-card[data-value]').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById('custom-value').value = '';
    
    updateCost();
    checkSubmitButton();
}

// Value selection
function selectValue(value) {
    console.log('Value selected:', value);
    selectedValue = value;
    
    // Update visual selection
    document.querySelectorAll('.option-card[data-value]').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`.option-card[data-value="${value}"]`).classList.add('selected');
    
    // Clear custom input
    document.getElementById('custom-value').value = '';
    
    updateCost();
    checkSubmitButton();
}

// Payment method selection
function selectPaymentMethod(method) {
    console.log('Payment method selected:', method);
    selectedPaymentMethod = method;
    
    // Update visual selection
    document.querySelectorAll('.option-card[data-payment]').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`.option-card[data-payment="${method}"]`).classList.add('selected');
    
    // Show/hide guest information for online payments
    const guestInfo = document.getElementById('guest-info');
    if (method === 'cmi' || method === 'stripe') {
        guestInfo.style.display = 'block';
    } else {
        guestInfo.style.display = 'none';
    }
    
    checkSubmitButton();
}

// Custom value selection
function selectCustomValue() {
    const value = parseFloat(document.getElementById('custom-value').value);
    if (value && value > 0) {
        console.log('Custom value selected:', value);
        selectedValue = value;
        
        // Clear other selections
        document.querySelectorAll('.option-card[data-value]').forEach(card => {
            card.classList.remove('selected');
        });
        
        updateCost();
        checkSubmitButton();
    }
}

// Time selection
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
    
    checkSubmitButton();
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
        
        checkSubmitButton();
    }
}

// Update cost calculation
function updateCost() {
    if (!selectedType || !selectedValue) {
        document.getElementById('base-cost').textContent = `0.00 ${pricingData.currency}`;
        document.getElementById('vat-cost').textContent = `0.00 ${pricingData.currency}`;
        document.getElementById('total-cost').textContent = `0.00 ${pricingData.currency}`;
        return;
    }
    
    let baseCost = 0;
    
    if (selectedType === 'kwh') {
        baseCost = selectedValue * pricingData.pricePerKwh;
    } else if (selectedType === 'minute') {
        baseCost = selectedValue * pricingData.pricePerMinute;
    }
    
    const subtotal = baseCost + pricingData.activationFee;
    const vat = subtotal * (pricingData.vatRate / 100);
    const total = subtotal + vat;
    
    document.getElementById('base-cost').textContent = `${baseCost.toFixed(2)} ${pricingData.currency}`;
    document.getElementById('vat-cost').textContent = `${vat.toFixed(2)} ${pricingData.currency}`;
    document.getElementById('total-cost').textContent = `${total.toFixed(2)} ${pricingData.currency}`;
}

// Check if submit button should be enabled
function checkSubmitButton() {
    const submitBtn = document.getElementById('submit-btn');
    const isPaymentMethodSelected = selectedPaymentMethod !== null;
    const isGuestInfoValid = selectedPaymentMethod === 'offline' || 
        (selectedPaymentMethod && document.getElementById('guest-email').value.trim() !== '');
    
    if (selectedType && selectedValue && selectedTime && isPaymentMethodSelected && isGuestInfoValid) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

// Show alert
function showAlert(message, type = 'error') {
    const alert = document.getElementById('alert');
    alert.textContent = message;
    alert.className = `alert ${type === 'error' ? 'error' : 'success'}`;
    alert.classList.add('show');
    
    setTimeout(() => {
        alert.classList.remove('show');
    }, 5000);
}

// Submit reservation
async function submitReservation() {
    const submitBtn = document.getElementById('submit-btn');
    
    if (!selectedType || !selectedValue || !selectedTime || !selectedPaymentMethod) {
        showAlert('Veuillez remplir tous les champs requis');
        return;
    }
    
    // Validate guest information for online payments
    if (selectedPaymentMethod === 'cmi' || selectedPaymentMethod === 'stripe') {
        const guestEmail = document.getElementById('guest-email').value.trim();
        if (!guestEmail) {
            showAlert('L\'email est requis pour les paiements en ligne');
            return;
        }
    }
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Création de la réservation...';
    
    try {
        const reservationData = {
            charging_point_id: {{ $chargingPoint->id }},
            pricing_plan_id: {{ $pricingPlan->id }},
            reservation_type: selectedType,
            reservation_value: selectedValue,
            start_time: selectedTime,
            payment_type: selectedPaymentMethod,
            guest_email: selectedPaymentMethod === 'offline' ? null : document.getElementById('guest-email').value.trim(),
            guest_phone: selectedPaymentMethod === 'offline' ? null : document.getElementById('guest-phone').value.trim()
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
        
        if (data.success === true) {
            // Handle online payment redirection
            if (data.payment_url) {
                showAlert('Redirection vers le paiement...', 'success');
                setTimeout(() => {
                    window.location.href = data.payment_url;
                }, 1500);
            } else if (data.reservation) {
                // Handle offline payment success
                showAlert('Réservation créée avec succès !', 'success');
                setTimeout(() => {
                    const redirectUrl = data.redirect_url || '/reservations/' + (data.reservation?.id || data.reservation_id) + '/thank-you';
                    window.location.href = redirectUrl;
                }, 2000);
            } else {
                showAlert(data.message || 'Réservation créée avec succès !', 'success');
            }
        } else {
            showAlert(data.message || 'Erreur lors de la création de la réservation');
        }
    } catch (error) {
        console.error('Reservation error:', error);
        showAlert('Erreur de connexion. Veuillez réessayer.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check" style="margin-right: 8px;"></i>Réserver la Borne';
    }
}
</script>
@endsection
