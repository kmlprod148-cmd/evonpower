// resources/views/components/notification.blade.php
@props([
    'type' => 'info', 
    'message' => '', 
    'dismissible' => true, 
    'timeout' => 0,
    'id' => 'notification-' . uniqid()
])

@php
    $bgColor = match($type) {
        'success' => 'bg-green-100 dark:bg-green-800',
        'error' => 'bg-red-100 dark:bg-red-800',
        'warning' => 'bg-yellow-100 dark:bg-yellow-800',
        default => 'bg-blue-100 dark:bg-blue-800'
    };
    
    $borderColor = match($type) {
        'success' => 'border-green-500',
        'error' => 'border-red-500',
        'warning' => 'border-yellow-500',
        default => 'border-blue-500'
    };
    
    $textColor = match($type) {
        'success' => 'text-green-700 dark:text-green-200',
        'error' => 'text-red-700 dark:text-red-200',
        'warning' => 'text-yellow-700 dark:text-yellow-200',
        default => 'text-blue-700 dark:text-blue-200'
    };
    
    $iconColor = match($type) {
        'success' => 'text-green-500 dark:text-green-400',
        'error' => 'text-red-500 dark:text-red-400',
        'warning' => 'text-yellow-500 dark:text-yellow-400',
        default => 'text-blue-500 dark:text-blue-400'
    };
@endphp

<div 
    id="{{ $id }}" 
    class="p-4 mb-6 border-l-4 {{ $bgColor }} {{ $borderColor }} {{ $textColor }} transition-all duration-300 ease-in-out transform translate-x-0" 
    role="alert">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            @if($type === 'success')
                <svg class="h-5 w-5 {{ $iconColor }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            @elseif($type === 'error')
                <svg class="h-5 w-5 {{ $iconColor }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            @elseif($type === 'warning')
                <svg class="h-5 w-5 {{ $iconColor }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            @else
                <svg class="h-5 w-5 {{ $iconColor }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            @endif
        </div>
        <div class="ml-3 flex-grow">
            <p>{{ $message }}</p>
        </div>
        @if($dismissible)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="inline-flex rounded-md p-1.5 {{ $textColor }} hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ str_replace('text', 'ring', $textColor) }}" onclick="dismissNotification('{{ $id }}')">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

@if($timeout > 0)
    @push('scripts')
    <script>
        setTimeout(function() {
            dismissNotification('{{ $id }}');
        }, {{ $timeout }});
    </script>
    @endpush
@endif

@once
    @push('scripts')
    <script>
        function dismissNotification(id) {
            const notification = document.getElementById(id);
            if (notification) {
                notification.classList.add('opacity-0', '-translate-x-full');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }
        }
    </script>
    @endpush
@endonce