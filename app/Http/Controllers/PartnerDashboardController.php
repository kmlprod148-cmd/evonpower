<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Auth;

class PartnerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $partner = auth()->user()->partner;
        if (!$partner) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Calculer les statistiques
        $stats = [
            'total_charging_points' => ChargingPoint::where('user_id', auth()->id())->count(),
            'active_charging_points' => ChargingPoint::where('user_id', auth()->id())->where('status', 'active')->count(),
            'total_transactions' => Transaction::whereHas('chargingPoint', function($query) {
                $query->where('user_id', auth()->id());
            })->where('status', 'completed')->count(),
            'total_revenue' => Transaction::whereHas('chargingPoint', function($query) {
                $query->where('user_id', auth()->id());
            })->where('status', 'completed')->sum('amount')
        ];

        // Récupérer les bornes récentes
        $recentChargingPoints = ChargingPoint::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.partner', compact('stats', 'recentChargingPoints'));
    }
}
