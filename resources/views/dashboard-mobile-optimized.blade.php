@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('content')
{{-- Pull to Refresh Wrapper --}}
<x-mobile-pull-to-refresh id="dashboard-ptr" onRefresh="refreshDashboard">
    
    {{-- Page Header - Mobile Optimized --}}
    <div class="evon-page-header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="evon-page-title text-2xl sm:text-3xl">
                    {{ __('messages.dashboard') }}
                </h1>
                <p class="evon-page-subtitle mt-1">
                    {{ __('messages.welcome_back') ?? 'Bienvenue sur votre tableau de bord' }}
                </p>
            </div>
            
            {{-- Actions - Full width on mobile --}}
            <div class="flex gap-2 sm:gap-3">
                <button 
                    onclick="refreshDashboard()"
                    class="btn-secondary flex-1 sm:flex-none flex items-center justify-center gap-2 py-2.5 sm:py-2"
                    aria-label="Actualiser le tableau de bord"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('messages.refresh') ?? 'Actualiser' }}</span>
                </button>
                
                <button 
                    onclick="openActionSheet('dashboard-actions')"
                    class="btn-primary flex-1 sm:flex-none flex items-center justify-center gap-2 py-2.5 sm:py-2"
                    aria-label="Actions rapides"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span class="hidden sm:inline">Actions</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Stats Grid - Mobile First (1 col mobile, 2 cols tablet, 4 cols desktop) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-6">
        
        {{-- Total Charging Points --}}
        <x-mobile-stat-card
            title="{{ __('messages.total_charging_points') }}"
            value="{{ $totalRecharges ?? 12 }}"
            change="+12%"
            changeType="positive"
            trend="vs mois dernier"
            iconColor="blue"
            href="{{ route('charging-points.index') }}"
        >
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </x-slot:icon>
        </x-mobile-stat-card>

        {{-- Active Charging Points --}}
        <x-mobile-stat-card
            title="{{ __('messages.active_charging_points') }}"
            value="{{ $rechargesActives ?? 4 }}"
            changeType="neutral"
            trend="En cours"
            iconColor="green"
            href="{{ route('charging-points.index', ['filter' => 'active']) }}"
        >
            <x-slot:icon>
                <div class="status-dot-live"></div>
            </x-slot:icon>
        </x-mobile-stat-card>

        {{-- Active Subscriptions --}}
        <x-mobile-stat-card
            title="{{ __('messages.active') }} (Abo)"
            value="{{ $abonnementsActifs ?? 3 }}"
            change="+2"
            changeType="positive"
            trend="nouveaux ce mois"
            iconColor="purple"
            href="{{ route('subscriptions.index') ?? '#' }}"
        >
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>
        </x-mobile-stat-card>

        {{-- Active Stations --}}
        <x-mobile-stat-card
            title="Bornes Actives"
            value="{{ $bornesActives ?? 16 }}"
            changeType="neutral"
            trend="Sur 20 installées"
            iconColor="eco"
            href="{{ route('stations.index') ?? '#' }}"
        >
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </x-slot:icon>
        </x-mobile-stat-card>
    </div>

    {{-- Charts & Data Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
        
        {{-- Chart Card - Full width on mobile, 2/3 on desktop --}}
        <div class="lg:col-span-2 evon-chart-card">
            <div class="evon-chart-header">
                <h3 class="evon-chart-title">{{ __('messages.sessions_chart') }}</h3>
                <div class="evon-chart-filters">
                    <button class="evon-chart-filter-button bg-eco-green-100 dark:bg-eco-green-900/30 text-eco-green-700 dark:text-eco-green-300 font-medium">
                        {{ __('messages.today') }}
                    </button>
                    <button class="evon-chart-filter-button">
                        7j
                    </button>
                    <button class="evon-chart-filter-button">
                        30j
                    </button>
                </div>
            </div>
            <div class="h-64 sm:h-80 w-full relative p-4">
                <canvas id="rechargesChart"></canvas>
            </div>
        </div>

        {{-- Active Sessions Table - Full width on mobile, 1/3 on desktop --}}
        <div class="evon-table-container">
            <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="evon-table-title">{{ __('messages.active_charging_points') }}</h3>
                <span class="text-xs font-semibold px-2.5 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full">
                    {{ count($rechargesActivesList ?? []) }}
                </span>
            </div>
            <div class="evon-table-wrapper max-h-[300px] sm:max-h-[340px] overflow-y-auto custom-scrollbar">
                <table class="evon-table">
                    <thead class="evon-table-head sticky top-0 z-10">
                        <tr>
                            <th class="evon-table-head-cell">Borne</th>
                            <th class="evon-table-head-cell text-right">Conso</th>
                            <th class="evon-table-head-cell text-right">État</th>
                        </tr>
                    </thead>
                    <tbody class="evon-table-body">
                        @forelse($rechargesActivesList ?? [] as $recharge)
                            <tr class="evon-table-row group hover:bg-eco-green-50 dark:hover:bg-eco-green-900/10 cursor-pointer"
                                onclick="window.location='{{ route('charging-points.show', $recharge['id'] ?? 1) }}'">
                                <td class="evon-table-cell font-medium">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-eco-green-100 dark:bg-eco-green-900/30 text-eco-green-600 dark:text-eco-green-400 rounded-lg flex items-center justify-center text-xs font-bold">
                                            {{ substr($recharge['name'], 0, 2) }}
                                        </div>
                                        <span class="truncate">{{ $recharge['name'] }}</span>
                                    </div>
                                </td>
                                <td class="evon-table-cell text-right font-semibold tabular-nums">
                                    {{ $recharge['kwh'] }} <span class="text-xs text-gray-500">kWh</span>
                                </td>
                                <td class="evon-table-cell text-right">
                                    <span class="status-dot-live"></span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="evon-table-cell text-center text-gray-500 py-8">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <span class="text-sm">{{ __('messages.no_data_available') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- View All Link --}}
            @if(count($rechargesActivesList ?? []) > 0)
                <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('charging-points.index') }}" 
                       class="text-sm font-medium text-eco-green-600 dark:text-eco-green-400 hover:text-eco-green-700 dark:hover:text-eco-green-300 flex items-center justify-center gap-1 group">
                        <span>Voir toutes les bornes</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            @endif
        </div>
    </div>

