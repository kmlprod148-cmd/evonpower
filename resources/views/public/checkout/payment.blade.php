@extends('layouts.guest')

@section('title', __('Checkout - Payment'))

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="ml-2 font-medium text-gray-900">{{ __('Duration') }}</span>
                </div>
                <div class="w-16 h-1 bg-green-500 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="ml-2 font-medium text-gray-900">{{ __('Information') }}</span>
                </div>
                <div class="w-16 h-1 bg-green-500 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center font-bold">3</div>
                    <span class="ml-2 font-medium text-gray-900">{{ __('Payment') }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ __('Payment') }}</h2>
                    
                    <!-- Payment Form -->
                    <form id="payment-form" method="POST" action="{{ route('public.checkout.pay', ['slug' => $slug]) }}">
                        @csrf
                        
                        <input type="hidden" name="session_id" value="{{ $session->id }}">
                        <input type="hidden" name="session_token" value="{{ $sessionToken }}">
                        
                        <!-- Card Details (for Stripe) -->
                        <div id="card-element" class="mb-6 p-4 border border-gray-300 rounded-lg">
                            <!-- Stripe Elements will be inserted here -->
                        </div>
                        
                        <!-- Error messages -->
                        <div id="payment-errors" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
                        </div>

                        <!-- Terms -->
                        <div class="mb-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="terms" id="terms" required value="1" class="sr-only peer">
                                <div class="w-5 h-5 border-2 border-gray-300 rounded peer-checked:bg-green-600 peer-checked:border-green-600 mr-3">
                                </div>
                                <span class="text-sm text-gray-600">
                                    {{ __('I accept the') }} <a href="#" class="text-green-600 hover:underline">{{ __('terms and conditions') }}</a>
                                </span>
                            </label>
                        </div>

                        <!-- Pay Button -->
                        <button type="submit" id="pay-button" 
                            class="w-full bg-green-600 text-white py-4 rounded-lg font-semibold text-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('Pay') }} {{ $totalPrice }} {{ $currency }}
                        </button>
                    </form>

                    <!-- CMI Redirect Form (hidden by default) -->
                    <div id="cmi-redirect" class="hidden mt-4 text-center">
                        <p class="text-gray-600 mb-4">{{ __('Redirecting to payment gateway...') }}</p>
                        <button type="button" id="cmi-submit" class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            {{ __('Click here if not redirected') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-4">
                    <h3 class="font-semibold text-gray-900 mb-4">{{ __('Order Summary') }}</h3>
                    
                    <div class="border-b pb-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-600">{{ __('Charge Point') }}</span>
                            <span class="font-medium text-gray-900">{{ $chargePoint->name }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-600">{{ __('Duration') }}</span>
                            <span class="font-medium text-gray-900">{{ $durationMinutes }} min</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">{{ __('Est. Consumption') }}</span>
                            <span class="font-medium text-gray-900">{{ $estimatedKwh }} kWh</span>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-900">{{ __('Total') }}</span>
                            <span class="text-2xl font-bold text-green-600">{{ $totalPrice }} {{ $currency }}</span>
                        </div>
                    </div>

                    <!-- User Info Summary -->
                    <div class="mt-6 pt-6 border-t">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">{{ __('Customer') }}</h4>
                        <p class="text-sm text-gray-600">{{ $guestInfo['first_name'] ?? '' }} {{ $guestInfo['last_name'] ?? '' }}</p>
                        <p class="text-sm text-gray-600">{{ $guestInfo['email'] ?? '' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($gatewayType === 'stripe')
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stripe = Stripe('{{ $stripePublicKey }}');
    const elements = stripe.elements();
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a',
            },
        },
    });
    
    cardElement.mount('#card-element');

    const paymentForm = document.getElementById('payment-form');
    const payButton = document.getElementById('pay-button');
    const paymentErrors = document.getElementById('payment-errors');

    paymentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        payButton.disabled = true;
        payButton.textContent = '{{ __("Processing...") }}';

        // Get client secret from server (already fetched in controller)
        const clientSecret = '{{ $clientSecret ?? "" }}';

        if (clientSecret) {
            // Confirm payment with Stripe
            const {error, paymentIntent} = await stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: cardElement,
                }
            });

            if (error) {
                paymentErrors.textContent = error.message;
                paymentErrors.classList.remove('hidden');
                payButton.disabled = false;
                payButton.textContent = '{{ __("Pay") }} {{ $totalPrice }} {{ $currency }}';
            } else if (paymentIntent.status === 'succeeded') {
                // Payment successful - redirect to confirmation
                window.location.href = '{{ route("public.checkout.confirm", ["slug" => $slug, "id" => $session->id]) }}';
            }
        } else {
            // No client secret - use server-side payment initiation
            // Form will be submitted normally
            paymentForm.submit();
        }
    });
});
</script>
@endif
@endpush
@endsection
