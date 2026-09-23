@extends('layouts.app')

@section('title', 'Nouveau Virement')

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
    <h1 class="text-2xl font-medium mb-6">Nouveau Virement</h1>
    
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
        <!-- Formulaire principal -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form action="{{ route('wire-transfers.store') }}" method="POST" id="wireTransferForm">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Expéditeur -->
                        <div>
                            <label for="sender_id" class="block text-sm font-medium text-gray-700 mb-1">Expéditeur*</label>
                            <select name="sender_id" id="sender_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('sender_id') border-red-500 @enderror" required>
                                <option value="">Sélectionnez un expéditeur</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" 
                                        {{ old('sender_id', request('sender_id')) == $user->id ? 'selected' : '' }}
                                        data-balance="{{ $user->balance }}"
                                        data-role="{{ $user->role }}">
                                        {{ $user->name }} ({{ $user->role }}) - {{ number_format($user->balance, 2) }} EUR
                                    </option>
                                @endforeach
                            </select>
                            @error('sender_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Destinataire -->
                        <div>
                            <label for="recipient_id" class="block text-sm font-medium text-gray-700 mb-1">Destinataire*</label>
                            <select name="recipient_id" id="recipient_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('recipient_id') border-red-500 @enderror" required>
                                <option value="">Sélectionnez un destinataire</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" 
                                        {{ old('recipient_id', request('recipient_id')) == $user->id ? 'selected' : '' }}
                                        data-balance="{{ $user->balance }}"
                                        data-role="{{ $user->role }}">
                                        {{ $user->name }} ({{ $user->role }}) - {{ number_format($user->balance, 2) }} EUR
                                    </option>
                                @endforeach
                            </select>
                            @error('recipient_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <!-- Montant -->
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Montant (EUR)*</label>
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" max="999999.99"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('amount') border-red-500 @enderror" 
                                value="{{ old('amount') }}" required>
                            @error('amount')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Type de virement -->
                        <div>
                            <label for="transfer_type" class="block text-sm font-medium text-gray-700 mb-1">Type de virement*</label>
                            <select name="transfer_type" id="transfer_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('transfer_type') border-red-500 @enderror" required>
                                <option value="">Sélectionnez un type</option>
                                @foreach($transferTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('transfer_type') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('transfer_type')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="3" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror" 
                            placeholder="Description du virement...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="mt-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes internes</label>
                        <textarea name="notes" id="notes" rows="2" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('notes') border-red-500 @enderror" 
                            placeholder="Notes internes (non visibles par les utilisateurs)...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Boutons -->
                    <div class="flex justify-end space-x-3 mt-8">
                        <a href="{{ route('wire-transfers.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Créer le Virement
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panneau d'information -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du Virement</h3>
                
                <!-- Solde de l'expéditeur -->
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Solde de l'expéditeur</h4>
                    <div id="senderBalance" class="text-lg font-semibold text-gray-900">
                        Sélectionnez un expéditeur
                    </div>
                </div>

                <!-- Solde du destinataire -->
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Solde du destinataire</h4>
                    <div id="recipientBalance" class="text-lg font-semibold text-gray-900">
                        Sélectionnez un destinataire
                    </div>
                </div>

                <!-- Montant du virement -->
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Montant du virement</h4>
                    <div id="transferAmount" class="text-lg font-semibold text-gray-900">
                        0.00 EUR
                    </div>
                </div>

                <!-- Solde après virement -->
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Solde après virement</h4>
                    <div id="senderBalanceAfter" class="text-sm text-gray-600">
                        -
                    </div>
                    <div id="recipientBalanceAfter" class="text-sm text-gray-600">
                        -
                    </div>
                </div>

                <!-- Validation -->
                <div id="validationMessage" class="mt-4 p-3 rounded-md hidden">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aide -->
            <div class="bg-blue-50 rounded-xl p-6 mt-6">
                <h3 class="text-lg font-medium text-blue-900 mb-2">Types de virements</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><strong>Admin → Intégrateur:</strong> Virement administratif vers un intégrateur</li>
                    <li><strong>Intégrateur → Opérateur:</strong> Virement d'un intégrateur vers un opérateur</li>
                    <li><strong>Opérateur → Client:</strong> Virement d'un opérateur vers un client</li>
                    <li><strong>Admin → Opérateur:</strong> Virement direct admin vers opérateur</li>
                    <li><strong>Intégrateur → Client:</strong> Virement direct intégrateur vers client</li>
                    <li><strong>Manuel:</strong> Virement manuel personnalisé</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const senderSelect = document.getElementById('sender_id');
    const recipientSelect = document.getElementById('recipient_id');
    const amountInput = document.getElementById('amount');
    const senderBalanceDiv = document.getElementById('senderBalance');
    const recipientBalanceDiv = document.getElementById('recipientBalance');
    const transferAmountDiv = document.getElementById('transferAmount');
    const senderBalanceAfterDiv = document.getElementById('senderBalanceAfter');
    const recipientBalanceAfterDiv = document.getElementById('recipientBalanceAfter');
    const validationMessageDiv = document.getElementById('validationMessage');

    function updateBalanceInfo() {
        const senderOption = senderSelect.options[senderSelect.selectedIndex];
        const recipientOption = recipientSelect.options[recipientSelect.selectedIndex];
        const amount = parseFloat(amountInput.value) || 0;

        // Mettre à jour les soldes affichés
        if (senderOption.value) {
            const senderBalance = parseFloat(senderOption.dataset.balance);
            senderBalanceDiv.textContent = `${senderBalance.toFixed(2)} EUR`;
            senderBalanceDiv.className = `text-lg font-semibold ${senderBalance >= 0 ? 'text-green-600' : 'text-red-600'}`;
        } else {
            senderBalanceDiv.textContent = 'Sélectionnez un expéditeur';
            senderBalanceDiv.className = 'text-lg font-semibold text-gray-900';
        }

        if (recipientOption.value) {
            const recipientBalance = parseFloat(recipientOption.dataset.balance);
            recipientBalanceDiv.textContent = `${recipientBalance.toFixed(2)} EUR`;
            recipientBalanceDiv.className = `text-lg font-semibold ${recipientBalance >= 0 ? 'text-green-600' : 'text-red-600'}`;
        } else {
            recipientBalanceDiv.textContent = 'Sélectionnez un destinataire';
            recipientBalanceDiv.className = 'text-lg font-semibold text-gray-900';
        }

        // Mettre à jour le montant du virement
        transferAmountDiv.textContent = `${amount.toFixed(2)} EUR`;

        // Calculer les soldes après virement
        if (senderOption.value && recipientOption.value && amount > 0) {
            const senderBalance = parseFloat(senderOption.dataset.balance);
            const recipientBalance = parseFloat(recipientOption.dataset.balance);
            
            const senderAfter = senderBalance - amount;
            const recipientAfter = recipientBalance + amount;

            senderBalanceAfterDiv.innerHTML = `
                <strong>Expéditeur:</strong> ${senderAfter.toFixed(2)} EUR
                <span class="ml-2 ${senderAfter >= 0 ? 'text-green-600' : 'text-red-600'}">
                    ${senderAfter >= 0 ? '✓' : '⚠'}
                </span>
            `;
            
            recipientBalanceAfterDiv.innerHTML = `
                <strong>Destinataire:</strong> ${recipientAfter.toFixed(2)} EUR
                <span class="ml-2 text-green-600">✓</span>
            `;

            // Validation
            validateTransfer(senderBalance, amount);
        } else {
            senderBalanceAfterDiv.textContent = '-';
            recipientBalanceAfterDiv.textContent = '-';
            validationMessageDiv.classList.add('hidden');
        }
    }

    function validateTransfer(senderBalance, amount) {
        validationMessageDiv.classList.remove('hidden');
        
        if (senderBalance < amount) {
            validationMessageDiv.className = 'mt-4 p-3 rounded-md bg-red-50 border border-red-200';
            validationMessageDiv.querySelector('svg').className = 'h-5 w-5 text-red-400';
            validationMessageDiv.querySelector('p').textContent = 'Solde insuffisant pour effectuer ce virement';
        } else if (amount <= 0) {
            validationMessageDiv.className = 'mt-4 p-3 rounded-md bg-yellow-50 border border-yellow-200';
            validationMessageDiv.querySelector('svg').className = 'h-5 w-5 text-yellow-400';
            validationMessageDiv.querySelector('p').textContent = 'Le montant doit être supérieur à 0';
        } else {
            validationMessageDiv.className = 'mt-4 p-3 rounded-md bg-green-50 border border-green-200';
            validationMessageDiv.querySelector('svg').className = 'h-5 w-5 text-green-400';
            validationMessageDiv.querySelector('p').textContent = 'Virement valide et prêt à être créé';
        }
    }

    // Événements
    senderSelect.addEventListener('change', updateBalanceInfo);
    recipientSelect.addEventListener('change', updateBalanceInfo);
    amountInput.addEventListener('input', updateBalanceInfo);

    // Validation du formulaire
    document.getElementById('wireTransferForm').addEventListener('submit', function(e) {
        const senderBalance = parseFloat(senderSelect.options[senderSelect.selectedIndex]?.dataset.balance || 0);
        const amount = parseFloat(amountInput.value) || 0;

        if (senderBalance < amount) {
            e.preventDefault();
            alert('Le solde de l\'expéditeur est insuffisant pour effectuer ce virement.');
            return false;
        }

        if (senderSelect.value === recipientSelect.value) {
            e.preventDefault();
            alert('L\'expéditeur et le destinataire ne peuvent pas être la même personne.');
            return false;
        }
    });

    // Initialiser l'affichage
    updateBalanceInfo();
});
</script>
@endsection
