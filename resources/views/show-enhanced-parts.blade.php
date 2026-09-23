                <!-- QR Code de réservation avec génération et téléchargement -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">QR Code de Réservation</h2>
                            <p class="text-sm text-gray-600 mt-1">Génération et téléchargement du QR Code</p>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="generateQRCode()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-qrcode mr-2"></i>Générer QR Code
                            </button>
                            <button onclick="downloadQRCode()" id="download-qr-btn" disabled class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fas fa-download mr-2"></i>Télécharger
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 mb-3">Informations de réservation</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Borne:</span>
                                        <span class="font-medium">{{ $chargingPoint->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Puissance:</span>
                                        <span class="font-medium">{{ $chargingPoint->power_output }} kW</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Statut:</span>
                                        <span class="font-medium text-green-600">{{ $chargingPoint->status }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 rounded-lg p-4">
                                <h3 class="font-semibold text-blue-900 mb-3">URL de réservation</h3>
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="reservation-url" readonly 
                                           value="{{ route('public.charging-point.offer.reservation', $chargingPoint->id) }}" 
                                           class="flex-1 px-3 py-2 bg-white border border-blue-200 rounded-lg text-sm">
                                    <button onclick="copyReservationUrl()" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <div id="qr-code-container" class="bg-gray-50 rounded-lg p-6 min-h-[200px] flex items-center justify-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-qrcode text-4xl mb-2"></i>
                                    <p class="text-sm">Cliquez sur "Générer QR Code" pour afficher</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions SteVe API avec interface intuitive -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Actions SteVe API</h2>
                            <p class="text-sm text-gray-600 mt-1">Interface de gestion des bornes SteVe</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600">ID Borne:</label>
                                <input type="text" id="steve-chargebox-id" placeholder="Ex: BORNE777" 
                                       class="px-3 py-1 border border-gray-300 rounded text-sm" value="{{ $chargingPoint->serial_number }}">
                            </div>
                            <button onclick="connectToSteVe()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-plug mr-2"></i>Connecter
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Actions disponibles</h3>
                            
                            <div class="space-y-3">
                                <button onclick="executeSteVeAction('start')" class="w-full flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-play text-green-600 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-green-900">Démarrer la charge</p>
                                            <p class="text-sm text-green-700">Initier une session de recharge</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-green-600"></i>
                                </button>

                                <button onclick="executeSteVeAction('stop')" class="w-full flex items-center justify-between p-4 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-stop text-red-600 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-red-900">Arrêter la charge</p>
                                            <p class="text-sm text-red-700">Terminer la session en cours</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-red-600"></i>
                                </button>

                                <button onclick="executeSteVeAction('status')" class="w-full flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-blue-900">Vérifier le statut</p>
                                            <p class="text-sm text-blue-700">Obtenir l'état de la borne</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-blue-600"></i>
                                </button>

                                <button onclick="executeSteVeAction('transactions')" class="w-full flex items-center justify-between p-4 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                                    <div class="flex items-center">
                                        <i class="fas fa-list text-purple-600 mr-3"></i>
                                        <div>
                                            <p class="font-medium text-purple-900">Historique des transactions</p>
                                            <p class="text-sm text-purple-700">Consulter les sessions passées</p>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-purple-600"></i>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Statut de connexion</h3>
                            
                            <div id="steve-connection-status" class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center mb-3">
                                    <div class="w-3 h-3 bg-gray-400 rounded-full mr-3" id="connection-indicator"></div>
                                    <span class="text-sm font-medium text-gray-700" id="connection-text">Non connecté</span>
                                </div>
                                <p class="text-sm text-gray-600" id="connection-details">Cliquez sur "Connecter" pour établir la connexion avec SteVe</p>
                            </div>

                            <div id="steve-action-results" class="bg-gray-50 rounded-lg p-4" style="display: none;">
                                <h4 class="font-semibold text-gray-900 mb-2">Résultat de l'action</h4>
                                <div id="action-result-content" class="text-sm text-gray-700"></div>
                            </div>
                        </div>
                    </div>
                </div>
