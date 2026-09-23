@props([
    'title' => '',
    'subtitle' => '',
    'actions' => null
])

<div class="evon-page-header">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex-1">
            @if($title)
                <h1 class="evon-page-title">{{ $title }}</h1>
            @else
                <h1 class="evon-page-title">
                    {{ $slot }}
                </h1>
            @endif
            
            @if($subtitle)
                <p class="evon-page-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        
        @if($actions)
            <div class="evon-page-actions">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

