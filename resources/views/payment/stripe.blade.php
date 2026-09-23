@extends('layouts.app') {{-- Assuming a base layout --}}

@section('content')
<div class="container">
    <h1>Complete Your Payment</h1>
    <p>Reservation #{{ $reservation->id }} - Amount: {{ $transaction->amount }} {{ $transaction->currency }}</p>

    <div id="payment-form">
        <div id="card-element">
            {{-- A Stripe Element will be inserted here. --}}
        </div>

        {{-- Used to display form errors. --}}
        <div id="card-errors" role="alert" class="text-danger mt-3"></div>

        <button id="submit-button" class="btn btn-primary mt-4">Submit Payment</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ config('services.stripe.key') }}'); // Your Stripe publishable key
    const clientSecret = '{{ $clientSecret }}'; // Passed from the backend

    const elements = stripe.elements();
    const card = elements.create('card');
    card.mount('#card-element');

    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const cardErrors = document.getElementById('card-errors');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        submitButton.disabled = true;

        const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: card,
            }
        });

        if (error) {
            cardErrors.textContent = error.message;
            submitButton.disabled = false;
        } else {
            // The payment has been processed!
            if (paymentIntent.status === 'succeeded') {
                // Show a success message to your customer
                // There's a risk of the customer closing the browser before this page is displayed.
                // We recommend you use webhooks to listen for the payment_intent.succeeded event.
                @if (Route::has('payment.success'))
                    window.location.href = '{{ route('payment.success') }}';
                @else
                    // Fallback or alternative action if payment.success route is not defined
                    window.location.href = "/"; // Redirect to home or a generic success page
                @endif
            } else {
                // Handle other statuses, e.g., 'requires_action', 'requires_confirmation'
                // For simplicity, we'll treat anything not 'succeeded' as a failure for now.
                window.location.href = '{{ route('payment.fail') }}';
            }
        }
    });

    // Handle real-time validation errors from the card Element.
    card.addEventListener('change', function(event) {
        if (event.error) {
            cardErrors.textContent = event.error.message;
        } else {
            cardErrors.textContent = '';
        }
        submitButton.disabled = !event.complete;
    });
</script>
@endpush