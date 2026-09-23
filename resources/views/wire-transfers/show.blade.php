@extends('layouts.app')

@section('title', 'Détails du Virement')

@section('content')
<div class="px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('wire-transfers.index') }}" class="flex items-center text-gray-500 hover:text-gray-700 transition duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Retour aux virements</span>
        </a>
    </div>

    <div class="text-gray-500 text-sm">Administration</div>
    <h1 class="text-2xl font-medium mb-6">Détails du Virement</h1>
    
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informations principales -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $wireTransfer->transfer_reference }}</h2>
                        <p class="text-sm text-gray-500">Créé le {{ $wireTransfer->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    <div class="text-right">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'processing' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$wireTransfer->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $wireTransfer->getFormattedStatus() }}
                        </span>
                    </div>
                </div>

                <!-- Informations du virement -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Expéditeur</h3>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700">{{ substr($wireTransfer->sender->name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $wireTransfer->sender->name }}</div>
                                <div class="text-sm text-gray-500">{{ $wireTransfer->sender->email }}</div>
                                <div class="text-sm text-gray-500">{{ ucfirst($wireTransfer->sender->role) }}</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Destinataire</h3>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700">{{ substr($wireTransfer->recipient->name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $wireTransfer->recipient->name }}</div>
                                <div class="text-sm text-gray-500">{{ $wireTransfer->recipient->email }}</div>
                                <div class="text-sm text-gray-500">{{ ucfirst($wireTransfer->recipient->role) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Détails financiers -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Détails Financiers</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500">Montant</div>
                            <div class="text-xl font-semibold text-gray-900">{{ number_format($wireTransfer->amount, 2) }} EUR</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500">Type</div>
                            <div class="text-lg font-semibold text-gray-900">{{ $wireTransfer->getFormattedType() }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm font-medium text-gray-500">Devise</div>
                            <div class="text-lg font-semibold text-gray-900">{{ $wireTransfer->currency }}</div>
                        </div>
                    </div>
                </div>

                <!-- Description et notes -->
                @if($wireTransfer->description || $wireTransfer->notes)
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Description et Notes</h3>
                        @if($wireTransfer->description)
                            <div class="mb-4">
                                <div class="text-sm font-medium text-gray-500 mb-1">Description</div>
                                <div class="text-sm text-gray-900">{{ $wireTransfer->description }}</div>
                            </div>
                        @endif
                        @if($wireTransfer->notes)
                            <div>
                                <div class="text-sm font-medium text-gray-500 mb-1">Notes internes</div>
                                <div class="text-sm text-gray-900">{{ $wireTransfer->notes }}</div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Historique des transactions -->
                @if($relatedTransactions->count() > 0)
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Transactions Associées</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($relatedTransactions as $transaction)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $transaction->user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $transaction->user->role }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($transaction->amount < 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Débit
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Crédit
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium {{ $transaction->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                                    {{ number_format(abs($transaction->amount), 2) }} EUR
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $transaction->getStatusLabel() }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $transaction->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Panneau latéral -->
        <div class="lg:col-span-1">
            <!-- Actions -->
            @if($wireTransfer->status === 'pending')
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                    
                    <div class="space-y-3">
                        <!-- Traiter le virement -->
                        <form action="{{ route('wire-transfers.process', $wireTransfer) }}" method="POST" class="inline-block w-full">
                            @csrf
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Traiter le Virement
                            </button>
                        </form>

                        <!-- Rejeter le virement -->
                        <button onclick="openRejectModal()" class="w-full inline-flex justify-center items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Rejeter
                        </button>

                        <!-- Annuler le virement -->
                        <form action="{{ route('wire-transfers.cancel', $wireTransfer) }}" method="POST" class="inline-block w-full">
                            @csrf
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Annuler
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Informations de traitement -->
            @if($wireTransfer->processed_by || $wireTransfer->processed_at)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations de Traitement</h3>
                    
                    @if($wireTransfer->processor)
                        <div class="mb-3">
                            <div class="text-sm font-medium text-gray-500">Traité par</div>
                            <div class="text-sm text-gray-900">{{ $wireTransfer->processor->name }}</div>
                        </div>
                    @endif

                    @if($wireTransfer->processed_at)
                        <div class="mb-3">
                            <div class="text-sm font-medium text-gray-500">Date de traitement</div>
                            <div class="text-sm text-gray-900">{{ $wireTransfer->processed_at->format('d/m/Y à H:i') }}</div>
                        </div>
                    @endif

                    @if($wireTransfer->completed_at)
                        <div class="mb-3">
                            <div class="text-sm font-medium text-gray-500">Date de finalisation</div>
                            <div class="text-sm text-gray-900">{{ $wireTransfer->completed_at->format('d/m/Y à H:i') }}</div>
                        </div>
                    @endif

                    @if($wireTransfer->rejected_at)
                        <div class="mb-3">
                            <div class="text-sm font-medium text-gray-500">Date de rejet</div>
                            <div class="text-sm text-gray-900">{{ $wireTransfer->rejected_at->format('d/m/Y à H:i') }}</div>
                        </div>
                    @endif

                    @if($wireTransfer->rejection_reason)
                        <div>
                            <div class="text-sm font-medium text-gray-500">Raison du rejet</div>
                            <div class="text-sm text-gray-900">{{ $wireTransfer->rejection_reason }}</div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Métadonnées -->
            @if($wireTransfer->metadata)
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Métadonnées</h3>
                    <pre class="text-xs text-gray-600 bg-gray-50 p-3 rounded overflow-x-auto">{{ json_encode($wireTransfer->metadata, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal de rejet -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Rejeter le Virement</h3>
            <form action="{{ route('wire-transfers.reject', $wireTransfer) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">Raison du rejet*</label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="3" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="Expliquez pourquoi ce virement est rejeté..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                        Rejeter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
function openRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

// Fermer le modal en cliquant à l'extérieur
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>
@endsection
