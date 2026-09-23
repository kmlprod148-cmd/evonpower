@extends('layouts.guest')

@section('title', __('Checkout - ') . $chargePoint->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center font-bold">
                        1
                    </div>
                    <span class="ml-2 font-medium text-gray-900">{{ __('Duration') }}</span>
                </div>
                <div class="w-16 h-1 bg-gray-300 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">
                        2
                    </div>
                    <span class="ml-2 font-medium text-gray-500">{{ __('Information') }}</span>
                </div>
                <div class="w-16 h-1 bg-gray-300 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">
                        3
                    </div>
                    <span class="ml-2 font-medium text-gray-500">{{ __('Payment') }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Charge Point Info Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $chargePoint->name }}</h1>
                            <p class="text-gray-600 mt-1">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $chargePoint->address ?? $chargePoint->city ?? 'Location' }}
                            </p>
                        </div>
                        @if($partner)
                        <div class="text-right">
                            <span class="text-sm text-gray-500">{{ __('Partner') }}</span>
                            <p class="font-medium text-gray-900">{{ $partner->name }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <!-- Connector Type -->
                        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            {{ $connectorType }}
                        </div>
                        <!-- Max Power -->
                        <div class="bg-green-50 text-green-700 px-4 py-2 rounded-lg">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            {{ $maxPower }} kW
                        </div>
                        <!-- Billing Type -->
                        <div class="bg-purple-50 text-purple-700 px-4 py-2 rounded-lg">
                            @if($billingType === 'per_minute')
                                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ __('Per minute') }}
                            @else
                                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                {{ __('Per kWh') }}
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Duration Selection -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Select charging duration') }}</h2>
                    
                    <form id="checkout-form">
                        @csrf
                        <input type="hidden" name="charge_point_id" value="{{ $chargePoint->id }}">
                        
                        <!-- Duration Pills -->
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mb-6">
                            @foreach($durationOptions as $duration)
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="{{ $duration }}" class="peer sr-only duration-radio">
                                <div class="px-4 py-3 text-center rounded-lg border-2 border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300 transition-all">
                                    <span class="block font-medium text-gray-900">
                                        @if($duration >= 120)
                                            {{ floor($duration/60) }}h{{ $duration%60 > 0 ? $duration%60 : '' }}
                                        @else
                                            {{ $duration }} min
                                        @endif
                                    </span>
                                </div>
                            </label>
                            @endforeach
                        </div>

                        <!-- Price Estimate -->
                        <div id="price-estimate" class="hidden bg-gray-50 rounded-lg p-4 mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600">{{ __('Estimated consumption') }}</span>
                                <span class="font-medium text-gray-900" id="estimated-kwh">- kWh</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600">{{ __('Price (excl. VAT)') }}</span>
                                <span class="font-medium text-gray-900" id="price-excl-vat">-</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600">{{ __('VAT') }}</span>
                                <span class="font-medium text-gray-900" id="vat-amount">-</span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-gray-900">{{ __('Total') }}</span>
                                    <span class="text-2xl font-bold text-green-600" id="total-price">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Continue Button -->
                        <button type="submit" id="continue-btn" class="w-full bg-green-600 text-white py-4 rounded-lg font-semibold text-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            {{ __('Continue to Information') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-4">
                    <h3 class="font-semibold text-gray-900 mb-4">{{ __('How it works') }}</h3>
                    <ol class="space-y-4">
                        <li class="flex items-start">
                            <span class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-sm font-bold mr-3">1</span>
                            <div>
                                <p class="font-medium text-gray-900">{{ __('Select duration') }}</p>
                                <p class="text-sm text-gray-500">{{ __('Choose how long you want to charge') }}</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-bold mr-3">2</span>
                            <div>
                                <p class="font-medium text-gray-900">{{ __('Enter your info') }}</p>
                                <p class="text-sm text-gray-500">{{ __('Quick registration or guest checkout') }}</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-bold mr-3">3</span>
                            <div>
                                <p class="font-medium text-gray-900">{{ __('Pay & Charge') }}</p>
                                <p class="text-sm text-gray-500">{{ __('Secure payment and start charging') }}</p>
                            </div>
                        </li>
                    </ol>

                    <div class="mt-6 pt-6 border-t">
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            {{ __('Secure payment') }}
                        </div>
                        <div class="flex items-center text-sm text-gray-500 mt-2">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            {{ __('Excess refunded to wallet') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const durationRadios = document.querySelectorAll('.duration-radio');
    const checkoutForm = document.getElementById('checkout-form');
    const priceEstimate = document.getElementById('price-estimate');
    const continueBtn = document.getElementById('continue-btn');
    
    let selectedDuration = null;
    let currentStep = 1;
    let sessionData = null;

    // Duration selection
    durationRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            selectedDuration = this.value;
            calculatePrice();
        });
    });

    // Calculate price
    async function calculatePrice() {
        if (!selectedDuration) return;

        try {
            const formData = new FormData(checkoutForm);
            formData.append('duration_minutes', selectedDuration);

            const response = await fetch('{{ route("public.checkout.calculate", ["slug" => $slug]) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (data.success !== false) {
                document.getElementById('estimated-kwh').textContent = data.estimated_kwh + ' kWh';
                document.getElementById('price-excl-vat').textContent = data.price_excl_vat + ' ' + data.currency;
                document.getElementById('vat-amount').textContent = data.vat_amount + ' ' + data.currency + ' (' + data.vat_rate + '%)';
                document.getElementById('total-price').textContent = data.total_price + ' ' + data.currency;
                
                priceEstimate.classList.remove('hidden');
                continueBtn.disabled = false;

                // Store price info
                window.checkoutData = {
                    ...data,
                    duration_minutes: selectedDuration
                };
            }
        } catch (error) {
            console.error('Price calculation error:', error);
        }
    }

    // Form submission - go to step 2
    checkoutForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!window.checkoutData) {
            alert('{{ __("Please select a duration first") }}');
            return;
        }

        // Store session data and redirect to info form
        sessionStorage.setItem('checkoutData', JSON.stringify(window.checkoutData));
        
        // Redirect to info form (could be inline or separate page)
        // For now, we'll show an inline form
        showStep2();
    });

    function showStep2() {
        // This would navigate to or show step 2 form
        // For simplicity, redirect to session creation endpoint
        window.location.href = '{{ route("public.checkout.user-info", ["slug" => $slug]) }}?data=' + btoa(JSON.stringify(window.checkoutData));
    }
});
</script>
@endpush
@endsection
