@extends('layouts.app')

@section('title', 'Gestion des Demandes de Crédit')
@section('page-title', 'Gestion des Demandes de Crédit')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Demandes de Crédit en Attente</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Approuvez ou rejetez les demandes de crédit des clients</p>
</div>

<!-- Messages d'alerte -->
@if(session('success'))
<div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
    {{ session('error') }}
</div>
@endif

<!-- Filtres -->
<div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label for="filter_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Statut
            </label>
            <select id="filter_status" onchange="applyFilters()"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                <option value="">En attente uniquement</option>
                <option value="pending" {{ (request('status') == 'pending') ? 'selected' : '' }}>En attente</option>
                <option value="approved" {{ (request('status') == 'approved') ? 'selected' : '' }}>Approuvées</option>
                <option value="rejected" {{ (request('status') == 'rejected') ? 'selected' : '' }}>Rejetées</option>
                <option value="cancelled" {{ (request('status') == 'cancelled') ? 'selected' : '' }}>Annulées</option>
                <option value="all" {{ (request('status') == 'all') ? 'selected' : '' }}>Toutes</option>
            </select>
        </div>

        <div>
            <label for="filter_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Client
            </label>
            <select id="filter_user_id" onchange="applyFilters()"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                <option value="">Tous les clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ (request('user_id') == $client->id) ? 'selected' : '' }}>
                        {{ $client->name }} ({{ $client->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Type
            </label>
            <select id="filter_type" onchange="applyFilters()"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                <option value="">Tous les types</option>
                <option value="manuel" {{ (request('type') == 'manuel') ? 'selected' : '' }}>Manuel</option>
                <option value="bonus" {{ (request('type') == 'bonus') ? 'selected' : '' }}>Bonus</option>
                <option value="automatique" {{ (request('type') == 'automatique') ? 'selected' : '' }}>Automatique</option>
            </select>
        </div>

        <div>
            <label for="filter_date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Date début
            </label>
            <input type="date" id="filter_date_from" onchange="applyFilters()"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                value="{{ request('date_from') }}">
        </div>

        <div>
            <label for="filter_date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Date fin
            </label>
            <input type="date" id="filter_date_to" onchange="applyFilters()"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                value="{{ request('date_to') }}">
        </div>
    </div>
</div>

<!-- Statistiques -->
@php
    $pendingCount = \App\Models\CreditRequest::where('status', 'pending')->count();
    $totalAmount = \App\Models\CreditRequest::where('status', 'pending')->sum('amount');
@endphp
<div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">En attente</p>
        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
            {{ $pendingCount }}
        </p>
    </div>
    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">Total demandé</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
            {{ number_format($totalAmount, 2, ',', ' ') }} EUR
        </p>
    </div>
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <p class="text-sm text-gray-600 dark:text-gray-400">Nombre de demandes</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {{ $requests->total() }}
        </p>
    </div>
</div>

<!-- Tableau des demandes -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Client
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Montant
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Description
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Statut
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($requests as $request)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $request->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div>
                                <div class="font-medium">{{ $request->user->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $request->user->email ?? '' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                            {{ number_format($request->amount, 2, ',', ' ') }} {{ $request->currency }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                {{ ucfirst($request->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $request->description ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($request->status === 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                    En attente
                                </span>
                            @elseif($request->status === 'approved')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    Approuvée
                                </span>
                            @elseif($request->status === 'rejected')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                    Rejetée
                                </span>
                            @elseif($request->status === 'cancelled')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    Annulée
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            @if($request->status === 'pending')
                                <button onclick="approveRequest({{ $request->id }})"
                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 font-medium">
                                    Approuver
                                </button>
                                <button onclick="showRejectModal({{ $request->id }})"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-medium">
                                    Rejeter
                                </button>
                            @else
                                <span class="text-gray-400 dark:text-gray-500 text-xs">Aucune action</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            Aucune demande de crédit trouvée.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($requests->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $requests->links() }}
        </div>
    @endif
</div>

<!-- Modal de rejet -->
<div id="reject-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Rejeter la demande</h3>
            <form id="reject-form">
                <input type="hidden" id="reject-request-id" name="request_id">
                <div class="mb-4">
                    <label for="rejection-reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Raison du rejet (optionnel)
                    </label>
                    <textarea id="rejection-reason" name="reason" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        placeholder="Expliquez pourquoi cette demande est rejetée"></textarea>
                </div>
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Rejeter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const params = new URLSearchParams();
    
    const status = document.getElementById('filter_status').value;
    const userId = document.getElementById('filter_user_id').value;
    const type = document.getElementById('filter_type').value;
    const dateFrom = document.getElementById('filter_date_from').value;
    const dateTo = document.getElementById('filter_date_to').value;
    
    if (status && status !== 'all') params.append('status', status);
    if (userId) params.append('user_id', userId);
    if (type) params.append('type', type);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    window.location.href = '{{ route("admin.credit-requests.index") }}?' + params.toString();
}

function approveRequest(requestId) {
    if (!confirm('Êtes-vous sûr de vouloir approuver cette demande ? Le crédit sera automatiquement ajouté au wallet du client.')) {
        return;
    }

    const url = `{{ url('admin/credit-requests') }}/${requestId}/approve`;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            window.location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Erreur: ' + (error.message || 'Une erreur est survenue'));
    });
}

function showRejectModal(requestId) {
    document.getElementById('reject-request-id').value = requestId;
    document.getElementById('reject-modal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.add('hidden');
    document.getElementById('reject-form').reset();
}

// Gestion du formulaire de rejet
document.getElementById('reject-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const requestId = formData.get('request_id');
    const reason = formData.get('reason');
    
    const url = `{{ url('admin/credit-requests') }}/${requestId}/reject`;
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reason: reason }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ ' + data.message);
            closeRejectModal();
            window.location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('❌ Erreur: ' + (error.message || 'Une erreur est survenue'));
    }
});
</script>
@endsection

