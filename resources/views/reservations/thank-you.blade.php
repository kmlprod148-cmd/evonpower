@extends('layouts.app')

@section('title', __('messages.reservation_confirmed') . ' - ' . __('messages.thank_you'))

@push('meta')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#10b981">
@endpush

@section('styles')
<link rel="stylesheet" href="{{ asset('css/reservations-thank-you.css') }}">
@endsection

@php
    $statusData = $thankYouStatus ?? null;
    $payment = $statusData['payment'] ?? [];
    $remoteStart = $statusData['remote_start'] ?? [];
    $paymentConfirmed = (bool) ($payment['confirmed'] ?? false);
    $paymentLabel = $paymentConfirmed ? 'Payment confirmed' : 'Waiting for Stripe webhook';
    $paymentBadgeClass = $paymentConfirmed
        ? 'bg-emerald-100 text-emerald-800'
        : 'bg-amber-100 text-amber-800';

    $remoteStatus = $remoteStart['status'] ?? 'awaiting_payment';
    $remoteMeta = [
        'awaiting_payment' => ['label' => 'Awaiting payment', 'class' => 'bg-amber-100 text-amber-800'],
        'awaiting_approval' => ['label' => 'Awaiting approval', 'class' => 'bg-amber-100 text-amber-800'],
        'queued' => ['label' => 'Queued for remote start', 'class' => 'bg-sky-100 text-sky-800'],
        'scheduled' => ['label' => 'Scheduled', 'class' => 'bg-indigo-100 text-indigo-800'],
        'processing' => ['label' => 'Starting charging point', 'class' => 'bg-sky-100 text-sky-800'],
        'success' => ['label' => 'Charging point started', 'class' => 'bg-emerald-100 text-emerald-800'],
        'failed' => ['label' => 'Remote start failed', 'class' => 'bg-rose-100 text-rose-800'],
    ];
    $remoteBadge = $remoteMeta[$remoteStatus] ?? ['label' => 'Updating', 'class' => 'bg-slate-100 text-slate-800'];
@endphp

