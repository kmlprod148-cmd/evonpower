/**
 * Composant pour les notifications publiques
 * Permet d'intégrer les notifications publiques dans n'importe quelle page
 */
class PublicNotifications {
    constructor(options = {}) {
        this.options = {
            container: options.container || '#public-notifications',
            apiUrl: options.apiUrl || '/public/notifications',
            refreshInterval: options.refreshInterval || 300000, // 5 minutes
            maxNotifications: options.maxNotifications || 5,
            showSystemNotifications: options.showSystemNotifications !== false,
            showAnnouncements: options.showAnnouncements !== false,
            autoRefresh: options.autoRefresh !== false,
            ...options
        };
        
        this.container = document.querySelector(this.options.container);
        this.notifications = [];
        this.isInitialized = false;
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.warn('PublicNotifications: Container not found');
            return;
        }
        
        this.createNotificationContainer();
        this.loadNotifications();
        
        if (this.options.autoRefresh) {
            this.startAutoRefresh();
        }
        
        this.isInitialized = true;
    }
    
    createNotificationContainer() {
        if (!this.container.querySelector('.notifications-wrapper')) {
            this.container.innerHTML = `
                <div class="notifications-wrapper">
                    <div class="notifications-header">
                        <h3 class="notifications-title">
                            <i class="fas fa-bell mr-2"></i>
                            Notifications
                        </h3>
                        <button class="notifications-toggle" aria-label="Toggle notifications">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="notifications-content">
                        <div class="notifications-list"></div>
                        <div class="notifications-empty hidden">
                            <p>Aucune notification</p>
                        </div>
                        <div class="notifications-loading">
                            <div class="spinner"></div>
                            <p>Chargement...</p>
                        </div>
                    </div>
                </div>
            `;
            
            this.bindEvents();
        }
    }
    
    bindEvents() {
        const toggle = this.container.querySelector('.notifications-toggle');
        const content = this.container.querySelector('.notifications-content');
        
        if (toggle && content) {
            toggle.addEventListener('click', () => {
                content.classList.toggle('hidden');
                const icon = toggle.querySelector('i');
                if (content.classList.contains('hidden')) {
                    icon.className = 'fas fa-chevron-down';
                } else {
                    icon.className = 'fas fa-chevron-up';
                }
            });
        }
    }
    
    async loadNotifications() {
        try {
            this.showLoading();
            
            const promises = [];
            
            if (this.options.showSystemNotifications) {
                promises.push(this.fetchSystemNotifications());
            }
            
            if (this.options.showAnnouncements) {
                promises.push(this.fetchAnnouncements());
            }
            
            const results = await Promise.all(promises);
            
            this.notifications = results.flat().slice(0, this.options.maxNotifications);
            this.renderNotifications();
            
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
            this.showError();
        }
    }
    
    async fetchSystemNotifications() {
        try {
            const response = await fetch(`${this.options.apiUrl}/system`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            return data.success ? data.notifications : [];
        } catch (error) {
            console.error('Erreur lors du chargement des notifications système:', error);
            return [];
        }
    }
    
    async fetchAnnouncements() {
        try {
            const response = await fetch(`${this.options.apiUrl}/announcements`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            return data.success ? data.announcements : [];
        } catch (error) {
            console.error('Erreur lors du chargement des annonces:', error);
            return [];
        }
    }
    
    renderNotifications() {
        const list = this.container.querySelector('.notifications-list');
        const empty = this.container.querySelector('.notifications-empty');
        const loading = this.container.querySelector('.notifications-loading');
        
        if (!list || !empty || !loading) return;
        
        loading.classList.add('hidden');
        
        if (this.notifications.length === 0) {
            list.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
        }
        
        list.classList.remove('hidden');
        empty.classList.add('hidden');
        
        list.innerHTML = this.notifications.map(notification => `
            <div class="notification-item" data-id="${notification.id}" data-type="${notification.type}">
                <div class="notification-header">
                    <span class="notification-type-indicator ${this.getTypeClass(notification.type)}"></span>
                    <h4 class="notification-title">${this.escapeHtml(notification.title)}</h4>
                    ${notification.priority && notification.priority !== 'normal' ? 
                        `<span class="notification-priority ${this.getPriorityClass(notification.priority)}">${notification.priority}</span>` : 
                        ''
                    }
                </div>
                <p class="notification-message">${this.escapeHtml(notification.message)}</p>
                <div class="notification-footer">
                    <span class="notification-time">${notification.human_time || this.formatTime(notification.created_at)}</span>
                    <button class="notification-view-btn" data-id="${notification.id}">
                        Voir plus
                    </button>
                </div>
            </div>
        `).join('');
        
        this.bindNotificationEvents();
    }
    
    bindNotificationEvents() {
        const viewButtons = this.container.querySelectorAll('.notification-view-btn');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const id = button.dataset.id;
                this.viewNotification(id);
            });
        });
    }
    
    async viewNotification(id) {
        try {
            const response = await fetch(`${this.options.apiUrl}/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotificationModal(data.notification);
            }
        } catch (error) {
            console.error('Erreur lors du chargement de la notification:', error);
        }
    }
    
    showNotificationModal(notification) {
        // Créer le modal s'il n'existe pas
        let modal = document.getElementById('notification-modal');
        
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'notification-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 hidden z-50';
            modal.innerHTML = `
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 id="modal-title" class="text-xl font-semibold"></h3>
                                <button id="modal-close" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <div id="modal-content" class="text-gray-700 mb-4"></div>
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <span id="modal-time"></span>
                                <button id="modal-viewed-btn" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    Marquer comme vue
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Bind modal events
            const closeBtn = modal.querySelector('#modal-close');
            const viewedBtn = modal.querySelector('#modal-viewed-btn');
            
            closeBtn.addEventListener('click', () => {
                modal.classList.add('hidden');
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
            
            viewedBtn.addEventListener('click', () => {
                this.markAsViewed(notification.id);
                viewedBtn.textContent = 'Vue';
                viewedBtn.classList.add('text-green-600');
                viewedBtn.disabled = true;
            });
        }
        
        // Remplir le modal
        modal.querySelector('#modal-title').textContent = notification.title;
        modal.querySelector('#modal-content').textContent = notification.message;
        modal.querySelector('#modal-time').textContent = notification.human_time || this.formatTime(notification.created_at);
        
        // Afficher le modal
        modal.classList.remove('hidden');
    }
    
    async markAsViewed(id) {
        try {
            await fetch(`${this.options.apiUrl}/${id}/viewed`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        } catch (error) {
            console.error('Erreur lors du marquage:', error);
        }
    }
    
    showLoading() {
        const loading = this.container.querySelector('.notifications-loading');
        const list = this.container.querySelector('.notifications-list');
        const empty = this.container.querySelector('.notifications-empty');
        
        if (loading) loading.classList.remove('hidden');
        if (list) list.classList.add('hidden');
        if (empty) empty.classList.add('hidden');
    }
    
    showError() {
        const loading = this.container.querySelector('.notifications-loading');
        const list = this.container.querySelector('.notifications-list');
        const empty = this.container.querySelector('.notifications-empty');
        
        if (loading) loading.classList.add('hidden');
        if (list) list.classList.add('hidden');
        if (empty) {
            empty.classList.remove('hidden');
            empty.innerHTML = '<p>Erreur de chargement</p>';
        }
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.loadNotifications();
        }, this.options.refreshInterval);
    }
    
    refresh() {
        this.loadNotifications();
    }
    
    getTypeClass(type) {
        const classes = {
            'system': 'bg-blue-500',
            'announcement': 'bg-yellow-500',
            'warning': 'bg-red-500',
            'error': 'bg-red-500',
            'success': 'bg-green-500',
            'info': 'bg-blue-500'
        };
        
        return classes[type] || 'bg-gray-500';
    }
    
    getPriorityClass(priority) {
        const classes = {
            'urgent': 'bg-red-100 text-red-800',
            'high': 'bg-yellow-100 text-yellow-800',
            'normal': 'bg-gray-100 text-gray-800',
            'low': 'bg-green-100 text-green-800'
        };
        
        return classes[priority] || classes.normal;
    }
    
    formatTime(timeString) {
        const date = new Date(timeString);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'À l\'instant';
        if (minutes < 60) return `Il y a ${minutes} min`;
        if (hours < 24) return `Il y a ${hours}h`;
        if (days < 7) return `Il y a ${days}j`;
        
        return date.toLocaleDateString('fr-FR');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export pour utilisation globale
window.PublicNotifications = PublicNotifications; 