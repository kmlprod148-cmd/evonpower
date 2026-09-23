@extends('layouts.app')

@section('title', __('messages.dashboard'))

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-professional.css') }}?v={{ file_exists(public_path('css/dashboard-professional.css')) ? filemtime(public_path('css/dashboard-professional.css')) : time() }}">
@endpush

@section('content')
@php
    $networkHealth = $summary['network_health'] ?? [];
    $lastUpdated = isset($summary['last_updated']) ? \Carbon\Carbon::parse($summary['last_updated']) : now();
    $totalStations = (int) ($summary['total_stations'] ?? 0);
    $availabilityRate = (float) ($availability['available']['percentage'] ?? 0);

    $availabilityItems = [
        [
            'label' => $availability['available']['label'] ?? 'Available',
            'count' => (int) ($availability['available']['count'] ?? 0),
            'percentage' => (float) ($availability['available']['percentage'] ?? 0),
            'tone' => 'success',
        ],
        [
            'label' => $availability['charging']['label'] ?? 'Charging',
            'count' => (int) ($availability['charging']['count'] ?? 0),
            'percentage' => (float) ($availability['charging']['percentage'] ?? 0),
            'tone' => 'info',
        ],
        [
            'label' => $availability['offline']['label'] ?? 'Offline',
            'count' => (int) ($availability['offline']['count'] ?? 0),
            'percentage' => (float) ($availability['offline']['percentage'] ?? 0),
            'tone' => 'muted',
        ],
        [
            'label' => $availability['faulted']['label'] ?? 'Faulted',
            'count' => (int) ($availability['faulted']['count'] ?? 0),
            'percentage' => (float) ($availability['faulted']['percentage'] ?? 0),
            'tone' => 'danger',
        ],
    ];

    $healthBadges = [
        [
            'label' => 'API',
            'value' => ($networkHealth['api_reachable'] ?? false) ? 'Online' : 'Unavailable',
            'tone' => ($networkHealth['api_reachable'] ?? false) ? 'success' : 'danger',
        ],
        [
            'label' => 'DB',
            'value' => ($networkHealth['database_connected'] ?? false) ? 'Connected' : 'Check',
            'tone' => ($networkHealth['database_connected'] ?? false) ? 'success' : 'warning',
        ],
        [
            'label' => 'Latency',
            'value' => isset($networkHealth['response_time_ms']) ? number_format((float) $networkHealth['response_time_ms'], 0) . ' ms' : 'n/a',
            'tone' => 'neutral',
        ],
    ];

    $topChargerTypes = collect($chargerTypes['types'] ?? [])
        ->map(function ($meta, $type) {
            return [
                'label' => $type,
                'count' => (int) ($meta['count'] ?? 0),
                'percentage' => (float) ($meta['percentage'] ?? 0),
            ];
        })
        ->sortByDesc('count')
        ->take(4)
        ->values();

    $weeklySessions = array_sum($chartPayload['sessions']['values'] ?? []);
@endphp