@section('content')
<div class="min-h-screen gradient-bg safe-area-top safe-area-bottom overflow-x-hidden">
    <div class="container mx-auto px-4 py-8 safe-area-left safe-area-right">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-8">
                @php
                    $logoPath = file_exists(public_path('images/evon-logo.png')) ? asset('images/evon-logo.png') : (file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : null);
                @endphp

                @if($logoPath)
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-xl rounded-2xl p-3 shadow-2xl border border-white/30 mb-6">
                        <img src="{{ $logoPath }}" alt="EVON Logo" class="h-full w-auto object-contain">
                    </div>
                @endif

                <h1 class="text-4xl md:text-5xl font-bold mb-3 {{ isset($reservation_not_found) && $reservation_not_found ? 'text-amber-600' : 'text-green-600' }}">
                    {{ isset($reservation_not_found) && $reservation_not_found ? __('messages.reservation_not_found_title') : __('messages.thank_you') }}
                </h1>
                <p class="text-lg md:text-xl text-green-50 max-w-3xl mx-auto">
                    @if(isset($reservation_not_found) && $reservation_not_found)
                        {{ __('messages.reservation_not_found_message', ['id' => $reservation_id_requested ?? '']) }}
                    @elseif(isset($reservation) && $reservation)
                        Your checkout has been received. This page tracks Stripe confirmation and the SteVe remote start in real time.
                    @else
                        {{ __('messages.reservation_created_success') }}
                    @endif
                </p>
            </div>

            @if(session('success'))
                <div class="glass-effect rounded-2xl p-5 mb-6 bg-green-50 border border-green-200">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-600 text-xl mt-0.5"></i>
                        <p class="text-green-800 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(isset($reservation_not_found) && $reservation_not_found)
                <div class="glass-effect rounded-3xl p-8 bg-white/95">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-semibold text-slate-900 mb-2">Reservation not available</h2>
                            <p class="text-slate-600">
                                The reservation details could not be loaded yet. If you have just completed a payment, refresh this page in a few seconds.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif(isset($reservation) && $reservation)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="glass-effect rounded-3xl p-6 bg-white/95">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Stripe confirmation</p>
                                <h2 class="text-2xl font-semibold text-slate-900 mt-1" id="payment-status-label">{{ $paymentLabel }}</h2>
                            </div>
                            <span id="payment-status-chip" class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $paymentBadgeClass }}">
                                {{ $paymentConfirmed ? 'Confirmed' : 'Pending' }}
                            </span>
                        </div>
                        <p class="text-slate-600" id="payment-status-message">
                            @if($paymentConfirmed)
                                Stripe has confirmed the payment and the reservation has been marked as paid.
                            @else
                                We are waiting for the webhook confirmation from Stripe before starting the charging flow.
                            @endif
                        </p>
                        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500 mb-1">Amount</p>
                                <p class="font-semibold text-slate-900" id="payment-amount">
                                    {{ number_format((float) ($payment['amount'] ?? $reservation->estimated_cost ?? 0), 2) }} {{ $payment['currency'] ?? 'EUR' }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500 mb-1">Gateway reference</p>
                                <p class="font-semibold text-slate-900 break-all" id="gateway-transaction-id">
                                    {{ $payment['gateway_transaction_id'] ?? $payment['stripe_session_id'] ?? 'Pending' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="glass-effect rounded-3xl p-6 bg-white/95">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <p class="text-sm uppercase tracking-[0.2em] text-slate-500">SteVe remote start</p>
                                <h2 class="text-2xl font-semibold text-slate-900 mt-1" id="remote-start-label">{{ $remoteBadge['label'] }}</h2>
                            </div>
                            <span id="remote-start-chip" class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $remoteBadge['class'] }}">
                                {{ ucfirst(str_replace('_', ' ', $remoteStatus)) }}
                            </span>
                        </div>
                        <p class="text-slate-600" id="remote-start-message">
                            {{ $remoteStart['message'] ?? 'The remote start status will appear here as soon as the webhook has been processed.' }}
                        </p>
                        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500 mb-1">Charging session</p>
                                <p class="font-semibold text-slate-900 break-all" id="charging-session-reference">
                                    {{ $remoteStart['charging_session_uuid'] ?? ($remoteStart['charging_session_id'] ?? 'Not created yet') }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-slate-500 mb-1">SteVe transaction</p>
                                <p class="font-semibold text-slate-900 break-all" id="steve-transaction-id">
                                    {{ $remoteStart['steve_transaction_id'] ?? 'Pending' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-effect rounded-3xl p-6 md:p-8 mb-6 bg-white/95">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div>
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Reservation summary</p>
                            <h2 class="text-2xl font-semibold text-slate-900 mt-1">
                                #{{ $reservation->id }} · {{ $reservation->chargingPoint?->name ?? 'Charging point' }}
                            </h2>
                        </div>
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800" id="reservation-display-status">
                            {{ $statusData['display_status'] ?? $reservation->getDisplayStatusLabel() }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-slate-500 mb-1">Start time</p>
                            <p class="font-semibold text-slate-900">
                                {{ $reservation->start_time ? $reservation->start_time->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-slate-500 mb-1">Station</p>
                            <p class="font-semibold text-slate-900">
                                {{ $reservation->chargingPoint?->station?->name ?? ($reservation->chargingPoint?->city ?? 'N/A') }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-slate-500 mb-1">Estimated total</p>
                            <p class="font-semibold text-slate-900">
                                {{ number_format((float) ($reservation->estimated_cost ?? $reservation->amount ?? 0), 2) }} EUR
                            </p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-slate-500 mb-1">Transaction status</p>
                            <p class="font-semibold text-slate-900" id="transaction-status">
                                {{ strtoupper((string) ($payment['transaction_status'] ?? 'pending')) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div id="remote-start-error-panel" class="{{ empty($remoteStart['last_error']) ? 'hidden' : '' }} glass-effect rounded-3xl p-6 mb-6 bg-rose-50 border border-rose-200">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-triangle-exclamation text-rose-600 text-xl mt-0.5"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-rose-900 mb-1">Remote start requires attention</h3>
                            <p class="text-rose-800" id="remote-start-error">{{ $remoteStart['last_error'] ?? '' }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass-effect rounded-3xl p-6 bg-white/95 mb-6">
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">What happens next</h3>
                    <div class="space-y-3 text-slate-700" id="next-steps-copy">
                        <p>1. Stripe confirms the payment through the webhook.</p>
                        <p>2. EVON marks the reservation as paid and queues the remote start.</p>
                        <p>3. SteVe sends the remote start command to the charging point and this page updates automatically.</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    @auth
                        <a href="{{ route('reservations.show', $reservation->id) }}"
                           class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-2xl transition-all duration-200 text-center">
                            View Reservation
                        </a>
                        <a href="{{ route('reservations.index') }}"
                           class="flex-1 bg-slate-700 hover:bg-slate-800 text-white font-semibold py-4 px-6 rounded-2xl transition-all duration-200 text-center">
                            My Reservations
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-2xl transition-all duration-200 text-center">
                            Sign In To Follow The Reservation
                        </a>
                    @endauth
                </div>
            @else
                <div class="glass-effect rounded-3xl p-8 bg-white/95">
                    <p class="text-slate-700">{{ __('messages.reservation_created_success') }}</p>
                </div>
            @endif

            <div class="text-center mt-8">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center text-gray-700 hover:text-gray-900 transition-colors duration-200 px-4 py-2 rounded-xl">
                    <i class="fas fa-home mr-2"></i>
                    <span>{{ __('messages.back_to_home') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(isset($reservation) && $reservation && $statusData)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const statusEndpoint = @json(route('reservations.thank-you.status', $reservation->id));
        const stripeSessionId = @json(request('session_id'));
        let currentStatus = @json($statusData);
        let pollTimer = null;

        const remoteMeta = {
            awaiting_payment: { label: 'Awaiting payment', chip: 'Awaiting payment', className: 'bg-amber-100 text-amber-800' },
            awaiting_approval: { label: 'Awaiting approval', chip: 'Awaiting approval', className: 'bg-amber-100 text-amber-800' },
            queued: { label: 'Queued for remote start', chip: 'Queued', className: 'bg-sky-100 text-sky-800' },
            scheduled: { label: 'Scheduled', chip: 'Scheduled', className: 'bg-indigo-100 text-indigo-800' },
            processing: { label: 'Starting charging point', chip: 'Processing', className: 'bg-sky-100 text-sky-800' },
            success: { label: 'Charging point started', chip: 'Started', className: 'bg-emerald-100 text-emerald-800' },
            failed: { label: 'Remote start failed', chip: 'Failed', className: 'bg-rose-100 text-rose-800' },
        };

        function formatMoney(amount, currency) {
            const safeAmount = Number(amount || 0).toFixed(2);
            return `${safeAmount} ${currency || 'EUR'}`;
        }

        function setChipClasses(element, className) {
            element.className = `inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium ${className}`;
        }

        function renderStatus(payload) {
            currentStatus = payload;

            const paymentConfirmed = Boolean(payload.payment && payload.payment.confirmed);
            const paymentChip = document.getElementById('payment-status-chip');
            const paymentLabel = document.getElementById('payment-status-label');
            const paymentMessage = document.getElementById('payment-status-message');
            const paymentAmount = document.getElementById('payment-amount');
            const gatewayTransactionId = document.getElementById('gateway-transaction-id');
            const transactionStatus = document.getElementById('transaction-status');
            const reservationDisplayStatus = document.getElementById('reservation-display-status');
            const remoteChip = document.getElementById('remote-start-chip');
            const remoteLabel = document.getElementById('remote-start-label');
            const remoteMessage = document.getElementById('remote-start-message');
            const sessionReference = document.getElementById('charging-session-reference');
            const steveTransactionId = document.getElementById('steve-transaction-id');
            const errorPanel = document.getElementById('remote-start-error-panel');
            const errorText = document.getElementById('remote-start-error');

            paymentLabel.textContent = paymentConfirmed ? 'Payment confirmed' : 'Waiting for Stripe webhook';
            paymentMessage.textContent = paymentConfirmed
                ? 'Stripe has confirmed the payment and the reservation has been marked as paid.'
                : 'We are waiting for the webhook confirmation from Stripe before starting the charging flow.';
            setChipClasses(paymentChip, paymentConfirmed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800');
            paymentChip.textContent = paymentConfirmed ? 'Confirmed' : 'Pending';

            paymentAmount.textContent = formatMoney(payload.payment?.amount, payload.payment?.currency);
            gatewayTransactionId.textContent = payload.payment?.gateway_transaction_id || payload.payment?.stripe_session_id || 'Pending';
            transactionStatus.textContent = String(payload.payment?.transaction_status || 'pending').toUpperCase();
            reservationDisplayStatus.textContent = payload.display_status || reservationDisplayStatus.textContent;

            const remoteState = payload.remote_start?.status || 'awaiting_payment';
            const remoteConfig = remoteMeta[remoteState] || { label: 'Updating', chip: 'Updating', className: 'bg-slate-100 text-slate-800' };
            remoteLabel.textContent = remoteConfig.label;
            remoteMessage.textContent = payload.remote_start?.message || 'The remote start status will appear here as soon as the webhook has been processed.';
            setChipClasses(remoteChip, remoteConfig.className);
            remoteChip.textContent = remoteConfig.chip;

            sessionReference.textContent = payload.remote_start?.charging_session_uuid
                || payload.remote_start?.charging_session_id
                || 'Not created yet';
            steveTransactionId.textContent = payload.remote_start?.steve_transaction_id || 'Pending';

            if (payload.remote_start?.last_error) {
                errorPanel.classList.remove('hidden');
                errorText.textContent = payload.remote_start.last_error;
            } else {
                errorPanel.classList.add('hidden');
                errorText.textContent = '';
            }

            if (!payload.should_poll && pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        }

        async function fetchStatus() {
            const url = new URL(statusEndpoint, window.location.origin);
            if (stripeSessionId) {
                url.searchParams.set('session_id', stripeSessionId);
            }

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                if (data.success && data.status) {
                    renderStatus(data.status);
                }
            } catch (error) {
                console.warn('Unable to refresh thank-you status', error);
            }
        }

        renderStatus(currentStatus);

        if (currentStatus.should_poll) {
            pollTimer = setInterval(fetchStatus, 3000);
            fetchStatus();
        }
    });
</script>
@endif
@endpush
