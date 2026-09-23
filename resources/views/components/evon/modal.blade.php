@props([
    'id' => 'modal',
    'title' => '',
    'size' => 'md', // sm, md, lg, xl
    'closeable' => true,
    'footer' => null
])

@php
    $sizeClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        'full' => 'max-w-full mx-4',
    ];
    
    $modalSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div x-data="{ 
        show: false,
        open() { 
            this.show = true;
            document.body.style.overflow = 'hidden';
        },
        close() { 
            this.show = false;
            document.body.style.overflow = 'auto';
        }
     }"
     @keydown.escape.window="close()"
     @{{ $id }}-open.window="open()"
     @{{ $id }}-close.window="close()"
     x-show="show"
     x-cloak
     class="evon-modal-container"
     role="dialog"
     aria-modal="true"
     :aria-labelledby="'{{ $id }}-title'"
     style="display: none;">
    
    <!-- Overlay -->
    <div x-show="show"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="evon-modal-overlay"
         @if($closeable) @click="close()" @endif
         aria-hidden="true"></div>
    
    <!-- Modal Panel -->
    <div class="evon-modal-wrapper">
        <div x-show="show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="evon-modal-panel {{ $modalSize }} w-full"
             @click.away="@if($closeable) close() @endif">
            
            <!-- Header -->
            @if($title || $closeable)
                <div class="evon-modal-header">
                    @if($title)
                        <h3 class="evon-modal-title" id="{{ $id }}-title">
                            {{ $title }}
                        </h3>
                    @endif
                    
                    @if($closeable)
                        <button @click="close()" 
                                class="evon-modal-close"
                                type="button"
                                aria-label="Fermer la fenêtre modale">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endif
            
            <!-- Body -->
            <div class="evon-modal-body">
                {{ $slot }}
            </div>
            
            <!-- Footer -->
            @if($footer)
                <div class="evon-modal-footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>

