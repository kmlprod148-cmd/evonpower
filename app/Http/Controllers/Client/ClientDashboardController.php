<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\WalletTransaction;
use App\Services\ClientBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientDashboardController extends Controller
{
    /**
     * Client Dashboard - Show personalized dashboard for EV charging clients.
     * For operators, partners and integrators, it also exposes reservations on visible charging points.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();

        $balanceService = app(ClientBalanceService::class);
        $balance = $balanceService->getBalance($user);
        $formattedBalance = $balanceService->getFormatted($user);

        $baseQuery = $this->reservationScopeForUser($user);

        $totalReservations = (clone $baseQuery)->count();
        $completedReservations = (clone $baseQuery)->where('status', 'completed')->count();
        $pendingReservations = (clone $baseQuery)
            ->whereIn('status', ['pending', 'pending_confirmation'])
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhereNotIn(DB::raw('UPPER(payment_status)'), ['PAID', 'PAYE']);
            })
            ->count();
        $cancelledReservations = (clone $baseQuery)->whereIn('status', ['cancelled', 'canceled'])->count();

        $recentReservations = (clone $baseQuery)
            ->with(['chargingPoint', 'chargingPoint.businessProfile'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $totalTransactions = Transaction::where('user_id', $user->id)->count();
        $totalEnergyKwh = Transaction::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'confirmed'])
            ->sum('energy_delivered');

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['chargingPoint', 'reservation'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $walletTransactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $totalSpent = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->sum('amount');

        $totalRecharged = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->sum('amount');

        $upcomingReservations = (clone $baseQuery)
            ->whereIn('status', ['confirmed', 'pending', 'pending_confirmation'])
            ->where('start_time', '>=', now())
            ->with(['chargingPoint', 'chargingPoint.businessProfile'])
            ->orderBy('start_time', 'asc')
            ->take(3)
            ->get();

        $stats = [
            'balance' => $formattedBalance,
            'balance_raw' => $balance,
            'total_reservations' => $totalReservations,
            'completed_reservations' => $completedReservations,
            'pending_reservations' => $pendingReservations,
            'cancelled_reservations' => $cancelledReservations,
            'total_transactions' => $totalTransactions,
            'total_energy_kwh' => round($totalEnergyKwh ?? 0, 2),
            'total_spent' => $totalSpent,
            'total_recharged' => $totalRecharged,
        ];

        return view('client.dashboard', compact(
            'stats',
            'recentReservations',
            'recentTransactions',
            'walletTransactions',
            'upcomingReservations'
        ));
    }

    /**
     * Get client statistics as JSON for AJAX calls.
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();

        $thirtyDaysAgo = now()->subDays(30);
        $reservationsQuery = $this->reservationScopeForUser($user);

        $reservationsLast30Days = (clone $reservationsQuery)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $transactionsLast30Days = Transaction::where('user_id', $user->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['completed', 'confirmed'])
            ->count();

        $energyLast30Days = Transaction::where('user_id', $user->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['completed', 'confirmed'])
            ->sum('energy_delivered');

        $spentLast30Days = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->where('type', 'debit')
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet->getFormattedBalance(),
                'reservations_last_30_days' => $reservationsLast30Days,
                'transactions_last_30_days' => $transactionsLast30Days,
                'energy_last_30_days' => round($energyLast30Days ?? 0, 2),
                'spent_last_30_days' => round($spentLast30Days ?? 0, 2),
            ],
        ]);
    }

    protected function reservationScopeForUser($user)
    {
        $userRoles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn($role) => strtolower($role))->toArray()
            : [];
        $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
        $hasSystemRole = !empty(array_intersect($userRoles, $systemRoles));

        if ($hasSystemRole) {
            return Reservation::query()->visibleToUser($user);
        }

        return Reservation::query()->where(function ($query) use ($user) {
            $query->where('user_id', $user->id);

            if (!empty(trim($user->email ?? ''))) {
                $query->orWhereRaw('LOWER(TRIM(guest_email)) = ?', [strtolower(trim($user->email))]);
            }

            if (!empty(trim($user->phone ?? ''))) {
                $query->orWhere('guest_phone', $user->phone);
            }
        });
    }
}
