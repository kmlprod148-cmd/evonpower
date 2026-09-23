@extends('layouts.app')

@section('title', __('messages.integrators') ?? 'Intégrateurs')
@section('page-title', __('messages.integrators') ?? 'Intégrateurs')

@push('styles')
<style>
    .integrators-container {
        max-width: 1400px;
        margin: 0 auto;
        /* Critical: ensure enough bottom padding for mobile nav */
        padding-bottom: 100px !important;
    }
    
    @media (min-width: 1024px) {
        .integrators-container {
            padding-bottom: 2rem !important;
        }
    }
    
    .integrator-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        text-transform: uppercase;
        flex-shrink: 0;
        color: white;
    }
    
    .integrator-avatar.gradient-1 { background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%); }
    .integrator-avatar.gradient-2 { background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); }
    .integrator-avatar.gradient-3 { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); }
    .integrator-avatar.gradient-4 { background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%); }
    .integrator-avatar.gradient-5 { background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%); }
    
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .status-pill.active {
        background: linear-gradient(135deg, rgba(74, 207, 123, 0.15) 0%, rgba(45, 212, 191, 0.15) 100%);
        color: #059669;
    }
    
    .status-pill.inactive {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }
    
    .dark .status-pill.active {
        background: rgba(74, 207, 123, 0.2);
        color: #6ee7b7;
    }
    
    .dark .status-pill.inactive {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }
    
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    
    .status-dot.active {
        background: #10b981;
        animation: pulse-dot 2s infinite;
    }
    
    .status-dot.inactive {
        background: #ef4444;
    }
    
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(1.2); }
    }
    
    .action-icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    
    .action-icon-btn:hover {
        transform: translateY(-2px);
    }
    
    .action-icon-btn.view { 
        background: rgba(59, 130, 246, 0.1); 
        color: #3b82f6; 
    }
    .action-icon-btn.view:hover { 
        background: #3b82f6; 
        color: white; 
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    
    .action-icon-btn.edit { 
        background: rgba(74, 207, 123, 0.1); 
        color: #4acf7b; 
    }
    .action-icon-btn.edit:hover { 
        background: #4acf7b; 
        color: white; 
        box-shadow: 0 4px 12px rgba(74, 207, 123, 0.4);
    }
    
    .action-icon-btn.delete { 
        background: rgba(239, 68, 68, 0.1); 
        color: #ef4444; 
    }
    .action-icon-btn.delete:hover { 
        background: #ef4444; 
        color: white; 
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    
    .table-row-hover {
        transition: all 0.15s ease;
    }
    
    .table-row-hover:hover {
        background-color: #f8fafc;
    }
    
    .dark .table-row-hover:hover {
        background-color: #334155;
    }
    
    .stat-card-integrator {
        background: white;
        border-radius: 1rem;
        padding: 1.25rem;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    
    .stat-card-integrator:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px -8px rgba(0, 0, 0, 0.1);
    }
    
    .dark .stat-card-integrator {
        background: #1e293b;
        border-color: #334155;
    }
    
    .header-gradient {
        background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 50%, #06b6d4 100%);
    }
    
    .empty-state-container {
        text-align: center;
        padding: 4rem 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 1.5rem;
        border: 2px dashed #cbd5e1;
    }
    
    .dark .empty-state-container {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        border-color: #475569;
    }
    
    /* Ensure table scrolls properly */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Last row visibility fix */
    .table-wrapper table tbody tr:last-child {
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="integrators-container p-4 lg:p-6">
    {{-- Header Banner --}}
    <div class="header-gradient text-white rounded-2xl shadow-lg p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold mb-2 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    {{ __('messages.integrators') ?? 'Intégrateurs' }}
                </h1>
                <p class="text-white/80">
                    {{ __('messages.manage_integrators_desc') ?? 'Gérez et visualisez tous les intégrateurs de votre système' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                @can('create_integrators')
                <a href="{{ route('integrators.create') }}" 
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-eco-green-700 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ __('messages.new_integrator') ?? 'Nouvel Intégrateur' }}
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card-integrator">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(74, 207, 123, 0.1) 0%, rgba(74, 207, 123, 0.2) 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-eco-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $integrators->count() }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                </div>
            </div>
        </div>
        
        <div class="stat-card-integrator">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.2) 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $integrators->where('is_active', true)->count() }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Actifs</p>
                </div>
            </div>
        </div>
        
        <div class="stat-card-integrator">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.2) 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $integrators->where('is_active', false)->count() }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Inactifs</p>
                </div>
            </div>
        </div>
        
        <div class="stat-card-integrator">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.2) 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $integrators->count() > 0 ? round(($integrators->where('is_active', true)->count() / $integrators->count()) * 100) : 0 }}%
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Taux actif</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    @if($integrators->count() > 0)
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
        {{-- Card Header --}}
        <div class="p-4 lg:p-6 border-b border-gray-100 dark:border-slate-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-eco-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    {{ __('messages.integrators_list') ?? 'Liste des Intégrateurs' }}
                </h2>
                <div class="flex items-center gap-2">
                    @if(Route::has('integrators.export'))
                    <a href="{{ route('integrators.export') }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ __('messages.export') ?? 'Exporter' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-wrapper">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-slate-700/50">
                        <th scope="col" class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.integrator') ?? 'Intégrateur' }}
                        </th>
                        <th scope="col" class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">
                            {{ __('messages.email') ?? 'Email' }}
                        </th>
                        <th scope="col" class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.status') ?? 'Statut' }}
                        </th>
                        <th scope="col" class="px-4 lg:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">
                            {{ __('messages.created_at') ?? 'Créé le' }}
                        </th>
                        <th scope="col" class="px-4 lg:px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.actions') ?? 'Actions' }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($integrators as $index => $integrator)
                    <tr class="table-row-hover">
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="integrator-avatar gradient-{{ ($index % 5) + 1 }}">
                                    {{ strtoupper(substr($integrator->name ?? 'I', 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $integrator->name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 sm:hidden">
                                        {{ $integrator->email }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $integrator->email }}
                            </span>
                        </td>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                            <span class="status-pill {{ $integrator->is_active ? 'active' : 'inactive' }}">
                                <span class="status-dot {{ $integrator->is_active ? 'active' : 'inactive' }}"></span>
                                {{ $integrator->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $integrator->created_at->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('integrators.show', $integrator) }}" 
                                   class="action-icon-btn view"
                                   title="{{ __('messages.view') ?? 'Voir' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                
                                @can('edit_integrators')
                                <a href="{{ route('integrators.edit', $integrator) }}" 
                                   class="action-icon-btn edit"
                                   title="{{ __('messages.edit') ?? 'Modifier' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                @endcan
                                
                                @can('delete_integrators')
                                <form method="POST" 
                                      action="{{ route('integrators.destroy', $integrator) }}" 
                                      class="inline"
                                      onsubmit="return confirm('{{ __('messages.confirm_delete_integrator') ?? 'Êtes-vous sûr de vouloir supprimer cet intégrateur ?' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="action-icon-btn delete"
                                            title="{{ __('messages.delete') ?? 'Supprimer' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{-- Pagination placeholder --}}
        @if(method_exists($integrators, 'hasPages') && $integrators->hasPages())
        <div class="px-4 lg:px-6 py-4 border-t border-gray-100 dark:border-slate-700">
            {{ $integrators->links() }}
        </div>
        @endif
    </div>
    @else
    {{-- Empty State --}}
    <div class="empty-state-container">
        <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-slate-600 flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
            {{ __('messages.no_integrators') ?? 'Aucun intégrateur trouvé' }}
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
            {{ __('messages.no_integrators_desc') ?? 'Il n\'y a actuellement aucun intégrateur visible pour votre compte.' }}
        </p>
        @can('create_integrators')
        <a href="{{ route('integrators.create') }}" 
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-white transition-all"
           style="background: linear-gradient(135deg, #4acf7b 0%, #2dd4bf 100%);">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {{ __('messages.create_first_integrator') ?? 'Créer le premier intégrateur' }}
        </a>
        @endcan
    </div>
    @endif
    
    {{-- Bottom spacer for mobile nav --}}
    <div class="h-20 lg:h-0"></div>
</div>
@endsection
