@extends('layouts.guest')

@section('title', __('Payment Confirmed'))

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4">
        
        <!-- Success Icon -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __('Payment Confirmed!') }}</h1>
            <p class="text-gray-600">{{ __('Your charging session has been authorized.') }}</p>
        </div>

        <!-- Receipt Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Session Details') }}</h2>
            
            <div class="space-y-4">
                <!-- Charge Point -->
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">{{ __('Charge Point') }}</span>
                    <span class="font-medium text-gray-900">{{ $checkoutSession->chargePoint->name ?? '-' }}</span>
                </div>
                
                <!-- Address -->
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">{{ __('Location') }}</span>
                    <span class="font-medium text-gray-900 text-right">
                        {{ $checkoutSession->chargePoint->address ?? '' }}
                        {{ $checkoutSession->chargePoint->city ? ', ' . $checkoutSession->chargePoint->city : '' }}
                    </span>
                </div>
                
                <!-- Partner -->
                @if($checkoutSession->chargePoint->partner)
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">{{ __('Partner') }}</span>
                    <span class="font-medium text-gray-900">{{ $checkoutSession->chargePoint->partner->name }}</span>
                </div>
                @endif
                
                <!-- Duration -->
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">{{ __('Duration Authorized') }}</span>
                    <span class="font-medium text-gray-900">{{ $checkoutSession->duration_minutes }} min</span>
                </div>
                
                <!-- Estimated kWh -->
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">{{ __('Estimated Consumption') }}</span>
                    <span class="font-medium text-gray-900">{{ $checkoutSession->estimated_energy }} kWh</span>
                </div>
                
                <!-- Start Time -->
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">{{ __('Start Time') }}</span>
                    <span class="font-medium text-gray-900">{{ $checkoutSession->start_time->format('d/m/Y H:i') }}</span>
                </div>
                
                <!-- Transaction ID -->
                @if($transaction)
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">{{ __('Transaction ID') }}</span>
                    <span class="font-medium text-gray-900 font-mono text-sm">{{ $transaction->id }}</span>
                </div>
                
                <!-- Amount Paid -->
                <div class="flex justify-between py-2">
                    <span class="font-semibold text-gray-900">{{ __('Amount Paid') }}</span>
                    <span class="font-bold text-green-600 text-xl">
                        {{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Important Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="font-medium text-blue-900">{{ __('Important') }}</h3>
                    <p class="text-sm text-blue-700 mt-1">
                        {{ __('Start charging within 15 minutes. After the session ends, any unused balance will be automatically refunded to your wallet.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Download Receipt -->
            <a href="{{ route('public.checkout.receipt.pdf', ['slug' => $slug, 'id' => $checkoutSession->id]) }}" 
                class="flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('Download Receipt') }}
            </a>
            
            <!-- Create Account (if guest) -->
            @if($checkoutSession->is_guest)
            <a href="{{ route('register') }}?email={{ urlencode($checkoutSession->guest_info['email'] ?? '') }}" 
                class="flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ __('Create Account') }}
            </a>
            @endif
        </div>

        <!-- Session ID for reference -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>{{ __('Session ID') }}: <span class="font-mono">{{ $checkoutSession->id }}</span></p>
            <p class="mt-1">{{ __('Please save this for your records.') }}</p>
        </div>

    </div>
</div>
@endsection
