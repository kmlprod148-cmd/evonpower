@props(['size' => 24, 'strokeWidth' => 2, 'class' => ''])

<svg 
    xmlns="http://www.w3.org/2000/svg" 
    width="{{ $size }}" 
    height="{{ $size }}" 
    viewBox="0 0 24 24" 
    fill="none" 
    stroke="currentColor" 
    stroke-width="{{ $strokeWidth }}" 
    stroke-linecap="round" 
    stroke-linejoin="round" 
    class="{{ $class }}"
>
    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
</svg>

