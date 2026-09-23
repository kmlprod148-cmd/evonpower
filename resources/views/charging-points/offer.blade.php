<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Evon Power - Détails de la borne</title>
    {{-- Tailwind deja inclus dans app.css --}}
    <link rel="stylesheet" href="{{ asset("vendor/fontawesome/css/all.min.css") }}">
    <style>
        body {
            /* set the entire page background to white */
            background-color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .header-gradient {
            background: linear-gradient(to right, #34d399, #10b981);
        }

        .qr-code-container {
            background-color: white;
            padding: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pricing-option {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .pricing-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .pricing-option.selected {
            border-color: #10b981;
            background-color: #f0fdf4;
        }

        .carousel-container {
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .carousel-container::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body>
    <div class="max-w-lg mx-auto p-4 min-h-screen flex flex-col">
        <!-- Header -->
        <header class="header-gradient text-white p-4 rounded-t-lg flex items-center justify-between mb-4">
            <div class="flex-1">
                <h1 class="text-xl font-bold" id="station-name">{{ $chargingPoint->name }}</h1>
                <p class="flex items-center text-sm mt-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span id="station-address">{{ $chargingPoint->address }}</span>
                </p>
            </div>
            <div class="qr-code-container">
                <img id="qr-code" src="{{ $qrCode }}" alt="QR Code de la borne" width="80" height="80">
            </div>
        </header>

        <!-- Main card -->
        <div class="card mb-4 flex-grow">
            <!-- Charging station image -->
            <div class="flex justify-center py-6 bg-white">
                <img id="charging-point-image" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=ChargingStationExample&bgcolor=EDF2F7" alt="Image de la borne" class="h-28">
            </div>

            <!-- Charging station details -->
            <div class="p-4 border-b border-gray-100">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white border border-gray-200 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Puissance en kW</p>
                        <p class="text-lg font-semibold" id="power-output">{{ $chargingPoint->power_output ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 rounded-lg">
                        <p class="text-xs text-gray-500">Partenaire</p>
                        <p class="text-lg font-semibold" id="partner-name">{{ $chargingPoint->partner->name ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-white border border-gray-200 p-3 rounded-lg col-span-2">
                        <p class="text-xs text-gray-500">Plan tarifaire actuel</p>
                        <p class="text-lg font-semibold text-green-600" id="pricing-plan-name">{{ $pricingPlan->name ?? 'Plan Standard' }}</p>
                    </div>
                </div>
            </div>

            <!-- Plan tarifaire details -->
            <div class="p-4 border-b border-gray-100 bg-green-50">
                <h3 class="text-md font-semibold text-gray-900 mb-3">Détails du Plan Tarifaire</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    @if($pricingPlan->price_per_kwh > 0)
                    <div class="bg-white p-2 rounded">
                        <p class="text-gray-600">Prix par kWh</p>
                        <p class="font-semibold text-green-600">{{ number_format($pricingPlan->price_per_kwh, 2) }} {{ $pricingPlan->currency }}/kWh</p>
                    </div>
                    @endif
                    @if($pricingPlan->price_per_minute > 0)
                    <div class="bg-white p-2 rounded">
                        <p class="text-gray-600">Prix par minute</p>
                        <p class="font-semibold text-green-600">{{ number_format($pricingPlan->price_per_minute, 2) }} {{ $pricingPlan->currency }}/min</p>
                    </div>
                    @endif
                    @if($pricingPlan->activation_fee > 0)
                    <div class="bg-white p-2 rounded">
                        <p class="text-gray-600">Frais d'activation</p>
                        <p class="font-semibold text-green-600">{{ number_format($pricingPlan->activation_fee, 2) }} {{ $pricingPlan->currency }}</p>
                    </div>
                    @endif
                    <div class="bg-white p-2 rounded">
                        <p class="text-gray-600">Type de tarification</p>
                        <p class="font-semibold text-green-600">{{ ucfirst($pricingPlan->rate_type) }}</p>
                    </div>
                </div>
            </div>

            <!-- Options de tarification -->
            <div class="p-4">
                @if($carouselType === 'minute' || $carouselType === 'both')
                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-4">Durée de recharge (minutes)</h2>
                    <div class="carousel-container">
                        <div class="flex space-x-3 pb-2">
                            @foreach($pricingOptions['minute'] ?? [] as $option)
                            <div class="pricing-option flex-shrink-0 bg-white border-2 border-gray-200 rounded-lg p-4 text-center min-w-[120px]" 
                                 data-type="minute" 
                                 data-value="{{ $option['value'] }}" 
                                 data-price="{{ $option['price'] }}">
                                <div class="text-lg font-bold text-gray-900">{{ $option['value'] }}</div>
                                <div class="text-sm text-gray-600">{{ $option['unit'] }}</div>
                                <div class="text-lg font-bold text-green-600 mt-2">{{ $option['formatted_price'] }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                @if($carouselType === 'kwh' || $carouselType === 'both')
                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-4">Énergie à charger (kWh)</h2>
                    <div class="carousel-container">
                        <div class="flex space-x-3 pb-2">
                            @foreach($pricingOptions['kwh'] ?? [] as $option)
                            <div class="pricing-option flex-shrink-0 bg-white border-2 border-gray-200 rounded-lg p-4 text-center min-w-[120px]" 
                                 data-type="kwh" 
                                 data-value="{{ $option['value'] }}" 
                                 data-price="{{ $option['price'] }}">
                                <div class="text-lg font-bold text-gray-900">{{ $option['value'] }}</div>
                                <div class="text-sm text-gray-600">{{ $option['unit'] }}</div>
                                <div class="text-lg font-bold text-green-600 mt-2">{{ $option['formatted_price'] }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Connecteurs supprimés -->

            <!-- Résumé du prix -->
            <div class="border-t border-gray-100 p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-lg">Prix total:</span>
                    <span class="text-xl font-bold text-green-600" id="total-price">0.00 {{ $currency ?? 'EUR' }}</span>
                </div>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span>Frais d'activation:</span>
                        <span id="activation-fee">{{ number_format($pricingPlan->activation_fee ?? 0, 2) }}</span> {{ $currency ?? 'EUR' }}
                    </div>
                    <div class="flex justify-between">
                        <span id="rate-label">Tarif:</span>
                        <span id="rate-amount">0.00</span> {{ $currency ?? 'EUR' }}
                    </div>
                    <div class="flex justify-between border-t pt-2 font-semibold">
                        <span>Total:</span>
                        <span id="final-total">0.00</span> {{ $currency ?? 'EUR' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Bouton de démarrage -->
        <button id="start-charging-btn" 
                class="w-full bg-green-600 text-white py-4 px-6 rounded-lg font-semibold text-lg hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                disabled>
            Démarrer la recharge
        </button>

        <!-- Formulaire de réservation -->
        <div class="card mb-4">
            <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Créer une réservation</h3>
                
                <form id="reservation-form" class="space-y-4">
                    @csrf
                    <input type="hidden" name="charging_point_id" value="{{ $chargingPoint->id }}">
                    <input type="hidden" name="pricing_plan_id" value="{{ $pricingPlan->id }}">
                    <input type="hidden" name="payment_type" value="offline">
                    
                    <div>
                        <label for="reservation_type" class="block text-sm font-medium text-gray-700 mb-2">Type de réservation</label>
                        <select name="reservation_type" id="reservation_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="kwh">Par Énergie (kWh)</option>
                            <option value="minute">Par Durée (Minutes)</option>
                        </select>
                    </div>

                    <div>
                        <label for="reservation_value" class="block text-sm font-medium text-gray-700 mb-2">Valeur</label>
                        <input type="number" name="reservation_value" id="reservation_value" min="1" step="0.01" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="Entrez la valeur" required>
                    </div>

                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Heure de début (optionnel)</label>
                        <input type="datetime-local" name="start_time" id="start_time" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="guest_email" class="block text-sm font-medium text-gray-700 mb-2">Email (optionnel)</label>
                        <input type="email" name="guest_email" id="guest_email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="votre@email.com">
                    </div>

                    <div>
                        <label for="guest_phone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone (optionnel)</label>
                        <input type="tel" name="guest_phone" id="guest_phone" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="+212 6XX XXX XXX">
                    </div>

                    <button type="submit" id="reservation-submit-btn"
                            class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
                        <span id="reservation-btn-text">Créer la réservation</span>
                        <span id="reservation-btn-loading" class="hidden">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Création en cours...
                        </span>
                    </button>
                </form>

                <div id="reservation-result" class="mt-4"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedOption = null;
            const startButton = document.getElementById('start-charging-btn');
            const totalPriceElement = document.getElementById('total-price');
            const rateLabelElement = document.getElementById('rate-label');
            const rateAmountElement = document.getElementById('rate-amount');
            const finalTotalElement = document.getElementById('final-total');
            const activationFee = {{ $pricingPlan->activation_fee ?? 0 }};
            const currency = '{{ $currency ?? 'EUR' }}';

            // Gestion des options de prix
            document.querySelectorAll('.pricing-option').forEach(option => {
                option.addEventListener('click', function() {
                    // Retirer la sélection précédente
                    document.querySelectorAll('.pricing-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Sélectionner l'option actuelle
                    this.classList.add('selected');
                    
                    // Mettre à jour les données sélectionnées
                    selectedOption = {
                        type: this.dataset.type,
                        value: parseInt(this.dataset.value),
                        price: parseFloat(this.dataset.price)
                    };
                    
                    // Mettre à jour l'affichage
                    updatePriceDisplay();
                    
                    // Activer le bouton
                    startButton.disabled = false;
                });
            });

            function updatePriceDisplay() {
                if (!selectedOption) return;
                
                const rateAmount = selectedOption.price - activationFee;
                const finalTotal = selectedOption.price;
                
                // Mettre à jour les labels selon le type
                if (selectedOption.type === 'minute') {
                    rateLabelElement.textContent = `Tarif temps (${selectedOption.value} min):`;
                } else {
                    rateLabelElement.textContent = `Tarif énergie (${selectedOption.value} kWh):`;
                }
                
                // Mettre à jour les montants
                totalPriceElement.textContent = `${finalTotal.toFixed(2)} ${currency}`;
                rateAmountElement.textContent = rateAmount.toFixed(2);
                finalTotalElement.textContent = finalTotal.toFixed(2);
            }

            // Gestion du bouton de démarrage
            startButton.addEventListener('click', function() {
                if (!selectedOption) {
                    alert('Veuillez d\'abord sélectionner une option de tarification.');
                    return;
                }
                
                // Préparer les données pour la réservation automatique
                const reservationData = {
                    charging_point_id: {{ $chargingPoint->id }},
                    pricing_plan_id: {{ $pricingPlan->id }},
                    reservation_type: selectedOption.type,
                    reservation_value: selectedOption.value,
                    payment_type: 'offline',
                    _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                };
                
                // Afficher un message de confirmation
                if (confirm(`Voulez-vous démarrer la recharge avec ${selectedOption.value} ${selectedOption.type === 'minute' ? 'minutes' : 'kWh'} pour ${selectedOption.price.toFixed(2)} {{ $currency ?? 'EUR' }}?`)) {
                    // Créer la réservation automatiquement
                    fetch('{{ route("reservations.store", $chargingPoint->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(reservationData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success || data.reservation) {
                            alert('Recharge démarrée avec succès!');
                            // Rediriger vers la page de suivi de la recharge
                            const redirectUrl = data.redirect_url || '/reservations/' + (data.reservation?.id || data.reservation_id);
                            window.location.href = redirectUrl;
                        } else {
                            alert('Erreur lors du démarrage de la recharge: ' + (data.message || 'Erreur inconnue'));
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Une erreur est survenue lors du démarrage de la recharge.');
                    });
                }
            });
        });

        // Gestion du formulaire de réservation
        $('#reservation-form').on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $('#reservation-submit-btn');
            const btnText = $('#reservation-btn-text');
            const btnLoading = $('#reservation-btn-loading');
            const resultDiv = $('#reservation-result');
            
            // Afficher l'état de chargement
            submitBtn.prop('disabled', true);
            btnText.addClass('hidden');
            btnLoading.removeClass('hidden');
            resultDiv.empty();
            
            // Préparer les données du formulaire
            const formData = {
                charging_point_id: {{ $chargingPoint->id }},
                pricing_plan_id: {{ $pricingPlan->id }},
                reservation_type: $('#reservation_type').val(),
                reservation_value: parseFloat($('#reservation_value').val()),
                start_time: $('#start_time').val(),
                guest_email: $('#guest_email').val(),
                guest_phone: $('#guest_phone').val(),
                payment_type: 'offline',
                _token: $('meta[name="csrf-token"]').attr('content')
            };
            
            // Validation côté client
            if (!formData.reservation_type || !formData.reservation_value) {
                showError('Veuillez remplir tous les champs obligatoires.');
                resetButton();
                return;
            }
            
            // Envoyer la requête
            $.ajax({
                url: '{{ route("reservations.store", $chargingPoint->id) }}',
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    console.log('Reservation response:', response);
                    
                    if (response.success || response.reservation) {
                        showSuccess('Réservation créée avec succès!');
                        
                        // Rediriger vers la page de remerciement après 2 secondes
                        setTimeout(() => {
                            const redirectUrl = response.redirect_url || '/reservations/' + (response.reservation?.id || response.reservation_id) + '/thank-you';
                            window.location.href = redirectUrl;
                        }, 2000);
                    } else {
                        showError(response.message || 'Erreur lors de la création de la réservation.');
                    }
                },
                error: function(xhr) {
                    console.error('Reservation error:', xhr);
                    
                    let errorMessage = 'Une erreur est survenue lors de la création de la réservation.';
                    
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            errorMessage = 'Erreurs de validation:<br><ul>';
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                errorMessage += '<li>' + value + '</li>';
                            });
                            errorMessage += '</ul>';
                        }
                    }
                    
                    showError(errorMessage);
                },
                complete: function() {
                    resetButton();
                }
            });
            
            function showSuccess(message) {
                resultDiv.html(`
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span class="text-green-800">${message}</span>
                        </div>
                    </div>
                `);
            }
            
            function showError(message) {
                resultDiv.html(`
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                            <span class="text-red-800">${message}</span>
                        </div>
                    </div>
                `);
            }
            
            function resetButton() {
                submitBtn.prop('disabled', false);
                btnText.removeClass('hidden');
                btnLoading.addClass('hidden');
            }
        });
    </script>
</body>
</html>