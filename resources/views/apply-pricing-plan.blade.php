@extends('layouts.app')

@section('title', 'Appliquer un Plan Tarifaire')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Appliquer un Plan Tarifaire
            </h1>
            <p class="text-gray-600">
                Sélectionnez et appliquez un plan tarifaire à la borne de recharge
            </p>
        </div>

        <!-- Informations de la borne -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                Borne de Recharge: {{ $chargingPoint->name }}
            </h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600">Adresse</p>
                    <p class="font-medium">{{ $chargingPoint->address }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Statut</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        @if($chargingPoint->status === 'online') bg-green-100 text-green-800
                        @elseif($chargingPoint->status === 'offline') bg-red-100 text-red-800
                        @else bg-yellow-100 text-yellow-800 @endif">
                        {{ ucfirst($chargingPoint->status) }}
                    </span>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Plan tarifaire actuel</p>
                    <p class="font-medium">
                        @if($chargingPoint->pricingPlan)
                            {{ $chargingPoint->pricingPlan->name }}
                        @else
                            <span class="text-yellow-600">Aucun plan assigné</span>
                        @endif
                    </p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600">Connecteurs</p>
                    <p class="font-medium">0 connecteur(s)</p>
                </div>
            </div>
        </div>

        <!-- Formulaire d'application -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">
                Sélectionner un Plan Tarifaire
            </h3>

            <form action="{{ route('charging-points.assign-pricing-plan', $chargingPoint) }}" method="POST" id="pricingPlanForm">
                @csrf
                
                <!-- Sélection du plan -->
                <div class="mb-6">
                    <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Plan Tarifaire *
                    </label>
                    
                    <select name="pricing_plan_id" id="pricing_plan_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                            required>
                        <option value="">-- Sélectionner un plan tarifaire --</option>
                        @foreach($pricingPlans as $plan)
                            <option value="{{ $plan->id }}" 
                                    data-rate-type="{{ $plan->rate_type }}"
                                    data-price-kwh="{{ $plan->price_per_kwh }}"
                                    data-price-minute="{{ $plan->price_per_minute }}"
                                    data-activation-fee="{{ $plan->activation_fee }}"
                                    data-currency="{{ $plan->currency }}">
                                {{ $plan->name }}
                                @if($plan->rate_type === 'minute')
                                    ({{ number_format($plan->price_per_minute, 2) }} {{ $plan->currency }}/min)
                                @elseif($plan->rate_type === 'kwh')
                                    ({{ number_format($plan->price_per_kwh, 2) }} {{ $plan->currency }}/kWh)
                                @elseif($plan->rate_type === 'mixed')
                                    ({{ number_format($plan->price_per_kwh, 2) }} {{ $plan->currency }}/kWh + {{ number_format($plan->price_per_minute, 2) }} {{ $plan->currency }}/min)
                                @else
                                    ({{ number_format($plan->base_rate, 2) }} {{ $plan->currency }} forfait)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    
                    @error('pricing_plan_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Détails du plan sélectionné -->
                <div id="planDetails" class="mb-6 hidden">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Détails du Plan</h4>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Type de tarification</p>
                                <p class="font-medium" id="rateType">-</p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-600">Prix par kWh</p>
                                <p class="font-medium" id="priceKwh">-</p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-600">Prix par minute</p>
                                <p class="font-medium" id="priceMinute">-</p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-600">Frais d'activation</p>
                                <p class="font-medium" id="activationFee">-</p>
                            </div>
                        </div>
                        
                        <!-- Exemple de calcul -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h5 class="text-sm font-medium text-gray-900 mb-2">Exemple de calcul</h5>
                            <div class="text-sm text-gray-600">
                                <p>Session de 15 kWh pendant 45 minutes:</p>
                                <p class="font-medium" id="exampleCalculation">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Options d'application -->
                <div class="mb-6">
                    <!-- Connecteurs supprimés -->
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('charging-points.show', $chargingPoint) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Annuler
                    </a>
                    
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Appliquer le Plan
                    </button>
                </div>
            </form>
        </div>

        <!-- Plans compatibles -->
        @if(isset($compatiblePlans) && $compatiblePlans->count() > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Plans Tarifaires Compatibles
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Plan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tarifs
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Priorité
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($compatiblePlans as $plan)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $plan->name }}</div>
                                <div class="text-sm text-gray-500">{{ $plan->description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ ucfirst($plan->rate_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($plan->price_per_kwh > 0)
                                    <div>{{ number_format($plan->price_per_kwh, 2) }} {{ $plan->currency }}/kWh</div>
                                @endif
                                @if($plan->price_per_minute > 0)
                                    <div>{{ number_format($plan->price_per_minute, 2) }} {{ $plan->currency }}/min</div>
                                @endif
                                @if($plan->activation_fee > 0)
                                    <div>Activation: {{ number_format($plan->activation_fee, 2) }} {{ $plan->currency }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $plan->priority }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const planSelect = document.getElementById('pricing_plan_id');
    const planDetails = document.getElementById('planDetails');
    
    planSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value && selectedOption && selectedOption.dataset) {
            // Afficher les détails
            planDetails.classList.remove('hidden');
            
            // Mettre à jour les détails
            document.getElementById('rateType').textContent = selectedOption.dataset.rateType.toUpperCase();
            document.getElementById('priceKwh').textContent = selectedOption.dataset.priceKwh + ' ' + selectedOption.dataset.currency;
            document.getElementById('priceMinute').textContent = selectedOption.dataset.priceMinute + ' ' + selectedOption.dataset.currency;
            document.getElementById('activationFee').textContent = selectedOption.dataset.activationFee + ' ' + selectedOption.dataset.currency;
            
            // Calculer l'exemple
            const priceKwh = parseFloat(selectedOption.dataset.priceKwh) || 0;
            const priceMinute = parseFloat(selectedOption.dataset.priceMinute) || 0;
            const activationFee = parseFloat(selectedOption.dataset.activationFee) || 0;
            const currency = selectedOption.dataset.currency;
            
            const energyCost = 15 * priceKwh;
            const timeCost = 45 * priceMinute;
            const totalCost = energyCost + timeCost + activationFee;
            
            document.getElementById('exampleCalculation').textContent = 
                `${totalCost.toFixed(2)} ${currency} (15 kWh × ${priceKwh} + 45 min × ${priceMinute} + activation ${activationFee})`;
        } else {
            planDetails.classList.add('hidden');
        }
    });
});
</script>
@endsection 