@props([
    'size' => 'normal', // normal, compact, small
    'type' => 'icon', // icon, image, text
    'icon' => '',
    'image' => '',
    'text' => '',
    'href' => '/',
    'class' => ''
])

@php
$sizeClasses = match($size) {
    'small' => 'logo-compact',
    'compact' => 'logo-compact',
    'normal' => 'logo-container',
    default => 'logo-container'
};

$containerClasses = "{$sizeClasses} {$class}";
@endphp

<a href="{{ $href }}" class="{{ $containerClasses }}">
    @if($type === 'image' && $image)
        <div class="logo-image-container">
            <img src="{{ $image }}" alt="{{ $text ?: 'Logo' }}" class="logo-image">
        </div>
    @elseif($type === 'icon' && $icon)
        <div class="logo-icon">
            {!! $icon !!}
        </div>
    @else
        <svg class="logo-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
    @endif
    
    @if($text)
        <span class="logo-text">{{ $text }}</span>
    @endif
</a>
