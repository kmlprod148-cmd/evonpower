@extends('layouts.app')

@section('title', __('Points de charge'))

@push('styles')
<style>
    .cp-content { padding-bottom: 120px; }
    
    /* Header gradient compact */
    .cp-header {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
        color: white;
    }
    
    /* Stats row compact */
    .cp-stats {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .cp-stat {
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(8px);
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        text-align: center;
        flex: 1;
    }
    .cp-stat-value { font-size: 1.1rem; font-weight: 700; }
    .cp-stat-label { font-size: 0.65rem; opacity: 0.9; text-transform: uppercase; }
    
    /* Filters compact */
    .cp-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1rem;
        background: white;
        padding: 0.875rem 1rem;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        align-items: center;
    }
    .dark .cp-filters { background: #1f2937; }
    
    .cp-search {
        flex: 1;
        min-width: 180px;
        position: relative;
    }
    .cp-search input {
        width: 100%;
        padding: 0.625rem 0.75rem 0.625rem 2.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.8125rem;
        transition: all 0.2s;
    }
    .cp-search input:focus {
        outline: none;
        border-color: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
    }
    .dark .cp-search input { background: #374151; border-color: #4b5563; color: white; }
    .cp-search svg {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        color: #9ca3af;
    }
    
    /* Cards grid - HALF SIZE */
    .cp-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0.75rem;
    }
    @media (min-width: 768px) {
        .cp-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
    }
    @media (min-width: 1024px) {
        .cp-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
    }
    
    /* Card compact - HALF SIZE */
    .cp-card {
        background: white;
        border-radius: 0.625rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        overflow: hidden;
        transition: all 0.2s;
        border: 1px solid #e5e7eb;
    }
    .dark .cp-card { background: #1f2937; border-color: #374151; }
    .cp-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
    
    .cp-card-header {
        padding: 0.625rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .dark .cp-card-header { border-color: #374151; }
    
    .cp-card-icon {
        width: 28px;
        height: 28px;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .cp-card-body { padding: 0.5rem 0.625rem; }
    
    .cp-card-footer {
        padding: 0.5rem 0.625rem;
        background: #f9fafb;
        border-top: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dark .cp-card-footer { background: #111827; border-color: #374151; }
    
    /* Status badge compact */
    .cp-status {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.375rem;
        border-radius: 9999px;
        font-size: 0.6rem;
        font-weight: 600;
        gap: 0.25rem;
    }
    .cp-status-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }
    .cp-status-online { background: #d1fae5; color: #065f46; }
    .cp-status-online .cp-status-dot { background: #10b981; }
    .cp-status-offline { background: #fee2e2; color: #991b1b; }
    .cp-status-offline .cp-status-dot { background: #ef4444; }
    .cp-status-maintenance { background: #ffedd5; color: #9a3412; }
    .cp-status-maintenance .cp-status-dot { background: #f97316; }
    
    .dark .cp-status-online { background: #065f46; color: #d1fae5; }
    .dark .cp-status-offline { background: #991b1b; color: #fee2e2; }
    .dark .cp-status-maintenance { background: #9a3412; color: #ffedd5; }
    
    /* Info rows compact */
    .cp-info {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.7rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    .cp-info:last-child { margin-bottom: 0; }
    .dark .cp-info { color: #9ca3af; }
    .cp-info svg { width: 12px; height: 12px; flex-shrink: 0; }
    
    /* Actions compact */
    .cp-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 0.25rem;
        transition: all 0.15s;
    }
    .cp-action svg { width: 12px; height: 12px; }
    
    /* Quick filter pills */
    .cp-pills {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .cp-pill {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid transparent;
        cursor: pointer;
    }
    .cp-pill:hover {
        transform: translateY(-1px);
    }
    /* Default state - all */
    .cp-pill-all {
        background: #f3f4f6;
        color: #374151;
        border-color: #e5e7eb;
    }
    .cp-pill-all:hover {
        background: #e5e7eb;
    }
    .dark .cp-pill-all {
        background: #374151;
        color: #f3f4f6;
        border-color: #4b5563;
    }
    .dark .cp-pill-all:hover {
        background: #4b5563;
    }
    /* Active state - all */
    .cp-pill-all-active {
        background: #1f2937;
        color: white;
        border-color: #1f2937;
    }
    /* Online state */
    .cp-pill-online {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }
    .cp-pill-online:hover {
        background: #bbf7d0;
    }
    .dark .cp-pill-online {
        background: #14532d;
        color: #86efac;
        border-color: #166534;
    }
    .dark .cp-pill-online:hover {
        background: #166534;
    }
    .cp-pill-online-active {
        background: #22c55e;
        color: white;
        border-color: #22c55e;
    }
    /* Maintenance state */
    .cp-pill-maintenance {
        background: #ffedd5;
        color: #9a3412;
        border-color: #fed7aa;
    }
    .cp-pill-maintenance:hover {
        background: #fed7aa;
    }
    .dark .cp-pill-maintenance {
        background: #7c2d12;
        color: #fdba74;
        border-color: #9a3412;
    }
    .dark .cp-pill-maintenance:hover {
        background: #9a3412;
    }
    .cp-pill-maintenance-active {
        background: #f97316;
        color: white;
        border-color: #f97316;
    }
    
    /* Empty state */
    .cp-empty {
        text-align: center;
        padding: 2rem;
        color: #6b7280;
        grid-column: 1 / -1;
    }
    .dark .cp-empty { color: #9ca3af; }
</style>
@endpush

@section('content')
@php
    $total = method_exists($chargingPoints, 'total') ? $chargingPoints->total() : $chargingPoints->count();
    $online = $stats['activeChargingPoints'] ?? 0;
    $maintenance = $stats['maintenanceChargingPoints'] ?? 0;
    $offline = $total - $online - $maintenance;
@endphp

<div class="cp-content p-4">
    {{-- Header compact --}}
    <div class="cp-header">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h1 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-charging-station"></i>
                    {{ __('Points de charge') }}
                </h1>
                <p class="text-xs opacity-90">{{ isset($isClientOnly) && $isClientOnly ? __('Parcourez les bornes disponibles et réservez') : __('Gérez vos bornes de recharge') }}</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                @if(auth()->check() && auth()->user()->hasRole(['admin', 'super_admin']))
                <button id="sync-steve-btn" class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-medium rounded-lg transition flex items-center gap-1">
                    <i class="fas fa-sync-alt" id="sync-icon"></i>
                    <span id="sync-text">{{ __('Synchro') }}</span>
                </button>
                @endif
                @if(!isset($isClientOnly) || !$isClientOnly)
                <a href="{{ route('charging-points.create.step1') }}" 
                   class="px-3 py-1.5 bg-white text-white text-xs font-medium rounded-lg hover:bg-green-50 transition flex items-center gap-1">
                    <i class="fas fa-plus"></i>
                    {{ __('Ajouter') }}
                </a>
                @endif
            </div>
        </div>
        
        {{-- Stats compact --}}
        <div class="cp-stats">
            <div class="cp-stat">
                <div class="cp-stat-value">{{ $total }}</div>
                <div class="cp-stat-label">{{ __('Total') }}</div>
            </div>
            <div class="cp-stat">
                <div class="cp-stat-value">{{ $online }}</div>
                <div class="cp-stat-label">{{ __('En ligne') }}</div>
            </div>
            <div class="cp-stat">
                <div class="cp-stat-value">{{ $maintenance }}</div>
                <div class="cp-stat-label">{{ __('Maintenance') }}</div>
            </div>
            <div class="cp-stat">
                <div class="cp-stat-value">{{ $offline }}</div>
                <div class="cp-stat-label">{{ __('Hors ligne') }}</div>
            </div>
        </div>
    </div>
    
    {{-- Flash Messages compact --}}
    @if(session('success'))
    <div class="mb-3 p-2 bg-green-100 dark:bg-green-900/30 border-l-3 border-green-500 text-green-700 dark:text-green-400 rounded text-xs flex items-center gap-2">
        <i class="fas fa-check-circle"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-3 p-2 bg-red-100 dark:bg-red-900/30 border-l-3 border-red-500 text-red-700 dark:text-red-400 rounded text-xs flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i>{{ session('error') }}
    </div>
    @endif
    
    {{-- Filters compact --}}
    <div class="cp-filters">
        <form action="{{ route('charging-points.index') }}" method="GET" class="flex flex-1 flex-wrap gap-2">
            <div class="cp-search flex-1">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Rechercher...') }}">
            </div>
            <select name="status" class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-xs bg-white dark:bg-gray-700 dark:text-white font-medium transition-all focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
                <option value="">{{ __('Tous') }}</option>
                <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>{{ __('En ligne') }}</option>
                <option value="offline" {{ request('status') == 'offline' ? 'selected' : '' }}>{{ __('Hors ligne') }}</option>
                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>{{ __('Maintenance') }}</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white text-xs font-semibold rounded-lg hover:from-green-600 hover:to-green-700 shadow-sm transition-all transform hover:scale-105">
                <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                {{ __('Filtrer') }}
            </button>
        </form>
        
        {{-- Quick pills --}}
        <div class="cp-pills">
            <a href="{{ route('charging-points.index') }}" 
               class="cp-pill {{ !request()->has('filter') ? 'cp-pill-all-active' : 'cp-pill-all' }}">
                {{ __('Tous') }}
            </a>
            <a href="{{ route('charging-points.index', ['filter' => 'online']) }}" 
               class="cp-pill {{ request('filter') == 'online' ? 'cp-pill-online-active' : 'cp-pill-online' }}">
                {{ __('En ligne') }}
            </a>
            <a href="{{ route('charging-points.index', ['filter' => 'maintenance']) }}" 
               class="cp-pill {{ request('filter') == 'maintenance' ? 'cp-pill-maintenance-active' : 'cp-pill-maintenance' }}">
                {{ __('Maintenance') }}
            </a>
        </div>
    </div>
    
    {{-- Cards Grid - HALF SIZE --}}
    <div class="cp-grid">
        @forelse($chargingPoints as $cp)
        @php
            $statusClass = match($cp->status) {
                'online' => 'cp-status-online',
                'offline' => 'cp-status-offline',
                default => 'cp-status-maintenance'
            };
            $statusLabel = match($cp->status) {
                'online' => __('En ligne'),
                'offline' => __('Hors ligne'),
                default => __('Maintenance')
            };
        @endphp
        <div class="cp-card">
            {{-- Header --}}
            <div class="cp-card-header">
                <div class="cp-card-icon bg-eco-green-100 dark:bg-eco-green-900/30">
                    <i class="fas fa-bolt text-eco-green-600 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-xs truncate">{{ $cp->name }}</h3>
                    <span class="cp-status {{ $statusClass }}">
                        <span class="cp-status-dot"></span>
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
            
            {{-- Body --}}
            <div class="cp-card-body">
                <div class="cp-info">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <span class="truncate">{{ $cp->city ?? __('N/A') }}</span>
                </div>
                <div class="cp-info">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                    <span class="font-mono truncate text-[10px]">{{ Str::limit($cp->serial_number, 12) }}</span>
                </div>
                @if($cp->group)
                <div class="cp-info">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span class="truncate">{{ $cp->group->name }}</span>
                </div>
                @endif
            </div>
            
            {{-- Footer --}}
            <div class="cp-card-footer">
                <a href="{{ route('charging-points.show', $cp) }}" 
                   class="cp-action text-indigo-600 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30" title="{{ __('Voir') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </a>
                <div class="flex gap-1">
                    @can('update', $cp)
                    <a href="{{ route('charging-points.edit', $cp) }}" 
                       class="cp-action text-blue-600 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30" title="{{ __('Modifier') }}">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    @endcan
                    @can('delete', $cp)
                    <form action="{{ route('charging-points.destroy', $cp) }}" method="POST" class="inline" 
                          onsubmit="return confirm('{{ __('Supprimer cette borne ?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cp-action text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/30" title="{{ __('Supprimer') }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
        @empty
        <div class="cp-empty">
            <i class="fas fa-charging-station text-3xl mb-2 text-gray-300"></i>
            <h3 class="font-medium text-gray-900 dark:text-white text-sm">{{ __('Aucun point de charge') }}</h3>
            <p class="text-xs mt-1">{{ isset($isClientOnly) && $isClientOnly ? __('Aucune borne disponible pour le moment') : __('Ajoutez votre première borne') }}</p>
            @if(!isset($isClientOnly) || !$isClientOnly)
            <a href="{{ route('charging-points.create.step1') }}" 
               class="inline-flex items-center mt-3 px-3 py-1.5 bg-eco-green-600 text-white text-xs rounded-lg hover:bg-eco-green-700 transition">
                <i class="fas fa-plus mr-1"></i>
                {{ __('Ajouter') }}
            </a>
            @endif
        </div>
        @endforelse
    </div>
    
    {{-- Pagination --}}
    @if($chargingPoints->hasPages())
    <div class="mt-4">
        {{ $chargingPoints->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync Steve button
    const syncBtn = document.getElementById('sync-steve-btn');
    if (syncBtn) {
        syncBtn.addEventListener('click', async function() {
            const icon = document.getElementById('sync-icon');
            const text = document.getElementById('sync-text');
            
            icon.classList.add('fa-spin');
            text.textContent = '{{ __("Synchro...") }}';
            syncBtn.disabled = true;
            
            try {
                const response = await fetch('/api/charging-points/sync-steve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                
                if (response.ok) {
                    location.reload();
                } else {
                    alert('{{ __("Erreur de synchronisation") }}');
                }
            } catch (e) {
                console.error(e);
            } finally {
                icon.classList.remove('fa-spin');
                text.textContent = '{{ __("Synchro") }}';
                syncBtn.disabled = false;
            }
        });
    }
});
</script>
@endpush
@endsection
