@props([
    'notifications' => [],
    'unreadCount' => 0
])

<div class="relative" x-data="notificationCenter()">
    <!-- Notification Bell Button -->
    <button @click="toggleNotifications()" 
            class="evon-header-button relative group"
            :class="{ 'ring-2 ring-eco-green-500': isOpen }"
            aria-label="Notifications"
            :aria-expanded="isOpen.toString()">
        
        <!-- Bell Icon with Animation -->
        <div class="relative" :class="{ 'animate-wiggle': hasNewNotification }">
            @include('components.icons.lucide-bell', ['size' => 20, 'class' => 'text-gray-600 dark:text-gray-400 group-hover:text-eco-green-600 dark:group-hover:text-eco-green-400 transition-colors'])
            
            <!-- Unread Badge -->
            @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 text-[10px] font-bold text-white items-center justify-center">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            </span>
            @endif
        </div>
    </button>

    <!-- Notifications Panel -->
    <div x-show="isOpen"
         x-cloak
         @click.away="isOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-3 w-96 max-w-[calc(100vw-2rem)] origin-top-right z-50">
        
        <!-- Panel Container -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden">
            
            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-eco-green-50 to-blue-50 dark:from-gray-800 dark:to-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Notifications
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="unreadText()"></p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Mark All as Read -->
                        <button @click="markAllAsRead()" 
                                x-show="unreadCount > 0"
                                class="text-xs font-medium text-eco-green-600 hover:text-eco-green-700 dark:text-eco-green-400 dark:hover:text-eco-green-300 transition-colors"
                                title="Tout marquer comme lu">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        
                        <!-- Settings -->
                        <button @click="openSettings()" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                title="Paramètres des notifications">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <button @click="activeTab = 'all'" 
                        class="flex-1 px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'all' 
                            ? 'text-eco-green-600 dark:text-eco-green-400 border-b-2 border-eco-green-600 dark:border-eco-green-400' 
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    Toutes <span class="ml-1 text-xs" x-text="'(' + notifications.length + ')'"></span>
                </button>
                <button @click="activeTab = 'unread'" 
                        class="flex-1 px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'unread' 
                            ? 'text-eco-green-600 dark:text-eco-green-400 border-b-2 border-eco-green-600 dark:border-eco-green-400' 
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    Non lues <span class="ml-1 text-xs" x-text="'(' + unreadCount + ')'"></span>
                </button>
                <button @click="activeTab = 'important'" 
                        class="flex-1 px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'important' 
                            ? 'text-eco-green-600 dark:text-eco-green-400 border-b-2 border-eco-green-600 dark:border-eco-green-400' 
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    Importantes <span class="ml-1 text-xs text-red-500">●</span>
                </button>
            </div>

            <!-- Notifications List -->
            <div class="max-h-96 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="notification in filteredNotifications()" :key="notification.id">
                    <div @click="handleNotificationClick(notification)"
                         class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
                         :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read }">
                        
                        <div class="flex items-start gap-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                     :class="getNotificationIconClass(notification.type)">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              :d="getNotificationIconPath(notification.type)" />
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5 line-clamp-2" x-text="notification.message"></p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(notification.created_at)"></span>
                                    <span x-show="notification.important" class="text-xs font-medium text-red-600 dark:text-red-400">
                                        • Important
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Unread Indicator -->
                            <div x-show="!notification.read" class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="filteredNotifications().length === 0" 
                     class="px-4 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aucune notification</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('notifications.index') }}" 
                   class="block text-center text-sm font-medium text-eco-green-600 hover:text-eco-green-700 dark:text-eco-green-400 dark:hover:text-eco-green-300 transition-colors">
                    Voir toutes les notifications →
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function notificationCenter() {
    return {
        isOpen: false,
        activeTab: 'all',
        hasNewNotification: false,
        unreadCount: {{ $unreadCount }},
        notifications: @json($notifications),

        toggleNotifications() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.hasNewNotification = false;
                // Marquer comme vu
                this.markAsViewed();
            }
        },

        filteredNotifications() {
            if (this.activeTab === 'all') {
                return this.notifications;
            } else if (this.activeTab === 'unread') {
                return this.notifications.filter(n => !n.read);
            } else if (this.activeTab === 'important') {
                return this.notifications.filter(n => n.important);
            }
            return this.notifications;
        },

        unreadText() {
            return this.unreadCount > 0 
                ? `${this.unreadCount} non ${this.unreadCount > 1 ? 'lues' : 'lue'}` 
                : 'Aucune nouvelle notification';
        },

        getNotificationIconClass(type) {
            const classes = {
                'success': 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                'warning': 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
                'error': 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
                'info': 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                'default': 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
            };
            return classes[type] || classes['default'];
        },

        getNotificationIconPath(type) {
            const paths = {
                'success': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'warning': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'error': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                'info': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'default': 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'
            };
            return paths[type] || paths['default'];
        },

        formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);

            if (minutes < 1) return 'À l\'instant';
            if (minutes < 60) return `Il y a ${minutes} min`;
            if (hours < 24) return `Il y a ${hours}h`;
            if (days < 7) return `Il y a ${days}j`;
            return date.toLocaleDateString('fr-FR');
        },

        handleNotificationClick(notification) {
            if (!notification.read) {
                this.markAsRead(notification.id);
            }
            if (notification.action_url) {
                window.location.href = notification.action_url;
            }
        },

        markAsRead(id) {
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                const notification = this.notifications.find(n => n.id === id);
                if (notification && !notification.read) {
                    notification.read = true;
                    this.unreadCount--;
                }
            });
        },

        markAllAsRead() {
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => {
                this.notifications.forEach(n => n.read = true);
                this.unreadCount = 0;
            });
        },

        markAsViewed() {
            fetch('/notifications/viewed', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
        },

        openSettings() {
            window.location.href = '{{ route("settings.notifications") }}';
        }
    }
}

// Wiggle animation
const style = document.createElement('style');
style.textContent = `
    @keyframes wiggle {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(-10deg); }
        75% { transform: rotate(10deg); }
    }
    .animate-wiggle {
        animation: wiggle 0.5s ease-in-out;
    }
`;
document.head.appendChild(style);
</script>

