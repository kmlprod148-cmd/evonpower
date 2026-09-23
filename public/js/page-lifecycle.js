/**
 * Page Lifecycle Handler - Remplacement moderne de beforeunload
 * 
 * L'événement beforeunload est déprécié dans les navigateurs modernes.
 * Ce module fournit des alternatives modernes et performantes.
 * 
 * Documentation: https://developer.chrome.com/blog/page-lifecycle-api/
 * 
 * @version 1.0.0
 * @date 2025-12-23
 */

(function() {
    'use strict';

    // Détection des fonctionnalités du navigateur
    const supportsPageHide = 'onpagehide' in window;
    const supportsVisibilityChange = 'hidden' in document;

    /**
     * Gestionnaire de nettoyage pour les ressources
     */
    class PageLifecycleManager {
        constructor() {
            this.cleanupCallbacks = new Set();
            this.initialized = false;
        }

        /**
         * Initialise les listeners appropriés
         */
        init() {
            if (this.initialized) return;

            // Événement pagehide - Remplace beforeunload/unload
            // Plus fiable pour déterminer quand une page est vraiment fermée
            if (supportsPageHide) {
                window.addEventListener('pagehide', (event) => {
                    this.executeCleanup('pagehide', event);
                }, { capture: true });
            }

            // Événement visibilitychange - Détecte quand la page est cachée
            // Utile pour les onglets en arrière-plan
            if (supportsVisibilityChange) {
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        this.executeCleanup('hidden', null);
                    }
                }, { capture: true });
            }

            // Fallback pour les anciens navigateurs (très rare maintenant)
            if (!supportsPageHide && !supportsVisibilityChange) {
                console.warn('⚠️ Navigateur ancien détecté - fonctionnalités limitées');
                // Note: Nous n'utilisons PAS beforeunload ici car il est déprécié
            }

            this.initialized = true;
            console.log('✅ PageLifecycleManager initialisé');
        }

        /**
         * Enregistre une fonction de nettoyage
         * @param {Function} callback - Fonction à appeler lors du nettoyage
         * @param {string} [id] - Identifiant optionnel pour le callback
         */
        registerCleanup(callback, id = null) {
            if (typeof callback !== 'function') {
                console.error('❌ Le callback doit être une fonction');
                return false;
            }

            const cleanupItem = {
                id: id || `cleanup_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                callback: callback,
                registered: new Date().toISOString()
            };

            this.cleanupCallbacks.add(cleanupItem);
            
            console.log(`✓ Nettoyage enregistré: ${cleanupItem.id}`);

            // Retourne une fonction pour désenregistrer
            return () => this.unregisterCleanup(cleanupItem.id);
        }

        /**
         * Désenregistre une fonction de nettoyage
         * @param {string} id - Identifiant du callback à retirer
         */
        unregisterCleanup(id) {
            for (const item of this.cleanupCallbacks) {
                if (item.id === id) {
                    this.cleanupCallbacks.delete(item);
                    console.log(`✓ Nettoyage désenregistré: ${id}`);
                    return true;
                }
            }
            return false;
        }

        /**
         * Exécute tous les callbacks de nettoyage
         * @param {string} reason - Raison du nettoyage
         * @param {Event} event - Événement déclencheur
         */
        executeCleanup(reason, event) {
            console.log(`🧹 Nettoyage en cours (${reason})...`);
            
            let successCount = 0;
            let errorCount = 0;

            for (const item of this.cleanupCallbacks) {
                try {
                    item.callback(reason, event);
                    successCount++;
                } catch (error) {
                    console.error(`❌ Erreur lors du nettoyage ${item.id}:`, error);
                    errorCount++;
                }
            }

            console.log(`✅ Nettoyage terminé: ${successCount} succès, ${errorCount} erreurs`);
        }

        /**
         * Nettoie un intervalle de manière sécurisée
         * @param {number|null} intervalId - ID de l'intervalle à nettoyer
         * @returns {Function} Callback de nettoyage
         */
        createIntervalCleanup(intervalId) {
            return () => {
                if (intervalId) {
                    clearInterval(intervalId);
                    console.log(`✓ Intervalle ${intervalId} nettoyé`);
                }
            };
        }

        /**
         * Nettoie un timeout de manière sécurisée
         * @param {number|null} timeoutId - ID du timeout à nettoyer
         * @returns {Function} Callback de nettoyage
         */
        createTimeoutCleanup(timeoutId) {
            return () => {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    console.log(`✓ Timeout ${timeoutId} nettoyé`);
                }
            };
        }

        /**
         * Nettoie une connexion WebSocket
         * @param {WebSocket} socket - Socket à fermer
         * @returns {Function} Callback de nettoyage
         */
        createWebSocketCleanup(socket) {
            return () => {
                if (socket && socket.readyState === WebSocket.OPEN) {
                    socket.close(1000, 'Page closing');
                    console.log('✓ WebSocket fermé');
                }
            };
        }

        /**
         * Nettoie un AbortController
         * @param {AbortController} controller - Controller à annuler
         * @returns {Function} Callback de nettoyage
         */
        createAbortControllerCleanup(controller) {
            return () => {
                if (controller) {
                    controller.abort();
                    console.log('✓ AbortController annulé');
                }
            };
        }
    }

    // Instance globale
    const pageLifecycle = new PageLifecycleManager();

    // Initialiser automatiquement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => pageLifecycle.init());
    } else {
        pageLifecycle.init();
    }

    // Exposer globalement
    window.PageLifecycle = pageLifecycle;

    // Helpers pour une utilisation simple
    window.onPageCleanup = (callback, id) => pageLifecycle.registerCleanup(callback, id);
    window.cleanupInterval = (intervalId) => pageLifecycle.registerCleanup(
        pageLifecycle.createIntervalCleanup(intervalId),
        `interval_${intervalId}`
    );
    window.cleanupTimeout = (timeoutId) => pageLifecycle.registerCleanup(
        pageLifecycle.createTimeoutCleanup(timeoutId),
        `timeout_${timeoutId}`
    );

    console.log('📦 PageLifecycle module chargé');
})();

