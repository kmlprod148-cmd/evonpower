@extends('layouts.app')

@section('title', __('messages.users') ?? 'Utilisateurs')
@section('page-title', __('messages.users') ?? 'Utilisateurs')

@push('styles')
<style>
    .users-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        flex-shrink: 0;
    }
    
    .user-avatar.gradient-1 { background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%); color: white; }
    .user-avatar.gradient-2 { background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); color: white; }
    .user-avatar.gradient-3 { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); color: white; }
    .user-avatar.gradient-4 { background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%); color: white; }
    .user-avatar.gradient-5 { background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%); color: white; }
    
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .role-badge.admin { background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.2) 100%); color: #dc2626; }
    .role-badge.operator { background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.2) 100%); color: #2563eb; }
    .role-badge.owner { background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.2) 100%); color: #7c3aed; }
    .role-badge.integrator { background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.2) 100%); color: #d97706; }
    .role-badge.user { background: linear-gradient(135deg, rgba(74, 207, 123, 0.1) 0%, rgba(74, 207, 123, 0.2) 100%); color: #059669; }
    .role-badge.partner { background: linear-gradient(135deg, rgba(236, 72, 153, 0.1) 0%, rgba(236, 72, 153, 0.2) 100%); color: #db2777; }
    
    .dark .role-badge.admin { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .dark .role-badge.operator { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
    .dark .role-badge.owner { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }
    .dark .role-badge.integrator { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    .dark .role-badge.user { background: rgba(74, 207, 123, 0.2); color: #6ee7b7; }
    .dark .role-badge.partner { background: rgba(236, 72, 153, 0.2); color: #f9a8d4; }
    
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    
    .status-dot.active { background-color: #10b981; animation: pulse-status 2s infinite; }
    .status-dot.inactive { background-color: #9ca3af; }
    
    @keyframes pulse-status {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
    }
    
    .action-btn.view { 
        background: rgba(59, 130, 246, 0.1); 
        color: #3b82f6; 
    }
    .action-btn.view:hover { 
        background: #3b82f6; 
        color: white; 
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    
    .action-btn.edit { 
        background: rgba(74, 207, 123, 0.1); 
        color: #4acf7b; 
    }
    .action-btn.edit:hover { 
        background: #4acf7b; 
        color: white; 
        box-shadow: 0 4px 12px rgba(74, 207, 123, 0.4);
    }
    
    .action-btn.delete { 
        background: rgba(239, 68, 68, 0.1); 
        color: #ef4444; 
    }
    .action-btn.delete:hover { 
        background: #ef4444; 
        color: white; 
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    
    .table-row-animate {
        transition: all 0.2s ease;
    }
    
    .table-row-animate:hover {
        background-color: #f8fafc;
        transform: scale(1.005);
    }
    
    .dark .table-row-animate:hover {
        background-color: #334155;
    }
    
    .search-input-premium {
        transition: all 0.3s ease;
        border: 2px solid #e2e8f0;
    }
    
    .search-input-premium:focus {
        border-color: #4acf7b;
        box-shadow: 0 0 0 3px rgba(74, 207, 123, 0.1);
    }
    
    .dark .search-input-premium {
        border-color: #475569;
        background-color: #1e293b;
        color: #f1f5f9;
    }
    
    .dark .search-input-premium:focus {
        border-color: #4acf7b;
    }
    
    .filter-chip {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .filter-chip:hover {
        border-color: #4acf7b;
        color: #4acf7b;
    }
    
    .filter-chip.active {
        background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%);
        color: white;
        border-color: transparent;
    }
    
    .dark .filter-chip {
        background: #334155;
        border-color: #475569;
        color: #94a3b8;
    }
    
    .dark .filter-chip:hover {
        border-color: #4acf7b;
        color: #4acf7b;
    }
    
    .dark .filter-chip.active {
        background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%);
        color: white;
    }
    
    .pagination-btn {
        width: 40px;
        height: 40px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
    }
    
    .pagination-btn:hover:not(.active):not(:disabled) {
        border-color: #4acf7b;
        color: #4acf7b;
    }
    
    .pagination-btn.active {
        background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%);
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 12px rgba(74, 207, 123, 0.3);
    }
    
    .dark .pagination-btn {
        background: #334155;
        border-color: #475569;
        color: #94a3b8;
    }
    
    .dark .pagination-btn.active {
        background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%);
        color: white;
    }
    
    .stats-mini-card {
        padding: 1rem 1.5rem;
        border-radius: 1rem;
        background: white;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.2s ease;
    }
    
    .stats-mini-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px -8px rgba(0, 0, 0, 0.1);
    }
    
    .dark .stats-mini-card {
        background: #1e293b;
        border-color: #334155;
    }
    
    .stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="users-container p-4 lg:p-6" x-data="usersIndex()">
    {{-- Page Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">
                {{ __('messages.users') ?? 'Utilisateurs' }}
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                {{ __('messages.manage_users_description') ?? 'Gérez les utilisateurs de votre plateforme' }}
            </p>
        </div>
        <a href="{{ route('admin.users.create') }}" 
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-white transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5"
           style="background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%); box-shadow: 0 4px 15px rgba(74, 207, 123, 0.3);">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            {{ __('messages.add_user') ?? 'Nouvel utilisateur' }}
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stats-mini-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, rgba(74, 207, 123, 0.1) 0%, rgba(74, 207, 123, 0.2) 100%);">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-eco-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $users->total() ?? 156 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
            </div>
        </div>
        <div class="stats-mini-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.2) 100%);">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $activeUsers ?? 142 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Actifs</p>
            </div>
        </div>
        <div class="stats-mini-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.2) 100%);">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $operatorCount ?? 12 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Opérateurs</p>
            </div>
        </div>
        <div class="stats-mini-card">
            <div class="stats-icon" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.2) 100%);">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $newThisMonth ?? 8 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Ce mois</p>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
        {{-- Filters Bar --}}
        <div class="p-4 lg:p-6 border-b border-gray-100 dark:border-slate-700">
            <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                {{-- Search --}}
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" 
                           x-model="searchQuery"
                           @input.debounce.300ms="filterUsers()"
                           placeholder="{{ __('messages.search_users') ?? 'Rechercher un utilisateur...' }}" 
                           class="search-input-premium pl-11 pr-4 py-3 rounded-xl w-full focus:outline-none">
                </div>
                
                {{-- Role Filters --}}
                <div class="flex flex-wrap gap-2">
                    <button @click="filterRole = ''" 
                            :class="filterRole === '' ? 'active' : ''"
                            class="filter-chip">
                        Tous
                    </button>
                    <button @click="filterRole = 'admin'" 
                            :class="filterRole === 'admin' ? 'active' : ''"
                            class="filter-chip">
                        Admin
                    </button>
                    <button @click="filterRole = 'operator'" 
                            :class="filterRole === 'operator' ? 'active' : ''"
                            class="filter-chip">
                        Opérateur
                    </button>
                    <button @click="filterRole = 'owner'" 
                            :class="filterRole === 'owner' ? 'active' : ''"
                            class="filter-chip">
                        Propriétaire
                    </button>
                    <button @click="filterRole = 'user'" 
                            :class="filterRole === 'user' ? 'active' : ''"
                            class="filter-chip">
                        Client
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-700/50">
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.user') ?? 'Utilisateur' }}
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.company') ?? 'Société' }}
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.role') ?? 'Rôle' }}
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.phone') ?? 'Téléphone' }}
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.status') ?? 'Statut' }}
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.actions') ?? 'Actions' }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($users ?? [] as $index => $user)
                    <tr class="table-row-animate">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="user-avatar gradient-{{ ($index % 5) + 1 }}">
                                    {{ strtoupper(substr($user->name ?? $user->prenom ?? 'U', 0, 1)) }}{{ strtoupper(substr($user->nom ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $user->name ?? ($user->prenom . ' ' . $user->nom) }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $user->raison_social ?? $user->company ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $role = $user->getRoleNames()->first() ?? 'user';
                            @endphp
                            <span class="role-badge {{ $role }}">
                                {{ ucfirst($role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $user->telephone ?? $user->phone ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="status-dot {{ $user->email_verified_at || $user->is_active ? 'active' : 'inactive' }}"></span>
                                <span class="text-sm {{ $user->email_verified_at || $user->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $user->email_verified_at || $user->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.show', $user->id) }}" 
                                   class="action-btn view" 
                                   title="{{ __('messages.view') ?? 'Voir' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('admin.users.edit', $user->id) }}" 
                                   class="action-btn edit"
                                   title="{{ __('messages.edit') ?? 'Modifier' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('messages.confirm_delete') ?? 'Êtes-vous sûr de vouloir supprimer cet utilisateur ?' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="action-btn delete"
                                            title="{{ __('messages.delete') ?? 'Supprimer' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    {{-- Empty State --}}
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ __('messages.no_users_found') ?? 'Aucun utilisateur trouvé' }}
                                </h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">
                                    {{ __('messages.add_first_user') ?? 'Commencez par ajouter un nouvel utilisateur' }}
                                </p>
                                <a href="{{ route('admin.users.create') }}" 
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-white"
                                   style="background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%);">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    {{ __('messages.add_user') ?? 'Ajouter un utilisateur' }}
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($users) && $users->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('messages.showing') ?? 'Affichage de' }} 
                <span class="font-semibold">{{ $users->firstItem() }}</span> 
                {{ __('messages.to') ?? 'à' }} 
                <span class="font-semibold">{{ $users->lastItem() }}</span> 
                {{ __('messages.of') ?? 'sur' }} 
                <span class="font-semibold">{{ $users->total() }}</span> 
                {{ __('messages.results') ?? 'résultats' }}
            </div>
            <div class="flex items-center gap-2">
                {{-- Previous --}}
                @if($users->onFirstPage())
                    <button disabled class="pagination-btn opacity-50 cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                @else
                    <a href="{{ $users->previousPageUrl() }}" class="pagination-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                @endif
                
                {{-- Page Numbers --}}
                @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" 
                       class="pagination-btn {{ $page == $users->currentPage() ? 'active' : '' }}">
                        {{ $page }}
                    </a>
                @endforeach
                
                {{-- Next --}}
                @if($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="pagination-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @else
                    <button disabled class="pagination-btn opacity-50 cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function usersIndex() {
        return {
            searchQuery: '',
            filterRole: '',
            
            filterUsers() {
                // Client-side filtering or trigger server request
                const url = new URL(window.location.href);
                
                if (this.searchQuery) {
                    url.searchParams.set('search', this.searchQuery);
                } else {
                    url.searchParams.delete('search');
                }
                
                if (this.filterRole) {
                    url.searchParams.set('role', this.filterRole);
                } else {
                    url.searchParams.delete('role');
                }
                
                // Optionally reload with filters
                // window.location.href = url.toString();
            }
        }
    }
</script>
@endpush
