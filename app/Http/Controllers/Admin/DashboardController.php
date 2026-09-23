<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChargingPoint;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Reservation;

class DashboardController extends Controller
{
    /**
     * Show the admin application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Récupérer les statistiques pour le dashboard
        $chargingPointsCount = ChargingPoint::count();
        $usersCount = User::count();
        $transactionsCount = Transaction::count();
        $reservationsCount = Reservation::count();

        return view('admin.dashboard', [
            'chargingPointsCount' => $chargingPointsCount,
            'usersCount' => $usersCount,
            'transactionsCount' => $transactionsCount,
            'reservationsCount' => $reservationsCount,
        ]);
    }
}