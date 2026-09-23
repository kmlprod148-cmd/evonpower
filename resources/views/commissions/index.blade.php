@extends('layouts.app')

@section('title', 'Transactions et Commissions')

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Transactions et Commissions</h1>
        <div class="flex space-x-2">
            <a href="{{ route('admin.commission-plans.create') }}" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Créer Business Plan
            </a>
            <a href="{{ route('commissions.dashboard') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Tableau de bord
            </a>
            <a href="{{ route('commissions.export') }}?{{ http_build_query(request()->all()) }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Exporter CSV
            </a>
        </div>
    </div>

    @include('partials.flash-messages')

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Filtres</h2>
        </div>
        <div class="p-4">
            <form action="{{ route('commissions.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" 
                        class="w-full rounded border-gray-300">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" 
                        class="w-full rounded border-gray-300">
                </div>
                <div>
                    <label for="integrator_id" class="block text-sm font-medium text-gray-700 mb-1">Intégrateur</label>
                    <select name="integrator_id" id="integrator_id" class="w-full rounded border-gray-300">
                        <option value="">Tous les intégrateurs</option>
                        @foreach($integrators as $integrator)
                            <option value="{{ $integrator->id }}" {{ request('integrator_id') == $integrator->id ? 'selected' : '' }}>
                                {{ $integrator->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="partner_id" class="block text-sm font-medium text-gray-700 mb-1">Partenaire</label>
                    <select name="partner_id" id="partner_id" class="w-full rounded border-gray-300">
                        <option value="">Tous les partenaires</option>
                        @foreach($partners as $partner)
                            <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                                {{ $partner->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="commission_paid_status" class="block text-sm font-medium text-gray-700 mb-1">Statut de paiement</label>
                    <select name="commission_paid_status" id="commission_paid_status" class="w-full rounded border-gray-300">
                        <option value="">Tous</option>
                        <option value="paid" {{ request('commission_paid_status') === 'paid' ? 'selected' : '' }}>Payées</option>
                        <option value="unpaid" {{ request('commission_paid_status') === 'unpaid' ? 'selected' : '' }}>Impayées</option>
                    </select>
                </div>
                <div>
                    <label for="commission_type" class="block text-sm font-medium text-gray-700 mb-1">Type de commission</label>
                    <select name="commission_type" id="commission_type" class="w-full rounded border-gray-300">
                        <option value="all" {{ request('commission_type', 'all') === 'all' ? 'selected' : '' }}>Toutes</option>
                        <option value="admin" {{ request('commission_type') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="integrator" {{ request('commission_type') === 'integrator' ? 'selected' : '' }}>Intégrateur</option>
                        <option value="partner" {{ request('commission_type') === 'partner' ? 'selected' : '' }}>Partenaire</option>
                    </select>
                </div>
                <div class="md:col-span-3 flex justify-end">
                    <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Appliquer les filtres
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Actions groupées -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Actions groupées</h2>
        </div>
        <div class="p-4">
            <form action="{{ route('commissions.mark-multiple-paid') }}" method="POST" id="bulk-action-form">
                @csrf
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label for="bulk_commission_type" class="block text-sm font-medium text-gray-700 mb-1">Type de commission</label>
                        <select name="commission_type" id="bulk_commission_type" class="w-full rounded border-gray-300" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="admin">Admin</option>
                            <option value="integrator">Intégrateur</option>
                            <option value="partner">Partenaire</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded" id="mark-paid-button" disabled>
                            Marquer comme payées
                        </button>
                    </div>
                </div>
                <div class="mt-2 text-sm text-gray-500">
                    Sélectionnez les transactions dans le tableau ci-dessous et choisissez un type de commission pour marquer les commissions comme payées.
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('commissions.index', array_merge(request()->all(), ['sort_by' => 'id', 'sort_dir' => request('sort_by') === 'id' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}">
                                ID
                                @if(request('sort_by') === 'id')
                                    <span class="ml-1">{{ request('sort_dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('commissions.index', array_merge(request()->all(), ['sort_by' => 'start_timestamp', 'sort_dir' => request('sort_by') === 'start_timestamp' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}">
                                Date
                                @if(request('sort_by') === 'start_timestamp')
                                    <span class="ml-1">{{ request('sort_dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Point de charge</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('commissions.index', array_merge(request()->all(), ['sort_by' => 'price_total', 'sort_dir' => request('sort_by') === 'price_total' && request('sort_dir') === 'asc' ? 'desc' : 'asc'])) }}">
                                Montant
                                @if(request('sort_by') === 'price_total')
                                    <span class="ml-1">{{ request('sort_dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                    <tr>
                        <td class="px-3 py-4">
                            <input type="checkbox" name="transaction_ids[]" form="bulk-action-form" value="{{ $transaction->id }}" class="transaction-checkbox rounded border-gray-300 text-blue-600">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $transaction->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $transaction->start_timestamp ? \Carbon\Carbon::parse($transaction->start_timestamp)->format('d/m/Y H:i') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div>{{ optional($transaction->chargingPoint)->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">
                                Int: {{ optional($transaction->chargingPoint)->integrator->name ?? 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Part: {{ optional($transaction->chargingPoint)->partner->name ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($transaction->price_total, 2) }} €
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full {{ $transaction->admin_commission_paid ? 'bg-green-500' : 'bg-red-500' }} mr-1"></span>
                                <span class="text-gray-900">Admin: {{ number_format($transaction->admin_commission, 2) }} €</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full {{ $transaction->integrator_commission_paid ? 'bg-green-500' : 'bg-red-500' }} mr-1"></span>
                                <span class="text-gray-900">Int.: {{ number_format($transaction->integrator_commission, 2) }} €</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full {{ $transaction->partner_commission_paid ? 'bg-green-500' : 'bg-red-500' }} mr-1"></span>
                                <span class="text-gray-900">Part.: {{ number_format($transaction->partner_commission, 2) }} €</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($transaction->areAllCommissionsPaid())
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Payée</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">En attente</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('commissions.show', $transaction) }}" class="text-blue-600 hover:text-blue-900">Détails</a>
                                
                                <form action="{{ route('commissions.recalculate', $transaction) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900">Recalculer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            Aucune transaction trouvée.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4">
            {{ $transactions->appends(request()->all())->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('select-all');
        const transactionCheckboxes = document.querySelectorAll('.transaction-checkbox');
        const markPaidButton = document.getElementById('mark-paid-button');
        const bulkCommissionType = document.getElementById('bulk_commission_type');
        
        // Gérer la sélection/désélection de toutes les transactions
        selectAllCheckbox.addEventListener('change', function() {
            transactionCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateMarkPaidButtonState();
        });
        
        // Mettre à jour l'état du bouton "Marquer comme payées"
        function updateMarkPaidButtonState() {
            const anyChecked = Array.from(transactionCheckboxes).some(checkbox => checkbox.checked);
            const typeSelected = bulkCommissionType.value !== '';
            markPaidButton.disabled = !(anyChecked && typeSelected);
        }
        
        // Écouter les changements sur les cases à cocher individuelles
        transactionCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateMarkPaidButtonState();
                
                // Mettre à jour la case "Tout sélectionner" si nécessaire
                const allChecked = Array.from(transactionCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            });
        });
        
        // Écouter les changements sur le type de commission
        bulkCommissionType.addEventListener('change', updateMarkPaidButtonState);
        
        // Initialiser l'état du bouton
        updateMarkPaidButtonState();
    });
</script>
@endpush
@endsection