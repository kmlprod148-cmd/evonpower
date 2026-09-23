@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <!-- Header -->
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.transactions.index') }}" class="mr-3 text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <p class="text-gray-600">Transactions</p>
            <h1 class="text-2xl font-bold">Instructions de Traitement des Transactions</h1>
        </div>
    </div>

    @if(isset($processingInstructions))
    <!-- Transaction Details -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <h2 class="text-lg font-semibold mb-4">Détails de la Transaction</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">ID Transaction</label>
                    <p class="text-lg font-semibold">{{ $processingInstructions['transaction_id'] }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Montant Total</label>
                    <p class="text-lg font-semibold">{{ number_format($processingInstructions['amount'], 2) }} {{ $processingInstructions['currency'] }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre d'étapes</label>
                    <p class="text-lg font-semibold">{{ count($processingInstructions['processing_steps']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Processing Steps -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h2 class="text-lg font-semibold mb-4">Étapes de Traitement</h2>
            
            @foreach($processingInstructions['processing_steps'] as $index => $step)
            <div class="mb-6 p-4 border border-gray-200 rounded-lg {{ $index % 2 == 0 ? 'bg-gray-50' : 'bg-white' }}">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-md font-semibold text-gray-900">{{ $step['description'] }}</h3>
                        <p class="text-sm text-gray-600">{{ $step['action'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-green-600">{{ number_format($step['amount'], 2) }} {{ $processingInstructions['currency'] }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst($step['handler']['type']) }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Handler Information -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Responsable</h4>
                        <div class="text-sm text-gray-600">
                            <p><strong>Business Profile:</strong> {{ $step['handler']['business_profile_name'] }}</p>
                            @if(isset($step['contact']))
                            <p><strong>Contact:</strong> {{ $step['contact']['name'] ?? 'N/A' }}</p>
                            <p><strong>Email:</strong> {{ $step['contact']['email'] ?? 'N/A' }}</p>
                            <p><strong>Téléphone:</strong> {{ $step['contact']['phone'] ?? 'N/A' }}</p>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Bank Account Information -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Compte Bancaire</h4>
                        <div class="text-sm text-gray-600">
                            @if(isset($step['bank_account']))
                            <p class="font-mono bg-gray-100 p-2 rounded">{{ $step['bank_account'] }}</p>
                            @else
                            <p class="text-gray-500">Non spécifié</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                @if(isset($step['limits']))
                <!-- Transaction Limits -->
                <div class="mt-4 p-3 bg-yellow-50 rounded border border-yellow-200">
                    <h4 class="text-sm font-medium text-yellow-800 mb-2">Limites de Transaction</h4>
                    <div class="grid grid-cols-2 gap-2 text-xs text-yellow-700">
                        @if($step['limits']['min_amount'])
                        <p><strong>Min:</strong> {{ number_format($step['limits']['min_amount'], 2) }} EUR</p>
                        @endif
                        @if($step['limits']['max_amount'])
                        <p><strong>Max:</strong> {{ number_format($step['limits']['max_amount'], 2) }} EUR</p>
                        @endif
                        @if($step['limits']['daily_limit'])
                        <p><strong>Quotidien:</strong> {{ number_format($step['limits']['daily_limit'], 2) }} EUR</p>
                        @endif
                        @if($step['limits']['monthly_limit'])
                        <p><strong>Mensuel:</strong> {{ number_format($step['limits']['monthly_limit'], 2) }} EUR</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @else
    <!-- No Instructions Available -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 text-center">
            <div class="text-gray-400 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune instruction disponible</h3>
            <p class="text-gray-600">Les instructions de traitement ne sont pas encore générées pour cette transaction.</p>
        </div>
    </div>
    @endif
</div>
@endsection
