@props([
    'showStats' => false,
    'stats' => [],
    'title' => null,
    'subtitle' => null,
    'breadcrumbs' => [],
    'user' => null
])

@php
    // Définir les variables avec des valeurs par défaut
    $user = $user ?? auth()->user();
    
    $pageTitle = $title ?? __('dashboard.dashboard');
@endphp

<!-- Top Navigation Bar -->
<div class="hidden md:block bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14 gap-4 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
            @include('layouts.partials.evon-header')
        </div>
    </div>
</div>

