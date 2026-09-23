/**
 * Exemple d'utilisation du composant PublicNotifications
 * 
 * Ce fichier montre comment intégrer les notifications publiques
 * dans différentes pages de votre application.
 */

// Exemple 1: Intégration simple dans une page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si le container existe
    const container = document.getElementById('public-notifications');
    
    if (container) {
        // Initialiser le composant avec les options par défaut
        const notifications = new PublicNotifications({
            container: '#public-notifications',
            apiUrl: '/public/notifications',
            maxNotifications: 5,
            autoRefresh: true,
            refreshInterval: 300000 // 5 minutes
        });
    }
});

// Exemple 2: Intégration avancée avec options personnalisées
function initializeAdvancedNotifications() {
    const notifications = new PublicNotifications({
        container: '#sidebar-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 3,
        showSystemNotifications: true,
        showAnnouncements: true,
        autoRefresh: true,
        refreshInterval: 600000 // 10 minutes
    });
    
    // Exposer l'instance pour un accès externe
    window.sidebarNotifications = notifications;
}

// Exemple 3: Intégration dans le header avec badge de compteur
function initializeHeaderNotifications() {
    const notifications = new PublicNotifications({
        container: '#header-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 3,
        showSystemNotifications: true,
        showAnnouncements: false, // Seulement les notifications système
        autoRefresh: true,
        refreshInterval: 180000 // 3 minutes
    });
    
    // Ajouter un badge de compteur
    const badge = document.createElement('span');
    badge.className = 'notification-badge';
    badge.style.cssText = `
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    `;
    
    const container = document.getElementById('header-notifications');
    if (container) {
        container.style.position = 'relative';
        container.appendChild(badge);
        
        // Mettre à jour le badge
        notifications.on('notificationsLoaded', function(count) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    }
}

// Exemple 4: Intégration dans une page de dashboard
function initializeDashboardNotifications() {
    const notifications = new PublicNotifications({
        container: '#dashboard-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 10,
        showSystemNotifications: true,
        showAnnouncements: true,
        autoRefresh: true,
        refreshInterval: 120000 // 2 minutes
    });
    
    // Ajouter des événements personnalisés
    notifications.on('notificationViewed', function(notificationId) {
        console.log('Notification vue:', notificationId);
        // Mettre à jour les statistiques du dashboard
        updateDashboardStats();
    });
}

// Exemple 5: Intégration mobile avec swipe
function initializeMobileNotifications() {
    const notifications = new PublicNotifications({
        container: '#mobile-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 5,
        showSystemNotifications: true,
        showAnnouncements: true,
        autoRefresh: false // Pas d'auto-refresh sur mobile pour économiser la batterie
    });
    
    // Ajouter le support du swipe (si la bibliothèque est disponible)
    if (typeof Swipeable !== 'undefined') {
        const container = document.getElementById('mobile-notifications');
        if (container) {
            new Swipeable(container, {
                onSwipeLeft: function() {
                    // Fermer les notifications
                    container.querySelector('.notifications-content').classList.add('hidden');
                },
                onSwipeRight: function() {
                    // Ouvrir les notifications
                    container.querySelector('.notifications-content').classList.remove('hidden');
                }
            });
        }
    }
}

// Exemple 6: Intégration avec des thèmes
function initializeThemedNotifications(theme = 'default') {
    const themes = {
        default: {
            container: '#notifications-default',
            apiUrl: '/public/notifications'
        },
        dark: {
            container: '#notifications-dark',
            apiUrl: '/public/notifications',
            theme: 'dark'
        },
        compact: {
            container: '#notifications-compact',
            apiUrl: '/public/notifications',
            maxNotifications: 3,
            compact: true
        }
    };
    
    const config = themes[theme] || themes.default;
    
    const notifications = new PublicNotifications(config);
    
    // Appliquer le thème CSS
    if (config.theme) {
        const container = document.querySelector(config.container);
        if (container) {
            container.classList.add(`theme-${config.theme}`);
        }
    }
    
    return notifications;
}

// Exemple 7: Intégration avec des filtres personnalisés
function initializeFilteredNotifications() {
    const notifications = new PublicNotifications({
        container: '#filtered-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 10,
        showSystemNotifications: true,
        showAnnouncements: true,
        autoRefresh: true
    });
    
    // Ajouter des filtres personnalisés
    const filterButtons = document.querySelectorAll('.notification-filter');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            notifications.filterByType(filter);
        });
    });
}

// Exemple 8: Intégration avec des notifications en temps réel
function initializeRealtimeNotifications() {
    const notifications = new PublicNotifications({
        container: '#realtime-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 5,
        autoRefresh: false // Pas d'auto-refresh car on utilise WebSocket
    });
    
    // Connexion WebSocket pour les notifications en temps réel
    if (typeof Echo !== 'undefined') {
        Echo.channel('public-notifications')
            .listen('PublicNotificationCreated', (e) => {
                notifications.addNotification(e.notification);
            })
            .listen('PublicNotificationUpdated', (e) => {
                notifications.updateNotification(e.notification);
            })
            .listen('PublicNotificationDeleted', (e) => {
                notifications.removeNotification(e.notificationId);
            });
    }
}

// Exemple 9: Intégration avec des préférences utilisateur
function initializeUserPreferencesNotifications() {
    // Récupérer les préférences utilisateur depuis localStorage
    const preferences = JSON.parse(localStorage.getItem('notificationPreferences') || '{}');
    
    const notifications = new PublicNotifications({
        container: '#preferences-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: preferences.maxNotifications || 5,
        showSystemNotifications: preferences.showSystem !== false,
        showAnnouncements: preferences.showAnnouncements !== false,
        autoRefresh: preferences.autoRefresh !== false,
        refreshInterval: preferences.refreshInterval || 300000
    });
    
    // Sauvegarder les préférences quand elles changent
    notifications.on('preferencesChanged', function(newPreferences) {
        localStorage.setItem('notificationPreferences', JSON.stringify(newPreferences));
    });
}

// Exemple 10: Intégration avec des analytics
function initializeAnalyticsNotifications() {
    const notifications = new PublicNotifications({
        container: '#analytics-notifications',
        apiUrl: '/public/notifications',
        maxNotifications: 5,
        autoRefresh: true
    });
    
    // Tracker les interactions avec les notifications
    notifications.on('notificationViewed', function(notificationId) {
        // Envoyer l'événement aux analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'notification_viewed', {
                notification_id: notificationId,
                event_category: 'notifications'
            });
        }
    });
    
    notifications.on('notificationClicked', function(notificationId) {
        // Envoyer l'événement aux analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'notification_clicked', {
                notification_id: notificationId,
                event_category: 'notifications'
            });
        }
    });
}

// Fonction utilitaire pour mettre à jour les statistiques du dashboard
function updateDashboardStats() {
    // Cette fonction peut être personnalisée selon vos besoins
    console.log('Mise à jour des statistiques du dashboard...');
}

// Initialisation automatique basée sur la page
document.addEventListener('DOMContentLoaded', function() {
    const page = document.body.dataset.page;
    
    switch (page) {
        case 'dashboard':
            initializeDashboardNotifications();
            break;
        case 'mobile':
            initializeMobileNotifications();
            break;
        case 'realtime':
            initializeRealtimeNotifications();
            break;
        case 'preferences':
            initializeUserPreferencesNotifications();
            break;
        case 'analytics':
            initializeAnalyticsNotifications();
            break;
        default:
            // Initialisation par défaut
            const container = document.getElementById('public-notifications');
            if (container) {
                new PublicNotifications({
                    container: '#public-notifications'
                });
            }
    }
}); 