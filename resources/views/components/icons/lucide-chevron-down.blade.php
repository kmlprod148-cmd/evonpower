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
    <path d="m6 9 6 6 6-6"/>
</svg>