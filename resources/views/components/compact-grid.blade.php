@props([
    'cols' => 1,
    'gap' => 2,
    'class' => ''
])

@php
$gridClasses = "compact-grid compact-grid-{$cols} compact-gap-{$gap} {$class}";
@endphp

<div class="{{ $gridClasses }}">
    {{ $slot }}
</div>
