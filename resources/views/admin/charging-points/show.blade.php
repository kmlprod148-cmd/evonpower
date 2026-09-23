@extends('layouts.app')

@section('title', 'Détails du Charging Point')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="chargePointRemote()">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $chargingPoint->name }}</h1>
                <div class="mt-2 flex items-center space-x-4">
                    @if($chargingPoint->is_public)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-globe mr-1"></i>Public
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-lock mr-1"></i>Privé
                        </span>
                    @endif
                    
                    @if($chargingPoint->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-check-circle mr-1"></i>Actif
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactif
                        </span>
                    @endif
                    
                    <span class="text-sm text-gray-500">
                        Créé le {{ $chargingPoint->created_at->format('d/m/Y à H:i') }}
                    </span>
                </div>
            </div>
            
            <div class="flex space-x-2">
                <a href="{{ route('admin.charging-points.edit', $chargingPoint) }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
                <a href="{{ route('admin.charging-points.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Informations générales -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Informations générales</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Détails du charging point</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Nom</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->name }}
                        </dd>
                    </div>
                    @if($chargingPoint->description)
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->description }}
                        </dd>
                    </div>
                    @endif
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Localisation</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->location }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Coordonnées</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->latitude }}, {{ $chargingPoint->longitude }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Configuration technique -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Configuration technique</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Spécifications du charging point</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Puissance maximale</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->max_power }} kW
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Type de connecteur</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->connector_type }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Assignations -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Assignations</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Propriétaire et plan tarifaire</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Propriétaire</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->user ? $chargingPoint->user->name : 'N/A' }}
                            @if($chargingPoint->user)
                                <span class="text-gray-500">({{ $chargingPoint->user->email }})</span>
                            @endif
                        </dd>
                    </div>
                    @if($chargingPoint->pricingPlan)
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Plan tarifaire</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->pricingPlan->name }}
                            <span class="text-gray-500">({{ $chargingPoint->pricingPlan->base_price }}€)</span>
                        </dd>
                    </div>
                    @endif
                    @if($chargingPoint->partner)
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Partenaire</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->partner->name }}
                        </dd>
                    </div>
                    @endif
                    @if($chargingPoint->group)
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Groupe</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->group->name }}
                        </dd>
                    </div>
                    @endif
                    @if($chargingPoint->station)
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Station</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $chargingPoint->station->name }}
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Remote Actions Card -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <i class="fas fa-plug mr-2"></i>Actions à distance
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Commandes OCPP vers la station</p>
                </div>
                <!-- Live Status Badge -->
                <div x-cloak x-show="statusLoading" class="text-sm text-gray-500">
                    <i class="fas fa-spinner fa-spin mr-1"></i>Chargement...
                </div>
                <div x-cloak x-show="!statusLoading && connectorStatus" class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                          :class="getStatusClass(connectorStatus)">
                        <span x-text="connectorStatus"></span>
                    </span>
                </div>
            </div>
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                <!-- Action Buttons -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <!-- Start Charge -->
                    <button @click="openModal('start')"
                            class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition duration-200">
                        <i class="fas fa-play mr-2"></i>Démarrer
                    </button>
                    
                    <!-- Stop Charge -->
                    <button @click="openModal('stop')"
                            class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition duration-200">
                        <i class="fas fa-stop mr-2"></i>Arrêter
                    </button>
                    
                    <!-- Unlock Connector -->
                    <button @click="openModal('unlock')"
                            class="inline-flex items-center justify-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-bold rounded-lg transition duration-200">
                        <i class="fas fa-unlock mr-2"></i>Déverrouiller
                    </button>
                    
                    <!-- Soft Reset / Reboot -->
                    <button @click="openModal('reset-soft')"
                            class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition duration-200">
                        <i class="fas fa-redo mr-2"></i>Reboot
                    </button>

                    <!-- Lock Connector -->
                    <button @click="openModal('lock')"
                            class="inline-flex items-center justify-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-lg transition duration-200">
                        <i class="fas fa-lock mr-2"></i>Verrouiller
                    </button>
                </div>

                <!-- Admin Only Section -->
                @can('admin-only')
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-3">
                        <i class="fas fa-shield-alt mr-1"></i>Actions administrateur
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Hard Reset -->
                        <button @click="openModal('reset-hard')"
                                class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition duration-200">
                            <i class="fas fa-radiation mr-2"></i>Reset Hard
                        </button>

                        <!-- Block Station -->
                        <button @click="openModal('block')"
                                class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-bold rounded-lg transition duration-200">
                            <i class="fas fa-ban mr-2"></i>Bloquer
                        </button>

                        <!-- Unblock Station -->
                        <button @click="openModal('unblock')"
                                class="inline-flex items-center justify-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-lg transition duration-200">
                            <i class="fas fa-check-circle mr-2"></i>Débloquer
                        </button>

                        <!-- Clear Cache -->
                        <button @click="openModal('clear-cache')"
                                class="inline-flex items-center justify-center px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-lg transition duration-200">
                            <i class="fas fa-broom mr-2"></i>Vider Cache
                        </button>
                    </div>
                </div>
                @endcan

                <!-- Steve Sync -->
                @if(!$chargingPoint->isProvisionedOnSteve())
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <button @click="syncToSteve()"
                            class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition duration-200">
                        <i class="fas fa-sync mr-2"></i>Enregistrer sur Steve
                    </button>
                    <p class="text-xs text-gray-500 mt-1">Ce point de charge n'est pas encore enregistré sur le serveur Steve.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Commands Log -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <i class="fas fa-history mr-2"></i>Historique des commandes
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">5 dernières commandes exécutées</p>
            </div>
            <div class="border-t border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commande</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" x-ref="commandsTable">
                        <tr x-show="commandsLoading">
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Chargement...
                            </td>
                        </tr>
                        <template x-for="command in commands" :key="command.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="command.command"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getCommandStatusClass(command.status)" x-text="command.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(command.created_at)"></td>
                            </tr>
                        </template>
                        <tr x-show="!commandsLoading && commands.length === 0">
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                Aucune commande récente
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div x-show="showModal" x-cloak
             class="fixed inset-0 overflow-y-auto z-50">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="showModal"
                     @click="closeModal()"
                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" x-text="modalTitle"></h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500" x-text="modalMessage"></p>
                                    
                                    <!-- Connector ID input for Start/Stop/Unlock -->
                                    <div x-show="needsConnectorId" class="mt-3">
                                        <label class="block text-sm font-medium text-gray-700">ID du connecteur</label>
                                        <input type="number" x-model="connectorId" 
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                               placeholder="1">
                                    </div>
                                    
                                    <!-- Transaction ID input for Stop -->
                                    <div x-show="needsTransactionId" class="mt-3">
                                        <label class="block text-sm font-medium text-gray-700">ID de transaction</label>
                                        <input type="number" x-model="transactionId" 
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border"
                                               placeholder="12345">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button @click="confirmAction()" 
                                :disabled="actionLoading"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            <span x-show="!actionLoading"><i class="fas fa-check mr-2"></i>Confirmer</span>
                            <span x-show="actionLoading"><i class="fas fa-spinner fa-spin mr-2"></i>Envoi...</span>
                        </button>
                        <button @click="closeModal()" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div x-show="showToast" 
             x-cloak
             x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transform ease-in duration-200 transition"
             x-transition:leave-start="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4"
             class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i x-show="toastType === 'success'" class="fas fa-check-circle text-green-400 text-xl"></i>
                        <i x-show="toastType === 'error'" class="fas fa-times-circle text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900" x-text="toastTitle"></p>
                        <p class="text-sm text-gray-500" x-text="toastMessage"></p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="showToast = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                            <span class="sr-only">Fermer</span>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center">
            <div class="flex space-x-3">
                <a href="{{ route('admin.charging-points.edit', $chargingPoint) }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
                
                <form action="{{ route('admin.charging-points.toggle-status', $chargingPoint) }}" 
                      method="POST" 
                      class="inline">
                    @csrf
                    <button type="submit" 
                            class="bg-{{ $chargingPoint->is_active ? 'red' : 'green' }}-600 hover:bg-{{ $chargingPoint->is_active ? 'red' : 'green' }}-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-{{ $chargingPoint->is_active ? 'pause' : 'play' }} mr-2"></i>
                        {{ $chargingPoint->is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
                
                <form action="{{ route('admin.charging-points.destroy', $chargingPoint) }}" 
                      method="POST" 
                      class="inline"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce charging point ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        <i class="fas fa-trash mr-2"></i>Supprimer
                    </button>
                </form>
            </div>
            
            <a href="{{ route('admin.charging-points.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function chargePointRemote() {
        return {
            chargingPointId: {{ $chargingPoint->id }},
            chargeBoxId: '{{ $chargingPoint->steve_charging_point_id ?? $chargingPoint->charge_box_id }}',
            statusLoading: true,
            connectorStatus: null,
            commandsLoading: true,
            commands: [],
            
            showModal: false,
            modalTitle: '',
            modalMessage: '',
            modalAction: '',
            actionLoading: false,
            
            needsConnectorId: false,
            needsTransactionId: false,
            connectorId: 1,
            transactionId: '',
            
            showToast: false,
            toastTitle: '',
            toastMessage: '',
            toastType: 'success',
            
            init() {
                this.pollStatus();
                this.fetchCommands();
                
                // Refresh status every 5 seconds
                setInterval(() => this.pollStatus(), 5000);
                // Refresh commands every 10 seconds
                setInterval(() => this.fetchCommands(), 10000);
            },
            
            async pollStatus() {
                try {
                    const response = await fetch(`/admin/charging-points/${this.chargingPointId}/realtime-status`);
                    const result = await response.json();

                    if (result.success && result.data) {
                        this.connectorStatus = result.data.status || null;
                    }
                } catch (error) {
                    console.error('Error polling status:', error);
                } finally {
                    this.statusLoading = false;
                }
            },

            async syncToSteve() {
                try {
                    const response = await fetch(`/admin/charging-points/${this.chargingPointId}/sync-steve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    const result = await response.json();
                    const type = result.success ? 'success' : 'error';
                    this.showToastNotification(type === 'success' ? 'Succès' : 'Erreur', result.message, type);
                    if (result.success) setTimeout(() => location.reload(), 1500);
                } catch (error) {
                    this.showToastNotification('Erreur', 'Impossible de contacter Steve', 'error');
                }
            },
            
            async fetchCommands() {
                try {
                    const response = await fetch(`/admin/charging-points/${this.chargingPointId}/commands`);
                    const result = await response.json();

                    if (result.success) {
                        this.commands = result.data;
                    }
                } catch (error) {
                    console.error('Error fetching commands:', error);
                } finally {
                    this.commandsLoading = false;
                }
            },
            
            getStatusClass(status) {
                const map = {
                    'Available': 'bg-green-100 text-green-800',
                    'Preparing': 'bg-blue-100 text-blue-800',
                    'Charging': 'bg-yellow-100 text-yellow-800',
                    'SuspendedEVSE': 'bg-gray-100 text-gray-800',
                    'SuspendedEV': 'bg-gray-100 text-gray-800',
                    'Finishing': 'bg-blue-100 text-blue-800',
                    'Reserved': 'bg-indigo-100 text-indigo-800',
                    'Unavailable': 'bg-red-100 text-red-800',
                    'Faulted': 'bg-red-200 text-red-900',
                };
                return map[status] || 'bg-gray-100 text-gray-800';
            },
            
            getCommandStatusClass(status) {
                const map = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'accepted': 'bg-green-100 text-green-800',
                    'rejected': 'bg-red-100 text-red-800',
                    'failed': 'bg-red-200 text-red-900',
                };
                return map[status] || 'bg-gray-100 text-gray-800';
            },
            
            formatDate(dateStr) {
                const date = new Date(dateStr);
                return date.toLocaleString();
            },
            
            openModal(action) {
                this.modalAction = action;
                this.needsConnectorId = false;
                this.needsTransactionId = false;
                
                switch(action) {
                    case 'start':
                        this.modalTitle = 'Démarrer la recharge';
                        this.modalMessage = 'Voulez-vous envoyer une commande RemoteStart ?';
                        this.needsConnectorId = true;
                        break;
                    case 'stop':
                        this.modalTitle = 'Arrêter la recharge';
                        this.modalMessage = 'Voulez-vous envoyer une commande RemoteStop ?';
                        this.needsTransactionId = true;
                        break;
                    case 'unlock':
                        this.modalTitle = 'Déverrouiller le connecteur';
                        this.modalMessage = 'Voulez-vous déverrouiller le connecteur à distance ?';
                        this.needsConnectorId = true;
                        break;
                    case 'reset-soft':
                        this.modalTitle = 'Reboot (Soft Reset)';
                        this.modalMessage = 'Voulez-vous effectuer un redémarrage logiciel de la station ?';
                        break;
                    case 'reset-hard':
                        this.modalTitle = 'Reset Hard';
                        this.modalMessage = 'ATTENTION: Redémarrage matériel forcé. La session en cours sera interrompue.';
                        break;
                    case 'lock':
                        this.modalTitle = 'Verrouiller le connecteur';
                        this.modalMessage = 'Le connecteur sera marqué Inoperative. Aucune nouvelle session ne sera possible.';
                        this.needsConnectorId = true;
                        break;
                    case 'block':
                        this.modalTitle = 'Bloquer la station';
                        this.modalMessage = 'Voulez-vous rendre toute la station indisponible ?';
                        break;
                    case 'unblock':
                        this.modalTitle = 'Débloquer la station';
                        this.modalMessage = 'Voulez-vous rendre la station opérationnelle ?';
                        break;
                    case 'clear-cache':
                        this.modalTitle = 'Vider le cache d\'autorisation';
                        this.modalMessage = 'La liste locale d\'autorisation sur la station sera effacée.';
                        break;
                }
                
                this.showModal = true;
            },
            
            closeModal() {
                this.showModal = false;
                this.modalAction = '';
            },
            
            async confirmAction() {
                this.actionLoading = true;
                let endpoint = '';
                let payload = {};
                
                switch(this.modalAction) {
                    case 'start':
                        endpoint = 'remote-start';
                        payload = { connector_id: this.connectorId, id_tag: 'EVON_WEB' };
                        break;
                    case 'stop':
                        endpoint = 'remote-stop';
                        payload = { transaction_id: this.transactionId };
                        break;
                    case 'unlock':
                        endpoint = 'unlock';
                        payload = { connector_id: this.connectorId };
                        break;
                    case 'lock':
                        endpoint = 'lock';
                        payload = { connector_id: this.connectorId };
                        break;
                    case 'reset-soft':
                        endpoint = 'reboot';
                        payload = { type: 'Soft' };
                        break;
                    case 'reset-hard':
                        endpoint = 'reset';
                        payload = { type: 'Hard' };
                        break;
                    case 'block':
                        endpoint = 'availability';
                        payload = { connector_id: 0, type: 'Inoperative' };
                        break;
                    case 'unblock':
                        endpoint = 'availability';
                        payload = { connector_id: 0, type: 'Operative' };
                        break;
                    case 'clear-cache':
                        endpoint = 'clear-cache';
                        payload = {};
                        break;
                }
                
                try {
                    const response = await fetch(`/admin/charging-points/${this.chargingPointId}/${endpoint}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showToastNotification('Succès', result.message, 'success');
                        this.fetchCommands();
                    } else {
                        this.showToastNotification('Erreur', result.message, 'error');
                    }
                } catch (error) {
                    this.showToastNotification('Erreur', 'Erreur de connexion au serveur', 'error');
                } finally {
                    this.actionLoading = false;
                    this.closeModal();
                }
            },
            
            showToastNotification(title, message, type) {
                this.toastTitle = title;
                this.toastMessage = message;
                this.toastType = type;
                this.showToast = true;
                
                setTimeout(() => {
                    this.showToast = false;
                }, 5000);
            }
        }
    }
</script>
@endpush
