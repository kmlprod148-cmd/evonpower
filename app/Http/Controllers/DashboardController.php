<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Services\SteveDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected SteveDashboardService $dashboardService;

    public function __construct(SteveDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        return $this->professional($request);
    }

    /**
     * Get real-time data from database and Steve API
     */
    public function realtimeData()
    {
        try {
            $summary = $this->dashboardService->getDashboardSummary();
            $activeSessions = $this->dashboardService->getActiveSessions();

            return response()->json([
                'totalRecharges' => $summary['total_sessions'] ?? 0,
                'rechargesActives' => $summary['active_sessions'] ?? 0,
                'bornesActives' => $summary['operational_stations'] ?? 0,
                'abonnementsActifs' => 0,
                'rechargesActivesList' => array_map(function (array $session): array {
                    return [
                        'name' => $session['charging_point_name'] ?? 'Station',
                        'kwh' => $session['estimated_energy'] ?? 0,
                    ];
                }, $activeSessions['sessions'] ?? []),
                'timestamp' => now()->toIso8601String(),
                'data_source' => 'authentic',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Professional dashboard with authentic data from database and Steve API.
     */
    public function professional(Request $request)
    {
        $user = auth()->user();

        if ($user) {
            $userRoles = $user->getRoleNames()->map(fn ($role) => strtolower($role))->toArray();
            $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
            $hasSystemRole = !empty(array_intersect($userRoles, $systemRoles));
            $hasClientRole = in_array('user', $userRoles, true) || in_array('client', $userRoles, true);
            $isClientOnly = !$hasSystemRole && $hasClientRole;

            if ($isClientOnly) {
                $url = route('dashboard.client');
                if ($request->has('lang')) {
                    $url .= '?lang=' . urlencode((string) $request->get('lang'));
                }

                return redirect($url);
            }
        }

        $summary = $this->dashboardService->getDashboardSummary();
        $activeSessions = $this->dashboardService->getActiveSessions();
        $energy = $this->dashboardService->getEnergyMetrics();
        $availability = $this->dashboardService->getAvailabilityStatus();
        $chargerTypes = $this->dashboardService->getChargerTypesBreakdown();
        $performance = $this->dashboardService->getPerformanceAnalytics();

        $totalRecharges = (int) ($summary['total_sessions'] ?? 0);
        $rechargesActives = (int) ($summary['active_sessions'] ?? 0);
        $bornesActives = (int) ($summary['operational_stations'] ?? 0);
        $totalStations = (int) ($summary['total_stations'] ?? 0);

        $energyValue = (float) ($energy['total_kwh'] ?? 0);
        if ($energyValue >= 1000000) {
            $energyDistributed = number_format($energyValue / 1000000, 1) . 'M';
        } elseif ($energyValue >= 1000) {
            $energyDistributed = number_format($energyValue / 1000, 1) . 'k';
        } else {
            $energyDistributed = number_format($energyValue, 0);
        }

        $networkStatus = [
            'stationsOnline' => $bornesActives,
            'totalStations' => $totalStations,
            'availabilityRate' => round((float) ($availability['available']['percentage'] ?? 0), 1),
            'totalPower' => number_format((float) ($energy['month_kwh'] ?? $energy['total_kwh'] ?? 0), 1) . ' kWh',
            'avgSessionDuration' => round((float) ($performance['avg_session_duration_minutes'] ?? 0), 1) . ' min',
        ];

        return view('dashboard.professional', [
            'totalRecharges' => $totalRecharges,
            'rechargesActives' => $rechargesActives,
            'bornesActives' => $bornesActives,
            'energyDistributed' => $energyDistributed,
            'liveSessions' => $this->buildLiveSessions($activeSessions['sessions'] ?? []),
            'topStations' => $this->buildTopStations(),
            'chartPayload' => $this->buildChartPayload($chargerTypes, $availability, $performance),
            'networkStatus' => $networkStatus,
            'showHeaderStats' => false,
            'pageTitle' => __('messages.dashboard'),
            'pageSubtitle' => null,
            'user_role' => $user?->getRoleNames()->first() ?? 'user',
            'summary' => $summary,
            'energy' => $energy,
            'availability' => $availability,
            'chargerTypes' => $chargerTypes,
            'performance' => $performance,
        ]);
    }

    /**
     * Real-time data for professional dashboard.
     */
    public function professionalRealtimeData()
    {
        try {
            $summary = $this->dashboardService->getDashboardSummary();
            $activeSessions = $this->dashboardService->getActiveSessions();

            return response()->json([
                'totalRecharges' => $summary['total_sessions'] ?? 0,
                'rechargesActives' => $summary['active_sessions'] ?? 0,
                'bornesActives' => $summary['operational_stations'] ?? 0,
                'activeSessions' => array_map(function (array $session): array {
                    return [
                        'station' => $session['charging_point_name'] ?? 'Station',
                        'status' => $session['status'] ?? 'unknown',
                        'kwh' => $session['estimated_energy'] ?? 0,
                    ];
                }, $activeSessions['sessions'] ?? []),
                'timestamp' => now()->toIso8601String(),
                'data_source' => 'authentic',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    protected function buildChartPayload(array $chargerTypes, array $availability, array $performance): array
    {
        $sessionsTrend = $this->buildSessionsTrend();
        $activeTrend = $this->buildActiveSessionsTrend();
        $energyTrend = $this->buildEnergyTrend();
        $stationPerformance = $this->buildStationPerformanceTrend();

        return [
            'sessions' => $sessionsTrend,
            'energy' => $energyTrend,
            'chargerTypes' => [
                'labels' => array_keys($chargerTypes['types'] ?? []),
                'values' => array_map(
                    static fn (array $type): int => (int) ($type['count'] ?? 0),
                    array_values($chargerTypes['types'] ?? [])
                ),
            ],
            'stationPerformance' => $stationPerformance,
            'availability' => [
                'labels' => [
                    $availability['available']['label'] ?? 'Available',
                    $availability['charging']['label'] ?? 'Charging',
                    $availability['offline']['label'] ?? 'Offline',
                    $availability['faulted']['label'] ?? 'Faulted',
                ],
                'values' => [
                    round((float) ($availability['available']['percentage'] ?? 0), 1),
                    round((float) ($availability['charging']['percentage'] ?? 0), 1),
                    round((float) ($availability['offline']['percentage'] ?? 0), 1),
                    round((float) ($availability['faulted']['percentage'] ?? 0), 1),
                ],
            ],
            'performanceRadar' => [
                'labels' => ['Availability', 'Utilization', 'Success', 'API', 'Energy', 'Capacity'],
                'values' => [
                    min(100, round((float) ($availability['available']['percentage'] ?? 0))),
                    min(100, round((float) ($availability['charging']['percentage'] ?? 0) * 2)),
                    min(100, round((float) ($performance['success_rate'] ?? 0))),
                    ($performance['api_reachable'] ?? false) ? 100 : 25,
                    min(100, $energyTrend['peak'] > 0 ? round(($energyTrend['latest'] / $energyTrend['peak']) * 100) : 0),
                    min(100, $stationPerformance['peak'] > 0 ? round(($stationPerformance['latest'] / $stationPerformance['peak']) * 100) : 0),
                ],
            ],
            'sparklines' => [
                'sessions' => $this->buildSparkline($sessionsTrend['values'] ?? []),
                'active' => $this->buildSparkline($activeTrend['values'] ?? []),
                'energy' => $this->buildSparkline($energyTrend['values'] ?? []),
                'stations' => $this->buildSparkline($stationPerformance['values'] ?? []),
            ],
        ];
    }

    protected function buildSessionsTrend(): array
    {
        $start = Carbon::today()->subDays(6)->startOfDay();

        $totals = ChargingSession::query()
            ->selectRaw('DATE(started_at) as trend_date, COUNT(*) as aggregate')
            ->where('started_at', '>=', $start)
            ->groupBy('trend_date')
            ->pluck('aggregate', 'trend_date');

        $labels = [];
        $values = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $day = Carbon::today()->subDays($offset);
            $labels[] = $day->translatedFormat('D');
            $values[] = (int) ($totals[$day->toDateString()] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'latest' => (int) end($values),
            'peak' => max($values ?: [0]),
        ];
    }

    protected function buildActiveSessionsTrend(): array
    {
        $start = Carbon::today()->subDays(6)->startOfDay();

        $totals = ChargingSession::query()
            ->selectRaw('DATE(started_at) as trend_date, COUNT(*) as aggregate')
            ->whereIn('status', [
                ChargingSession::STATUS_ACTIVE,
                ChargingSession::STATUS_IN_PROGRESS,
                ChargingSession::STATUS_INITIATING,
                ChargingSession::STATUS_COMPLETED,
            ])
            ->where('started_at', '>=', $start)
            ->groupBy('trend_date')
            ->pluck('aggregate', 'trend_date');

        $labels = [];
        $values = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $day = Carbon::today()->subDays($offset);
            $labels[] = $day->translatedFormat('D');
            $values[] = (int) ($totals[$day->toDateString()] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'latest' => (int) end($values),
            'peak' => max($values ?: [0]),
        ];
    }

    protected function buildEnergyTrend(): array
    {
        $bucketExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', started_at) AS INTEGER) / 4"
            : 'FLOOR(HOUR(started_at) / 4)';

        $rows = ChargingSession::query()
            ->selectRaw("{$bucketExpr} as bucket, COALESCE(SUM(actual_energy), 0) as aggregate")
            ->whereDate('started_at', Carbon::today())
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        $labels = [];
        $values = [];

        for ($bucket = 0; $bucket < 6; $bucket++) {
            $labels[] = sprintf('%02d:00', $bucket * 4);
            $values[] = round((float) ($rows[$bucket] ?? 0), 1);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'latest' => (float) end($values),
            'peak' => max($values ?: [0]),
        ];
    }

    protected function buildStationPerformanceTrend(): array
    {
        $windowStart = Carbon::now()->subDays(30)->startOfDay();

        $stations = ChargingPoint::query()
            ->withCount([
                'chargingSessions as sessions_count' => static function ($query) use ($windowStart) {
                    $query->where('status', ChargingSession::STATUS_COMPLETED)
                        ->where('started_at', '>=', $windowStart);
                },
            ])
            ->orderByDesc('sessions_count')
            ->limit(5)
            ->get(['id', 'name']);

        $labels = [];
        $values = [];

        foreach ($stations as $station) {
            if ((int) ($station->sessions_count ?? 0) <= 0) {
                continue;
            }

            $labels[] = $station->name ?: 'Station';
            $values[] = (int) $station->sessions_count;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'latest' => (int) ($values[0] ?? 0),
            'peak' => max($values ?: [0]),
        ];
    }

    protected function buildTopStations(): array
    {
        $windowStart = Carbon::now()->subDays(30)->startOfDay();

        return ChargingPoint::query()
            ->withCount([
                'chargingSessions as sessions_count' => static function ($query) use ($windowStart) {
                    $query->where('status', ChargingSession::STATUS_COMPLETED)
                        ->where('started_at', '>=', $windowStart);
                },
            ])
            ->withSum([
                'chargingSessions as energy_sum' => static function ($query) use ($windowStart) {
                    $query->where('status', ChargingSession::STATUS_COMPLETED)
                        ->where('started_at', '>=', $windowStart);
                },
            ], 'actual_energy')
            ->orderByDesc('sessions_count')
            ->limit(5)
            ->get(['id', 'name', 'city'])
            ->filter(static fn (ChargingPoint $station): bool => (int) ($station->sessions_count ?? 0) > 0)
            ->map(static function (ChargingPoint $station): array {
                return [
                    'name' => $station->name ?: 'Station',
                    'city' => $station->city ?: 'N/A',
                    'sessions' => (int) ($station->sessions_count ?? 0),
                    'energy' => round((float) ($station->energy_sum ?? 0), 1),
                ];
            })
            ->values()
            ->all();
    }

    protected function buildLiveSessions(array $sessions): array
    {
        return array_map(function (array $session): array {
            $status = strtolower((string) ($session['status'] ?? 'unknown'));
            [$statusLabel, $statusTone] = $this->mapStatusPresentation($status);

            return [
                'station' => $session['charging_point_name'] ?? 'Station',
                'tag' => $session['ocpp_tag'] ?: 'N/A',
                'status' => $status,
                'status_label' => $statusLabel,
                'status_tone' => $statusTone,
                'energy_kwh' => round((float) ($session['estimated_energy'] ?? 0), 1),
                'duration' => $this->formatDuration((int) ($session['duration_seconds'] ?? 0)),
            ];
        }, $sessions);
    }

    protected function buildSparkline(array $values): array
    {
        $cleaned = array_values(array_map(
            static fn ($value): float => round((float) $value, 1),
            $values
        ));

        return $cleaned === [] ? [0, 0, 0, 0] : $cleaned;
    }

    protected function mapStatusPresentation(string $status): array
    {
        return match ($status) {
            ChargingSession::STATUS_ACTIVE,
            ChargingSession::STATUS_IN_PROGRESS,
            ChargingSession::STATUS_INITIATING => ['Active', 'success'],
            ChargingSession::STATUS_PENDING => ['Pending', 'warning'],
            ChargingSession::STATUS_FAILED,
            ChargingSession::STATUS_ERROR,
            ChargingSession::STATUS_TIMEOUT => ['Attention', 'danger'],
            ChargingSession::STATUS_COMPLETED,
            ChargingSession::STATUS_STOPPED => ['Completed', 'neutral'],
            default => [ucfirst(str_replace('_', ' ', $status)), 'neutral'],
        };
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        return sprintf('%dm', max(1, $minutes));
    }
}
