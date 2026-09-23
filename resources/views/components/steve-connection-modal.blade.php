<!-- SteVe OCPP Connection Modal Component -->
<div id="steveConnectionModal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-50 flex items-center justify-center" x-data="steveConnectionModal()">
    <div class="bg-black border border-green-500 rounded-lg shadow-xl max-w-md w-full mx-4">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-green-500 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-green-500">
                Intégration Connexion SteVe OCPP
            </h2>
            <button @click="closeModal()" class="text-white hover:text-green-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-4 space-y-4">
            <!-- Charge Box ID Display -->
            <div>
                <label class="block text-sm font-medium text-green-500 mb-2">
                    ID de la Borne (Charge Box ID)
                </label>
                <div class="flex items-center space-x-2">
                    <input
                        type="text"
                        id="chargeBoxId"
                        x-model="chargeBoxId"
                        readonly
                        class="flex-1 px-3 py-2 border border-green-500 rounded-lg bg-black text-green-500 font-mono"
                        placeholder="Récupération de l'ID..."
                    >
                    <button
                        @click="refreshChargeBoxId()"
                        class="px-3 py-2 bg-green-600 hover:bg-green-500 text-black rounded-lg transition-colors font-bold"
                        title="Actualiser l'ID"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Server URL -->
            <div>
                <label class="block text-sm font-medium text-green-500 mb-2">
                    URL du serveur SteVe
                </label>
                <input
                    type="text"
                    x-model="serverUrl"
                    class="w-full px-3 py-2 border border-green-500 rounded-lg bg-black text-green-500 text-sm font-mono"
                    placeholder="ws://158.69.27.239:8080/steve/websocket/CentralSystemService/"
                >
            </div>

            <!-- Server Status -->
            <div>
                <label class="block text-sm font-medium text-green-500 mb-2">
                    Statut du serveur
                </label>
                <div class="flex items-center space-x-2 p-3 bg-gray-900 border border-green-500 rounded-lg">
                    <div
                        id="statusIndicator"
                        class="w-3 h-3 rounded-full transition-colors"
                        :class="serverStatus === 'connected' ? 'bg-green-500' : serverStatus === 'connecting' ? 'bg-yellow-500' : 'bg-red-500'"
                    ></div>
                    <span class="text-sm font-medium text-green-500" x-text="getStatusLabel()"></span>
                </div>
            </div>

            <!-- WebSocket URL Preview -->
            <div>
                <label class="block text-sm font-medium text-green-500 mb-2">
                    URL WebSocket générée
                </label>
                <div class="p-3 bg-gray-900 rounded-lg border border-green-500">
                    <p class="text-xs text-green-400 break-all font-mono" x-text="generatedWebSocketUrl"></p>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center space-x-2 p-3 bg-gray-900 border border-green-500 rounded-lg">
                <svg class="w-4 h-4 text-green-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-sm text-green-500">Connexion en cours...</span>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="p-3 bg-gray-900 rounded-lg border border-red-500">
                <p class="text-sm text-red-400 font-mono" x-text="errorMessage"></p>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="p-3 bg-gray-900 rounded-lg border border-green-500">
                <p class="text-sm text-green-400 font-mono" x-text="successMessage"></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-green-500 flex items-center justify-end space-x-3">
            <button
                @click="closeModal()"
                class="px-4 py-2 text-white bg-black border border-green-500 hover:bg-green-500 hover:text-black rounded-lg transition-colors font-bold"
            >
                Annuler
            </button>
            <button
                @click="connectToSteVe()"
                :disabled="!chargeBoxId || isLoading"
                class="px-4 py-2 bg-green-500 hover:bg-green-400 disabled:bg-gray-600 text-black rounded-lg transition-colors font-bold"
            >
                Connecter
            </button>
        </div>
    </div>
</div>

<script>
function steveConnectionModal() {
    return {
        chargeBoxId: '',
        serverUrl: 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/',
        serverStatus: 'disconnected',
        isLoading: false,
        errorMessage: '',
        successMessage: '',
        chargingPointId: null,

        init() {
            // Get charging point ID from data attribute or URL
            const modal = document.getElementById('steveConnectionModal');
            this.chargingPointId = modal.dataset.chargingPointId;
            this.loadChargeBoxId();
        },

        async loadChargeBoxId() {
            try {
                // Try to get charge box ID from the charging point
                const response = await fetch(`/api/charging-points/${this.chargingPointId}/charge-box-id`);
                const data = await response.json();
                
                if (data.success && data.charge_box_id) {
                    this.chargeBoxId = data.charge_box_id;
                } else {
                    // Generate a default ID if not available
                    this.chargeBoxId = `BORNE_${this.chargingPointId}`;
                }
            } catch (error) {
                console.error('Error loading charge box ID:', error);
                this.chargeBoxId = `BORNE_${this.chargingPointId}`;
            }
        },

        async refreshChargeBoxId() {
            this.isLoading = true;
            this.errorMessage = '';
            try {
                await this.loadChargeBoxId();
                this.successMessage = 'ID actualisé avec succès';
                setTimeout(() => this.successMessage = '', 3000);
            } catch (error) {
                this.errorMessage = 'Erreur lors de l\'actualisation de l\'ID';
            } finally {
                this.isLoading = false;
            }
        },

        async connectToSteVe() {
            if (!this.chargeBoxId) {
                this.errorMessage = 'L\'ID de la borne est requis';
                return;
            }

            this.isLoading = true;
            this.errorMessage = '';
            this.successMessage = '';

            try {
                const response = await fetch(`/api/charging-points/${this.chargingPointId}/connect-steve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        charge_box_id: this.chargeBoxId,
                        steve_server_url: this.serverUrl
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.serverStatus = 'connected';
                    this.successMessage = 'Borne connectée avec succès au serveur SteVe';
                    
                    // Emit event for parent component
                    window.dispatchEvent(new CustomEvent('steveConnected', {
                        detail: {
                            chargeBoxId: this.chargeBoxId,
                            websocketUrl: data.data.websocket_url,
                            chargingPoint: data.data.charging_point
                        }
                    }));

                    setTimeout(() => this.closeModal(), 2000);
                } else {
                    this.errorMessage = data.message || 'Erreur lors de la connexion';
                    this.serverStatus = 'disconnected';
                }
            } catch (error) {
                this.errorMessage = 'Erreur réseau: ' + error.message;
                this.serverStatus = 'disconnected';
            } finally {
                this.isLoading = false;
            }
        },

        get generatedWebSocketUrl() {
            if (!this.chargeBoxId) return 'URL sera générée après saisie de l\'ID';
            return this.serverUrl + this.chargeBoxId;
        },

        getStatusLabel() {
            const labels = {
                'connected': '✓ Connecté',
                'connecting': '⟳ Connexion en cours...',
                'disconnected': '✗ Déconnecté'
            };
            return labels[this.serverStatus] || 'Statut inconnu';
        },

        openModal() {
            document.getElementById('steveConnectionModal').classList.remove('hidden');
            this.loadChargeBoxId();
        },

        closeModal() {
            document.getElementById('steveConnectionModal').classList.add('hidden');
            this.errorMessage = '';
            this.successMessage = '';
            this.isLoading = false;
        }
    };
}
</script>

