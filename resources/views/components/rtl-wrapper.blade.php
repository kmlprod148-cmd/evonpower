{{-- resources/views/components/rtl-wrapper.blade.php --}}
@props(['class' => ''])

@php
    $currentLocale = app()->getLocale();
    $isRTL = $currentLocale === 'ar';
    $direction = $isRTL ? 'rtl' : 'ltr';
@endphp

<div 
    class="rtl-wrapper {{ $class }}" 
    dir="{{ $direction }}"
    lang="{{ $currentLocale }}"
    x-data="{ isRTL: {{ $isRTL ? 'true' : 'false' }} }"
>
    {{ $slot }}
</div>

@push('styles')
<style>
/* Support RTL pour l'arabe */
[dir="rtl"] {
    text-align: right;
}

[dir="rtl"] .flex {
    flex-direction: row-reverse;
}

[dir="rtl"] .space-x-1 > * + * {
    margin-left: 0;
    margin-right: 0.25rem;
}

[dir="rtl"] .space-x-2 > * + * {
    margin-left: 0;
    margin-right: 0.5rem;
}

[dir="rtl"] .space-x-3 > * + * {
    margin-left: 0;
    margin-right: 0.75rem;
}

[dir="rtl"] .space-x-4 > * + * {
    margin-left: 0;
    margin-right: 1rem;
}

[dir="rtl"] .ml-1 { margin-left: 0; margin-right: 0.25rem; }
[dir="rtl"] .ml-2 { margin-left: 0; margin-right: 0.5rem; }
[dir="rtl"] .ml-3 { margin-left: 0; margin-right: 0.75rem; }
[dir="rtl"] .ml-4 { margin-left: 0; margin-right: 1rem; }
[dir="rtl"] .ml-5 { margin-left: 0; margin-right: 1.25rem; }
[dir="rtl"] .ml-6 { margin-left: 0; margin-right: 1.5rem; }

[dir="rtl"] .mr-1 { margin-right: 0; margin-left: 0.25rem; }
[dir="rtl"] .mr-2 { margin-right: 0; margin-left: 0.5rem; }
[dir="rtl"] .mr-3 { margin-right: 0; margin-left: 0.75rem; }
[dir="rtl"] .mr-4 { margin-right: 0; margin-left: 1rem; }
[dir="rtl"] .mr-5 { margin-right: 0; margin-left: 1.25rem; }
[dir="rtl"] .mr-6 { margin-right: 0; margin-left: 1.5rem; }

[dir="rtl"] .pl-1 { padding-left: 0; padding-right: 0.25rem; }
[dir="rtl"] .pl-2 { padding-left: 0; padding-right: 0.5rem; }
[dir="rtl"] .pl-3 { padding-left: 0; padding-right: 0.75rem; }
[dir="rtl"] .pl-4 { padding-left: 0; padding-right: 1rem; }
[dir="rtl"] .pl-5 { padding-left: 0; padding-right: 1.25rem; }
[dir="rtl"] .pl-6 { padding-left: 0; padding-right: 1.5rem; }

[dir="rtl"] .pr-1 { padding-right: 0; padding-left: 0.25rem; }
[dir="rtl"] .pr-2 { padding-right: 0; padding-left: 0.5rem; }
[dir="rtl"] .pr-3 { padding-right: 0; padding-left: 0.75rem; }
[dir="rtl"] .pr-4 { padding-right: 0; padding-left: 1rem; }
[dir="rtl"] .pr-5 { padding-right: 0; padding-left: 1.25rem; }
[dir="rtl"] .pr-6 { padding-right: 0; padding-left: 1.5rem; }

[dir="rtl"] .text-left { text-align: right; }
[dir="rtl"] .text-right { text-align: left; }

[dir="rtl"] .float-left { float: right; }
[dir="rtl"] .float-right { float: left; }

[dir="rtl"] .border-l { border-left: none; border-right: 1px solid; }
[dir="rtl"] .border-r { border-right: none; border-left: 1px solid; }

[dir="rtl"] .rounded-l { border-top-left-radius: 0; border-bottom-left-radius: 0; border-top-right-radius: 0.25rem; border-bottom-right-radius: 0.25rem; }
[dir="rtl"] .rounded-r { border-top-right-radius: 0; border-bottom-right-radius: 0; border-top-left-radius: 0.25rem; border-bottom-left-radius: 0.25rem; }

/* Animations spécifiques RTL */
[dir="rtl"] .transform {
    transform: scaleX(-1);
}

[dir="rtl"] .transition-transform {
    transition: transform 0.3s ease;
}

/* Support pour les icônes et éléments directionnels */
[dir="rtl"] .icon-flip {
    transform: scaleX(-1);
}

/* Navigation RTL */
[dir="rtl"] .nav-item {
    margin-left: 0;
    margin-right: 1rem;
}

[dir="rtl"] .nav-item:last-child {
    margin-right: 0;
}

/* Formulaires RTL */
[dir="rtl"] .form-group {
    text-align: right;
}

[dir="rtl"] .form-label {
    text-align: right;
}

[dir="rtl"] .form-control {
    text-align: right;
}

/* Tables RTL */
[dir="rtl"] .table th,
[dir="rtl"] .table td {
    text-align: right;
}

/* Boutons RTL */
[dir="rtl"] .btn-group .btn:not(:last-child) {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-top-right-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;
}

[dir="rtl"] .btn-group .btn:not(:first-child) {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-top-left-radius: 0.25rem;
    border-bottom-left-radius: 0.25rem;
}

/* Modals et dropdowns RTL */
[dir="rtl"] .dropdown-menu {
    right: 0;
    left: auto;
}

[dir="rtl"] .modal-dialog {
    margin-right: auto;
    margin-left: auto;
}

/* Breadcrumbs RTL */
[dir="rtl"] .breadcrumb-item + .breadcrumb-item::before {
    content: "\\";
    transform: scaleX(-1);
}

/* Pagination RTL */
[dir="rtl"] .pagination .page-item:first-child .page-link {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-top-right-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;
}

[dir="rtl"] .pagination .page-item:last-child .page-link {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-top-left-radius: 0.25rem;
    border-bottom-left-radius: 0.25rem;
}
</style>
@endpush
