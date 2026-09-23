@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Notifications</h1>
    <ul class="space-y-4">
        @forelse($notifications as $notification)
            <li class="p-4 rounded shadow {{ $notification->read_at ? 'bg-white' : 'bg-green-50 font-semibold' }} flex items-center justify-between">
                <div>
                    {{ $notification->data['message'] ?? 'Notification' }}
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $notification->created_at->diffForHumans() }}
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    @if(!$notification->read_at)
                        <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-green-600 hover:underline">Marquer comme lue</button>
                        </form>
                    @else
                        <span class="text-xs text-gray-400">Lue</span>
                    @endif
                </div>
            </li>
        @empty
            <li class="p-4 bg-white rounded shadow text-gray-500">Aucune notification</li>
        @endforelse
    </ul>
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection 