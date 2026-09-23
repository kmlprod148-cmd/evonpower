@if($notifications->count() > 0)
    <div class="p-2 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h6 class="text-sm font-medium text-gray-700">{{ __('Notifications') }}</h6>
            <button id="mark-all-read" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                {{ __('Tout marquer comme lu') }}
            </button>
        </div>
    </div>
    
    <div class="max-h-72 overflow-y-auto">
        @foreach($notifications as $notification)
            <div class="notification-item p-3 border-b border-gray-100 hover:bg-gray-50" data-id="{{ $notification->id }}">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        @if($notification->type == 'info')
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        @elseif($notification->type == 'success')
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        @elseif($notification->type == 'warning')
                            <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        @elseif($notification->type == 'error')
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <h6 class="text-sm font-medium text-gray-800">{{ Str::limit($notification->title, 40) }}</h6>
                            <span class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        @if($notification->content)
                            <p class="text-xs text-gray-600 mt-1">{{ Str::limit($notification->content, 60) }}</p>
                        @endif
                        <div class="mt-2 flex justify-between">
                            @if($notification->link)
                                <a href="{{ $notification->link }}" class="text-xs text-blue-600 hover:text-blue-800">
                                    {{ __('Voir détails') }}
                                </a>
                            @else
                                <span></span>
                            @endif
                            <button class="text-xs text-gray-500 hover:text-gray-700 mark-as-read" data-id="{{ $notification->id }}">
                                {{ __('Marquer comme lu') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="p-6 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
        <p class="text-gray-500">{{ __('Aucune notification non lue') }}</p>
    </div>
@endif

<div class="p-2 border-t border-gray-200 text-center">
    <a href="{{ route('admin.notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
        {{ __('Voir toutes les notifications') }}
    </a>
</div>

<script>
    // Marquer une notification comme lue
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const id = this.dataset.id;
            const item = this.closest('.notification-item');
            
            fetch('{{ route('admin.notifications.mark-as-read', '') }}/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    item.remove();
                    
                    // Mettre à jour le compteur
                    const counter = document.getElementById('notification-counter');
                    if (counter) {
                        const count = parseInt(counter.textContent) - 1;
                        
                        if (count > 0) {
                            counter.textContent = count;
                        } else {
                            counter.classList.add('hidden');
                            
                            // S'il n'y a plus de notifications, afficher le message vide
                            if (document.querySelectorAll('.notification-item').length === 0) {
                                document.querySelector('.max-h-72').innerHTML = `
                                    <div class="p-6 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <p class="text-gray-500">{{ __('Aucune notification non lue') }}</p>
                                    </div>
                                `;
                            }
                        }
                    }
                }
            });
        });
    });
    
    // Marquer toutes les notifications comme lues
    const markAllReadButton = document.getElementById('mark-all-read');
    if (markAllReadButton) {
        markAllReadButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            fetch('{{ route('admin.notifications.mark-all-as-read') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface
                    const counter = document.getElementById('notification-counter');
                    if (counter) {
                        counter.classList.add('hidden');
                    }
                    
                    const container = document.querySelector('.max-h-72');
                    if (container) {
                        container.innerHTML = `
                            <div class="p-6 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <p class="text-gray-500">{{ __('Aucune notification non lue') }}</p>
                            </div>
                        `;
                    }
                }
            });
        });
    }
    
    // Rediriger vers le lien de la notification si on clique sur l'élément
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const link = this.querySelector('a')?.getAttribute('href');
            
            // Marquer comme lu
            fetch('{{ route('admin.notifications.mark-as-read', '') }}/' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            // Rediriger si un lien existe
            if (link) {
                window.location.href = link;
            }
        });
    });
</script>