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
    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
    <circle cx="12" cy="7" r="4"/>
</svg>