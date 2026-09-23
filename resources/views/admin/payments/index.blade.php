@extends('layouts.app')

@section('title', 'Gestion des Paiements')

@push('styles')
<style>
    .pay-content { padding-bottom: 200px; min-height: 100vh; }
    .pay-header { 
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); 
        border-radius: 12px; 
        padding: 16px 20px; 
        margin-bottom: 16px; 
        color: white;
    }
    .pay-header-title { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .pay-header-sub { font-size: 0.75rem; opacity: 0.9; margin-top: 2px; }
    .pay-header-stats { display: flex; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
    .pay-header-stat { 
        background: rgba(255,255,255,0.15); 
        padding: 8px 14px; 
        border-radius: 8px; 
        text-align: center;
        min-width: 80px;
    }
    .pay-header-stat-value { font-size: 1.25rem; font-weight: 700; }
    .pay-header-stat-label { font-size: 0.65rem; opacity: 0.9; text-transform: uppercase; }
    .pay-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .pay-header-btn {
        background: rgba(255,255,255,0.2);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: background 0.2s;
        text-decoration: none;
        color: white;
        border: none;
        cursor: pointer;
    }
    .pay-header-btn:hover { background: rgba(255,255,255,0.3); }
    
    .pay-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 16px; }
    @media (max-width: 1200px) { .pay-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .pay-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px) { .pay-grid { grid-template-columns: 1fr; } }
    
    .pay-card {
        background: white;
        border-radius: 8px;
        padding: 10px 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dark .pay-card { background: #1f2937; border-color: #374151; }
    
    .pay-card-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .pay-card-icon svg, .pay-card-icon i { width: 16px; height: 16px; font-size: 14px; }
    .pay-card-content { flex: 1; min-width: 0; }
    .pay-card-label { font-size: 0.6rem; color: #6b7280; text-transform: uppercase; font-weight: 500; }
    .dark .pay-card-label { color: #9ca3af; }
    .pay-card-value { font-size: 1rem; font-weight: 700; color: #111827; }
    .dark .pay-card-value { color: #f9fafb; }
    
    .pay-filters {
        background: white;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .dark .pay-filters { background: #1f2937; border-color: #374151; }
    .pay-filters-grid { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; }
    .pay-filter-group { display: flex; flex-direction: column; min-width: 120px; flex: 1; }
    .pay-filter-label { font-size: 0.65rem; color: #6b7280; font-weight: 500; margin-bottom: 3px; }
    .dark .pay-filter-label { color: #9ca3af; }
    .pay-filter-input {
        padding: 6px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 0.75rem;
        background: white;
        color: #111827;
    }
    .dark .pay-filter-input { background: #111827; border-color: #374151; color: #f9fafb; }
    .pay-filter-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
    }
    .pay-filter-btn-primary { background: #3b82f6; color: white; }
    .pay-filter-btn-secondary { background: #e5e7eb; color: #374151; }
    .dark .pay-filter-btn-secondary { background: #374151; color: #9ca3af; }
    
    .pay-table-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 80px;
    }
    .dark .pay-table-card { background: #1f2937; border-color: #374151; }
    .pay-table-header {
        padding: 10px 14px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dark .pay-table-header { border-color: #374151; }
    .pay-table-title { font-size: 0.85rem; font-weight: 600; color: #111827; }
    .dark .pay-table-title { color: #f9fafb; }
    
    .pay-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .pay-table th, .pay-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f3f4f6; }
    .dark .pay-table th, .dark .pay-table td { border-color: #374151; }
    .pay-table th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.65rem; text-transform: uppercase; }
    .dark .pay-table th { background: #111827; color: #9ca3af; }
    .pay-table td { color: #374151; }
    .dark .pay-table td { color: #d1d5db; }
    .pay-table tr:hover { background: #f9fafb; }
    .dark .pay-table tr:hover { background: #111827; }
    
    .pay-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.6rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .pay-badge-pending { background: #fef3c7; color: #92400e; }
    .pay-badge-processing { background: #dbeafe; color: #1e40af; }
    .pay-badge-completed { background: #d1fae5; color: #065f46; }
    .pay-badge-failed { background: #fee2e2; color: #991b1b; }
    .pay-badge-cancelled { background: #f3f4f6; color: #374151; }
    .pay-badge-refunded { background: #fce7f3; color: #be185d; }
    .dark .pay-badge-pending { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    .dark .pay-badge-processing { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
    .dark .pay-badge-completed { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
    .dark .pay-badge-failed { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .dark .pay-badge-cancelled { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
    .dark .pay-badge-refunded { background: rgba(236, 72, 153, 0.2); color: #f9a8d4; }
    
    .pay-action-btn {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.65rem;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        text-decoration: none;
    }
    .pay-action-view { background: #3b82f6; color: white; }
    .pay-action-refund { background: #f59e0b; color: white; }
    .pay-action-edit { background: #10b981; color: white; }
    
    .pay-empty { text-align: center; padding: 30px; color: #6b7280; font-size: 0.8rem; }
    .dark .pay-empty { color: #9ca3af; }
    
    .pay-pagination { padding: 12px; display: flex; justify-content: center; }
</style>
@endpush

@section('content')
<div class="pay-content p-4">
    
    <!-- Compact Header -->
    <div class="pay-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="pay-header-title">
                    <i class="fas fa-credit-card"></i>
                    {{ __('Gestion des Paiements') }}
                </h1>
                <p class="pay-header-sub">{{ __('Surveillez et gérez tous les paiements') }}</p>
            </div>
            <div class="pay-header-actions">
                <button class="pay-header-btn" onclick="exportPayments()">
                    <i class="fas fa-download"></i>
                    {{ __('Exporter') }}
                </button>
            </div>
        </div>
        <div class="pay-header-stats">
            <div class="pay-header-stat">
                <div class="pay-header-stat-value">{{ $stats['total_payments'] }}</div>
                <div class="pay-header-stat-label">{{ __('Total') }}</div>
            </div>
            <div class="pay-header-stat">
                <div class="pay-header-stat-value">{{ $stats['completed_payments'] }}</div>
                <div class="pay-header-stat-label">{{ __('Réussis') }}</div>
            </div>
            <div class="pay-header-stat">
                <div class="pay-header-stat-value">{{ $stats['pending_payments'] }}</div>
                <div class="pay-header-stat-label">{{ __('Attente') }}</div>
            </div>
            <div class="pay-header-stat">
                <div class="pay-header-stat-value">{{ number_format($stats['total_amount'], 0) }}€</div>
                <div class="pay-header-stat-label">{{ __('Montant') }}</div>
            </div>
        </div>
    </div>

    <!-- Stats Cards (Half Size) -->
    <div class="pay-grid">
        <div class="pay-card">
            <div class="pay-card-icon" style="background: #dbeafe; color: #2563eb;">
                <i class="fas fa-list"></i>
            </div>
            <div class="pay-card-content">
                <div class="pay-card-label">{{ __('Total') }}</div>
                <div class="pay-card-value">{{ $stats['total_payments'] }}</div>
            </div>
        </div>
        
        <div class="pay-card">
            <div class="pay-card-icon" style="background: #d1fae5; color: #059669;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="pay-card-content">
                <div class="pay-card-label">{{ __('Réussis') }}</div>
                <div class="pay-card-value" style="color: #059669;">{{ $stats['completed_payments'] }}</div>
            </div>
        </div>
        
        <div class="pay-card">
            <div class="pay-card-icon" style="background: #fef3c7; color: #d97706;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="pay-card-content">
                <div class="pay-card-label">{{ __('Attente') }}</div>
                <div class="pay-card-value" style="color: #d97706;">{{ $stats['pending_payments'] }}</div>
            </div>
        </div>
        
        <div class="pay-card">
            <div class="pay-card-icon" style="background: #fee2e2; color: #dc2626;">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="pay-card-content">
                <div class="pay-card-label">{{ __('Échecs') }}</div>
                <div class="pay-card-value" style="color: #dc2626;">{{ $stats['failed_payments'] }}</div>
            </div>
        </div>
        
        <div class="pay-card">
            <div class="pay-card-icon" style="background: #d1fae5; color: #059669;">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="pay-card-content">
                <div class="pay-card-label">{{ __('Montant Total') }}</div>
                <div class="pay-card-value">{{ number_format($stats['total_amount'], 2) }}€</div>
            </div>
        </div>
        
        <div class="pay-card">
            <div class="pay-card-icon" style="background: #dbeafe; color: #2563eb;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="pay-card-content">
                <div class="pay-card-label">{{ __('Aujourd\'hui') }}</div>
                <div class="pay-card-value">{{ number_format($stats['today_amount'], 2) }}€</div>
            </div>
        </div>
    </div>

    <!-- Filters (Compact) -->
    <div class="pay-filters">
        <form method="GET" id="filters-form">
            <div class="pay-filters-grid">
                <div class="pay-filter-group">
                    <label class="pay-filter-label">{{ __('Statut') }}</label>
                    <select name="status" class="pay-filter-input">
                        <option value="">{{ __('Tous') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('En attente') }}</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>{{ __('En cours') }}</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('Terminé') }}</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>{{ __('Échec') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Annulé') }}</option>
                        <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>{{ __('Remboursé') }}</option>
                    </select>
                </div>

                <div class="pay-filter-group">
                    <label class="pay-filter-label">{{ __('Méthode') }}</label>
                    <select name="payment_method" class="pay-filter-input">
                        <option value="">{{ __('Toutes') }}</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ request('payment_method') == $method->id ? 'selected' : '' }}>
                                {{ $method->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pay-filter-group">
                    <label class="pay-filter-label">{{ __('Date début') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="pay-filter-input">
                </div>

                <div class="pay-filter-group">
                    <label class="pay-filter-label">{{ __('Date fin') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="pay-filter-input">
                </div>

                <div class="pay-filter-group">
                    <label class="pay-filter-label">{{ __('Recherche') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('ID, transaction...') }}" class="pay-filter-input">
                </div>

                <div class="flex gap-2" style="align-self: flex-end;">
                    <button type="submit" class="pay-filter-btn pay-filter-btn-primary">
                        <i class="fas fa-search"></i> {{ __('Filtrer') }}
                    </button>
                    <a href="{{ route('admin.payments.index') }}" class="pay-filter-btn pay-filter-btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Payments Table (Compact) -->
    <div class="pay-table-card">
        <div class="pay-table-header">
            <span class="pay-table-title">{{ __('Liste des Paiements') }}</span>
            <span style="font-size: 0.7rem; color: #6b7280;">{{ $payments->total() ?? 0 }} {{ __('résultats') }}</span>
        </div>

        <div style="overflow-x: auto;">
            <table class="pay-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('Transaction') }}</th>
                        <th>{{ __('Montant') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Méthode') }}</th>
                        <th>{{ __('Réservation') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td><strong>{{ $payment->id }}</strong></td>
                            <td>
                                <div style="font-family: monospace; font-size: 0.7rem;">{{ Str::limit($payment->transaction_id, 15) }}</div>
                                @if($payment->external_id)
                                    <div style="font-size: 0.6rem; color: #9ca3af;">{{ Str::limit($payment->external_id, 12) }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</strong>
                            </td>
                            <td>
                                <span class="pay-badge pay-badge-{{ $payment->status }}">{{ $payment->status }}</span>
                            </td>
                            <td>{{ $payment->paymentMethod->name ?? 'N/A' }}</td>
                            <td>
                                @if($payment->reservation_id)
                                <a href="{{ route('admin.reservations.show', $payment->reservation_id) }}" style="color: #3b82f6; text-decoration: none;">
                                    #{{ $payment->reservation_id }}
                                </a>
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                <div>{{ $payment->created_at->format('d/m/Y') }}</div>
                                <div style="font-size: 0.6rem; color: #9ca3af;">{{ $payment->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px;">
                                    <a href="{{ route('admin.payments.show', $payment->id) }}" class="pay-action-btn pay-action-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($payment->status === 'completed')
                                        <button onclick="refundPayment({{ $payment->id }})" class="pay-action-btn pay-action-refund">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    @endif
                                    <button onclick="updateStatus({{ $payment->id }})" class="pay-action-btn pay-action-edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="pay-empty">
                                <i class="fas fa-inbox" style="font-size: 1.5rem; margin-bottom: 8px; display: block;"></i>
                                {{ __('Aucun paiement trouvé') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="pay-pagination">
                {{ $payments->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function exportPayments() {
    const form = document.getElementById('filters-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    window.open(`{{ route('admin.payments.export') }}?${params.toString()}`, '_blank');
}

function refundPayment(paymentId) {
    if (confirm('{{ __("Êtes-vous sûr de vouloir rembourser ce paiement ?") }}')) {
        fetch(`/admin/payments/${paymentId}/refund`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('{{ __("Erreur lors du remboursement") }}');
        });
    }
}

function updateStatus(paymentId) {
    const newStatus = prompt('{{ __("Nouveau statut (pending, processing, completed, failed, cancelled, refunded):") }}');
    if (newStatus) {
        fetch(`/admin/payments/${paymentId}/status`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('{{ __("Erreur lors de la mise à jour") }}');
        });
    }
}
</script>
@endpush
@endsection
