<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Public Charging Station Reservation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                        <strong>Public Reservation:</strong> This reservation system is open to the public. No account required.
                    </div>
                    <h3 class="text-lg font-semibold mb-4">{{ $station->name ?? 'Charging Station' }}</h3>
                    
                    @if($station->address || $station->city)
                        <p class="text-gray-600 mb-4">
                            <strong>Address:</strong> 
                            {{ $station->address ?? '' }}
                            @if($station->city)
                                , {{ $station->city }}
                                @if($station->postal_code), {{ $station->postal_code }}@endif
                            @endif
                            @if($station->country), {{ $station->country }}@endif
                        </p>
                    @endif
                    
                    @if($station->pricingPlan)
                        <p><strong>Tariff Plan:</strong> {{ $station->pricingPlan->name }}</p>
                        <p><strong>Price per minute:</strong> €{{ number_format($station->pricingPlan->price_per_minute ?? 0, 2) }}</p>
                        <p><strong>Max Reservation Duration:</strong> {{ $station->pricingPlan->max_duration ?? 60 }} minutes</p>
     
                        <div x-data="{ duration: 1, pricePerMinute: {{ $station->pricingPlan->price_per_minute ?? 0 }}, maxDuration: {{ $station->pricingPlan->max_duration ?? 60 }}, calculatedCost: 0 }" x-init="calculatedCost = (duration * pricePerMinute).toFixed(2)" class="mt-6">
                    @else
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                            <strong>Warning:</strong> No pricing plan available for this charging station.
                        </div>
                        <div x-data="{ duration: 1, pricePerMinute: 0, maxDuration: 60, calculatedCost: 0 }" x-init="calculatedCost = (duration * pricePerMinute).toFixed(2)" class="mt-6">
                    @endif
                    
                    <form action="{{ route('public.charging-point.offer.store-reservation.web', $station->id) }}" method="POST" id="reservationForm">
                        @csrf
                        
                        <!-- Hidden fields for the reservation -->
                        <input type="hidden" name="charging_point_id" value="{{ $station->id }}">
                        <input type="hidden" name="pricing_plan_id" value="{{ $station->pricingPlan->id ?? '' }}">
                        
                        <div class="mb-4">
                            <label for="duration_minutes" class="block text-sm font-medium text-gray-700">Reservation Duration (minutes):</label>
                            <input type="range" id="reservation_value" name="duration_minutes"
                                   x-model="duration"
                                   @input="calculatedCost = (duration * pricePerMinute).toFixed(2)"
                                   min="1" :max="maxDuration" step="1"
                                   class="mt-1 block w-full">
                            <span x-text="duration" class="text-gray-600 text-sm"></span> minutes
                        </div>

                        <div class="mb-4">
                            <p><strong>Calculated Cost:</strong> €<span x-text="calculatedCost"></span></p>
                        </div>

                        <!-- Guest Information -->
                        <div class="mb-4">
                            <label for="guest_email" class="block text-sm font-medium text-gray-700">Email:</label>
                            <input type="email" id="guest_email" name="guest_email" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="mb-4">
                            <label for="guest_phone" class="block text-sm font-medium text-gray-700">Phone Number:</label>
                            <input type="tel" id="guest_phone" name="guest_phone"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Payment Type -->
                        <div class="mb-4">
                            <label for="payment_type" class="block text-sm font-medium text-gray-700">Payment Method:</label>
                            <select id="payment_type" name="payment_type" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select payment method</option>
                                <option value="cmi">CMI Payment</option>
                                <option value="offline">Offline Payment</option>
                            </select>
                        </div>

                        @error('duration_minutes')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                        @error('guest_email')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                        @error('payment_type')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                        @error('reservation_failed')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror

                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150" @if(!$station->pricingPlan) disabled @endif>
                            Make Reservation
                        </button>
                    </form>
                    </div>

                    <!-- Success/Error Messages -->
                    <div id="reservationMessage" class="mt-4 hidden">
                        <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded hidden">
                            <span id="successText"></span>
                        </div>
                        <div id="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded hidden">
                            <span id="errorText"></span>
                        </div>
                    </div>

                    <script>
                        document.getElementById('reservationForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            const form = this;
                            const submitButton = form.querySelector('button[type="submit"]');
                            const originalText = submitButton.innerHTML;
                            
                            // Disable button and show loading
                            submitButton.disabled = true;
                            submitButton.innerHTML = 'Processing...';
                            
                            // Hide previous messages
                            document.getElementById('reservationMessage').classList.add('hidden');
                            document.getElementById('successMessage').classList.add('hidden');
                            document.getElementById('errorMessage').classList.add('hidden');
                            
                            // Get form data
                            const formData = new FormData(form);
                            
                            // Submit via AJAX
                            fetch(form.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Show success message
                                    document.getElementById('successText').textContent = data.message;
                                    document.getElementById('successMessage').classList.remove('hidden');
                                    document.getElementById('reservationMessage').classList.remove('hidden');
                                    
                                    // Reset form
                                    form.reset();
                                } else {
                                    // Show error message
                                    document.getElementById('errorText').textContent = data.message || 'An error occurred';
                                    document.getElementById('errorMessage').classList.remove('hidden');
                                    document.getElementById('reservationMessage').classList.remove('hidden');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                document.getElementById('errorText').textContent = 'An error occurred while processing your request';
                                document.getElementById('errorMessage').classList.remove('hidden');
                                document.getElementById('reservationMessage').classList.remove('hidden');
                            })
                            .finally(() => {
                                // Re-enable button
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalText;
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>