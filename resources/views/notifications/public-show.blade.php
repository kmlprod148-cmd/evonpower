@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- En-tête avec bouton retour -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <a href="{{ route('public.notifications.index') }}" 
                       class="text-blue-600 hover:text-blue-800 mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">Détails de la notification</h1>
                </div>
                <div class="flex space-x-2">
                    <button id="markViewedBtn" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-check mr-2"></i>Marquer comme vue
                    </button>
                </div>
            </div>

            <!-- Contenu de la notification -->
            <div class="border rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <span class="inline-block w-4 h-4 rounded-full mr-4 
                        @if($notification->type === 'system') bg-blue-500
                        @elseif($notification->type === 'announcement') bg-yellow-500
                        @elseif($notification->type === 'warning') bg-red-500
                        @else bg-gray-500
                        @endif"></span>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $notification->title }}</h2>
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span>
                                <i class="fas fa-clock mr-1"></i>
                                {{ $notification->created_at->format('d/m/Y à H:i') }}
                            </span>
                            <span>
                                <i class="fas fa-tag mr-1"></i>
                                {{ ucfirst($notification->type) }}
                            </span>
                            @if($notification->priority)
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($notification->priority === 'urgent') bg-red-100 text-red-800
                                    @elseif($notification->priority === 'high') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($notification->priority) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="prose max-w-none">
                    <div class="text-gray-700 text-lg leading-relaxed">
                        {!! nl2br(e($notification->message)) !!}
                    </div>
                </div>

                @if($notification->action_url)
                    <div class="mt-6">
                        <a href="{{ $notification->action_url }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            {{ $notification->action_text ?? 'En savoir plus' }}
                        </a>
                    </div>
                @endif

                <!-- Métadonnées -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                        <div>
                            <span class="font-medium">Créée le :</span>
                            {{ $notification->created_at->format('d/m/Y à H:i:s') }}
                        </div>
                        <div>
                            <span class="font-medium">Il y a :</span>
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                        @if($notification->expires_at)
                            <div>
                                <span class="font-medium">Expire le :</span>
                                {{ $notification->expires_at->format('d/m/Y à H:i') }}
                            </div>
                        @endif
                        @if($notification->createdBy)
                            <div>
                                <span class="font-medium">Par :</span>
                                {{ $notification->createdBy->name }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-between items-center">
                <div class="flex space-x-2">
                    <button id="shareBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-share mr-2"></i>Partager
                    </button>
                    <button id="printBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-print mr-2"></i>Imprimer
                    </button>
                </div>
                <div class="text-sm text-gray-500">
                    ID: {{ $notification->id }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const markViewedBtn = document.getElementById('markViewedBtn');
    const shareBtn = document.getElementById('shareBtn');
    const printBtn = document.getElementById('printBtn');
    const notificationId = {{ $notification->id }};

    // Marquer comme vue
    markViewedBtn.addEventListener('click', function() {
        fetch(`{{ route('public.notifications.viewed', ['id' => '__ID__']) }}`.replace('__ID__', notificationId), {
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
                this.innerHTML = '<i class="fas fa-check mr-2"></i>Vue';
                this.classList.remove('bg-green-500', 'hover:bg-green-600');
                this.classList.add('bg-gray-500', 'hover:bg-gray-600');
                this.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur lors du marquage:', error);
        });
    });

    // Partager
    shareBtn.addEventListener('click', function() {
        if (navigator.share) {
            navigator.share({
                title: '{{ $notification->title }}',
                text: '{{ Str::limit($notification->message, 100) }}',
                url: window.location.href
            });
        } else {
            // Fallback pour les navigateurs qui ne supportent pas l'API Share
            const url = window.location.href;
            const text = '{{ $notification->title }}';
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(`${text}\n\n${url}`);
                alert('Lien copié dans le presse-papiers !');
            } else {
                // Fallback pour les navigateurs plus anciens
                const textArea = document.createElement('textarea');
                textArea.value = `${text}\n\n${url}`;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Lien copié dans le presse-papiers !');
            }
        }
    });

    // Imprimer
    printBtn.addEventListener('click', function() {
        window.print();
    });
});
</script>
@endpush 