<div class="dashboard-professional">
    <section class="dp-shell">
        <div class="dp-topbar">
            <div class="dp-topbar-copy">
                <span class="dp-eyebrow">{{ __('dashboard.live') }}</span>
                <h2>{{ __('messages.realtime_overview') }}</h2>
                <p>
                    {{ __('dashboard.last_updated') }}
                    <strong>{{ $lastUpdated->translatedFormat('d M Y, H:i') }}</strong>
                    <span class="dp-topbar-separator">/</span>
                    {{ number_format($availabilityRate, 1) }}% {{ __('messages.availability') }}
                </p>
            </div>

            <div class="dp-topbar-actions">
                <div class="dp-health-badges">
                    @foreach($healthBadges as $badge)
                        <div class="dp-health-badge dp-health-badge--{{ $badge['tone'] }}">
                            <span class="dp-health-badge-label">{{ $badge['label'] }}</span>
                            <span class="dp-health-badge-value">{{ $badge['value'] }}</span>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="dp-refresh-button" onclick="window.location.reload()">
                    {{ __('messages.refresh') }}
                </button>
            </div>
        </div>

        <div class="dp-kpi-grid">
            <article class="dp-kpi-card dp-kpi-card--success">
                <div class="dp-kpi-head">
                    <div>
                        <span class="dp-kpi-label">{{ __('messages.charging_sessions') }}</span>
                        <h3 class="dp-kpi-value" data-counter="{{ $totalRecharges }}">0</h3>
                    </div>
                    <span class="dp-kpi-pill">{{ round((float) ($performance['success_rate'] ?? 0), 1) }}% {{ __('messages.success_rate') }}</span>
                </div>
                <p class="dp-kpi-meta">{{ __('messages.view_all_sessions') }}</p>
                <div class="dp-sparkline"><canvas id="sparkline1"></canvas></div>
            </article>

            <article class="dp-kpi-card dp-kpi-card--info">
                <div class="dp-kpi-head">
                    <div>
                        <span class="dp-kpi-label">{{ __('messages.active_sessions') }}</span>
                        <h3 class="dp-kpi-value" data-counter="{{ $rechargesActives }}">0</h3>
                    </div>
                    <span class="dp-kpi-pill">{{ __('messages.in_progress_now') }}</span>
                </div>
                <p class="dp-kpi-meta">{{ count($liveSessions) }} {{ __('messages.active_label') }}</p>
                <div class="dp-sparkline"><canvas id="sparkline2"></canvas></div>
            </article>

            <article class="dp-kpi-card dp-kpi-card--energy">
                <div class="dp-kpi-head">
                    <div>
                        <span class="dp-kpi-label">{{ __('messages.energy_distributed') }}</span>
                        <h3 class="dp-kpi-value dp-kpi-value--static">{{ $energyDistributed }} kWh</h3>
                    </div>
                    <span class="dp-kpi-pill">{{ number_format((float) ($energy['today_kwh'] ?? 0), 1) }} kWh {{ __('messages.today') }}</span>
                </div>
                <p class="dp-kpi-meta">{{ number_format((float) ($energy['month_kwh'] ?? 0), 1) }} kWh {{ __('messages.this_month') }}</p>
                <div class="dp-sparkline"><canvas id="sparkline3"></canvas></div>
            </article>

            <article class="dp-kpi-card dp-kpi-card--neutral">
                <div class="dp-kpi-head">
                    <div>
                        <span class="dp-kpi-label">{{ __('messages.operational_stations') }}</span>
                        <h3 class="dp-kpi-value" data-counter="{{ $bornesActives }}">0</h3>
                    </div>
                    <span class="dp-kpi-pill">{{ number_format($availabilityRate, 1) }}% {{ __('messages.availability') }}</span>
                </div>
                <p class="dp-kpi-meta">{{ $bornesActives }} / {{ $totalStations }} {{ __('messages.stations') }}</p>
                <div class="dp-sparkline"><canvas id="sparkline4"></canvas></div>
            </article>
        </div>

        <div class="dp-main-grid">
            <article class="dp-panel dp-panel--gradient dp-panel--sessions">
                <div class="dp-panel-head dp-panel-head--light">
                    <div>
                        <span class="dp-panel-eyebrow">7 days</span>
                        <h3>{{ __('messages.charging_sessions') }}</h3>
                        <p>{{ __('messages.evolution_last_7_days') }}</p>
                    </div>
                    <div class="dp-panel-value-group">
                        <strong>{{ $weeklySessions }}</strong>
                        <span>{{ __('dashboard.sessions') }}</span>
                    </div>
                </div>
                <div class="dp-chip-row">
                    <span class="dp-chip dp-chip--light">7 days</span>
                    <span class="dp-chip dp-chip--light">Live data</span>
                </div>
                <div class="dp-chart-wrap dp-chart-wrap--large">
                    <canvas id="sessionsChartPro"></canvas>
                </div>
            </article>

            <article class="dp-panel dp-panel--gradient dp-panel--energy">
                <div class="dp-panel-head dp-panel-head--light">
                    <div>
                        <span class="dp-panel-eyebrow">{{ __('messages.today') }}</span>
                        <h3>{{ __('messages.energy_distributed') }}</h3>
                        <p>{{ __('messages.kwh_per_hour') }}</p>
                    </div>
                    <div class="dp-panel-value-group">
                        <strong>{{ number_format((float) ($energy['today_kwh'] ?? 0), 1) }} kWh</strong>
                        <span>{{ __('messages.this_month') }}: {{ number_format((float) ($energy['month_kwh'] ?? 0), 1) }} kWh</span>
                    </div>
                </div>
                <div class="dp-chip-row">
                    <span class="dp-chip dp-chip--light">4h blocks</span>
                    <span class="dp-chip dp-chip--light">{{ __('dashboard.live') }}</span>
                </div>
                <div class="dp-chart-wrap dp-chart-wrap--large">
                    <canvas id="energyChartPro"></canvas>
                </div>
            </article>
        </div>

        <div class="dp-secondary-grid">
            <article class="dp-panel dp-panel--surface">
                <div class="dp-panel-head">
                    <div>
                        <span class="dp-panel-eyebrow">Mix</span>
                        <h3>{{ __('messages.charger_types') }}</h3>
                    </div>
                </div>
                <div class="dp-chart-wrap"><canvas id="chargerTypesChartPro"></canvas></div>
            </article>

            <article class="dp-panel dp-panel--surface">
                <div class="dp-panel-head">
                    <div>
                        <span class="dp-panel-eyebrow">Top 5</span>
                        <h3>{{ __('messages.performance_by_station') }}</h3>
                    </div>
                </div>
                <div class="dp-chart-wrap"><canvas id="stationPerformanceChartPro"></canvas></div>
            </article>

            <article class="dp-panel dp-panel--surface">
                <div class="dp-panel-head">
                    <div>
                        <span class="dp-panel-eyebrow">Status</span>
                        <h3>{{ __('messages.station_availability') }}</h3>
                    </div>
                </div>
                <div class="dp-chart-wrap"><canvas id="availabilityChartPro"></canvas></div>
            </article>
        </div>

        <div class="dp-activity-grid">
            <article class="dp-panel dp-panel--surface dp-panel--table">
                <div class="dp-panel-head">
                    <div>
                        <span class="dp-panel-eyebrow">Live</span>
                        <h3>{{ __('messages.realtime_sessions') }}</h3>
                    </div>
                    <span class="dp-table-badge">{{ count($liveSessions) }} {{ __('messages.active_label') }}</span>
                </div>

                @if(count($liveSessions) > 0)
                    <div class="dp-table-wrap">
                        <table class="dp-table">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.station') }}</th>
                                    <th>OCPP Tag</th>
                                    <th>{{ __('messages.status') }}</th>
                                    <th class="text-right">kWh</th>
                                    <th class="text-right">{{ __('messages.duration') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($liveSessions as $session)
                                    <tr>
                                        <td>
                                            <div class="dp-table-primary">{{ $session['station'] }}</div>
                                        </td>
                                        <td>
                                            <span class="dp-table-secondary">{{ $session['tag'] }}</span>
                                        </td>
                                        <td>
                                            <span class="dp-status-badge dp-status-badge--{{ $session['status_tone'] }}">{{ $session['status_label'] }}</span>
                                        </td>
                                        <td class="text-right">{{ number_format((float) $session['energy_kwh'], 1) }}</td>
                                        <td class="text-right">{{ $session['duration'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="dp-empty-state">
                        <p>{{ __('dashboard.no_active_charging') }}</p>
                    </div>
                @endif

                <div class="dp-panel-footer">
                    <a href="{{ route('transactions.index') }}" class="dp-link-action">{{ __('messages.view_all_sessions') }}</a>
                </div>
            </article>

            <aside class="dp-side-stack">
                <article class="dp-panel dp-panel--surface">
                    <div class="dp-panel-head">
                        <div>
                            <span class="dp-panel-eyebrow">Ops</span>
                            <h3>{{ __('messages.network_status') }}</h3>
                        </div>
                    </div>

                    <div class="dp-metric-grid">
                        <div class="dp-metric-card">
                            <span>{{ __('messages.stations_online') }}</span>
                            <strong>{{ $networkStatus['stationsOnline'] }} / {{ $networkStatus['totalStations'] }}</strong>
                        </div>
                        <div class="dp-metric-card">
                            <span>{{ __('messages.sessions_in_progress') }}</span>
                            <strong>{{ $rechargesActives }}</strong>
                        </div>
                        <div class="dp-metric-card">
                            <span>{{ __('messages.energy_distributed') }}</span>
                            <strong>{{ $networkStatus['totalPower'] }}</strong>
                        </div>
                        <div class="dp-metric-card">
                            <span>{{ __('messages.avg_session_duration') }}</span>
                            <strong>{{ $networkStatus['avgSessionDuration'] }}</strong>
                        </div>
                    </div>

                    <div class="dp-progress-stack">
                        @foreach($availabilityItems as $item)
                            <div class="dp-progress-row">
                                <div class="dp-progress-head">
                                    <span>{{ $item['label'] }}</span>
                                    <span>{{ $item['count'] }} / {{ $totalStations }}</span>
                                </div>
                                <div class="dp-progress-track">
                                    <span class="dp-progress-fill dp-progress-fill--{{ $item['tone'] }}" style="width: {{ min(100, max(0, $item['percentage'])) }}%"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="dp-panel dp-panel--surface">
                    <div class="dp-panel-head">
                        <div>
                            <span class="dp-panel-eyebrow">Top mix</span>
                            <h3>{{ __('messages.charger_types') }}</h3>
                        </div>
                    </div>

                    @if($topChargerTypes->isNotEmpty())
                        <div class="dp-list-stack">
                            @foreach($topChargerTypes as $type)
                                <div class="dp-list-item">
                                    <div>
                                        <strong>{{ $type['label'] }}</strong>
                                        <span>{{ number_format($type['percentage'], 1) }}%</span>
                                    </div>
                                    <span>{{ $type['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="dp-empty-state dp-empty-state--compact">
                            <p>{{ __('messages.no_data_available') }}</p>
                        </div>
                    @endif
                </article>
            </aside>
        </div>

        <div class="dp-footer-grid">
            <article class="dp-panel dp-panel--surface">
                <div class="dp-panel-head">
                    <div>
                        <span class="dp-panel-eyebrow">Model</span>
                        <h3>{{ __('messages.network_status') }}</h3>
                    </div>
                </div>
                <div class="dp-radar-wrap"><canvas id="performanceRadarChartPro"></canvas></div>
            </article>

            <article class="dp-panel dp-panel--surface">
                <div class="dp-panel-head">
                    <div>
                        <span class="dp-panel-eyebrow">Ranking</span>
                        <h3>{{ __('dashboard.top_stations') }}</h3>
                    </div>
                </div>

                @if(count($topStations) > 0)
                    <div class="dp-list-stack">
                        @foreach($topStations as $index => $station)
                            <div class="dp-rank-item">
                                <span class="dp-rank-index">{{ $index + 1 }}</span>
                                <div class="dp-rank-copy">
                                    <strong>{{ $station['name'] }}</strong>
                                    <span>{{ $station['city'] }}</span>
                                </div>
                                <div class="dp-rank-metrics">
                                    <strong>{{ $station['sessions'] }} {{ __('dashboard.sessions') }}</strong>
                                    <span>{{ number_format((float) $station['energy'], 1) }} kWh</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dp-empty-state dp-empty-state--compact">
                        <p>{{ __('messages.no_data_available') }}</p>
                    </div>
                @endif
            </article>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script id="dashboard-professional-data" type="application/json">@json($chartPayload)</script>
<script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
<script src="{{ asset('js/dashboard-professional.js') }}?v={{ file_exists(public_path('js/dashboard-professional.js')) ? filemtime(public_path('js/dashboard-professional.js')) : time() }}"></script>
@endpush
