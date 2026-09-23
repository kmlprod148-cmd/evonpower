@php
    use App\Services\NotificationService;
    
    $notificationService = app(NotificationService::class);
    $unreadCount = $notificationService->getUnreadCount();
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open"
            class="p-2 rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
            aria-label="{{ __('Notifications') }}">
        <span class="sr-only">{{ __('Notifications') }}</span>
        <div class="relative">
            <!-- Bell Icon -->
            @include('components.icons.lucide-bell', ['size' => 24, 'class' => 'text-current'])
            @if($unreadCount > 0)
                <span id="notification-counter" class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] text-xs font-bold leading-none text-white bg-red-500 rounded-full px-1">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @else
                <span id="notification-counter" class="hidden absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] text-xs font-bold leading-none text-white bg-red-500 rounded-full px-1">
                    0
                </span>
            @endif
        </div>
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none z-50"
         style="display: none;">

        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Notifications') }}</h3>
                @if($unreadCount > 0)
                <button @click="markAllAsRead()"
                        class="text-xs text-primary hover:text-primary/80 font-medium transition-colors duration-200">
                    {{ __('Tout marquer comme lu') }}
                </button>
                @endif
            </div>
        </div>

        <!-- Notifications Content -->
        <div id="notifications-container" class="max-h-96 overflow-y-auto">
            @if($unreadCount > 0)
            <div class="p-4 text-center">
                <svg class="animate-spin h-5 w-5 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">{{ __('Chargement...') }}</p>
            </div>
            @else
            <div class="p-6 text-center">
                <svg class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('Aucune notification') }}</p>
                <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">{{ __('Vous êtes à jour !') }}</p>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <a href="#"
               class="text-xs text-gray-600 dark:text-gray-400 hover:text-primary transition-colors duration-200">
                {{ __('Voir toutes les notifications') }}
            </a>
        </div>
    </div>
</div>

@auth
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationButton = document.querySelector('button[aria-label="{{ __('Notifications') }}"]');
        const notificationsContainer = document.getElementById('notifications-container');
        let notificationsLoaded = false;

        if (notificationButton) {
            notificationButton.addEventListener('click', function() {
                // Only load notifications if they haven't been loaded yet and there are unread notifications
                if (!notificationsLoaded && {{ $unreadCount }} > 0) {
                    loadNotifications();
                }
            });
        }

        function loadNotifications() {
            // Check if route exists before making the request
            @if(Route::has('admin.notifications.api.unread'))
            fetch('{{ route('admin.notifications.api.unread') }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 401 || response.status === 403) {
                            // Silently handle authentication errors
                            notificationsContainer.innerHTML = `
                                <div class="p-4 text-center">
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">Notifications non disponibles</p>
                                </div>
                            `;
                            return;
                        }
                        if (response.status === 500) {
                            console.error('Notifications: Erreur serveur');
                            notificationsContainer.innerHTML = `
                                <div class="p-4 text-center">
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">Service temporairement indisponible</p>
                                </div>
                            `;
                            return;
                        }
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.notifications) {
                        // Afficher les notifications
                        if (data.notifications.length === 0) {
                            notificationsContainer.innerHTML = `
                                <div class="p-4 text-center">
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">Aucune notification</p>
                                </div>
                            `;
                        } else {
                            // Construire le HTML des notifications
                            let html = '';
                            data.notifications.forEach(notification => {
                                html += `
                                    <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                                        <p class="text-sm text-gray-900 dark:text-white">${notification.title || 'Notification'}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">${notification.created_at}</p>
                                    </div>
                                `;
                            });
                            notificationsContainer.innerHTML = html;
                        }
                        notificationsLoaded = true;
                    } else {
                        throw new Error(data.message || 'Erreur inconnue');
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    notificationsContainer.innerHTML = `
                        <div class="p-4 text-center">
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Erreur de chargement</p>
                        </div>
                    `;
                });
            @else
            console.warn('Notification API route not found');
            notificationsContainer.innerHTML = `
                <div class="p-4 text-center">
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Service non disponible</p>
                </div>
            `;
            @endif
        }

        // Function to mark all notifications as read
        window.markAllAsRead = function() {
            @if(Route::has('admin.notifications.mark-all-read'))
            fetch('{{ route('admin.notifications.mark-all-read') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationCounter(0);
                    notificationsContainer.innerHTML = `
                        <div class="p-6 text-center">
                            <svg class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('Aucune notification') }}</p>
                            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">{{ __('Vous êtes à jour !') }}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error marking notifications as read:', error);
            });
            @endif
        };

        function updateNotificationCounter(count) {
            const counter = document.getElementById('notification-counter');
            if (counter) {
                if (count > 0) {
                    counter.textContent = count > 99 ? '99+' : count;
                    counter.classList.remove('hidden');
                } else {
                    counter.classList.add('hidden');
                }
            }
        }

        // Refresh notification count every 2 minutes
        function refreshNotificationCount() {
            @if(Route::has('admin.notifications.api.unread'))
            fetch('{{ route('admin.notifications.api.unread') }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 401) {
                            console.log('Notifications: Utilisateur non authentifié');
                            updateNotificationCounter(0);
                            return;
                        }
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && typeof data.count !== 'undefined') {
                        updateNotificationCounter(data.count);
                    }
                })
                .catch(error => {
                    console.error('Error refreshing notification count:', error);
                    updateNotificationCounter(0);
                });
            @endif
        }

        // Refresh every 2 minutes
        setInterval(refreshNotificationCount, 120000);
    });
</script>
@endauth