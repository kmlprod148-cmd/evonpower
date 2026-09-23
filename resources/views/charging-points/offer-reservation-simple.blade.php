@extends('layouts.app')

@section('title', 'Réservation de la borne - Test')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                Réservation - {{ $chargingPoint->name }}
            </h1>
            <p class="text-gray-600">
                Station: {{ $chargingPoint->station->name ?? 'Non spécifiée' }}
            </p>
        </div>

        <!-- Test des variables -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Test des variables</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-medium text-gray-700">Charging Point</h3>
                    <p class="text-sm text-gray-600">ID: {{ $chargingPoint->id }}</p>
                    <p class="text-sm text-gray-600">Nom: {{ $chargingPoint->name }}</p>
                    <p class="text-sm text-gray-600">Puissance: {{ $chargingPoint->power_output }} kW</p>
                </div>
                <div>
                    <h3 class="font-medium text-gray-700">Plan tarifaire</h3>
                    <p class="text-sm text-gray-600">Nom: {{ $pricingPlan->name }}</p>
                    <p class="text-sm text-gray-600">Prix/kWh: {{ $pricingPlan->price_per_kwh }} {{ $currency ?? 'EUR' }}</p>
                    <p class="text-sm text-gray-600">Prix/minute: {{ $pricingPlan->price_per_minute }} {{ $currency ?? 'EUR' }}</p>
                </div>
            </div>
        </div>

        <!-- Test des créneaux horaires -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Créneaux horaires ({{ count($timeSlots) }} créneaux)</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                @foreach($timeSlots as $slot)
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-2 text-center text-sm">
                    <div class="font-medium">{{ $slot['time'] }}</div>
                    <div class="text-xs text-gray-500">{{ $slot['available'] ? 'Disponible' : 'Occupé' }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Test du formulaire simple -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Formulaire de test</h2>
            <form>
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de réservation</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="kwh">Par kWh</option>
                        <option value="minute">Par minute</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valeur</label>
                    <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Entrez une valeur">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                    Tester la réservation
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
