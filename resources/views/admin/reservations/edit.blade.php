@extends('layouts.app')

@section('page-title', 'Modifier la Réservation')

@section('content')
<!-- Header Section -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6">
    <div class="bg-gradient-to-r from-warning to-warning-600 rounded-t-2xl p-6">
        <div class="flex items-center">
            <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                <i class="fas fa-edit text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">Modifier la Réservation</h1>
                <p class="text-warning-100 mt-1">Réservation #{{ $reservation->id }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Form Section -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <div class="bg-warning-100 dark:bg-warning-900 rounded-full p-2 mr-3">
                <i class="fas fa-edit text-warning-600 dark:text-warning-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Informations de la Réservation</h3>
        </div>
    </div>
    
    <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Date de début -->
            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar-alt text-primary-600 mr-1"></i>Date de début
                </label>
                <input type="datetime-local" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" 
                       id="start_time" 
                       name="start_time" 
                       value="{{ old('start_time', $reservation->start_time ? $reservation->start_time->format('Y-m-d\TH:i') : '') }}"
                       required>
                @error('start_time')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Date de fin -->
            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar-alt text-primary-600 mr-1"></i>Date de fin
                </label>
                <input type="datetime-local" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" 
                       id="end_time" 
                       name="end_time" 
                       value="{{ old('end_time', $reservation->end_time ? $reservation->end_time->format('Y-m-d\TH:i') : '') }}"
                       required>
                @error('end_time')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Type de réservation -->
            <div>
                <label for="reservation_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-tag text-primary-600 mr-1"></i>Type de réservation
                </label>
                <select class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" 
                        id="reservation_type" 
                        name="reservation_type" 
                        required>
                    <option value="kwh" {{ old('reservation_type', $reservation->reservation_type) === 'kwh' ? 'selected' : '' }}>
                        kWh
                    </option>
                    <option value="minutes" {{ old('reservation_type', $reservation->reservation_type) === 'minutes' ? 'selected' : '' }}>
                        Minutes
                    </option>
                </select>
                @error('reservation_type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Valeur de réservation -->
            <div>
                <label for="reservation_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-bolt text-primary-600 mr-1"></i>Valeur
                </label>
                <input type="number" 
                       step="0.01" 
                       min="0" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" 
                       id="reservation_value" 
                       name="reservation_value" 
                       value="{{ old('reservation_value', $reservation->reservation_value) }}"
                       required>
                @error('reservation_value')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <!-- Notes -->
        <div class="mt-6">
            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <i class="fas fa-sticky-note text-primary-600 mr-1"></i>Notes
            </label>
            <textarea class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:text-white" 
                      id="notes" 
                      name="notes" 
                      rows="3" 
                      placeholder="Notes additionnelles...">{{ old('notes', $reservation->notes) }}</textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        
        <!-- Informations de la réservation (lecture seule) -->
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Informations de la réservation</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600 dark:text-gray-400">Utilisateur :</span>
                    <span class="ml-2 font-medium text-gray-900 dark:text-white">
                        {{ $reservation->user->name ?? 'Invité' }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">Email :</span>
                    <span class="ml-2 font-medium text-gray-900 dark:text-white">
                        {{ $reservation->user->email ?? $reservation->guest_email ?? 'N/A' }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">Borne :</span>
                    <span class="ml-2 font-medium text-gray-900 dark:text-white">
                        {{ $reservation->chargingPoint->name ?? 'N/A' }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">Statut :</span>
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $reservation->status->color() }}-100 text-{{ $reservation->status->color() }}-800 dark:bg-{{ $reservation->status->color() }}-900 dark:text-{{ $reservation->status->color() }}-200">
                        {{ $reservation->getDisplayStatusLabel() }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="flex justify-end space-x-4 mt-6">
            <a href="{{ route('admin.reservations.show', $reservation) }}" 
               class="inline-flex items-center px-6 py-3 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                <i class="fas fa-times mr-2"></i>Annuler
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors">
                <i class="fas fa-save mr-2"></i>Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate end time when start time changes
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const reservationValueInput = document.getElementById('reservation_value');
    const reservationTypeSelect = document.getElementById('reservation_type');
    
    function calculateEndTime() {
        if (startTimeInput.value && reservationValueInput.value) {
            const startTime = new Date(startTimeInput.value);
            const value = parseFloat(reservationValueInput.value);
            const type = reservationTypeSelect.value;
            
            let durationInMinutes;
            if (type === 'minutes') {
                durationInMinutes = value;
            } else {
                // For kWh, assume 1 hour per kWh (this is just an example)
                durationInMinutes = value * 60;
            }
            
            const endTime = new Date(startTime.getTime() + durationInMinutes * 60000);
            endTimeInput.value = endTime.toISOString().slice(0, 16);
        }
    }
    
    startTimeInput.addEventListener('change', calculateEndTime);
    reservationValueInput.addEventListener('input', calculateEndTime);
    reservationTypeSelect.addEventListener('change', calculateEndTime);
});
</script>
@endpush
@endsection
