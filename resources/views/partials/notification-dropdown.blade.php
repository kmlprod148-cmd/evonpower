@php
    $unreadCount = 0;
    if (auth()->check()) {
        $unreadCount = App\Models\AdminNotification::unread()
            ->notExpired()
            ->forRole(auth()->user()->getRoleNames()->toArray())
            ->count();
    }
@endphp

<div x-data="{ open: false }" class="relative ml-3">
    <button @click="open = !open" 
            class="p-1 rounded-full text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-evon-green-500"
            aria-label="{{ __('Notifications') }}">
        <span class="sr-only">{{ __('Notifications') }}</span>
        <div class="relative">
            <!-- Bell Icon -->
            @include('components.icons.lucide-bell', ['size' => 24, 'class' => 'text-current'])
            @if($unreadCount > 0)
                <span id="notification-counter" class="absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
                    {{ $unreadCount }}
                </span>
            @else
                <span id="notification-counter" class="hidden absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
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
         class="origin-top-right absolute right-0 mt-2 w-80 sm:w-96 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
         style="display: none;">
        
        <!-- Contenu du dropdown chargé en Ajax -->
        <div id="notifications-container" class="w-full">
            <div class="p-4 text-center">
                <svg class="animate-spin h-5 w-5 text-gray-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-gray-500">{{ __('Chargement...') }}</p>
            </div>
        </div>
    </div>
</div>

@auth
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationButton = document.querySelector('button[aria-label="Notifications"]');
        const notificationsContainer = document.getElementById('notifications-container');
        
        if (notificationButton) {
            notificationButton.addEventListener('click', function() {
                // Chargement des notifications (JSON + cookies Sanctum)
                fetch('{{ route('admin.notifications.api.unread') }}', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'include'
                })
                    .then(response => response.json())
                    .then(data => {
                        notificationsContainer.innerHTML = renderNotificationsHtml(data.notifications || []);
                    })
                    .catch(error => {
                        console.error('Error loading notifications:', error);
                        notificationsContainer.innerHTML = `
                            <div class="p-4 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 text-gray-700">{{ __('Erreur de chargement') }}</p>
                            </div>
                        `;
                    });
            });
        }
        
        // Actualiser le compteur de notifications toutes les 2 minutes
        function refreshNotificationCount() {
            fetch('{{ route('admin.notifications.api.unread') }}', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'include'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const counter = document.getElementById('notification-counter');
                        if (counter) {
                            if (data.count > 0) {
                                counter.textContent = data.count;
                                counter.classList.remove('hidden');
                            } else {
                                counter.classList.add('hidden');
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error refreshing notification count:', error);
                });
        }
        
        // Fonction pour rendre le HTML des notifications
        function renderNotificationsHtml(items) {
            if (!items.length) {
                return `<div class="p-6 text-center text-sm text-gray-500">{{ __('Aucune notification') }}</div>`;
            }
            return items.map(n => `
                <div class="px-4 py-3 border-b border-gray-100">
                    <div class="text-sm font-medium">${n.title ?? 'Notification'}</div>
                    <div class="text-xs text-gray-500">${n.created_at ?? ''}</div>
                </div>
            `).join('');
        }
        
        // Actualiser toutes les 2 minutes (120000 ms)
        setInterval(refreshNotificationCount, 120000);
    });
</script>
@endauth