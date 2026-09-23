@extends('layouts.app')

@section('title', 'Informations Client')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('public.payment.station', $chargingPointId) }}" class="hover:text-gray-700">1. Borne</a>
            <span>/</span>
            <span class="text-blue-600 font-medium">2. Vos informations</span>
            <span>/</span>
            <span class="text-gray-400">3. Paiement</span>
        </nav>

        <h1 class="text-2xl font-bold text-gray-800 mb-6">Vos informations</h1>

        <!-- Récapitulatif estimation -->
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Estimation:</span>
                <span class="text-xl font-bold text-blue-600">
                    {{ number_format($estimate['estimated_cost'], 2) }} EUR
                </span>
            </div>
            <p class="text-sm text-blue-600 mt-1">
                {{ $estimate['reservation_type'] === 'energy' ? $estimate['reservation_value'] . ' kWh' : $estimate['reservation_value'] . ' minutes' }}
            </p>
        </div>

        <!-- Formulaire -->
        <form id="customer-form" method="POST">
            @csrf
            
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <!-- Nom complet -->
                <div class="mb-4">
                    <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nom complet <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="customer_name" name="customer_name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Votre nom complet">
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                        Adresse email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="customer_email" name="customer_email" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="votre@email.com">
                    <p class="text-xs text-gray-500 mt-1">Reçu de confirmation envoyé à cette adresse</p>
                </div>

                <!-- Téléphone -->
                <div class="mb-4">
                    <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Numéro de téléphone <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" id="customer_phone" name="customer_phone" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="+33 6 12 34 56 78">
                    <p class="text-xs text-gray-500 mt-1">Format international (ex: +33...)</p>
                </div>

                <!-- Adresse de facturation -->
                <div class="mb-4">
                    <label for="customer_address" class="block text-sm font-medium text-gray-700 mb-2">
                        Adresse de facturation
                    </label>
                    <textarea id="customer_address" name="customer_address" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Adresse complète pour la facture"></textarea>
                </div>

                <!-- Inscription rapide -->
                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="register_account" id="register_account" value="1"
                               class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                        <div>
                            <span class="font-medium text-gray-700">Créer un compte</span>
                            <p class="text-sm text-gray-500">Bénéficiez de tarifs préférentiels et d'un historique de vos sessions</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Champs cachés pour les données -->
            <input type="hidden" name="reservation_type" value="{{ $estimate['reservation_type'] }}">
            <input type="hidden" name="reservation_value" value="{{ $estimate['reservation_value'] }}">
            <input type="hidden" name="estimated_cost" value="{{ $estimate['estimated_cost'] }}">

            <!-- Boutons -->
            <div class="flex gap-4">
                <a href="{{ route('public.payment.station', $chargingPointId) }}" 
                   class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-medium text-center hover:bg-gray-300 transition">
                    Retour
                </a>
                <button type="submit" 
                        class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition">
                    Continuer vers le paiement
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('customer-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Traitement...';
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect_url;
            } else {
                alert(data.error || 'Une erreur est survenue');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Continuer vers le paiement';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Continuer vers le paiement';
        });
    });
    
    // Validation du téléphone en temps réel
    const phoneInput = document.getElementById('customer_phone');
    phoneInput.addEventListener('input', function(e) {
        // Supprimer les caractères non numériques sauf +
        let value = e.target.value.replace(/[^\d+]/g, '');
        e.target.value = value;
    });
});
</script>
@endpush
@endsection
