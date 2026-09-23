@extends('layouts.app')

@section('title', 'Toutes les Recharges de Crédit')

@section('content')
<div class="evon-page-header">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="evon-page-title">Toutes les Recharges de Crédit</h1>
            <p class="evon-page-subtitle">Gestion complète des recharges clients</p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.credit-recharges.dashboard') }}" 
               class="btn-secondary">
                ← Dashboard
            </a>
            <a href="{{ route('admin.credit-recharges.pending') }}" 
               class="btn-primary flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $stats['pending'] }} En Attente
            </a>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">Total</span>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</span>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-yellow-600 dark:text-yellow-400">En Attente</span>
            <span class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] }}</span>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-green-600 dark:text-green-400">Complétées</span>
            <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</span>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-red-600 dark:text-red-400">Échouées</span>
            <span class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['failed'] }}</span>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <form method="GET" action="{{ route('admin.credit-recharges.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
            <select name="status" class="input-field">
                <option value="">Tous</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complétée</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échouée</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Méthode</label>
            <select name="payment_method" class="input-field">
                <option value="">Toutes</option>
                <option value="offline" {{ request('payment_method') === 'offline' ? 'selected' : '' }}>Hors ligne</option>
                <option value="cmi" {{ request('payment_method') === 'cmi' ? 'selected' : '' }}>CMI</option>
                <option value="stripe" {{ request('payment_method') === 'stripe' ? 'selected' : '' }}>Stripe</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Début</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="input-field">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Fin</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="input-field">
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-primary flex-1">Filtrer</button>
            <a href="{{ route('admin.credit-recharges.index') }}" class="btn-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Recharges Table -->
<div class="card">
    <div class="evon-table-wrapper">
        <table class="evon-table">
            <thead class="evon-table-head">
                <tr>
                    <th class="evon-table-head-cell">Référence</th>
                    <th class="evon-table-head-cell">Client</th>
                    <th class="evon-table-head-cell">Montant</th>
                    <th class="evon-table-head-cell">Méthode</th>
                    <th class="evon-table-head-cell">Statut</th>
                    <th class="evon-table-head-cell">Date</th>
                    <th class="evon-table-head-cell">Actions</th>
                </tr>
            </thead>
            <tbody class="evon-table-body">
                @forelse($recharges as $recharge)
                <tr class="evon-table-row">
                    <td class="evon-table-cell">
                        <span class="font-mono text-xs">{{ $recharge->reference }}</span>
                    </td>
                    <td class="evon-table-cell">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $recharge->user->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $recharge->user->email ?? '' }}</p>
                        </div>
                    </td>
                    <td class="evon-table-cell">
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ number_format($recharge->amount, 2) }} {{ $recharge->currency }}
                        </span>
                    </td>
                    <td class="evon-table-cell">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            {{ $recharge->payment_method === 'offline' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                            {{ $recharge->payment_method === 'cmi' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                            {{ $recharge->payment_method === 'stripe' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : '' }}">
                            {{ strtoupper($recharge->payment_method) }}
                        </span>
                    </td>
                    <td class="evon-table-cell">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $recharge->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                            {{ $recharge->status === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                            {{ $recharge->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                            {{ ucfirst($recharge->status) }}
                        </span>
                    </td>
                    <td class="evon-table-cell">
                        <div>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $recharge->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $recharge->created_at->format('H:i') }}</p>
                        </div>
                    </td>
                    <td class="evon-table-cell">
                        <a href="{{ route('admin.credit-recharges.show', $recharge) }}" 
                           class="inline-flex items-center gap-1 text-sm font-medium text-eco-green-600 hover:text-eco-green-700">
                            Détails
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="evon-table-cell text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aucune recharge trouvée</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($recharges->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $recharges->links() }}
    </div>
    @endif
</div>
@endsection
