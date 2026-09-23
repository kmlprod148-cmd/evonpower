@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex items-center mb-4">
                    <a href="{{ route('charging-points.show', $chargingPoint->id) }}" class="mr-4 text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-xl font-medium text-gray-900">QR Code pour {{ $chargingPoint->name }}</h1>
                </div>

                <div class="flex justify-center mt-8">
                    @if(isset($qrCodeUrl))
                        <img src="{{ $qrCodeUrl }}" alt="QR Code for {{ $chargingPoint->name }}" class="w-64 h-64">
                    @else
                        <p class="text-red-500">Impossible de générer le QR Code.</p>
                    @endif
                </div>

                <div class="flex justify-center mt-6">
                    <a href="{{ $qrCodeUrl }}" download="{{ 'qr_code_' . $chargingPoint->serial_number . '.svg' }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Télécharger le QR Code
                    </a>
                </div>

                <div class="flex justify-center mt-4">
                    <span class="text-xs text-gray-500 break-all">{{ $offerUrl }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection