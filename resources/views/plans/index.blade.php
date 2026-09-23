@extends('layouts.app')

@section('title', __('Plans Tarifaires'))

@push('styles')
<style>
    .plans-content { padding-bottom: 120px; }
    
    /* Header gradient */
    .plans-header {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        color: white;
    }
    
    /* Stats cards */
    .plan-stat {
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        border-radius: 0.75rem;
        padding: 1rem;
        text-align: center;
    }
    .plan-stat-value { font-size: 1.5rem; font-weight: 700; }
    .plan-stat-label { font-size: 0.75rem; opacity: 0.9; }
    
    /* Plan cards grid */
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1rem;
    }
    
    /* Plan card */
    .plan-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: all 0.2s;
    }
    .dark .plan-card { background: #1f2937; }
    .plan-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    
    .plan-card-header {
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .dark .plan-card-header { border-color: #374151; }
    
    .plan-card-body { padding: 1rem; }
    
    .plan-card-footer {
        padding: 0.75rem 1rem;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dark .plan-card-footer { background: #111827; border-color: #374151; }
    
    /* Plan name & icon */
    .plan-icon {
        width: 40px;
        height: 40px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    /* Status badge */
    .plan-status {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .plan-status-active { background: #d1fae5; color: #065f46; }
    .plan-status-inactive { background: #fee2e2; color: #991b1b; }
    .dark .plan-status-active { background: #065f46; color: #d1fae5; }
    .dark .plan-status-inactive { background: #991b1b; color: #fee2e2; }
    
    /* Price display */
    .plan-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: #059669;
    }
    .plan-price-unit {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 400;
    }
    
    /* Info rows */
    .plan-info {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        font-size: 0.8rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .plan-info:last-child { border-bottom: none; }
    .dark .plan-info { border-color: #374151; }
    .plan-info-label { color: #6b7280; }
    .dark .plan-info-label { color: #9ca3af; }
    .plan-info-value { color: #111827; font-weight: 500; }
    .dark .plan-info-value { color: #f3f4f6; }
    
    /* Action buttons */
    .plan-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        transition: all 0.15s;
        text-decoration: none;
    }
    
    /* Search & Filter */
    .plans-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .plans-search {
        flex: 1;
        min-width: 200px;
        position: relative;
    }
    .plans-search input {
        width: 100%;
        padding: 0.5rem 0.75rem 0.5rem 2.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }
    .dark .plans-search input { background: #374151; border-color: #4b5563; color: white; }
    .plans-search svg {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }
    
    /* Empty state */
    .plans-empty {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }
    .dark .plans-empty { color: #9ca3af; }
</style>
@endpush

@section('content')
@php
    $plansCollection = $plans ?? collect();
    $totalPlans = method_exists($plansCollection, 'total') ? $plansCollection->total() : $plansCollection->count();
    $activePlans = $plansCollection->where('is_active', true)->count();
    $inactivePlans = $plansCollection->where('is_active', false)->count();
@endphp

<div class="plans-content p-4">
    {{-- Header with Stats --}}
    <div class="plans-header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-tags"></i>
                    {{ __('Plans Tarifaires') }}
                </h1>
                <p class="text-sm opacity-90 mt-1">{{ __('Gérez vos plans de tarification pour les recharges') }}</p>
            </div>
            <a href="{{ route('plans.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-white text-green-600 font-medium rounded-lg hover:bg-green-50 transition">
                <i class="fas fa-plus mr-2"></i>
                {{ __('Nouveau plan') }}
            </a>
        </div>
        
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-3 mt-4">
            <div class="plan-stat">
                <div class="plan-stat-value">{{ $totalPlans }}</div>
                <div class="plan-stat-label">{{ __('Total') }}</div>
            </div>
            <div class="plan-stat">
                <div class="plan-stat-value">{{ $activePlans }}</div>
                <div class="plan-stat-label">{{ __('Actifs') }}</div>
            </div>
            <div class="plan-stat">
                <div class="plan-stat-value">{{ $inactivePlans }}</div>
                <div class="plan-stat-label">{{ __('Inactifs') }}</div>
            </div>
        </div>
    </div>
    
    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-400 rounded text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif
    
    @if(session('error'))
    <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-400 rounded text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif
    
    {{-- Filters --}}
    <div class="plans-filters" x-data="{ search: '', status: '', type: '' }">
        <div class="plans-search">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" placeholder="{{ __('Rechercher un plan...') }}" 
                   @input="filterPlans()" id="searchInput">
        </div>
        <select x-model="status" @change="filterPlans()" id="statusFilter"
                class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 dark:text-white">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="active">{{ __('Actifs') }}</option>
            <option value="inactive">{{ __('Inactifs') }}</option>
        </select>
        <select x-model="type" @change="filterPlans()" id="typeFilter"
                class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 dark:text-white">
            <option value="">{{ __('Tous les types') }}</option>
            <option value="fixed">{{ __('Forfait fixe') }}</option>
            <option value="time">{{ __('À la minute') }}</option>
            <option value="energy">{{ __('Par kWh') }}</option>
        </select>
    </div>
    
    {{-- Plans Grid --}}
    <div class="plans-grid" id="plansGrid">
        @forelse($plansCollection as $plan)
        @php
            $typeLabels = ['fixed' => __('Forfait fixe'), 'time' => __('À la minute'), 'energy' => __('Par kWh')];
            $typeLabel = $typeLabels[$plan->rate_type ?? ''] ?? __('Non défini');
            
            $price = 0;
            $unit = '';
            if ($plan->rate_type === 'fixed') {
                $price = $plan->base_rate ?? 0;
                $unit = 'EUR';
            } elseif ($plan->rate_type === 'time') {
                $price = $plan->price_per_minute ?? 0;
                $unit = '/min';
            } elseif ($plan->rate_type === 'energy') {
                $price = $plan->price_per_kwh ?? 0;
                $unit = '/kWh';
            }
        @endphp
        <div class="plan-card" 
             data-name="{{ strtolower($plan->name ?? '') }}"
             data-status="{{ $plan->is_active ? 'active' : 'inactive' }}"
             data-type="{{ $plan->rate_type ?? '' }}">
            
            {{-- Header --}}
            <div class="plan-card-header">
                <div class="flex items-center gap-3">
                    <div class="plan-icon bg-green-100 dark:bg-green-900/30">
                        <i class="fas fa-calculator text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
                            {{ $plan->name ?? __('Plan sans nom') }}
                        </h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $typeLabel }}</span>
                    </div>
                </div>
                <span class="plan-status {{ $plan->is_active ? 'plan-status-active' : 'plan-status-inactive' }}">
                    {{ $plan->is_active ? __('Actif') : __('Inactif') }}
                </span>
            </div>
            
            {{-- Body --}}
            <div class="plan-card-body">
                {{-- Price --}}
                <div class="text-center mb-3 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <span class="plan-price">{{ number_format($price, 2) }}</span>
                    <span class="plan-price-unit">EUR{{ $unit }}</span>
                </div>
                
                {{-- Info --}}
                <div class="plan-info">
                    <span class="plan-info-label">{{ __('Frais activation') }}</span>
                    <span class="plan-info-value">{{ number_format($plan->activation_fee ?? 0, 2) }} EUR</span>
                </div>
                <div class="plan-info">
                    <span class="plan-info-label">{{ __('Durée max') }}</span>
                    <span class="plan-info-value">
                        @if($plan->max_duration > 0)
                            {{ $plan->max_duration }} min
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </span>
                </div>
                <div class="plan-info">
                    <span class="plan-info-label">{{ __('Priorité') }}</span>
                    <span class="plan-info-value">{{ $plan->priority ?? 0 }}</span>
                </div>
                
                @if($plan->description)
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ Str::limit($plan->description, 60) }}
                </div>
                @endif
            </div>
            
            {{-- Footer Actions --}}
            <div class="plan-card-footer">
                <div class="flex gap-1">
                    <a href="{{ route('plans.show', $plan) }}" 
                       class="plan-action text-indigo-600 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('plans.edit', $plan) }}" 
                       class="plan-action text-green-600 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
                <div class="flex gap-1">
                    @if(!$plan->is_active)
                    <form action="{{ route('plans.activate', $plan) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="plan-action text-blue-600 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </form>
                    @endif
                    <form action="{{ route('plans.destroy', $plan) }}" method="POST" class="inline" 
                          onsubmit="return confirm('{{ __('Supprimer ce plan ?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="plan-action text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="plans-empty col-span-full">
            <i class="fas fa-tags text-4xl mb-3 text-gray-300"></i>
            <h3 class="font-medium text-gray-900 dark:text-white">{{ __('Aucun plan tarifaire') }}</h3>
            <p class="text-sm mt-1">{{ __('Créez votre premier plan pour commencer') }}</p>
            <a href="{{ route('plans.create') }}" 
               class="inline-flex items-center mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-plus mr-2"></i>
                {{ __('Créer un plan') }}
            </a>
        </div>
        @endforelse
    </div>
    
    {{-- Pagination --}}
    @if(method_exists($plansCollection, 'links'))
    <div class="mt-4">
        {{ $plansCollection->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function filterPlans() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const type = document.getElementById('typeFilter').value;
    
    document.querySelectorAll('.plan-card').forEach(card => {
        const name = card.dataset.name || '';
        const cardStatus = card.dataset.status || '';
        const cardType = card.dataset.type || '';
        
        const matchesSearch = !search || name.includes(search);
        const matchesStatus = !status || cardStatus === status;
        const matchesType = !type || cardType === type;
        
        card.style.display = (matchesSearch && matchesStatus && matchesType) ? '' : 'none';
    });
}
</script>
@endpush
@endsection
