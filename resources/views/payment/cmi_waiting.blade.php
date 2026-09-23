@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Waiting for Payment Confirmation</h1>
    <p>Please wait while we confirm your payment. This may take a few moments.</p>
    <div id="status">
        <p>Current Status: <span id="payment-status">Pending</span></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const orderId = "{{ $orderId }}";
        const statusElement = document.getElementById('payment-status');
        let intervalId = setInterval(function () {
            fetch(`/payment/status/${orderId}`)
                .then(response => response.json())
                .then(data => {
                    statusElement.textContent = data.status;
                    if (data.status === 'success') {
                        clearInterval(intervalId);
                        @if (Route::has('payment.success'))
                            window.location.href = "{{ route('payment.success') }}";
                        @else
                            // Fallback or alternative action if payment.success route is not defined
                            window.location.href = "/"; // Redirect to home or a generic success page
                        @endif
                    } else if (data.status === 'failed') {
                        clearInterval(intervalId);
                        window.location.href = "{{ route('payment.fail') }}";
                    }
                })
                .catch(error => {
                    console.error('Error fetching payment status:', error);
                });
        }, 3000); // Poll every 3 seconds
    });
</script>
@endsection