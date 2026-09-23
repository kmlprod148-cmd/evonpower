@props([
    'title' => 'Aucune donnée',
    'description' => '',
    'icon' => null,
    'action' => null
])

<div {{ $attributes->merge(['class' => 'evon-empty-state']) }}>
    @if($icon)
        <div class="evon-empty-state-icon">
            {{ $icon }}
        </div>
    @else
        <svg class="evon-empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
    @endif
    
    <h3 class="evon-empty-state-title">{{ $title }}</h3>
    
    @if($description)
        <p class="evon-empty-state-description">{{ $description }}</p>
    @endif
    
    @if($action)
        <div class="mt-6">
            {{ $action }}
        </div>
    @endif
    
    {{ $slot }}
</div>

