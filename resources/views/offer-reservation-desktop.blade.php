@extends('layouts.public')

@section('title', 'Réservation de la borne')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 via-blue-50 to-white flex flex-col items-center py-12 px-4">
    
    <!-- Header -->
    <div class="w-full max-w-6xl bg-white shadow-lg rounded-xl overflow-hidden mb-10">
        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-8 flex flex-col lg:flex-row justify-between items-start lg:items-center">
            <div>
                <h1 class="text-3xl font-bold">{{ $chargingPoint->name ?? 'Borne de Recharge' }}</h1>
                <p class="mt-3 flex items-center text-lg opacity-90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ $chargingPoint->address ?? 'Adresse non disponible' }}
                </p>
            </div>
            <div class="mt-6 lg:mt-0">
                <span class="bg-white text-green-700 font-semibold px-6 py-3 rounded-full text-lg shadow-lg">
                    Disponible
                </span>
            </div>
        </div>
    </div>

    <!-- Reservation Card -->
    <div class="w-full max-w-6xl bg-white rounded-xl shadow-lg p-10">
        <h2 class="text-2xl font-semibold text-gray-800 mb-8 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3v3h6v-3c0-1.657-1.343-3-3-3z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 16h6m-3-8V4m6 12v4H6v-4" />
            </svg>
            Réservez votre session de charge
        </h2>

        <!-- Reservation Form -->
        <form method="POST" action="{{ route('offer.reserve', $chargingPoint->id ?? 0) }}" class="space-y-8">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-8">
                <div>
                    <label class="block text-gray-700 font-medium mb-3 text-lg">Date de réservation</label>
                    <input type="date" name="date" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-lg py-3 px-4" required>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-3 text-lg">Heure de début</label>
                    <input type="time" name="start_time" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-lg py-3 px-4" required>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-3 text-lg">Durée (en minutes)</label>
                    <select name="duration" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-lg py-3 px-4">
                        <option value="30">30 min</option>
                        <option value="60">1 heure</option>
                        <option value="90">1h 30</option>
                        <option value="120">2 heures</option>
                        <option value="180">3 heures</option>
                        <option value="240">4 heures</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-3 text-lg">Type de véhicule</label>
                    <select name="vehicle_type" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-lg py-3 px-4">
                        <option value="voiture">Voiture</option>
                        <option value="moto">Moto</option>
                        <option value="camion">Camion</option>
                        <option value="bus">Bus</option>
                        <option value="utilitaire">Véhicule utilitaire</option>
                    </select>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="bg-gray-50 rounded-lg p-6 mt-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Informations complémentaires</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Nom complet</label>
                        <input type="text" name="full_name" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 py-2 px-3" placeholder="Votre nom complet">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Numéro de téléphone</label>
                        <input type="tel" name="phone" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 py-2 px-3" placeholder="+212 6XX XXX XXX">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-gray-700 font-medium mb-2">Commentaires (optionnel)</label>
                        <textarea name="comments" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 py-2 px-3" placeholder="Informations supplémentaires..."></textarea>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="flex justify-center pt-8">
                <button type="submit" class="px-12 py-4 bg-green-600 text-white rounded-lg font-semibold text-lg shadow-lg hover:bg-green-700 transition transform hover:scale-105">
                    Réserver maintenant
                </button>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="mt-16 text-sm text-gray-500 text-center">
        © {{ date('Y') }} Borne Connectée — Tous droits réservés.
    </footer>
</div>
@endsection