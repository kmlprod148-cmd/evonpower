<!-- Connectors Control Section -->
<div class="bg-white shadow rounded-lg p-6 mb-6" 
     x-data="connectorControl({{ $chargingPoint->id }}, '{{ $chargingPoint->charge_box_id }}', {{ json_encode($connectorsData ?? []) }})">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Contrôle des Connecteurs</h3>
            <p class="text-sm text-gray-600 mt-1">Démarrer ou arrêter une session de recharge</p>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="fixed top-4 right-4 z-50 space-y-2" x-show="toasts.length > 0" x-cloak>
        <template x-for="(toast, index) in toasts" :key="index">
            <div 
                x-show="toast.show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-x-full"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform translate-x-full"
                :class="{
                    'bg-green-50 border-green-200 text-green-800': toast.type === 'success',
                    'bg-red-50 border-red-200 text-red-800': toast.type === 'error',
                    'bg-blue-50 border-blue-200 text-blue-800': toast.type === 'info',
                }"
                class="border-l-4 rounded-lg shadow-lg p-4 min-w-[300px] max-w-md"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg x-show="toast.type === 'success'" class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <svg x-show="toast.type === 'error'" class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <svg x-show="toast.type === 'info'" class="h-5 w-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-semibold" x-text="toast.title"></p>
                            <p class="text-sm" x-text="toast.message"></p>
                        </div>
                    </div>
                    <button @click="removeToast(index)" class="ml-4 text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Connectors List -->
    <div class="space-y-4">
        <template x-for="connector in connectors" :key="connector.id">
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <div>
                                <h4 class="font-semibold text-gray-900" x-text="'Connecteur ' + connector.connector_id"></h4>
                                <p class="text-sm text-gray-600" x-text="connector.type + ' • ' + connector.power + ' kW'"></p>
                            </div>
                            <div>
                                <span 
                                    :class="{
                                        'bg-green-100 text-green-800': connector.status === 'Available',
                                        'bg-blue-100 text-blue-800': connector.status === 'Occupied' || connector.has_active_transaction,
                                        'bg-red-100 text-red-800': connector.status === 'Unavailable' || connector.status === 'Faulted',
                                        'bg-yellow-100 text-yellow-800': connector.status === 'Preparing' || connector.status === 'Finishing',
                                    }"
                                    class="px-2 py-1 rounded-full text-xs font-semibold"
                                    x-text="connector.status"
                                ></span>
                            </div>
                        </div>

                        <!-- Active Transaction Info -->
                        <div x-show="connector.has_active_transaction" class="mt-2 text-sm text-gray-600">
                            <p x-text="'Session active depuis ' + formatTime(connector.active_transaction?.started_at)"></p>
                            <p x-show="connector.active_transaction?.start_meter" 
                               x-text="'Mètre de départ: ' + connector.active_transaction.start_meter + ' Wh'"></p>
                        </div>

                        <!-- Reservation Info -->
                        <div x-show="connector.has_reservation" class="mt-2 text-sm text-blue-600">
                            <p x-text="'Réservation: ' + (connector.reservation?.start_time ? formatTime(connector.reservation.start_time) : 'N/A')"></p>
                            <p x-show="connector.reservation?.end_time" 
                               x-text="'Fin prévue: ' + formatTime(connector.reservation.end_time)"></p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- Start Button -->
                        <button
                            @click="startCharging(connector)"
                            :disabled="!connector.can_start || connector.loading"
                            :class="{
                                'bg-gray-300 cursor-not-allowed': !connector.can_start || connector.loading,
                                'bg-green-600 hover:bg-green-700': connector.can_start && !connector.loading,
                            }"
                            class="px-4 py-2 text-white rounded-lg font-medium transition-colors flex items-center space-x-2"
                        >
                            <svg x-show="!connector.loading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg x-show="connector.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="connector.loading ? 'Démarrage...' : 'Démarrer'"></span>
                        </button>

                        <!-- Stop Button -->
                        <button
                            @click="stopCharging(connector)"
                            :disabled="!connector.has_active_transaction || connector.loading"
                            :class="{
                                'bg-gray-300 cursor-not-allowed': !connector.has_active_transaction || connector.loading,
                                'bg-red-600 hover:bg-red-700': connector.has_active_transaction && !connector.loading,
                            }"
                            class="px-4 py-2 text-white rounded-lg font-medium transition-colors flex items-center space-x-2"
                        >
                            <svg x-show="!connector.loading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"/>
                            </svg>
                            <svg x-show="connector.loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="connector.loading ? 'Arrêt...' : 'Arrêter'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="connectors.length === 0" class="text-center py-8 text-gray-500">
            <p>Aucun connecteur disponible</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function connectorControl(chargingPointId, chargeBoxId, initialConnectors) {
    return {
        chargingPointId: chargingPointId,
        chargeBoxId: chargeBoxId,
        connectors: initialConnectors.map(c => ({
            ...c,
            loading: false,
            can_start: false,
        })),
        toasts: [],

        init() {
            // Vérifier canStart pour chaque connecteur
            this.connectors.forEach(connector => {
                this.checkCanStart(connector);
            });

            // Écouter les événements Pusher/Laravel Echo
            if (typeof Echo !== 'undefined') {
                Echo.channel(`chargepoint.${this.chargeBoxId}`)
                    .listen('.transaction.updated', (e) => {
                        this.handleTransactionUpdate(e);
                    });
            }
        },

        async checkCanStart(connector) {
            try {
                const response = await axios.get('/api/v1/charging/can-start', {
                    params: {
                        chargeBoxId: this.chargeBoxId,
                        connectorId: connector.connector_id,
                    }
                });

                connector.can_start = response.data.can_start;
            } catch (error) {
                console.error('Error checking can start:', error);
                connector.can_start = false;
            }
        },

        async startCharging(connector) {
            if (!connector.can_start || connector.loading) return;

            connector.loading = true;

            try {
                const response = await axios.post('/api/v1/charging/start', {
                    chargeBoxId: this.chargeBoxId,
                    connectorId: connector.connector_id,
                    ocppTag: 'ADMIN_{{ auth()->id() }}',
                });

                if (response.data.success) {
                    this.showToast('success', 'Recharge démarrée', 
                        `Session démarrée avec succès. Transaction ID: ${response.data.transaction.id}`);
                    
                    // Mettre à jour l'état du connecteur
                    connector.has_active_transaction = true;
                    connector.active_transaction = response.data.transaction;
                    connector.status = 'Occupied';
                    connector.can_start = false;
                } else {
                    this.showToast('error', 'Échec du démarrage', response.data.message || 'Erreur inconnue');
                }
            } catch (error) {
                console.error('Error starting charging:', error);
                this.showToast('error', 'Erreur', 
                    error.response?.data?.message || error.message || 'Erreur lors du démarrage');
            } finally {
                connector.loading = false;
            }
        },

        async stopCharging(connector) {
            if (!connector.has_active_transaction || connector.loading) return;

            connector.loading = true;

            try {
                const response = await axios.post('/api/v1/charging/stop', {
                    chargeBoxId: this.chargeBoxId,
                    connectorId: connector.connector_id,
                    transactionId: connector.active_transaction?.id,
                });

                if (response.data.success) {
                    this.showToast('success', 'Recharge arrêtée', 
                        `Session arrêtée avec succès. Transaction ID: ${response.data.transaction.id}`);
                    
                    // Mettre à jour l'état du connecteur
                    connector.has_active_transaction = false;
                    connector.active_transaction = null;
                    connector.status = 'Available';
                    connector.can_start = true;
                } else {
                    this.showToast('error', 'Échec de l\'arrêt', response.data.message || 'Erreur inconnue');
                }
            } catch (error) {
                console.error('Error stopping charging:', error);
                this.showToast('error', 'Erreur', 
                    error.response?.data?.message || error.message || 'Erreur lors de l\'arrêt');
            } finally {
                connector.loading = false;
            }
        },

        handleTransactionUpdate(event) {
            const transaction = event;
            const connector = this.connectors.find(c => c.connector_id === transaction.connector_id);

            if (connector) {
                if (transaction.status === 'ACTIVE') {
                    connector.has_active_transaction = true;
                    connector.active_transaction = {
                        id: transaction.transaction_id,
                        started_at: transaction.started_at,
                        start_meter: transaction.start_meter,
                    };
                    connector.status = 'Occupied';
                    connector.can_start = false;
                    this.showToast('info', 'Mise à jour', 'Transaction démarrée');
                } else if (transaction.status === 'STOPPED') {
                    connector.has_active_transaction = false;
                    connector.active_transaction = null;
                    connector.status = 'Available';
                    connector.can_start = true;
                    this.showToast('info', 'Mise à jour', 'Transaction arrêtée');
                }
            }
        },

        showToast(type, title, message) {
            const toast = {
                type,
                title,
                message,
                show: true,
            };

            this.toasts.push(toast);

            // Auto-remove après 5 secondes
            setTimeout(() => {
                this.removeToast(this.toasts.indexOf(toast));
            }, 5000);
        },

        removeToast(index) {
            if (index >= 0 && index < this.toasts.length) {
                this.toasts[index].show = false;
                setTimeout(() => {
                    this.toasts.splice(index, 1);
                }, 300);
            }
        },

        formatTime(isoString) {
            if (!isoString) return 'N/A';
            const date = new Date(isoString);
            return date.toLocaleString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    };
}
</script>
@endpush