</x-mobile-pull-to-refresh>

{{-- Action Sheet for Quick Actions --}}
<x-mobile-action-sheet id="dashboard-actions" title="Actions rapides" subtitle="Choisissez une action">
    @can('create-charging-point')
    <button onclick="window.location='{{ route('charging-points.create') }}'; closeActionSheet('dashboard-actions');" class="action-sheet-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        <span>Nouvelle borne de recharge</span>
    </button>
    @endcan
    
    @can('view-transactions')
    <button onclick="window.location='{{ route('transactions.index') }}'; closeActionSheet('dashboard-actions');" class="action-sheet-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span>Voir les transactions</span>
    </button>
    @endcan
    
    <button onclick="window.print(); closeActionSheet('dashboard-actions');" class="action-sheet-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        <span>Imprimer le rapport</span>
    </button>
</x-mobile-action-sheet>

@endsection

@push('scripts')
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
// Fonction de refresh du dashboard
window.refreshDashboard = async function() {
    console.log('Refreshing dashboard...');
    
    try {
        // Simuler un appel API (remplacer par votre vrai appel)
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Recharger la page ou mettre à jour les données via AJAX
        window.location.reload();
        
    } catch (error) {
        console.error('Erreur lors du refresh:', error);
        alert('Erreur lors de l\'actualisation du tableau de bord');
    }
};

// Chart.js initialization
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('rechargesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                datasets: [{
                    label: 'Sessions de recharge',
                    data: [12, 19, 15, 25, 22, 30, 28],
                    borderColor: '#4acf7b',
                    backgroundColor: 'rgba(74, 207, 123, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#4acf7b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
});
</script>
@endpush

