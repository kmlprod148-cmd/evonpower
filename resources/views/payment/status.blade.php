@extends('layouts.public')

@section('title', 'Statut du paiement')

@push('styles')
<style>
    body {
        background-color: #f8fafc;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .status-container {
        max-width: 600px;
        margin: 2rem auto;
        padding: 2rem;
    }
    
    .status-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        padding: 3rem;
        text-align: center;
    }
    
    .status-icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
    }
    
    .status-success {
        color: #10b981;
    }
    
    .status-error {
        color: #ef4444;
    }
    
    .status-pending {
        color: #f59e0b;
    }
    
    .btn-primary {
        background: linear-gradient(to right, #3b82f6, #1d4ed8);
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
</style>
@endpush

@section('content')
<div class="status-container">
    <div class="status-card">
        @if($status === 'success')
            <div class="status-icon status-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Paiement réussi !</h1>
            <p class="text-lg text-gray-600 mb-6">{{ $message }}</p>
            <div class="space-y-4">
                <a href="{{ route('dashboard') }}" class="btn-primary">
                    <i class="fas fa-home mr-2"></i>
                    Retour au tableau de bord
                </a>
                <div class="text-sm text-gray-500">
                    Vous recevrez un email de confirmation sous peu.
                </div>
            </div>
        @elseif($status === 'fail')
            <div class="status-icon status-error">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Paiement échoué</h1>
            <p class="text-lg text-gray-600 mb-6">{{ $message }}</p>
            <div class="space-y-4">
                <a href="{{ url()->previous() }}" class="btn-primary">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Réessayer
                </a>
                <div class="text-sm text-gray-500">
                    Si le problème persiste, contactez notre support.
                </div>
            </div>
        @else
            <div class="status-icon status-pending">
                <i class="fas fa-clock"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Paiement en cours</h1>
            <p class="text-lg text-gray-600 mb-6">{{ $message }}</p>
            <div class="text-sm text-gray-500">
                Veuillez patienter pendant que nous traitons votre paiement...
            </div>
        @endif
    </div>
</div>
@endsection