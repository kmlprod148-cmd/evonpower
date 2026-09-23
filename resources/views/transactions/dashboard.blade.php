@extends('layouts.app')

@section('title', 'Dashboard des Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-chart-pie me-2"></i>
                            Dashboard des Transactions
                        </h1>
                        <p class="page-subtitle text-muted">
                            Analysez vos performances avec des statistiques détaillées et des graphiques
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('transactions.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i>
                            Historique
                        </a>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="fas fa-download me-2"></i>
                            Exporter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Composant Livewire pour le dashboard -->
    <livewire:transaction-dashboard />

    <!-- Composant Livewire pour l'export -->
    <livewire:transaction-export />
</div>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    margin: 0;
}

.page-subtitle {
    font-size: 1.1rem;
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.container-fluid {
    padding: 1rem;
}

@media (max-width: 768px) {
    .page-header {
        padding: 1rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
@endsection
