@props(['type', 'message'])

@php
    $classes = [
        'success' => 'bg-green-100 border-l-4 border-green-500 text-green-700',
        'error' => 'bg-red-100 border-l-4 border-red-500 text-red-700',
        'warning' => 'bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700',
        'info' => 'bg-blue-100 border-l-4 border-blue-500 text-blue-700',
    ];
    $currentClass = $classes[$type] ?? $classes['info'];
@endphp

<div class="mb-4 p-4 rounded shadow-sm {{ $currentClass }}" role="alert">
    <p>{{ $message }}</p>
</div>