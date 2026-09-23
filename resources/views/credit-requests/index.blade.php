@extends('layouts.app')

@section('title', 'Demande de Crédit')
@section('page-title', 'Demande de Crédit')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Demande de Crédit</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Demandez du crédit qui sera examiné par un administrateur</p>
</div>

<!-- Solde actuel -->
<div class="mb-6 bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm opacity-90">Solde actuel</p>
            <p class="text-3xl font-bold">{{ number_format($balance, 2, ',', ' ') }} EUR</p>
        </div>
        <svg class="w-16 h-16 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
</div>

<!-- Onglets -->
<div class="mb-6">
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('request')" id="tab-request" class="tab-button active border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Faire une demande
            </button>
            <button onclick="switchTab('history')" id="tab-history" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Mes demandes
            </button>
        </nav>
    </div>
</div>

<!-- Contenu de l'onglet "Faire une demande" -->
<div id="tab-content-request" class="tab-content">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Nouvelle demande de crédit</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            Remplissez le formulaire ci-dessous pour demander du crédit. Votre demande sera examinée par un administrateur.
        </p>
        
        <form id="credit-request-form" class="space-y-4">
            @csrf
            
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Montant demandé (EUR) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    placeholder="0.00">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Montant minimum : 0.01 EUR
                </p>
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Type de crédit <span class="text-red-500">*</span>
                </label>
                <select name="type" id="type" required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="manuel">Manuel</option>
                    <option value="bonus">Bonus</option>
                    <option value="automatique">Automatique</option>
                </select>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Description / Raison de la demande
                </label>
                <textarea name="description" id="description" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    placeholder="Expliquez la raison de votre demande de crédit (optionnel)"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <button type="button" onclick="resetForm()"
                    class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Réinitialiser
                </button>
                <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    Soumettre la demande
                </button>
            </div>
        </form>

        <!-- Message de résultat -->
        <div id="form-result" class="mt-4 hidden"></div>
    </div>
</div>

<!-- Contenu de l'onglet "Mes demandes" -->
<div id="tab-content-history" class="tab-content hidden">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Historique de mes demandes</h2>
        
        <!-- Filtres -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="filter_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Statut
                </label>
                <select id="filter_status" onchange="applyFilters()"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">Tous les statuts</option>
                    <option value="pending" {{ (request('status') == 'pending') ? 'selected' : '' }}>En attente</option>
                    <option value="approved" {{ (request('status') == 'approved') ? 'selected' : '' }}>Approuvée</option>
                    <option value="rejected" {{ (request('status') == 'rejected') ? 'selected' : '' }}>Rejetée</option>
                    <option value="cancelled" {{ (request('status') == 'cancelled') ? 'selected' : '' }}>Annulée</option>
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
        </div>

        <!-- Tableau des demandes -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Montant
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Description
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($requests->items() as $request)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $request->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($request->amount, 2, ',', ' ') }} {{ $request->currency }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ ucfirst($request->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($request->status === 'pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        En attente
                                    </span>
                                @elseif($request->status === 'approved')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Approuvée
                                    </span>
                                @elseif($request->status === 'rejected')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Rejetée
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        Annulée
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $request->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($request->status === 'pending')
                                    <button onclick="cancelRequest({{ $request->id }})"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Annuler
                                    </button>
                                @elseif($request->status === 'rejected' && $request->rejection_reason)
                                    <button onclick="showRejectionReason('{{ $request->rejection_reason }}')"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        Voir raison
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucune demande de crédit trouvée.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($requests->hasPages())
            <div class="mt-4">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>

<style>
.tab-button.active {
    border-color: #10b981;
    color: #10b981;
}
</style>

<script>
function switchTab(tab) {
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Désactiver tous les onglets
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Afficher le contenu sélectionné
    document.getElementById('tab-content-' + tab).classList.remove('hidden');
    
    // Activer l'onglet sélectionné
    document.getElementById('tab-' + tab).classList.add('active');
}

function resetForm() {
    document.getElementById('credit-request-form').reset();
    document.getElementById('form-result').classList.add('hidden');
}

function applyFilters() {
    const params = new URLSearchParams();
    
    const status = document.getElementById('filter_status').value;
    const type = document.getElementById('filter_type').value;
    
    if (status) params.append('status', status);
    if (type) params.append('type', type);
    
    window.location.href = '{{ route("credit-requests.index") }}?' + params.toString();
}

function cancelRequest(requestId) {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette demande ?')) {
        return;
    }

    fetch(`{{ route('credit-requests.index') }}/${requestId}/cancel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
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
        alert('❌ Erreur: ' + error.message);
    });
}

function showRejectionReason(reason) {
    alert('Raison du rejet:\n\n' + reason);
}

// Gestion du formulaire de demande
document.getElementById('credit-request-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const resultDiv = document.getElementById('form-result');
    
    // Désactiver le bouton
    submitButton.disabled = true;
    submitButton.textContent = 'Traitement...';
    
    try {
        const response = await fetch('{{ route("credit-requests.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-green-800 dark:text-green-200 font-medium">${data.message}</span>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
            
            // Réinitialiser le formulaire
            resetForm();
            
            // Recharger la page après 2 secondes
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-800 dark:text-red-200 font-medium">${data.message || 'Erreur lors de la soumission de la demande'}</span>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-800 dark:text-red-200 font-medium">Erreur: ${error.message}</span>
                </div>
            </div>
        `;
        resultDiv.classList.remove('hidden');
    } finally {
        // Réactiver le bouton
        submitButton.disabled = false;
        submitButton.textContent = 'Soumettre la demande';
    }
});
</script>
@endsection

