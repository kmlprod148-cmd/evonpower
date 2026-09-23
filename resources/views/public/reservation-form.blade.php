<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - {{ $data['charging_point']->name ?? 'Borne de Recharge' }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="{{ asset("vendor/fontawesome/css/all.min.css") }}" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .option-card {
            transition: all 0.2s ease;
        }
        .option-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .option-card.selected {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
    </style>
</head>
<body class="bg-white min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('public.charging-point.offer', $data['charging_point']->id) }}" 
                       class="text-white hover:text-green-100 transition duration-200">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold">{{ $data['charging_point']->name ?? 'Borne de Recharge' }}</h1>
                        <p class="text-green-100 text-sm">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            {{ $data['charging_point']->address ?? 'Adresse non disponible' }}, {{ $data['charging_point']->city ?? '' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white bg-opacity-20">
                        <span class="w-2 h-2 rounded-full mr-2 bg-green-300"></span>
                        {{ ucfirst($data['charging_point']->status ?? 'online') }}
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Reservation Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Pricing Plan Info -->
                @if($data['activePricingPlan'])
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-tag text-green-500 mr-2"></i>
                            Plan Tarifaire
                        </h2>
                        
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-green-900">{{ $data['activePricingPlan']->name ?? 'Plan Standard' }}</h3>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Actif
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if(isset($data['activePricingPlan']->price_per_kwh) && $data['activePricingPlan']->price_per_kwh > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Prix par kWh:</span>
                                    <span class="font-medium">{{ number_format($data['activePricingPlan']->price_per_kwh, 4) }} {{ $data['activePricingPlan']->currency ?? 'EUR' }}</span>
                                </div>
                                @endif
                                
                                @if(isset($data['activePricingPlan']->price_per_minute) && $data['activePricingPlan']->price_per_minute > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Prix par minute:</span>
                                    <span class="font-medium">{{ number_format($data['activePricingPlan']->price_per_minute, 2) }} {{ $data['activePricingPlan']->currency ?? 'EUR' }}</span>
                                </div>
                                @endif
                                
                                @if(isset($data['activePricingPlan']->activation_fee) && $data['activePricingPlan']->activation_fee > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Frais d'activation:</span>
                                    <span class="font-medium">{{ number_format($data['activePricingPlan']->activation_fee, 2) }} {{ $data['activePricingPlan']->currency ?? 'EUR' }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Reservation Form -->
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-calendar-plus text-green-500 mr-2"></i>
                            Réserver ma borne
                        </h2>
                        
                        <form id="reservationForm" action="{{ route('public.charging-point.offer.store-reservation.web', $data['charging_point']->id) }}" method="POST">
                            @csrf
                            
                            <!-- Reservation Type Selection -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Type de réservation</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="option-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer" data-type="kwh">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="font-semibold text-gray-900">Énergie (kWh)</h4>
                                                <p class="text-sm text-gray-600">Réserver une quantité d'énergie spécifique</p>
                                            </div>
                                            <i class="fas fa-bolt text-green-500 text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="option-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer" data-type="minute">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="font-semibold text-gray-900">Durée (minutes)</h4>
                                                <p class="text-sm text-gray-600">Réserver un temps de charge spécifique</p>
                                            </div>
                                            <i class="fas fa-clock text-blue-500 text-xl"></i>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="reservation_type" id="reservation_type" value="">
                            </div>

                            <!-- Reservation Options -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Options disponibles</h3>
                                <div id="reservationOptions" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <!-- Options will be populated by JavaScript -->
                                </div>
                                <input type="hidden" name="reservation_value" id="reservation_value" value="">
                            </div>

                            <!-- Date and Time Selection -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Date et heure</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="reservation_date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                                        <input type="date" name="reservation_date" id="reservation_date" 
                                               class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                               min="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div>
                                        <label for="reservation_time" class="block text-sm font-medium text-gray-700 mb-2">Heure</label>
                                        <select name="reservation_time" id="reservation_time" 
                                                class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
                                            <option value="">Sélectionner une heure</option>
                                            @foreach($data['timeSlots'] as $slot)
                                                <option value="{{ $slot['time'] }}">{{ $slot['time'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de contact</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="guest_email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" name="guest_email" id="guest_email" 
                                               class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                               placeholder="votre.email@example.com">
                                    </div>
                                    <div>
                                        <label for="guest_phone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                        <input type="tel" name="guest_phone" id="guest_phone" 
                                               class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                               placeholder="+212 6 12 34 56 78">
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">* Fournissez au moins un email ou un numéro de téléphone</p>
                            </div>

                            <!-- Payment Type -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Méthode de paiement</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="option-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer" data-payment="card">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="font-semibold text-gray-900">Carte bancaire</h4>
                                                <p class="text-sm text-gray-600">Paiement sécurisé en ligne</p>
                                            </div>
                                            <i class="fas fa-credit-card text-blue-500 text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="option-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer" data-payment="cash">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="font-semibold text-gray-900">Espèces</h4>
                                                <p class="text-sm text-gray-600">Paiement sur place</p>
                                            </div>
                                            <i class="fas fa-money-bill text-green-500 text-xl"></i>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="payment_type" id="payment_type" value="">
                            </div>

                            <!-- Total Price -->
                            <div class="mb-6">
                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-semibold text-gray-900">Total estimé:</span>
                                        <span class="text-2xl font-bold text-green-600" id="totalPrice">0.00 {{ $data['currency'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" id="submitBtn" 
                                        class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-check mr-2"></i>
                                    Confirmer la réservation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Summary -->
            <div class="space-y-6">
                <!-- Charging Point Summary -->
                <div class="bg-white rounded-xl shadow-md card-hover">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-info-circle text-green-500 mr-2"></i>
                            Informations de la Borne
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                                <span class="text-gray-600 font-medium">Puissance max</span>
                                <span class="text-gray-900 font-semibold">{{ number_format($data['charging_point']->power_output ?? 0, 1) }} kW</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                                <span class="text-gray-600 font-medium">Type de connecteur</span>
                                <span class="text-gray-900 font-semibold">{{ $data['charging_point']->connector_type ?? 'N/A' }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg">
                                <span class="text-gray-600 font-medium">Statut</span>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    {{ ucfirst($data['charging_point']->status ?? 'online') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="bg-blue-50 rounded-xl shadow-md">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-blue-900 mb-4">
                            <i class="fas fa-question-circle text-blue-500 mr-2"></i>
                            Besoin d'aide ?
                        </h2>
                        
                        <div class="space-y-3">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-phone text-blue-500 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-blue-900">Support téléphonique</h4>
                                    <p class="text-sm text-blue-700">+212 5 22 34 56 78</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-envelope text-blue-500 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-blue-900">Email</h4>
                                    <p class="text-sm text-blue-700">support@evonpower.com</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-8">
        <div class="container mx-auto px-4 text-center">
            <div class="flex items-center justify-center space-x-4 mb-4">
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-bolt text-white text-lg"></i>
                </div>
                <h3 class="text-xl font-bold">Evon Power</h3>
            </div>
            <p class="text-gray-300 text-sm">Votre partenaire de confiance pour la recharge électrique</p>
            <div class="mt-4 flex justify-center space-x-6 text-xs text-gray-400">
                <a href="{{ route('guest-reservations.index') }}" class="hover:text-white transition duration-200">Mes Réservations</a>
                <a href="#" class="hover:text-white transition duration-200">Support</a>
                <a href="#" class="hover:text-white transition duration-200">Conditions</a>
                <a href="#" class="hover:text-white transition duration-200">Confidentialité</a>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reservationOptions = @json($data['reservationOptions']);
            const currency = @json($data['currency']);
            
            let selectedType = '';
            let selectedValue = 0;
            let selectedPayment = '';
            
            // Reservation type selection
            document.querySelectorAll('[data-type]').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('[data-type]').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedType = this.dataset.type;
                    document.getElementById('reservation_type').value = selectedType;
                    updateOptions();
                });
            });
            
            // Payment type selection
            document.querySelectorAll('[data-payment]').forEach(card => {
                card.addEventListener('click', function() {
                    document.querySelectorAll('[data-payment]').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedPayment = this.dataset.payment;
                    document.getElementById('payment_type').value = selectedPayment;
                    updateSubmitButton();
                });
            });
            
            function updateOptions() {
                const container = document.getElementById('reservationOptions');
                container.innerHTML = '';
                
                const filteredOptions = reservationOptions.filter(option => option.type === selectedType);
                
                filteredOptions.forEach(option => {
                    const card = document.createElement('div');
                    card.className = 'option-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer';
                    card.dataset.value = option.value;
                    card.dataset.price = option.price;
                    
                    card.innerHTML = `
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">${option.value}</div>
                            <div class="text-sm text-gray-600">${option.unit}</div>
                            <div class="text-lg font-semibold text-green-600 mt-2">${option.formatted_price}</div>
                        </div>
                    `;
                    
                    card.addEventListener('click', function() {
                        document.querySelectorAll('#reservationOptions .option-card').forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedValue = parseFloat(this.dataset.value);
                        document.getElementById('reservation_value').value = selectedValue;
                        updateTotalPrice();
                        updateSubmitButton();
                    });
                    
                    container.appendChild(card);
                });
            }
            
            function updateTotalPrice() {
                const selectedCard = document.querySelector('#reservationOptions .option-card.selected');
                if (selectedCard) {
                    const price = parseFloat(selectedCard.dataset.price);
                    document.getElementById('totalPrice').textContent = `${price.toFixed(2)} ${currency}`;
                }
            }
            
            function updateSubmitButton() {
                const submitBtn = document.getElementById('submitBtn');
                const isValid = selectedType && selectedValue > 0 && selectedPayment;
                submitBtn.disabled = !isValid;
            }
            
            // Form validation
            document.getElementById('reservationForm').addEventListener('submit', function(e) {
                const email = document.getElementById('guest_email').value;
                const phone = document.getElementById('guest_phone').value;
                
                if (!email && !phone) {
                    e.preventDefault();
                    showCustomAlert({
                        type: 'warning',
                        title: 'Contact requis',
                        message: 'Veuillez fournir au moins un email ou un numéro de téléphone.',
                        primaryBtn: {
                            text: 'Compris',
                            action: () => hideCustomAlert()
                        }
                    });
                    return false;
                }
                
                if (!selectedType || !selectedValue || !selectedPayment) {
                    e.preventDefault();
                    showCustomAlert({
                        type: 'warning',
                        title: 'Champs requis',
                        message: 'Veuillez sélectionner tous les champs requis.',
                        primaryBtn: {
                            text: 'Compris',
                            action: () => hideCustomAlert()
                        }
                    });
                    return false;
                }
            });
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('reservation_date').min = today;
        });
    </script>

    <!-- Custom Alert Overlay -->
    <div id="customAlertOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
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

    <script>
    // Fonctions pour le popup d'alerte moderne
    function showCustomAlert(options) {
        const overlay = document.getElementById('customAlertOverlay');
        const icon = document.getElementById('customAlertIcon');
        const iconClass = document.getElementById('customAlertIconClass');
        const title = document.getElementById('customAlertTitle');
        const message = document.getElementById('customAlertMessage');
        const bodyContent = document.getElementById('customAlertBodyContent');
        const primaryBtn = document.getElementById('customAlertPrimaryBtn');
        const secondaryBtn = document.getElementById('customAlertSecondaryBtn');

        // Configuration de l'icône
        icon.className = `flex justify-center mb-4 ${options.type}`;
        
        // Configuration de l'icône selon le type
        switch(options.type) {
            case 'success':
                iconClass.className = 'fas fa-check text-green-500 text-3xl';
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

        // Configuration du contenu
        title.textContent = options.title || '';
        message.textContent = options.message || '';
        bodyContent.innerHTML = options.bodyContent || '';

        // Configuration des boutons
        if (options.primaryBtn) {
            primaryBtn.textContent = options.primaryBtn.text;
            primaryBtn.onclick = options.primaryBtn.action;
            primaryBtn.style.display = 'block';
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

        // Afficher le popup
        overlay.classList.remove('hidden');
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }

    function hideCustomAlert() {
        const overlay = document.getElementById('customAlertOverlay');
        overlay.classList.add('hidden');
        
        // Restaurer le scroll du body
        document.body.style.overflow = '';
    }

    // Fermer le popup en cliquant sur l'overlay
    document.getElementById('customAlertOverlay').addEventListener('click', function(e) {
        if (e.target === this) {
            hideCustomAlert();
        }
    });

    // Fermer le popup avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideCustomAlert();
        }
    });
    </script>
</body>
</html>
