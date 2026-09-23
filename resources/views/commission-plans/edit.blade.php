@extends('layouts.app')

@section('title', 'Modifier un Plan de Commission')

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Modifier un Plan de Commission</h1>
        <div class="flex space-x-2">
            <a href="{{ route('admin.commission-plans.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                Retour à la liste
            </a>
            <a href="{{ route('commission-plans.show', $commissionPlan) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Voir les détails
            </a>
        </div>
    </div>

    @include('partials.flash-messages')

    <div class="bg-white rounded-lg shadow-md overflow-hidden p-6">
        <form action="{{ route('commission-plans.update', $commissionPlan) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Informations de base -->
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-4 pb-2 border-b">Informations de base</h2>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du plan <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $commissionPlan->name) }}" required
                        class="w-full rounded border-gray-300 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select name="is_active" id="is_active" class="w-full rounded border-gray-300">
                        <option value="1" {{ old('is_active', $commissionPlan->is_active) ? 'selected' : '' }}>Actif</option>
                        <option value="0" {{ old('is_active', $commissionPlan->is_active) ? '' : 'selected' }}>Inactif</option>
                    </select>
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="3" 
                        class="w-full rounded border-gray-300 @error('description') border-red-500 @enderror">{{ old('description', $commissionPlan->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Pourcentages de commission -->
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-4 pb-2 border-b">Pourcentages de commission</h2>
                </div>

                <div>
                    <label for="admin_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage Admin <span class="text-red-500">*</span></label>
                    <input type="number" name="admin_percentage" id="admin_percentage" value="{{ old('admin_percentage', $commissionPlan->admin_percentage) }}" 
                        min="0" max="100" step="0.01" required
                        class="w-full rounded border-gray-300 @error('admin_percentage') border-red-500 @enderror">
                    @error('admin_percentage')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="integrator_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage Intégrateur <span class="text-red-500">*</span></label>
                    <input type="number" name="integrator_percentage" id="integrator_percentage" value="{{ old('integrator_percentage', $commissionPlan->integrator_percentage) }}" 
                        min="0" max="100" step="0.01" required
                        class="w-full rounded border-gray-300 @error('integrator_percentage') border-red-500 @enderror">
                    @error('integrator_percentage')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="partner_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage Partenaire <span class="text-red-500">*</span></label>
                    <input type="number" name="partner_percentage" id="partner_percentage" value="{{ old('partner_percentage', $commissionPlan->partner_percentage) }}" 
                        min="0" max="100" step="0.01" required
                        class="w-full rounded border-gray-300 @error('partner_percentage') border-red-500 @enderror">
                    @error('partner_percentage')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="total_percentage" class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                    <input type="number" id="total_percentage" value="{{ $commissionPlan->admin_percentage + $commissionPlan->integrator_percentage + $commissionPlan->partner_percentage }}" readonly
                        class="w-full rounded border-gray-300 bg-gray-100">
                    <p class="text-xs text-gray-500 mt-1">Le total des pourcentages peut être inférieur à 100%.</p>
                </div>

                <!-- Conditions d'application -->
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-4 pb-2 border-b">Conditions d'application</h2>
                </div>

                <div>
                    <label for="min_transaction_value" class="block text-sm font-medium text-gray-700 mb-1">Valeur minimale de transaction</label>
                    <input type="number" name="min_transaction_value" id="min_transaction_value" value="{{ old('min_transaction_value', $commissionPlan->min_transaction_value) }}" 
                        min="0" step="0.01"
                        class="w-full rounded border-gray-300 @error('min_transaction_value') border-red-500 @enderror">
                    @error('min_transaction_value')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="max_transaction_value" class="block text-sm font-medium text-gray-700 mb-1">Valeur maximale de transaction</label>
                    <input type="number" name="max_transaction_value" id="max_transaction_value" value="{{ old('max_transaction_value', $commissionPlan->max_transaction_value) }}" 
                        min="0" step="0.01"
                        class="w-full rounded border-gray-300 @error('max_transaction_value') border-red-500 @enderror">
                    @error('max_transaction_value')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="applies_to_type" class="block text-sm font-medium text-gray-700 mb-1">Type d'application <span class="text-red-500">*</span></label>
                    <select name="applies_to_type" id="applies_to_type" required
                        class="w-full rounded border-gray-300 @error('applies_to_type') border-red-500 @enderror">
                        <option value="global" {{ old('applies_to_type', $commissionPlan->applies_to_type) === 'global' ? 'selected' : '' }}>Global</option>
                        <option value="integrator" {{ old('applies_to_type', $commissionPlan->applies_to_type) === 'integrator' ? 'selected' : '' }}>Intégrateur spécifique</option>
                        <option value="partner" {{ old('applies_to_type', $commissionPlan->applies_to_type) === 'partner' ? 'selected' : '' }}>Partenaire spécifique</option>
                        <option value="group" {{ old('applies_to_type', $commissionPlan->applies_to_type) === 'group' ? 'selected' : '' }}>Groupe spécifique</option>
                    </select>
                    @error('applies_to_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="applies_to_id_container" class="{{ old('applies_to_type', $commissionPlan->applies_to_type) === 'global' ? 'hidden' : '' }}">
                    <label for="applies_to_id" class="block text-sm font-medium text-gray-700 mb-1">Entité d'application</label>
                    <select name="applies_to_id" id="applies_to_id" 
                        class="w-full rounded border-gray-300 @error('applies_to_id') border-red-500 @enderror">
                        <option value="">Sélectionnez une entité</option>
                        <!-- Les options seront chargées dynamiquement via JavaScript -->
                    </select>
                    @error('applies_to_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Gestion des transactions -->
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-4 pb-2 border-b">Gestion des transactions et commissions</h2>
                </div>

                <div>
                    <label for="transaction_manager" class="block text-sm font-medium text-gray-700 mb-1">Gestionnaire des transactions</label>
                    <select name="transaction_manager" id="transaction_manager" class="w-full rounded border-gray-300">
                        <option value="admin" {{ old('transaction_manager', $commissionPlan->transaction_manager) === 'admin' ? 'selected' : '' }}>Administrateur</option>
                        <option value="integrator" {{ old('transaction_manager', $commissionPlan->transaction_manager) === 'integrator' ? 'selected' : '' }}>Intégrateur</option>
                        <option value="partner" {{ old('transaction_manager', $commissionPlan->transaction_manager) === 'partner' ? 'selected' : '' }}>Partenaire</option>
                        <option value="shared" {{ old('transaction_manager', $commissionPlan->transaction_manager) === 'shared' ? 'selected' : '' }}>Gestion partagée</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Qui a le droit principal de gérer les transactions et commissions.</p>
                </div>

                <div>
                    <label for="requires_approval" class="block text-sm font-medium text-gray-700 mb-1">Approbation requise</label>
                    <select name="requires_approval" id="requires_approval" class="w-full rounded border-gray-300">
                        <option value="0" {{ old('requires_approval', $commissionPlan->requires_approval) ? '' : 'selected' }}>Non</option>
                        <option value="1" {{ old('requires_approval', $commissionPlan->requires_approval) ? 'selected' : '' }}>Oui</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Si les modifications nécessitent une approbation.</p>
                </div>

                <div id="shared_permissions_container" class="{{ old('transaction_manager', $commissionPlan->transaction_manager) === 'shared' ? '' : 'hidden' }} col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Permissions partagées</label>
                    <div class="grid grid-cols-3 gap-4 p-4 border rounded-lg">
                        <div>
                            <h4 class="font-medium mb-2">Administrateur</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[admin][]" value="view" class="rounded border-gray-300 text-blue-600" checked disabled>
                                    <span class="ml-2 text-sm">Voir</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[admin][]" value="edit" class="rounded border-gray-300 text-blue-600"
                                        {{ isset($commissionPlan->transaction_permissions['admin']) && in_array('edit', $commissionPlan->transaction_permissions['admin']) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Modifier</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[admin][]" value="approve" class="rounded border-gray-300 text-blue-600"
                                        {{ isset($commissionPlan->transaction_permissions['admin']) && in_array('approve', $commissionPlan->transaction_permissions['admin']) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Approuver</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium mb-2">Intégrateur</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[integrator][]" value="view" class="rounded border-gray-300 text-blue-600"
                                        {{ isset($commissionPlan->transaction_permissions['integrator']) && in_array('view', $commissionPlan->transaction_permissions['integrator']) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Voir</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[integrator][]" value="edit" class="rounded border-gray-300 text-blue-600"
                                        {{ isset($commissionPlan->transaction_permissions['integrator']) && in_array('edit', $commissionPlan->transaction_permissions['integrator']) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Modifier</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[integrator][]" value="approve" class="rounded border-gray-300 text-blue-600"
                                        {{ isset($commissionPlan->transaction_permissions['integrator']) && in_array('approve', $commissionPlan->transaction_permissions['integrator']) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Approuver</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium mb-2">Partenaire</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[partner][]" value="view" class="rounded border-gray-300 text-blue-600"
                                        {{ isset($commissionPlan->transaction_permissions['partner']) && in_array('view', $commissionPlan->transaction_permissions['partner']) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Voir</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[partner][]" value="edit" class="rounded border-gray-300 text-blue-600"
                                        {{ isset($commissionPlan->transaction_permissions['partner']) && in_array('edit', $commissionPlan->transaction_permissions['partner']) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Modifier</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[partner][]" value="approve" class="rounded border-gray-300 text-blue-600"
                                        {{ isset($commissionPlan->transaction_permissions['partner']) && in_array('approve', $commissionPlan->transaction_permissions['partner']) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Approuver</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="approval_workflow_container" class="{{ old('requires_approval', $commissionPlan->requires_approval) ? '' : 'hidden' }} col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Flux d'approbation</label>
                    <div class="p-4 border rounded-lg">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Actions nécessitant une approbation</label>
                            <div class="grid grid-cols-2 gap-2">
                                @php
                                    $approvalActions = $commissionPlan->approval_workflow['actions'] ?? [];
                                @endphp
                                <label class="flex items-center">
                                    <input type="checkbox" name="approval_actions[]" value="create_transaction" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array('create_transaction', $approvalActions) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Création de transaction</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="approval_actions[]" value="edit_commission" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array('edit_commission', $approvalActions) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Modification de commission</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="approval_actions[]" value="mark_paid" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array('mark_paid', $approvalActions) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Marquer comme payé</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="approval_actions[]" value="delete_transaction" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array('delete_transaction', $approvalActions) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Suppression de transaction</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Approbateurs par défaut</label>
                            <div class="grid grid-cols-3 gap-2">
                                @php
                                    $defaultApprovers = $commissionPlan->approval_workflow['default_approvers'] ?? [];
                                @endphp
                                <label class="flex items-center">
                                    <input type="checkbox" name="default_approvers[]" value="admin" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array('admin', $defaultApprovers) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Administrateur</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="default_approvers[]" value="integrator" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array('integrator', $defaultApprovers) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Intégrateur</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="default_approvers[]" value="partner" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array('partner', $defaultApprovers) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm">Partenaire</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paramètres avancés -->
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold mb-4 pb-2 border-b">Paramètres avancés</h2>
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priorité</label>
                    <input type="number" name="priority" id="priority" value="{{ old('priority', $commissionPlan->priority) }}"
                        min="0" step="1"
                        class="w-full rounded border-gray-300 @error('priority') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1">Valeur plus élevée = priorité plus élevée</p>
                    @error('priority')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="is_default" class="block text-sm font-medium text-gray-700 mb-1">Plan par défaut</label>
                    <select name="is_default" id="is_default" class="w-full rounded border-gray-300">
                        <option value="0" {{ old('is_default', $commissionPlan->is_default) ? '' : 'selected' }}>Non</option>
                        <option value="1" {{ old('is_default', $commissionPlan->is_default) ? 'selected' : '' }}>Oui</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Si oui, ce plan sera utilisé par défaut.</p>
                </div>

                <div>
                    <label for="valid_from" class="block text-sm font-medium text-gray-700 mb-1">Date de début de validité</label>
                    <input type="date" name="valid_from" id="valid_from" value="{{ old('valid_from', $commissionPlan->valid_from ? $commissionPlan->valid_from->format('Y-m-d') : '') }}"
                        class="w-full rounded border-gray-300 @error('valid_from') border-red-500 @enderror">
                    @error('valid_from')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">Date de fin de validité</label>
                    <input type="date" name="valid_until" id="valid_until" value="{{ old('valid_until', $commissionPlan->valid_until ? $commissionPlan->valid_until->format('Y-m-d') : '') }}"
                        class="w-full rounded border-gray-300 @error('valid_until') border-red-500 @enderror">
                    @error('valid_until')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                    Mettre à jour le plan
                </button>
            </div>
        </form>
    </div>
    
    <!-- Recalculer les commissions -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden p-6 mt-6">
        <h2 class="text-lg font-semibold mb-4 pb-2 border-b">Recalculer les commissions</h2>
        
        <form action="{{ route('commission-plans.recalculate', $commissionPlan) }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" name="start_date" id="start_date" 
                        class="w-full rounded border-gray-300">
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" name="end_date" id="end_date" 
                        class="w-full rounded border-gray-300">
                </div>
                
                <div>
                    <label for="apply_to" class="block text-sm font-medium text-gray-700 mb-1">Appliquer à</label>
                    <select name="apply_to" id="apply_to" required class="w-full rounded border-gray-300">
                        <option value="plan_transactions">Transactions utilisant ce plan</option>
                        <option value="filtered_transactions">Transactions correspondant aux critères du plan</option>
                        <option value="all_transactions">Toutes les transactions</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="text-sm text-gray-500 mb-4">
                    Cette action recalculera les commissions pour les transactions existantes en utilisant ce plan de commission.
                    Si aucune date n'est spécifiée, toutes les transactions seront prises en compte.
                </p>
                
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                    Recalculer les commissions
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Calcul du total des pourcentages
        const adminPercentage = document.getElementById('admin_percentage');
        const integratorPercentage = document.getElementById('integrator_percentage');
        const partnerPercentage = document.getElementById('partner_percentage');
        const totalPercentage = document.getElementById('total_percentage');
        
        function updateTotal() {
            const admin = parseFloat(adminPercentage.value) || 0;
            const integrator = parseFloat(integratorPercentage.value) || 0;
            const partner = parseFloat(partnerPercentage.value) || 0;
            totalPercentage.value = (admin + integrator + partner).toFixed(2);
        }
        
        adminPercentage.addEventListener('input', updateTotal);
        integratorPercentage.addEventListener('input', updateTotal);
        partnerPercentage.addEventListener('input', updateTotal);
        
        // Initialiser le total
        updateTotal();
        
        // Gestion de l'affichage du champ d'entité en fonction du type d'application
        const appliesToType = document.getElementById('applies_to_type');
        const appliesToIdContainer = document.getElementById('applies_to_id_container');
        const appliesToId = document.getElementById('applies_to_id');
        
        appliesToType.addEventListener('change', function() {
            if (this.value === 'global') {
                appliesToIdContainer.classList.add('hidden');
                appliesToId.value = '';
            } else {
                appliesToIdContainer.classList.remove('hidden');
                loadEntities(this.value);
            }
        });
        
        // Gestion de l'affichage des permissions partagées
        const transactionManager = document.getElementById('transaction_manager');
        const sharedPermissionsContainer = document.getElementById('shared_permissions_container');
        
        transactionManager.addEventListener('change', function() {
            if (this.value === 'shared') {
                sharedPermissionsContainer.classList.remove('hidden');
            } else {
                sharedPermissionsContainer.classList.add('hidden');
            }
        });
        
        // Gestion de l'affichage du flux d'approbation
        const requiresApproval = document.getElementById('requires_approval');
        const approvalWorkflowContainer = document.getElementById('approval_workflow_container');
        
        requiresApproval.addEventListener('change', function() {
            if (this.value === '1') {
                approvalWorkflowContainer.classList.remove('hidden');
            } else {
                approvalWorkflowContainer.classList.add('hidden');
            }
        });
        
        // Charger les entités en fonction du type sélectionné
        function loadEntities(type) {
            appliesToId.innerHTML = '<option value="">Chargement...</option>';
            
            let url = '';
            switch(type) {
                case 'integrator':
                    url = '/api/integrators';
                    break;
                case 'partner':
                    url = '/api/partners';
                    break;
                case 'group':
                    url = '/api/groups';
                    break;
                default:
                    return;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    appliesToId.innerHTML = '<option value="">Sélectionnez une entité</option>';
                    data.forEach(entity => {
                        const option = document.createElement('option');
                        option.value = entity.id;
                        option.textContent = entity.name;
                        
                        // Sélectionner l'entité actuelle
                        if (entity.id == {{ $commissionPlan->applies_to_id ?? 'null' }}) {
                            option.selected = true;
                        }
                        
                        appliesToId.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des entités:', error);
                    appliesToId.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        }
        
        // Charger les entités au chargement si nécessaire
        if (appliesToType.value !== 'global') {
            loadEntities(appliesToType.value);
        }
    });
</script>
@endpush
@endsection