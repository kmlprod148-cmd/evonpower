/**
 * Charging Point Show Page JavaScript
 * Gère toutes les interactions de la page de détails du point de charge
 */

(function() {
    'use strict';

    // Variables globales
    let connectionStatusInterval = null;
    let stopConnectionStatusCheck = false;

    /**
     * Initialisation au chargement de la page
     */
    document.addEventListener('DOMContentLoaded', function() {
        initTabs();
        initStatsPeriod();
        initConnectionStatusCheck();
    });

    /**
     * Initialise le système de tabs
     */
    function initTabs() {
        const tabLinks = document.querySelectorAll('[data-tab-target]');
        const tabContents = document.querySelectorAll('[data-tab-content]');

        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetTab = this.getAttribute('data-tab-target');

                // Désactiver tous les tabs
                tabLinks.forEach(l => {
                    l.classList.remove('border-green-500', 'text-green-600', 'font-medium');
                    l.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                });

                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });

                // Activer le tab cliqué
                this.classList.add('border-green-500', 'text-green-600', 'font-medium');
                this.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');

                // Afficher le contenu correspondant
                const targetContent = document.querySelector(`[data-tab-content="${targetTab}"]`);
                if (targetContent) {
                    targetContent.classList.remove('hidden');
                }
            });
        });
    }

    /**
     * Initialise le sélecteur de période des statistiques
     */
    function initStatsPeriod() {
        const statsPeriod = document.getElementById('stats-period');
        if (statsPeriod) {
            statsPeriod.addEventListener('change', function() {
                // TODO: Implémenter la récupération des données selon la période
                console.log('Period changed to:', this.value);
            });
        }
    }

    /**
     * Initialise la vérification du statut de connexion
     */
    function initConnectionStatusCheck() {
        const chargingPointId = window.chargingPointId;
        if (!chargingPointId) return;

        // Vérifier le statut au chargement
        checkConnectionStatus();

        // Vérifier toutes les 30 secondes
        if (!stopConnectionStatusCheck) {
            connectionStatusInterval = setInterval(function() {
                if (!stopConnectionStatusCheck) {
                    checkConnectionStatus();
                } else if (connectionStatusInterval) {
                    clearInterval(connectionStatusInterval);
                    connectionStatusInterval = null;
                }
            }, 30000);
        }
    }

    /**
     * Vérifie le statut de connexion
     */
    async function checkConnectionStatus() {
        if (stopConnectionStatusCheck) return;

        const chargingPointId = window.chargingPointId;
        if (!chargingPointId) return;

        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            const response = await fetch(`/api/auto-connect/status/${chargingPointId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            });

            if (response.status === 401) {
                stopConnectionStatusCheck = true;
                if (connectionStatusInterval) {
                    clearInterval(connectionStatusInterval);
                    connectionStatusInterval = null;
                }
                return;
            }

            if (!response.ok) {
                console.error(`Erreur HTTP ${response.status} lors de la vérification du statut`);
                return;
            }

            const data = await response.json();

            if (data.success) {
                const connectBtn = document.getElementById('connect-btn');
                const connectBtnText = document.getElementById('connect-btn-text');

                if (connectBtn && connectBtnText) {
                    if (data.data.connected) {
                        connectBtn.classList.remove('bg-red-600', 'hover:bg-red-700', 'bg-gray-600', 'hover:bg-gray-700');
                        connectBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                        connectBtnText.textContent = 'Connecté';
                    } else {
                        connectBtn.classList.remove('bg-green-600', 'hover:bg-green-700', 'bg-red-600', 'hover:bg-red-700');
                        connectBtn.classList.add('bg-gray-600', 'hover:bg-gray-700');
                        connectBtnText.textContent = 'Déconnecté';
                    }
                }
            }
        } catch (error) {
            console.error('Erreur lors de la vérification du statut:', error);
        }
    }

    /**
     * Affiche une notification
     */
    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    };

    /**
     * Affiche le modal de connexion SteVe
     */
    window.openSteveConnectionModal = function() {
        const modal = document.getElementById('steve-connection-modal');
        if (modal) {
            modal.classList.remove('hidden');
            fetchSteveApiStatus();
        }
    };

    /**
     * Ferme le modal de connexion SteVe
     */
    window.closeSteveConnectionModal = function() {
        const modal = document.getElementById('steve-connection-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    /**
     * Récupère le statut de l'API Steve
     */
    async function fetchSteveApiStatus() {
        const statusContainer = document.getElementById('steve-api-status');
        const loadingIndicator = document.getElementById('steve-status-loading');
        const wsUrlDisplay = document.getElementById('websocket-url-display');
        const chargingPointId = window.chargingPointId;

        if (!statusContainer || !loadingIndicator || !chargingPointId) return;

        loadingIndicator.classList.remove('hidden');
        statusContainer.classList.add('hidden');
        statusContainer.textContent = '';

        try {
            const chargeBoxId = document.getElementById('charge-box-id-display')?.value || '';
            const wsBaseUrl = 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/';
            const wsUrl = wsBaseUrl + chargeBoxId;

            if (wsUrlDisplay) {
                wsUrlDisplay.innerHTML = `<span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></span>${wsUrl}`;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(`/steve-integration/charging-points/${chargingPointId}/complete-api-status`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            let apiStatus;

            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');
            const responseText = await response.text();

            if (response.ok && isJson) {
                try {
                    apiStatus = JSON.parse(responseText);
                    if (!apiStatus.websocket_url) {
                        apiStatus.websocket_url = wsUrl;
                    }
                } catch (parseError) {
                    console.error('Erreur de parsing JSON:', parseError);
                    throw new Error('La réponse du serveur n\'est pas du JSON valide');
                }
            } else {
                apiStatus = {
                    websocket_url: wsUrl,
                    charge_box_id: chargeBoxId,
                    charging_point_id: chargingPointId,
                    timestamp: new Date().toISOString(),
                    success: false,
                    error: `HTTP ${response.status}`,
                    message: `Erreur HTTP ${response.status}`,
                    http_status: response.status
                };
            }

            loadingIndicator.classList.add('hidden');
            statusContainer.classList.remove('hidden');

            updateStatusBadge(apiStatus);

            const jsonString = JSON.stringify(apiStatus, null, 2);
            statusContainer.innerHTML = syntaxHighlight(jsonString, apiStatus);

        } catch (error) {
            loadingIndicator.classList.add('hidden');
            statusContainer.classList.remove('hidden');

            const errorStatus = {
                success: false,
                error: error.message,
                message: 'Erreur lors de la récupération du statut de l\'API Steve',
                timestamp: new Date().toISOString()
            };

            updateStatusBadge(errorStatus);
            statusContainer.innerHTML = syntaxHighlight(JSON.stringify(errorStatus, null, 2), errorStatus);
            console.error('Steve API Status Error:', error);
        }
    }

    /**
     * Met à jour le badge de statut
     */
    function updateStatusBadge(data) {
        const badge = document.getElementById('status-badge');
        if (!badge) return;

        const isSuccess = data?.success !== false &&
                         (!data?.test_connection || data.test_connection.success) &&
                         (!data?.charger_status || data.charger_status.success);

        const hasErrors = data?.error ||
                         (data?.test_connection && !data.test_connection.success) ||
                         (data?.charger_status && !data.charger_status.success);

        if (isSuccess && !hasErrors) {
            badge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full status-badge-success flex items-center';
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-400 mr-2 animate-pulse"></span>Connecté';
        } else if (hasErrors) {
            badge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full status-badge-error flex items-center';
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-400 mr-2"></span>Erreur';
        } else {
            badge.className = 'px-2.5 py-1 text-xs font-semibold rounded-full status-badge-warning flex items-center';
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-yellow-400 mr-2 animate-pulse"></span>Partiel';
        }
    }

    /**
     * Coloration syntaxique JSON
     */
    function syntaxHighlight(json, data) {
        if (typeof json !== 'string') {
            json = JSON.stringify(json, null, 2);
        }

        const isSuccess = data?.success !== false;
        const statusClass = isSuccess ? 'json-success' : 'json-error';

        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

        json = json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'json-number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'json-key';
                } else {
                    cls = 'json-string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'json-boolean';
            } else if (/null/.test(match)) {
                cls = 'json-null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });

        return '<div class="json-container ' + statusClass + '">' + json + '</div>';
    }

    /**
     * Fonction pour connecter le point de charge
     */
    window.connectChargingPoint = function() {
        openSteveConnectionModal();
    };

    /**
     * Fonction pour afficher les informations sur les parts
     */
    window.showPartsInfoModal = function() {
        // Cette fonction est définie dans le template Blade car elle nécessite des données PHP
        if (window.showPartsInfoModalImpl) {
            window.showPartsInfoModalImpl();
        }
    };

    /**
     * Fonction pour tester la connexion SteVe
     */
    window.testSteVeConnection = function() {
        const chargingPointId = window.chargingPointId;
        if (!chargingPointId) {
            showNotification('ID du point de charge non trouvé', 'error');
            return;
        }

        const button = event?.target || document.querySelector('[onclick*="testSteVeConnection"]');
        const originalText = button?.innerHTML || '';
        
        if (button) {
            button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Test...';
            button.disabled = true;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch(`/charging-points/${chargingPointId}/test-steve-connection`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Connexion réussie', 'success');
            } else {
                showNotification(data.message || 'Erreur lors du test de connexion', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erreur lors du test de connexion', 'error');
        })
        .finally(() => {
            if (button) {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        });
    };

    /**
     * Fonction pour copier l'URL WebSocket
     */
    window.copyWebSocketUrl = function() {
        const input = document.querySelector('input[value*="ws://"]') || 
                     document.querySelector('#steve-server-url-input');
        
        if (input) {
            input.select();
            input.setSelectionRange(0, 99999); // Pour mobile
            
            try {
                document.execCommand('copy');
                showNotification('URL WebSocket copiée !', 'success');
            } catch (err) {
                // Fallback pour les navigateurs modernes
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(input.value).then(() => {
                        showNotification('URL WebSocket copiée !', 'success');
                    }).catch(() => {
                        showNotification('Erreur lors de la copie', 'error');
                    });
                } else {
                    showNotification('Erreur lors de la copie', 'error');
                }
            }
        } else {
            showNotification('URL WebSocket non trouvée', 'error');
        }
    };

    /**
     * Fonction helper pour afficher des notifications
     */
    function showNotification(message, type = 'success') {
        // Supprimer les notifications existantes
        const existingNotifications = document.querySelectorAll('.notification-toast');
        existingNotifications.forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `notification-toast fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg transition-all duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateY(0)';
        }, 10);

        // Supprimer après 3 secondes
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Exposer showNotification globalement si nécessaire
    window.showNotification = showNotification;

})();

