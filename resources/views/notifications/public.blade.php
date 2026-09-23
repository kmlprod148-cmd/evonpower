@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Notifications Publiques</h1>
                <div class="flex space-x-2">
                    <button id="refreshBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Actualiser
                    </button>
                </div>
            </div>

            <!-- Filtres -->
            <div class="mb-6">
                <div class="flex flex-wrap gap-2">
                    <button class="filter-btn active bg-blue-500 text-white px-4 py-2 rounded-lg" data-filter="all">
                        Toutes
                    </button>
                    <button class="filter-btn bg-green-500 text-white px-4 py-2 rounded-lg" data-filter="system">
                        Système
                    </button>
                    <button class="filter-btn bg-yellow-500 text-white px-4 py-2 rounded-lg" data-filter="announcement">
                        Annonces
                    </button>
                    <button class="filter-btn bg-red-500 text-white px-4 py-2 rounded-lg" data-filter="warning">
                        Alertes
                    </button>
                </div>
            </div>

            <!-- Liste des notifications -->
            <div id="notificationsList" class="space-y-4">
                @forelse($notifications as $notification)
                    <div class="notification-item border rounded-lg p-4 hover:shadow-md transition-shadow" 
                         data-type="{{ $notification->type }}">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="inline-block w-3 h-3 rounded-full mr-3 
                                        @if($notification->type === 'system') bg-blue-500
                                        @elseif($notification->type === 'announcement') bg-yellow-500
                                        @elseif($notification->type === 'warning') bg-red-500
                                        @else bg-gray-500
                                        @endif"></span>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $notification->title }}</h3>
                                    @if($notification->priority === 'high' || $notification->priority === 'urgent')
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full
                                            @if($notification->priority === 'urgent') bg-red-100 text-red-800
                                            @else bg-yellow-100 text-yellow-800
                                            @endif">
                                            {{ ucfirst($notification->priority) }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-gray-700 mb-3">{{ $notification->message }}</p>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                    <button class="view-btn text-blue-600 hover:text-blue-800 hover:underline" 
                                            data-id="{{ $notification->id }}">
                                        Voir plus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-bell text-4xl"></i>
                        </div>
                        <p class="text-gray-500">Aucune notification publique pour le moment</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($notifications->hasPages())
                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal pour afficher une notification -->
<div id="notificationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="modalTitle" class="text-xl font-semibold"></h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div id="modalContent" class="text-gray-700 mb-4"></div>
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span id="modalTime"></span>
                    <button id="markViewedBtn" class="text-blue-600 hover:text-blue-800 hover:underline">
                        Marquer comme vue
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationsList = document.getElementById('notificationsList');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const refreshBtn = document.getElementById('refreshBtn');
    const modal = document.getElementById('notificationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    const modalTime = document.getElementById('modalTime');
    const closeModal = document.getElementById('closeModal');
    const markViewedBtn = document.getElementById('markViewedBtn');
    let currentNotificationId = null;

    // Filtrage des notifications
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Mettre à jour les boutons actifs
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Filtrer les notifications
            const items = document.querySelectorAll('.notification-item');
            items.forEach(item => {
                if (filter === 'all' || item.dataset.type === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Actualiser les notifications
    refreshBtn.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Actualisation...';
        
        fetch('{{ route("public.notifications.index") }}', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'actualisation:', error);
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Actualiser';
        });
    });

    // Ouvrir le modal
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            currentNotificationId = id;
            
            fetch(`{{ route('public.notifications.show', ['id' => '__ID__']) }}`.replace('__ID__', id), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = data.notification;
                    modalTitle.textContent = notification.title;
                    modalContent.textContent = notification.message;
                    modalTime.textContent = notification.human_time;
                    modal.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement de la notification:', error);
            });
        });
    });

    // Fermer le modal
    closeModal.addEventListener('click', function() {
        modal.classList.add('hidden');
    });

    // Fermer le modal en cliquant à l'extérieur
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    // Marquer comme vue
    markViewedBtn.addEventListener('click', function() {
        if (!currentNotificationId) return;
        
        fetch(`{{ route('public.notifications.viewed', ['id' => '__ID__']) }}`.replace('__ID__', currentNotificationId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.textContent = 'Vue';
                this.classList.add('text-green-600');
                this.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur lors du marquage:', error);
        });
    });
});
</script>
@endpush 