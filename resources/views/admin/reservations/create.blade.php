@extends('layouts.app')

@section('page-title', 'Créer une Réservation')

@section('content')
<!-- Header Section -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6">
    <div class="bg-gradient-to-r from-primary to-primary-600 rounded-t-2xl p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                    <i class="fas fa-plus text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Créer une Réservation</h1>
                    <p class="text-primary-100 mt-1">Créer une nouvelle réservation manuellement</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.reservations.index') }}" 
                   class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition-all duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Form Section -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Informations de la Réservation</h2>
    </div>
    
    <form action="{{ route('admin.reservations.store') }}" method="POST" class="p-6">
        @csrf
        
        @if($duplicateReservation)
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                <span class="text-blue-700 dark:text-blue-300">
                    Duplication de la réservation #{{ $duplicateReservation->id }}
                </span>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Charging Point Selection -->
            <div class="lg:col-span-2">
                <label for="charging_point_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Borne de Recharge <span class="text-red-500">*</span>
                </label>
                <select name="charging_point_id" id="charging_point_id" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('charging_point_id') border-red-500 @enderror" required>
                    <option value="">Sélectionner une borne</option>
                    @foreach($chargingPoints as $point)
                        <option value="{{ $point->id }}" 
                                @if($duplicateReservation && $duplicateReservation->charging_point_id == $point->id) selected @endif
                                @if(old('charging_point_id') == $point->id) selected @endif>
                            {{ $point->name }} - {{ $point->address }}
                        </option>
                    @endforeach
                </select>
                @error('charging_point_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- User Selection -->
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Utilisateur
                </label>
                <select name="user_id" id="user_id" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('user_id') border-red-500 @enderror">
                    <option value="">Sélectionner un utilisateur (optionnel)</option>
                    <!-- Les utilisateurs seront chargés via AJAX -->
                </select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Guest Email -->
            <div>
                <label for="guest_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Email Invité
                </label>
                <input type="email" name="guest_email" id="guest_email" 
                       value="{{ old('guest_email', $duplicateReservation->guest_email ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('guest_email') border-red-500 @enderror"
                       placeholder="email@example.com">
                @error('guest_email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Guest Phone -->
            <div>
                <label for="guest_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Téléphone Invité
                </label>
                <input type="tel" name="guest_phone" id="guest_phone" 
                       value="{{ old('guest_phone', $duplicateReservation->guest_phone ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('guest_phone') border-red-500 @enderror"
                       placeholder="+33 1 23 45 67 89">
                @error('guest_phone')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Start Time -->
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Heure de Début <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" name="start_time" id="start_time" 
                       value="{{ old('start_time', $duplicateReservation ? $duplicateReservation->start_time->format('Y-m-d\TH:i') : '') }}"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('start_time') border-red-500 @enderror" required>
                @error('start_time')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- End Time -->
            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Heure de Fin <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" name="end_time" id="end_time" 
                       value="{{ old('end_time', $duplicateReservation ? $duplicateReservation->end_time->format('Y-m-d\TH:i') : '') }}"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('end_time') border-red-500 @enderror" required>
                @error('end_time')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Reservation Value -->
            <div>
                <label for="reservation_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Valeur de la Réservation <span class="text-red-500">*</span>
                </label>
                <input type="number" name="reservation_value" id="reservation_value" 
                       value="{{ old('reservation_value', $duplicateReservation->reservation_value ?? '') }}"
                       step="0.01" min="0"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('reservation_value') border-red-500 @enderror" required>
                @error('reservation_value')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Reservation Type -->
            <div>
                <label for="reservation_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Type de Réservation <span class="text-red-500">*</span>
                </label>
                <select name="reservation_type" id="reservation_type" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('reservation_type') border-red-500 @enderror" required>
                    <option value="kwh" @if(old('reservation_type', $duplicateReservation->reservation_type ?? '') == 'kwh') selected @endif>kWh</option>
                    <option value="minutes" @if(old('reservation_type', $duplicateReservation->reservation_type ?? '') == 'minutes') selected @endif>Minutes</option>
                </select>
                @error('reservation_type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div class="lg:col-span-2">
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Notes
                </label>
                <textarea name="notes" id="notes" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white @error('notes') border-red-500 @enderror"
                          placeholder="Notes optionnelles sur la réservation">{{ old('notes', $duplicateReservation->notes ?? '') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.reservations.index') }}" 
               class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                Annuler
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors duration-200">
                <i class="fas fa-save mr-2"></i>Créer la Réservation
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation des dates
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    startTimeInput.addEventListener('change', function() {
        const startTime = new Date(this.value);
        const minEndTime = new Date(startTime.getTime() + 30 * 60000); // +30 minutes
        endTimeInput.min = minEndTime.toISOString().slice(0, 16);
    });
    
    // Validation croisée
    endTimeInput.addEventListener('change', function() {
        const startTime = new Date(startTimeInput.value);
        const endTime = new Date(this.value);
        
        if (endTime <= startTime) {
            alert('L\'heure de fin doit être postérieure à l\'heure de début');
            this.value = '';
        }
    });
    
    // Chargement des utilisateurs via AJAX (optionnel)
    // Vous pouvez implémenter cette fonctionnalité si nécessaire
});
</script>
@endsection
