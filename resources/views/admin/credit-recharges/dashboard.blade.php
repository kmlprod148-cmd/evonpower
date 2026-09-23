@extends('layouts.app')

@section('title', 'Gestion des Crédits - Dashboard')

@push('styles')
<style>
    /* Disable overscroll/elastic effect */
    html, body {
        overscroll-behavior: none;
        overscroll-behavior-y: none;
        -webkit-overflow-scrolling: auto;
    }
    
    .cr-content { 
        padding: 1rem; 
        padding-bottom: 120px; 
        min-height: 100vh; 
        max-width: 1400px; 
        margin: 0 auto;
        overscroll-behavior: none;
    }
    
    .cr-header { 
        background: linear-gradient(135deg, #059669 0%, #047857 100%); 
        border-radius: 16px; 
        padding: 1.25rem 1.5rem; 
        margin-bottom: 1.25rem; 
        color: white;
        box-shadow: 0 4px 15px rgba(5, 150, 105, 0.25);
    }
    .cr-header-title { 
        font-size: 1.125rem; 
        font-weight: 700; 
        display: flex; 
        align-items: center; 
        gap: 0.5rem; 
    }
    .cr-header-sub { font-size: 0.75rem; opacity: 0.9; margin-top: 0.25rem; }
    .cr-header-stats { 
        display: flex; 
        gap: 0.75rem; 
        margin-top: 1rem; 
        flex-wrap: wrap; 
    }
    .cr-header-stat { 
        background: rgba(255,255,255,0.15); 
        backdrop-filter: blur(10px);
        padding: 0.625rem 1rem; 
        border-radius: 10px; 
        text-align: center;
        min-width: 70px;
        transition: transform 0.2s, background 0.2s;
    }
    .cr-header-stat:hover { 
        background: rgba(255,255,255,0.25);
        transform: translateY(-2px);
    }
    .cr-header-stat-value { font-size: 1.125rem; font-weight: 700; }
    .cr-header-stat-label { font-size: 0.625rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
    .cr-header-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .cr-header-btn {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 0.5rem 0.875rem;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.375rem;
        transition: all 0.2s;
        text-decoration: none;
        color: white;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .cr-header-btn:hover { 
        background: rgba(255,255,255,0.3); 
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .cr-grid { 
        display: grid; 
        grid-template-columns: repeat(4, 1fr); 
        gap: 1rem; 
        margin-bottom: 1.25rem; 
    }
    @media (max-width: 1024px) { .cr-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .cr-grid { grid-template-columns: 1fr; } }
    
    .cr-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 0.875rem;
        transition: all 0.2s;
    }
    .cr-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .dark .cr-card { background: #1f2937; border-color: #374151; }
    
    .cr-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .cr-card-icon svg { width: 20px; height: 20px; }
    .cr-card-content { flex: 1; min-width: 0; }
    .cr-card-label { font-size: 0.6875rem; color: #6b7280; text-transform: uppercase; font-weight: 600; letter-spacing: 0.3px; }
    .dark .cr-card-label { color: #9ca3af; }
    .cr-card-value { font-size: 1.25rem; font-weight: 700; color: #111827; margin-top: 0.125rem; }
    .dark .cr-card-value { color: #f9fafb; }
    .cr-card-sub { font-size: 0.6875rem; color: #6b7280; margin-top: 0.125rem; }
    .dark .cr-card-sub { color: #9ca3af; }
    
    .cr-charts { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
        gap: 1rem; 
        margin-bottom: 1.25rem; 
    }
    @media (max-width: 768px) { .cr-charts { grid-template-columns: 1fr; } }
    
    .cr-chart-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        border: 1px solid #e5e7eb;
    }
    .cr-chart-container {
        position: relative;
        height: 180px;
        width: 100%;
    }
    .dark .cr-chart-card { background: #1f2937; border-color: #374151; }
    .cr-chart-title { 
        font-size: 0.8125rem; 
        font-weight: 600; 
        color: #111827; 
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .dark .cr-chart-title { color: #f9fafb; }
    
    .cr-lists { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
        gap: 1rem; 
        margin-bottom: 2rem;
    }
    @media (max-width: 768px) { .cr-lists { grid-template-columns: 1fr; } }
    
    .cr-list-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        border: 1px solid #e5e7eb;
    }
    .dark .cr-list-card { background: #1f2937; border-color: #374151; }
    .cr-list-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .dark .cr-list-header { border-bottom-color: #374151; }
    .cr-list-title { font-size: 0.8125rem; font-weight: 600; color: #111827; }
    .dark .cr-list-title { color: #f9fafb; }
    .cr-list-link { 
        font-size: 0.6875rem; 
        color: #059669; 
        text-decoration: none; 
        font-weight: 500;
        transition: color 0.2s;
    }
    .cr-list-link:hover { color: #047857; }
    
    .cr-list-item {
        display: flex;
        align-items: center;
        padding: 0.625rem 0.75rem;
        background: #f9fafb;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
    }
    .cr-list-item:hover { background: #f3f4f6; }
    .dark .cr-list-item { background: #111827; }
    .dark .cr-list-item:hover { background: #1a2432; }
    .cr-list-item:last-child { margin-bottom: 0; }
    
    .cr-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        flex-shrink: 0;
        box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
    }
    .cr-list-info { flex: 1; min-width: 0; margin-left: 0.625rem; }
    .cr-list-name { 
        font-size: 0.8125rem; 
        font-weight: 500; 
        color: #111827; 
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis; 
    }
    .dark .cr-list-name { color: #f9fafb; }
    .cr-list-meta { font-size: 0.6875rem; color: #6b7280; margin-top: 0.125rem; }
    .dark .cr-list-meta { color: #9ca3af; }
    .cr-list-amount { 
        font-size: 0.8125rem; 
        font-weight: 600; 
        color: #059669;
        white-space: nowrap;
    }
    
    .cr-badge {
        font-size: 0.625rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .cr-badge-green { background: #d1fae5; color: #065f46; }
    .dark .cr-badge-green { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
    .cr-badge-yellow { background: #fef3c7; color: #92400e; }
    .dark .cr-badge-yellow { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    .cr-badge-red { background: #fee2e2; color: #991b1b; }
    .dark .cr-badge-red { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    
    .cr-empty { 
        text-align: center; 
        padding: 1.5rem; 
        color: #9ca3af; 
        font-size: 0.8125rem;
        font-style: italic;
    }
    .dark .cr-empty { color: #6b7280; }
</style>
@endpush

@section('content')
<div class="cr-content">
    
    <!-- Compact Header -->
    <div class="cr-header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="cr-header-title">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Gestion des Crédits') }}
                </h1>
                <p class="cr-header-sub">{{ __('Vue d\'ensemble des recharges et approbations') }}</p>
            </div>
            <div class="cr-header-actions">
                @if($stats['pending'] > 0)
                <a href="{{ route('admin.credit-recharges.pending') }}" class="cr-header-btn" style="background: rgba(251, 191, 36, 0.3); border-color: rgba(251, 191, 36, 0.5);">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span style="font-weight: 700;">{{ $stats['pending'] }}</span> {{ __('En Attente') }}
                </a>
                @endif
                <a href="{{ route('admin.credit-recharges.index') }}" class="cr-header-btn">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    {{ __('Voir tout') }}
                </a>
            </div>
        </div>
        <div class="cr-header-stats">
            <div class="cr-header-stat">
                <div class="cr-header-stat-value">{{ number_format($stats['total']) }}</div>
                <div class="cr-header-stat-label">{{ __('Total') }}</div>
            </div>
            <div class="cr-header-stat">
                <div class="cr-header-stat-value">{{ number_format($stats['total_amount'], 0) }}</div>
                <div class="cr-header-stat-label">MAD</div>
            </div>
            <div class="cr-header-stat">
                <div class="cr-header-stat-value">{{ $stats['completed'] }}</div>
                <div class="cr-header-stat-label">{{ __('Complétées') }}</div>
            </div>
            <div class="cr-header-stat">
                <div class="cr-header-stat-value">{{ $stats['failed'] }}</div>
                <div class="cr-header-stat-label">{{ __('Échouées') }}</div>
            </div>
        </div>
    </div>

    <!-- Stats Cards (Half Size) -->
    <div class="cr-grid">
        <div class="cr-card">
            <div class="cr-card-icon" style="background: #dbeafe; color: #2563eb;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div class="cr-card-content">
                <div class="cr-card-label">{{ __('Total Recharges') }}</div>
                <div class="cr-card-value">{{ number_format($stats['total']) }}</div>
                <div class="cr-card-sub">{{ number_format($stats['total_amount'], 2) }} MAD</div>
            </div>
        </div>
        
        <div class="cr-card">
            <div class="cr-card-icon" style="background: #fef3c7; color: #d97706;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="cr-card-content">
                <div class="cr-card-label">{{ __('En Attente') }}</div>
                <div class="cr-card-value" style="color: #d97706;">{{ $stats['pending'] }}</div>
                <div class="cr-card-sub">{{ number_format($stats['pending_amount'], 2) }} MAD</div>
            </div>
        </div>
        
        <div class="cr-card">
            <div class="cr-card-icon" style="background: #d1fae5; color: #059669;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="cr-card-content">
                <div class="cr-card-label">{{ __('Complétées') }}</div>
                <div class="cr-card-value" style="color: #059669;">{{ $stats['completed'] }}</div>
                <div class="cr-card-sub">{{ __('Succès') }}</div>
            </div>
        </div>
        
        <div class="cr-card">
            <div class="cr-card-icon" style="background: #fee2e2; color: #dc2626;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="cr-card-content">
                <div class="cr-card-label">{{ __('Échouées') }}</div>
                <div class="cr-card-value" style="color: #dc2626;">{{ $stats['failed'] }}</div>
                <div class="cr-card-sub">{{ __('Échec') }}</div>
            </div>
        </div>
    </div>

    <!-- Charts Row (Compact) -->
    <div class="cr-charts">
        <div class="cr-chart-card">
            <div class="cr-chart-title">
                <svg class="w-4 h-4" style="color: #059669;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                </svg>
                {{ __('Répartition par Méthode') }}
            </div>
            <div class="cr-chart-container">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>
        <div class="cr-chart-card">
            <div class="cr-chart-title">
                <svg class="w-4 h-4" style="color: #059669;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                {{ __('Évolution Quotidienne') }}
            </div>
            <div class="cr-chart-container">
                <canvas id="dailyEvolutionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Lists Row (Compact) -->
    <div class="cr-lists">
        <!-- Top Clients -->
        <div class="cr-list-card">
            <div class="cr-list-header">
                <span class="cr-list-title">
                    <svg class="w-4 h-4 inline-block mr-1" style="color: #059669;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{ __('Top 10 Clients') }}
                </span>
            </div>
            @forelse($topClients as $index => $client)
            <div class="cr-list-item">
                <div class="cr-avatar" style="{{ $index === 0 ? 'background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);' : '' }}">
                    {{ substr($client->user->name ?? 'U', 0, 1) }}
                </div>
                <div class="cr-list-info">
                    <div class="cr-list-name">{{ $client->user->name ?? 'N/A' }}</div>
                    <div class="cr-list-meta">{{ $client->count }} {{ __('recharges') }}</div>
                </div>
                <div class="cr-list-amount">{{ number_format($client->total_amount, 0) }} MAD</div>
            </div>
            @empty
            <div class="cr-empty">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                {{ __('Aucune donnée') }}
            </div>
            @endforelse
        </div>

        <!-- Recent Recharges -->
        <div class="cr-list-card">
            <div class="cr-list-header">
                <span class="cr-list-title">
                    <svg class="w-4 h-4 inline-block mr-1" style="color: #059669;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Dernières Recharges') }}
                </span>
                <a href="{{ route('admin.credit-recharges.index') }}" class="cr-list-link">{{ __('Voir tout') }} →</a>
            </div>
            @forelse($recentRecharges as $recharge)
            <a href="{{ route('admin.credit-recharges.show', $recharge) }}" class="cr-list-item" style="text-decoration: none;">
                <span class="cr-badge {{ $recharge->status === 'completed' ? 'cr-badge-green' : '' }}{{ $recharge->status === 'pending' ? 'cr-badge-yellow' : '' }}{{ $recharge->status === 'failed' ? 'cr-badge-red' : '' }}">
                    {{ ucfirst($recharge->status) }}
                </span>
                <div class="cr-list-info">
                    <div class="cr-list-name">{{ $recharge->user->name ?? 'N/A' }}</div>
                    <div class="cr-list-meta">{{ $recharge->created_at->diffForHumans() }}</div>
                </div>
                <div class="cr-list-amount">{{ number_format($recharge->amount, 0) }} {{ $recharge->currency }}</div>
            </a>
            @empty
            <div class="cr-empty">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                {{ __('Aucune recharge') }}
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
// Payment Methods Chart
const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
new Chart(paymentMethodsCtx, {
    type: 'doughnut',
    data: {
        labels: @json($byPaymentMethod->pluck('payment_method')),
        datasets: [{
            data: @json($byPaymentMethod->pluck('total')),
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6'],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } }
        }
    }
});

// Daily Evolution Chart - Bar chart (more stable than line)
const dailyEvolutionCtx = document.getElementById('dailyEvolutionChart').getContext('2d');
new Chart(dailyEvolutionCtx, {
    type: 'bar',
    data: {
        labels: @json($dailyEvolution->pluck('date')),
        datasets: [{
            label: 'MAD',
            data: @json($dailyEvolution->pluck('total')),
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: '#10b981',
            borderWidth: 1,
            borderRadius: 4,
            barThickness: 'flex',
            maxBarThickness: 40
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: { 
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 10,
                cornerRadius: 6
            }
        },
        scales: {
            x: { 
                ticks: { font: { size: 9 }, maxRotation: 45 },
                grid: { display: false }
            },
            y: { 
                ticks: { font: { size: 9 } },
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' }
            }
        }
    }
});
</script>
@endpush
@endsection
