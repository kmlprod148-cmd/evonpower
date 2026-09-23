@extends('layouts.app')

@section('title', __('messages.users_management') ?? 'Gestion des Utilisateurs')
@section('page-title', __('messages.users_management') ?? 'Gestion des Utilisateurs')

@push('styles')
<style>
    .admin-users-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1rem;
    }
    
    /* Header Banner */
    .users-header-banner {
        background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 50%, #06b6d4 100%);
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
        position: relative;
        overflow: hidden;
    }
    
    .users-header-banner::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        transform: translate(30%, -30%);
    }
    
    .users-header-banner h1 {
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }
    
    .users-header-banner p {
        color: rgba(255,255,255,0.9);
        font-size: 0.875rem;
        margin: 0.25rem 0 0;
    }
    
    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    
    @media (max-width: 1024px) {
        .stats-row { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 640px) {
        .stats-row { grid-template-columns: 1fr; }
    }
    
    .stat-card {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 4px 15px -3px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    
    .dark .stat-card {
        background: #1e293b;
        border-color: rgba(255,255,255,0.1);
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px -5px rgba(0,0,0,0.12);
    }
    
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .stat-icon svg { width: 22px; height: 22px; color: white; }
    .stat-icon.green { background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%); }
    .stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); }
    .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%); }
    
    .stat-info h3 { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; }
    .stat-info p { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0.15rem 0 0; }
    .dark .stat-info h3 { color: #94a3b8; }
    .dark .stat-info p { color: #f8fafc; }
    
    /* Main Card */
    .users-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 20px -5px rgba(0,0,0,0.1);
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .dark .users-card {
        background: #1e293b;
        border-color: rgba(255,255,255,0.1);
    }
    
    .users-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .dark .users-card-header {
        border-bottom-color: #334155;
    }
    
    .users-card-header h2 {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .dark .users-card-header h2 {
        color: #f8fafc;
    }
    
    .btn-add-user {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%);
        color: white;
        border-radius: 0.5rem;
        font-size: 0.813rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px -2px rgba(74, 207, 123, 0.4);
    }
    
    .btn-add-user:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px -2px rgba(74, 207, 123, 0.5);
        color: white;
    }
    
    .btn-add-user svg { width: 16px; height: 16px; }
    
    /* Filters */
    .filters-row {
        padding: 1rem 1.25rem;
        background: #f8fafc;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }
    
    .dark .filters-row {
        background: #0f172a;
    }
    
    .search-input {
        flex: 1;
        min-width: 200px;
        max-width: 300px;
        padding: 0.5rem 0.75rem 0.5rem 2.25rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        font-size: 0.813rem;
        background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E") 0.6rem center no-repeat;
        background-size: 16px;
        transition: all 0.2s ease;
    }
    
    .search-input:focus {
        outline: none;
        border-color: #4acf7b;
        box-shadow: 0 0 0 3px rgba(74, 207, 123, 0.15);
    }
    
    .dark .search-input {
        background-color: #1e293b;
        border-color: #475569;
        color: #f8fafc;
    }
    
    .filter-select {
        padding: 0.5rem 2rem 0.5rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        font-size: 0.813rem;
        background: white;
        cursor: pointer;
        min-width: 140px;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #4acf7b;
    }
    
    .dark .filter-select {
        background: #1e293b;
        border-color: #475569;
        color: #f8fafc;
    }
    
    /* Table */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .users-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .users-table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-size: 0.7rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .dark .users-table th {
        background: #0f172a;
        color: #94a3b8;
        border-bottom-color: #334155;
    }
    
    .users-table td {
        padding: 0.75rem 1rem;
        font-size: 0.813rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .dark .users-table td {
        color: #e2e8f0;
        border-bottom-color: #1e293b;
    }
    
    .users-table tbody tr {
        transition: background 0.15s ease;
    }
    
    .users-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .dark .users-table tbody tr:hover {
        background: #334155;
    }
    
    /* User Avatar */
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.813rem;
        text-transform: uppercase;
        flex-shrink: 0;
        color: white;
    }
    
    .user-avatar.gradient-1 { background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%); }
    .user-avatar.gradient-2 { background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); }
    .user-avatar.gradient-3 { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); }
    .user-avatar.gradient-4 { background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%); }
    .user-avatar.gradient-5 { background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%); }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .user-name {
        font-weight: 600;
        color: #1e293b;
    }
    
    .dark .user-name {
        color: #f8fafc;
    }
    
    .user-email {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    .dark .user-email {
        color: #94a3b8;
    }
    
    /* Role Badge */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .role-badge.admin { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
    .role-badge.operator { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
    .role-badge.owner { background: rgba(139, 92, 246, 0.1); color: #7c3aed; }
    .role-badge.integrator { background: rgba(245, 158, 11, 0.1); color: #d97706; }
    .role-badge.user { background: rgba(74, 207, 123, 0.1); color: #059669; }
    .role-badge.partner { background: rgba(236, 72, 153, 0.1); color: #db2777; }
    
    .dark .role-badge.admin { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .dark .role-badge.operator { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
    .dark .role-badge.owner { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; }
    .dark .role-badge.integrator { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    .dark .role-badge.user { background: rgba(74, 207, 123, 0.2); color: #6ee7b7; }
    .dark .role-badge.partner { background: rgba(236, 72, 153, 0.2); color: #f9a8d4; }
    
    /* Status */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 500;
    }
    
    .status-pill.active {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }
    
    .status-pill.inactive {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
    }
    
    .dark .status-pill.active {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
    }
    
    .dark .status-pill.inactive {
        background: rgba(107, 114, 128, 0.2);
        color: #9ca3af;
    }
    
    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    
    .status-pill.active .status-dot {
        animation: pulse-dot 2s infinite;
    }
    
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 0.35rem;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 0.4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
        text-decoration: none;
    }
    
    .action-btn svg { width: 16px; height: 16px; }
    
    .action-btn.view { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .action-btn.view:hover { background: #3b82f6; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
    
    .action-btn.edit { background: rgba(74, 207, 123, 0.1); color: #4acf7b; }
    .action-btn.edit:hover { background: #4acf7b; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(74, 207, 123, 0.3); }
    
    .action-btn.delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .action-btn.delete:hover { background: #ef4444; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
    }
    
    .empty-state-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(74, 207, 123, 0.1) 0%, rgba(45, 212, 191, 0.1) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
    
    .empty-state-icon svg { width: 32px; height: 32px; color: #4acf7b; }
    
    .empty-state h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 0.5rem;
    }
    
    .dark .empty-state h3 { color: #f8fafc; }
    
    .empty-state p {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0 0 1.25rem;
    }
    
    .dark .empty-state p { color: #94a3b8; }
    
    /* Alert */
    .alert-custom {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin: 0 1.25rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.813rem;
    }
    
    .alert-custom.success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
        color: #059669;
    }
    
    .alert-custom.error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #dc2626;
    }
    
    .alert-custom svg { width: 18px; height: 18px; flex-shrink: 0; }
    
    /* Pagination */
    .pagination-wrapper {
        padding: 1rem 1.25rem;
        border-top: 1px solid #f1f5f9;
    }
    
    .dark .pagination-wrapper {
        border-top-color: #334155;
    }
    
    /* ID Badge */
    .id-badge {
        display: inline-flex;
        padding: 0.15rem 0.5rem;
        background: #f1f5f9;
        border-radius: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: #64748b;
    }
    
    .dark .id-badge {
        background: #334155;
        color: #94a3b8;
    }
    
    /* Date */
    .date-text {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    .dark .date-text {
        color: #94a3b8;
    }
</style>
@endpush

@section('content')
<div class="admin-users-container" x-data="usersAdmin()">
    <!-- Header Banner -->
    <div class="users-header-banner">
        <h1>{{ __('messages.users_management') ?? 'Gestion des Utilisateurs' }}</h1>
        <p>{{ __('messages.manage_users_subtitle') ?? 'Gérez les utilisateurs et leurs permissions' }}</p>
    </div>
    
    @php
        $totalUsers = method_exists($users, 'total') ? $users->total() : $users->count();
        $activeUsers = $users->where('is_active', true)->count();
        $adminCount = $users->filter(fn($u) => method_exists($u, 'hasRole') ? $u->hasRole('admin') : ($u->role ?? '') === 'admin')->count();
        $newUsers = $users->filter(fn($u) => $u->created_at >= now()->subDays(30))->count();
    @endphp
    
    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon green">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div class="stat-info">
                <h3>Total Utilisateurs</h3>
                <p>{{ $totalUsers }}</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-info">
                <h3>Actifs</h3>
                <p>{{ $activeUsers }}</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon orange">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div class="stat-info">
                <h3>Administrateurs</h3>
                <p>{{ $adminCount }}</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-info">
                <h3>Nouveaux (30j)</h3>
                <p>{{ $newUsers }}</p>
            </div>
        </div>
    </div>
    
    <!-- Main Card -->
    <div class="users-card">
        <div class="users-card-header">
            <h2>{{ __('messages.users_list') ?? 'Liste des Utilisateurs' }}</h2>
            <a href="{{ route('admin.users.create') }}" class="btn-add-user">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('messages.new_user') ?? 'Nouvel Utilisateur' }}
            </a>
        </div>
        
        <!-- Filters -->
        <div class="filters-row">
            <input type="text" 
                   class="search-input" 
                   placeholder="{{ __('messages.search_user') ?? 'Rechercher un utilisateur...' }}"
                   x-model="searchQuery"
                   @input.debounce.300ms="filterUsers()">
            
            <select class="filter-select" x-model="statusFilter" @change="filterUsers()">
                <option value="">Tous les statuts</option>
                <option value="active">Actifs</option>
                <option value="inactive">Inactifs</option>
            </select>
            
            <select class="filter-select" x-model="roleFilter" @change="filterUsers()">
                <option value="">Tous les rôles</option>
                <option value="admin">Administrateur</option>
                <option value="integrator">Intégrateur</option>
                <option value="partner">Partenaire</option>
                <option value="operator">Opérateur</option>
                <option value="owner">Propriétaire</option>
                <option value="user">Utilisateur</option>
            </select>
        </div>
        
        <!-- Alerts -->
        @if(session('success'))
            <div class="alert-custom success">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert-custom error">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif
        
        @if($users->count() > 0)
            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('messages.user') ?? 'Utilisateur' }}</th>
                            <th>{{ __('messages.role') ?? 'Rôle' }}</th>
                            <th>{{ __('messages.status') ?? 'Statut' }}</th>
                            <th>{{ __('messages.last_login') ?? 'Dernière connexion' }}</th>
                            <th>{{ __('messages.created_at') ?? 'Créé le' }}</th>
                            <th class="text-right">{{ __('messages.actions') ?? 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            @php
                                $gradientIndex = ($user->id % 5) + 1;
                                $userRole = $user->getRoleNames()->first() ?? $user->role ?? 'user';
                            @endphp
                            <tr x-show="shouldShowUser({{ json_encode([
                                'name' => $user->name,
                                'email' => $user->email,
                                'role' => $userRole,
                                'is_active' => $user->is_active ?? true
                            ]) }})">
                                <td>
                                    <span class="id-badge">#{{ $user->id }}</span>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar gradient-{{ $gradientIndex }}">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="user-name">{{ $user->name }}</div>
                                            <div class="user-email">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge {{ strtolower($userRole) }}">
                                        {{ ucfirst($userRole) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-pill {{ ($user->is_active ?? true) ? 'active' : 'inactive' }}">
                                        <span class="status-dot"></span>
                                        {{ ($user->is_active ?? true) ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="date-text">
                                        {{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : ($user->last_login ? $user->last_login->format('d/m/Y H:i') : 'Jamais') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="date-text">{{ $user->created_at->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <div class="action-btns" style="justify-content: flex-end;">
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="action-btn view" title="Voir">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="action-btn edit" title="Modifier">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <button type="button" class="action-btn delete" title="Supprimer" onclick="confirmDelete({{ $user->id }})">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator || $users instanceof \Illuminate\Pagination\Paginator)
                <div class="pagination-wrapper">
                    {{ $users->links() }}
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3>Aucun utilisateur trouvé</h3>
                <p>Aucun utilisateur n'a été créé dans le système.</p>
                <a href="{{ route('admin.users.create') }}" class="btn-add-user">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Créer un Utilisateur
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function usersAdmin() {
    return {
        searchQuery: '',
        statusFilter: '',
        roleFilter: '',
        
        filterUsers() {
            // Client-side filtering is handled by shouldShowUser
        },
        
        shouldShowUser(user) {
            // Search filter
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                const matchesName = user.name.toLowerCase().includes(query);
                const matchesEmail = user.email.toLowerCase().includes(query);
                if (!matchesName && !matchesEmail) return false;
            }
            
            // Status filter
            if (this.statusFilter) {
                const isActive = user.is_active;
                if (this.statusFilter === 'active' && !isActive) return false;
                if (this.statusFilter === 'inactive' && isActive) return false;
            }
            
            // Role filter
            if (this.roleFilter) {
                if (user.role.toLowerCase() !== this.roleFilter.toLowerCase()) return false;
            }
            
            return true;
        }
    }
}

function confirmDelete(userId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.users.destroy", ":id") }}'.replace(':id', userId);
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
