/**
 * Correctif pour les requêtes API de notifications
 * Gère les erreurs d'authentification et les requêtes échouées
 */

(function() {
    'use strict';
    
    console.log('🔔 Correctif API notifications initialisé... v2.0');
    
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
        // Vérifier si l'utilisateur est authentifié avant de faire la requête
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.log('🔔 Pas de token CSRF trouvé, arrêt des requêtes notifications');
            return null;
        }
        
        // Vérifier si nous sommes sur une page d'authentification
        if (window.location.pathname.includes('/login') || 
            window.location.pathname.includes('/register') ||
            window.location.pathname.includes('/password/reset')) {
            console.log('🔔 Page d\'authentification détectée, arrêt des requêtes notifications');
            return null;
        }
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content')
            },
            timeout: CONFIG.timeout
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), CONFIG.timeout);
            
            const response = await fetch(url, {
                ...finalOptions,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                const error = new Error(`HTTP ${response.status}: ${response.statusText}`);
                // attacher explicitement le statut pour une détection fiable
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
        if (isRequesting) {
            console.log('🔔 Requête de notifications déjà en cours...');
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
                console.log('🔔 Utilisateur non authentifié, arrêt définitif des notifications');
                retryCount = 0;
                isRequesting = false;
                stopNotifications = true;
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
                return;
            }
            
            if (retryCount < CONFIG.maxRetries) {
                retryCount++;
                console.log(`🔄 Nouvelle tentative dans ${CONFIG.retryDelay}ms...`);
                
                setTimeout(() => {
                    isRequesting = false;
                    fetchNotificationsWithRetry();
                }, CONFIG.retryDelay);
            } else {
                console.error('❌ Échec définitif de la récupération des notifications');
                retryCount = 0;
                isRequesting = false;
            }
        }
    }
    
    /**
     * Mettre à jour l'interface utilisateur avec les notifications
     */
    function updateNotificationUI(data) {
        try {
            // Mettre à jour le compteur de notifications si l'élément existe
            const notificationCount = document.querySelector('.notification-count');
            if (notificationCount) {
                notificationCount.textContent = data.count || 0;
                notificationCount.style.display = data.count > 0 ? 'inline' : 'none';
            }
            
            // Mettre à jour la liste des notifications si l'élément existe
            const notificationList = document.querySelector('.notification-list');
            if (notificationList && data.notifications) {
                notificationList.innerHTML = '';
                
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = '<div class="text-gray-500 text-sm">Aucune notification</div>';
                } else {
                    data.notifications.forEach(notification => {
                        const notificationElement = document.createElement('div');
                        notificationElement.className = 'notification-item p-2 border-b border-gray-200';
                        notificationElement.innerHTML = `
                            <div class="text-sm font-medium">${notification.title || 'Notification'}</div>
                            <div class="text-xs text-gray-500">${notification.message || ''}</div>
                        `;
                        notificationList.appendChild(notificationElement);
                    });
                }
            }
            
        } catch (error) {
            console.warn('⚠️ Erreur lors de la mise à jour de l\'interface:', error);
        }
    }
    
    /**
     * Initialiser le système de notifications
     */
    function initNotificationSystem() {
        console.log('🔔 Initialisation du système de notifications...');
        
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
        
        // Écouter les événements de mise à jour des notifications
        document.addEventListener('notification-updated', () => {
            fetchNotificationsWithRetry();
        });
        
        console.log('✅ Système de notifications initialisé');
    }
    
    /**
     * Fonction utilitaire pour déclencher une mise à jour manuelle
     */
    window.refreshNotifications = function() {
        console.log('🔄 Mise à jour manuelle des notifications...');
        retryCount = 0;
        fetchNotificationsWithRetry();
    };
    
    /**
     * Fonction utilitaire pour obtenir le statut des notifications
     */
    window.getNotificationStatus = function() {
        return {
            isRequesting: isRequesting,
            retryCount: retryCount,
            maxRetries: CONFIG.maxRetries
        };
    };
    
    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotificationSystem);
    } else {
        initNotificationSystem();
    }
    
    console.log('✅ Correctif API notifications chargé');
})();
