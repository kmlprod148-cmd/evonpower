@extends('layouts.app')

@section('title', 'Mode de Paiement')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('public.payment.station', $chargingPointId) }}" class="hover:text-gray-700">1. Borne</a>
            <span>/</span>
            <a href="{{ route('public.payment.customer-form', $chargingPointId) }}" class="hover:text-gray-700">2. Informations</a>
            <span>/</span>
            <span class="text-blue-600 font-medium">3. Paiement</span>
        </nav>

        <h1 class="text-2xl font-bold text-gray-800 mb-6">Mode de paiement</h1>

        <!-- Récapitulatif -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Montant à payer:</span>
                <span class="text-xl font-bold text-blue-600">
                    {{ number_format($estimate['estimated_cost'] ?? 0, 2) }} EUR
                </span>
            </div>
            <div class="text-sm text-gray-500">
                {{ ($estimate['reservation_type'] ?? '') === 'energy' ? ($estimate['reservation_value'] ?? '') . ' kWh' : ($estimate['reservation_value'] ?? '') . ' min' }}
                • {{ $customer['customer_name'] ?? '' }}
            </div>
        </div>

        <!-- Options de paiement -->
        <form id="payment-form" method="POST">
            @csrf
            
            <div class="space-y-4 mb-6">
                <!-- CMI -->
                <label class="block cursor-pointer">
                    <input type="radio" name="payment_gateway" value="cmi" class="sr-only peer" checked>
                    <div class="bg-white rounded-lg shadow-md p-4 border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-800">Carte bancaire (CMI)</h3>
                                <p class="text-sm text-gray-500">Visa, Mastercard, Maestro</p>
                            </div>
                            <div class="text-blue-600">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Stripe -->
                <label class="block cursor-pointer">
                    <input type="radio" name="payment_gateway" value="stripe" class="sr-only peer">
                    <div class="bg-white rounded-lg shadow-md p-4 border-2 border-gray-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" viewBox="0 0 24 24" fill="none">
                                    <rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                                    <path d="M2 10h20" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-800">Stripe</h3>
                                <p class="text-sm text-gray-500">Paiement sécurisé par Stripe</p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            <!-- Options supplémentaires -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="save_card" id="save_card" value="1"
                           class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-600">Enregistrer ma carte pour les prochain paiements</span>
                </label>
            </div>

            <!-- Sécurité -->
            <div class="bg-green-50 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-green-800">Paiement sécurisé</p>
                        <p class="text-sm text-green-600">Vos données sont chiffrées et protégées</p>
                    </div>
                </div>
            </div>

            <!-- Boutons -->
            <div class="flex gap-4">
                <a href="{{ route('public.payment.customer-form', $chargingPointId) }}" 
                   class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-medium text-center hover:bg-gray-300 transition">
                    Retour
                </a>
                <button type="submit" 
                        class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition">
                    Payer {{ number_format($estimate['estimated_cost'] ?? 0, 2) }} EUR
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payment-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Redirection...';
        
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
                // Rediriger vers la passerelle de paiement
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                } else {
                    // Si pas d'URL de paiement (pour test), aller vers la confirmation
                    window.location.href = '{{ route("public.payment.success", $chargingPointId) }}?reservation_id=' + data.reservation_id;
                }
            } else {
                alert(data.error || 'Une erreur est survenue');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Payer {{ number_format($estimate["estimated_cost"] ?? 0, 2) }} EUR';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Payer {{ number_format($estimate["estimated_cost"] ?? 0, 2) }} EUR';
        });
    });
});
</script>
@endpush
@endsection
