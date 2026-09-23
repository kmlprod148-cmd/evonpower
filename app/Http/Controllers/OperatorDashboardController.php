<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OperatorDashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $totalRevenue = 0;

        if ($user->businessProfile) {
            $totalRevenue = $user->businessProfile->transactions()
                ->where('status', TransactionStatus::COMPLETED)
                ->sum('amount');
        }

        // Statistiques pour le dashboard opérateur
        $stats = [
            'total_charging_points' => $user->businessProfile ? $user->businessProfile->chargingPoints()->count() : 0,
            'active_charging_points' => $user->businessProfile ? $user->businessProfile->chargingPoints()->where('status', 'online')->count() : 0,
            'total_sessions' => $user->businessProfile ? $user->businessProfile->transactions()->count() : 0,
            'active_sessions' => $user->businessProfile ? $user->businessProfile->transactions()->where('status', 'active')->count() : 0,
            'total_transactions' => $user->businessProfile ? $user->businessProfile->transactions()->count() : 0,
            'transactions_this_month' => $user->businessProfile ? $user->businessProfile->transactions()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count() : 0,
            'integrator_name' => $user->businessProfile && $user->businessProfile->integrator ? $user->businessProfile->integrator->name : 'N/A',
            'created_at' => $user->businessProfile ? $user->businessProfile->created_at->format('d/m/Y') : 'N/A',
            'last_login' => $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Jamais',
            'total_revenue' => $totalRevenue,
            'today_revenue' => $user->businessProfile ? $user->businessProfile->transactions()
                ->where('status', TransactionStatus::COMPLETED)
                ->whereDate('created_at', today())
                ->sum('amount') : 0,
        ];

        // Activité récente (dernières transactions)
        $recentActivity = $user->businessProfile ? $user->businessProfile->transactions()
            ->with(['chargingPoint', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get() : collect();

        return view('operator.dashboard', compact('totalRevenue', 'stats', 'recentActivity'));
    }
}