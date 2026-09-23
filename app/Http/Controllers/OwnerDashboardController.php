<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $chargingPointIds = $this->visibleChargingPointIds($user);

        $stats = $this->calculateStats($chargingPointIds);

        $recentReservations = $this->reservationsQuery($chargingPointIds)
            ->with(['chargingPoint', 'user', 'pricingPlan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $pendingCount = $this->pendingReservationsQuery($chargingPointIds)->count();
        $revenueData = $this->getRevenueData($chargingPointIds);

        return view('dashboard.owner-dashboard', compact(
            'stats',
            'recentReservations',
            'pendingCount',
            'revenueData'
        ));
    }

    private function calculateStats($chargingPointIds)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $reservationQuery = $this->reservationsQuery($chargingPointIds);

        return [
            'charging_points' => ChargingPoint::whereIn('id', $chargingPointIds)->count(),
            'active_charging_points' => ChargingPoint::whereIn('id', $chargingPointIds)
                ->whereIn('status', ['active', 'online', 'charging', 'reserved'])
                ->count(),
            'active_reservations' => (clone $reservationQuery)
                ->whereIn('status', $this->successfulReservationStatuses())
                ->count(),
            'pending_reservations' => $this->pendingReservationsQuery($chargingPointIds)->count(),
            'today_reservations' => (clone $reservationQuery)
                ->whereDate('created_at', $today)
                ->count(),
            'monthly_revenue' => Transaction::whereHas('reservation', function ($query) use ($chargingPointIds) {
                $query->whereIn('charging_point_id', $chargingPointIds);
            })
                ->where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)
                ->sum('amount'),
            'today_revenue' => Transaction::whereHas('reservation', function ($query) use ($chargingPointIds) {
                $query->whereIn('charging_point_id', $chargingPointIds);
            })
                ->where('status', 'completed')
                ->whereDate('created_at', $today)
                ->sum('amount'),
        ];
    }

    private function getRevenueData($chargingPointIds)
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');

            $revenue = Transaction::whereHas('reservation', function ($query) use ($chargingPointIds) {
                $query->whereIn('charging_point_id', $chargingPointIds);
            })
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $values[] = (float) $revenue;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Get charging point performance
     */
    public function getChargingPointPerformance(Request $request, ChargingPoint $chargingPoint)
    {
        $user = Auth::user();

        if (!$this->userOwnsChargingPoint($user, $chargingPoint)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays($period);
        $reservations = Reservation::query()
            ->where('charging_point_id', $chargingPoint->id)
            ->where('created_at', '>=', $startDate);

        $performance = [
            'total_reservations' => (clone $reservations)->count(),
            'confirmed_reservations' => (clone $reservations)
                ->whereIn('status', $this->successfulReservationStatuses())
                ->count(),
            'pending_reservations' => (clone $reservations)
                ->whereIn('status', $this->pendingReservationStatuses())
                ->count(),
            'rejected_reservations' => (clone $reservations)
                ->where('status', 'rejected')
                ->count(),
            'total_revenue' => Transaction::query()
                ->where('charging_point_id', $chargingPoint->id)
                ->where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'average_reservation_value' => (clone $reservations)
                ->whereIn('status', $this->successfulReservationStatuses())
                ->avg('estimated_cost'),
            'utilization_rate' => $this->calculateUtilizationRate($chargingPoint, clone $reservations),
        ];

        return response()->json($performance);
    }

    /**
     * Get reservation analytics
     */
    public function getReservationAnalytics(Request $request)
    {
        $user = Auth::user();
        $chargingPointIds = $this->visibleChargingPointIds($user);
        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays($period);
        $reservations = Reservation::query()
            ->whereIn('charging_point_id', $chargingPointIds)
            ->where('created_at', '>=', $startDate);

        $analytics = [
            'total_reservations' => (clone $reservations)->count(),
            'status_breakdown' => [
                'pending' => (clone $reservations)->whereIn('status', $this->pendingReservationStatuses())->count(),
                'confirmed' => (clone $reservations)->where('status', ReservationStatus::CONFIRMED->value)->count(),
                'active' => (clone $reservations)->where('status', ReservationStatus::ACTIVE->value)->count(),
                'completed' => (clone $reservations)->where('status', ReservationStatus::COMPLETED->value)->count(),
                'rejected' => (clone $reservations)->where('status', 'rejected')->count(),
                'cancelled' => (clone $reservations)->whereIn('status', ['cancelled', ReservationStatus::CANCELED->value])->count(),
            ],
            'type_breakdown' => [
                'kwh' => (clone $reservations)->where('reservation_type', 'kwh')->count(),
                'minute' => (clone $reservations)->where('reservation_type', 'minute')->count(),
            ],
            'revenue_by_day' => $this->getRevenueByDay($chargingPointIds, $period),
            'top_charging_points' => $this->getTopChargingPoints($chargingPointIds, $period),
        ];

        return response()->json($analytics);
    }

    private function userOwnsChargingPoint(User $user, ChargingPoint $chargingPoint)
    {
        return ChargingPoint::query()
            ->visibleToUser($user)
            ->whereKey($chargingPoint->getKey())
            ->exists();
    }

    private function calculateUtilizationRate(ChargingPoint $chargingPoint, $reservations)
    {
        $totalHours = 24 * 7;
        $usedHours = $reservations
            ->whereIn('status', $this->successfulReservationStatuses())
            ->sum('estimated_duration') / 60;

        return $totalHours > 0 ? ($usedHours / $totalHours) * 100 : 0;
    }

    private function getRevenueByDay($chargingPointIds, $period)
    {
        $data = [];

        for ($i = $period - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = Transaction::whereHas('reservation', function ($query) use ($chargingPointIds) {
                $query->whereIn('charging_point_id', $chargingPointIds);
            })
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => (float) $revenue,
            ];
        }

        return $data;
    }

    private function getTopChargingPoints($chargingPointIds, $period)
    {
        $startDate = Carbon::now()->subDays($period);

        return ChargingPoint::whereIn('id', $chargingPointIds)
            ->withCount(['reservations' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                    ->whereIn('status', $this->successfulReservationStatuses());
            }])
            ->withSum(['reservations' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                    ->whereIn('status', $this->successfulReservationStatuses());
            }], 'estimated_cost')
            ->orderBy('reservations_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($point) {
                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'location' => $point->location,
                    'reservations_count' => $point->reservations_count,
                    'total_revenue' => $point->reservations_sum_estimated_cost ?? 0,
                ];
            });
    }

    private function visibleChargingPointIds(User $user)
    {
        return ChargingPoint::query()
            ->visibleToUser($user)
            ->pluck('id');
    }

    private function reservationsQuery($chargingPointIds)
    {
        return Reservation::query()->whereIn('charging_point_id', $chargingPointIds);
    }

    private function pendingReservationsQuery($chargingPointIds)
    {
        return $this->reservationsQuery($chargingPointIds)
            ->whereIn('status', $this->pendingReservationStatuses())
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhereNotIn(DB::raw('UPPER(payment_status)'), ['PAID', 'PAYE']);
            });
    }

    private function pendingReservationStatuses(): array
    {
        return [
            ReservationStatus::PENDING->value,
            ReservationStatus::PENDING_CONFIRMATION->value,
        ];
    }

    private function successfulReservationStatuses(): array
    {
        return [
            ReservationStatus::CONFIRMED->value,
            ReservationStatus::ACTIVE->value,
            ReservationStatus::COMPLETED->value,
        ];
    }
}
