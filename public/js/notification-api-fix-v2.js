/**
 * Correctif pour l'API de notifications
 * Version 2.1 - Prévention des initialisations multiples
 */

(function() {
    'use strict';
    
    // Debug mode - set to true for verbose logging
    const DEBUG = false;
    const log = DEBUG ? console.log.bind(console) : () => {};
    
    // Garde global pour éviter les initialisations multiples
    if (window.__NOTIFICATION_SYSTEM_INITIALIZED__) {
        return;
    }
    
    // Marquer comme initialisé
    window.__NOTIFICATION_SYSTEM_INITIALIZED__ = true;
    
    // Configuration
    const CONFIG = {
        apiUrl: '/api/v1/admin/notifications/unread',
        retryDelay: 2000,
        maxRetries: 3,
        timeout: 5000
    };
    
    // Variables globales
    let retryCount = 0;
    let isRequesting = false;
    let stopNotifications = false;
    let intervalId = null;
    
    /**
     * Fonction pour faire une requête API sécurisée
     */
    async function makeSecureApiRequest(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.timeout);
        
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin',
                ...options,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                const error = new Error(`HTTP ${response.status}: ${response.statusText}`);
                error.status = response.status;
                throw error;
            }
            
            return await response.json();
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('Timeout de la requête');
            }
            throw error;
        }
    }
    
    /**
     * Fonction pour récupérer les notifications avec retry
     */
    async function fetchNotificationsWithRetry() {
        if (isRequesting || stopNotifications) {
            return;
        }
        
        isRequesting = true;
        
        try {
            console.log(`🔔 Tentative ${retryCount + 1} de récupération des notifications...`);
            
            const data = await makeSecureApiRequest(CONFIG.apiUrl);
            
            if (data.success) {
                console.log(`✅ Notifications récupérées: ${data.count} non lues`);
                retryCount = 0; // Reset du compteur en cas de succès
                
                // Mettre à jour l'interface utilisateur si nécessaire
                updateNotificationUI(data);
                
                return data;
            } else {
                throw new Error(data.message || 'Erreur inconnue');
            }
            
        } catch (error) {
            console.warn(`⚠️ Erreur lors de la récupération des notifications: ${error.message}`);
            
            // Si c'est une erreur 401 (Unauthorized), arrêter immédiatement et désactiver le polling
            if (error.status === 401 || error.message.includes('401') || error.message.includes('Unauthorized')) {
                log('🔔 Utilisateur non authentifié, arrêt définitif des notifications');
                stopNotifications = true;
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
                return;
            }
            
            // Si on a atteint le nombre maximum de tentatives
            if (retryCount >= CONFIG.maxRetries) {
                console.error('❌ Échec définitif de la récupération des notifications');
                stopNotifications = true;
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
                return;
            }
            
            // Incrémenter le compteur et programmer une nouvelle tentative
            retryCount++;
            console.log(`🔄 Nouvelle tentative dans ${CONFIG.retryDelay}ms...`);
            
            setTimeout(() => {
                isRequesting = false;
                if (!stopNotifications) {
                    fetchNotificationsWithRetry();
                }
            }, CONFIG.retryDelay);
            
        } finally {
            isRequesting = false;
        }
    }
    
    /**
     * Fonction pour mettre à jour l'interface utilisateur
     */
    function updateNotificationUI(data) {
        // Mettre à jour le badge de notifications si il existe
        const notificationBadge = document.querySelector('.notification-badge');
        if (notificationBadge) {
            notificationBadge.textContent = data.count || 0;
            notificationBadge.style.display = data.count > 0 ? 'inline' : 'none';
        }
    }
    
    /**
     * Fonction d'initialisation du système de notifications
     */
    function initNotificationSystem() {
        // Vérifier si déjà initialisé
        if (window.__NOTIFICATION_SYSTEM_STARTED__) {
            log('🔔 Système de notifications déjà démarré, ignoré');
            return;
        }
        
        window.__NOTIFICATION_SYSTEM_STARTED__ = true;
        log('🔔 Initialisation du système de notifications...');
        
        // Vérifier si l'utilisateur est authentifié avant de démarrer
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            log('🔔 Utilisateur non authentifié, notifications désactivées');
            stopNotifications = true;
            return;
        }
        
        // Récupérer les notifications au chargement
        if (!stopNotifications) {
            fetchNotificationsWithRetry();
        }
        
        // Récupérer les notifications périodiquement (toutes les 30 secondes)
        intervalId = setInterval(() => {
            if (!isRequesting && !stopNotifications) {
                fetchNotificationsWithRetry();
            }
        }, 30000);
        
        log('✅ Système de notifications initialisé');
    }
    
    // Initialisation automatique (une seule fois)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotificationSystem, { once: true });
    } else {
        // Utiliser un petit délai pour éviter les conflits avec d'autres scripts
        setTimeout(initNotificationSystem, 100);
    }
    
    log('✅ Correctif API notifications v2.1 chargé');
    
})();
