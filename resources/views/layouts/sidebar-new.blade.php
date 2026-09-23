@extends('layouts.app')

@section('content')
@php
    $isAdminNavUser = auth()->user()->hasAnyRole(['admin', 'Admin', 'super_admin', 'super-admin', 'super admin', 'Super Admin', 'Super-Admin']);
@endphp
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Sidebar -->
    <nav class="sidebar fixed top-16 left-0 bottom-0 w-56 bg-white dark:bg-gray-800 shadow-lg border-r border-gray-200 dark:border-gray-700 z-30 transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out overflow-y-auto overflow-x-hidden"
         :class="{ 'open': sidebarOpen, 'collapsed': sidebarCollapsed }"
         x-data="{ sidebarOpen: false, sidebarCollapsed: $persist(false) }">

        <!-- Sidebar Header -->
        <div class="sidebar-header p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center">
                <div class="sidebar-logo">
                    <img src="{{ asset('images/logo.png') }}" alt="EVON Logo" class="h-8 w-auto">
                </div>
                <h2 class="sidebar-title ml-3 text-lg font-semibold text-gray-800 dark:text-white" x-show="!sidebarCollapsed">EVON</h2>
            </div>

            <!-- Toggle Button -->
            <button @click="sidebarCollapsed = !sidebarCollapsed"
                    class="sidebar-toggle p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-primary transition-colors duration-200">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="!sidebarCollapsed">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-show="sidebarCollapsed">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="sidebar-menu px-3 py-6 space-y-1">
            <!-- SECTION TABLEAU DE BORD -->
            <div class="nav-section">
                <h3 class="sidebar-text text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 mb-3" x-show="!sidebarCollapsed">
                    Tableau de bord
                </h3>

                <!-- Dashboard Principal -->
                @if($isAdminNavUser)
                <a href="#" class="nav-item">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Dashboard Admin</span>
                </a>
                @elseif(auth()->user()->hasRole('integrator'))
                <a href="#" class="nav-item">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Dashboard Intégrateur</span>
                </a>
                @elseif(auth()->user()->hasRole('operator'))
                <a href="{{ route('operator.dashboard') }}"
                   class="nav-item {{ request()->routeIs('operator.dashboard') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Dashboard Opérateur</span>
                </a>
                @else
                @endif
            </div>

            <!-- ROLE-BASED NAVIGATION -->
            @if($isAdminNavUser)
                @include('layouts.partials.sidebar-admin')
            @elseif(auth()->user()->hasRole('integrator'))
                @include('layouts.partials.sidebar-integrator')
            @elseif(auth()->user()->hasRole('operator'))
                @include('layouts.partials.sidebar-operator')
            @else
                @include('layouts.partials.sidebar-default')
            @endif

            <!-- SECTION PARAMÈTRES -->
            <div class="nav-section mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                <h3 class="sidebar-text text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 mb-3" x-show="!sidebarCollapsed">
                    Paramètres
                </h3>

                <!-- Général -->
                <a href="#" class="nav-item">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Général</span>
                </a>

                <!-- Profil -->
                <a href="#" class="nav-item">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Profil</span>
                </a>

                <!-- Sécurité -->
                <a href="#" class="nav-item">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Sécurité</span>
                </a>

                <!-- Notifications -->
                <a href="#" class="nav-item">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828zM4.828 17h7.172l-2.586-2.586a2 2 0 00-2.828 0L4.828 17z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Notifications</span>
                </a>

                <!-- API -->
                <a href="#" class="nav-item">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">API</span>
                </a>

                <!-- Facturation -->
                <a href="#" class="nav-item">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span class="sidebar-text" x-show="!sidebarCollapsed">Facturation</span>
                </a>
            </div>

            <!-- Quick Actions Section -->
            <div class="nav-section mt-8 pt-4" x-show="!sidebarCollapsed">
                <h3 class="sidebar-text text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 mb-3">
                    Actions Rapides
                </h3>

                <div class="space-y-2 px-3">


                    @if($isAdminNavUser)
                    <a href="#" class="quick-action secondary">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="text-xs">Intégrateur</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasRole('integrator'))
                    <a href="#" class="quick-action secondary">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="text-xs">Opérateur Système</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasRole('integrator'))
                    <a href="#" class="quick-action primary">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="text-xs">Borne</span>
                    </a>
                    @endif

                    @can('view_reports')
                    <a href="#" class="quick-action neutral">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="text-xs">Rapport</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </nav>

<style>
/* Sidebar Styles */
.sidebar {
    @apply fixed top-16 left-0 bottom-0 w-56 bg-white dark:bg-gray-800 shadow-lg border-r border-gray-200 dark:border-gray-700 z-30;
    @apply transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out;
    @apply overflow-y-auto overflow-x-hidden;
}

.sidebar.open {
    @apply translate-x-0;
}

.sidebar.collapsed {
    @apply lg:w-10;
}

.sidebar-nav {
    @apply h-full flex flex-col;
}

.sidebar-header {
    @apply p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between;
}

.sidebar-toggle {
    @apply p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-primary;
    @apply transition-colors duration-200;
}

.sidebar-menu {
    @apply flex-1 px-3 py-6 space-y-1;
}

.nav-section {
    @apply space-y-1;
}

.nav-item {
    @apply group flex items-center px-3 py-2 text-sm font-medium rounded-lg;
    @apply text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-primary;
    @apply transition-all duration-200;
    @apply focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2;
}

.nav-item.active {
    @apply bg-green-500 text-white border-r-2 border-green-500;
}

.nav-item:hover .nav-icon {
    @apply text-primary;
}

.nav-item.active .nav-icon {
    @apply text-white;
}

.nav-icon {
    @apply h-5 w-5 text-gray-500 group-hover:text-primary mr-3 flex-shrink-0;
    @apply transition-colors duration-200;
}

.sidebar-text {
    @apply transition-all duration-300;
}

.sidebar.collapsed .sidebar-text {
    @apply lg:opacity-0 lg:w-0 lg:overflow-hidden;
}

.sidebar.collapsed .nav-item {
    @apply lg:justify-center lg:px-2;
}

.sidebar.collapsed .nav-icon {
    @apply lg:mr-0;
}

/* Quick Actions */
.quick-action {
    @apply flex items-center space-x-2 px-3 py-2 rounded-lg text-white text-sm font-medium;
    @apply transition-all duration-200 transform hover:scale-105;
}

.quick-action.primary {
    @apply bg-primary hover:bg-primary/90;
}

.quick-action.secondary {
    @apply bg-secondary hover:bg-secondary/90;
}

.quick-action.neutral {
    @apply bg-gray-500 hover:bg-gray-600;
}

/* Content area adjustment */
.content {
    @apply ml-0 lg:ml-56 pt-16 transition-all duration-300;
}

.content.collapsed {
    @apply lg:ml-16;
}
</style>
