<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClientStatisticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminClientStatisticsController extends Controller
{
    protected ClientStatisticsService $clientStatisticsService;

    public function __construct(ClientStatisticsService $clientStatisticsService)
    {
        $this->middleware('auth');
        $this->middleware('role:admin|super_admin');
        $this->clientStatisticsService = $clientStatisticsService;
    }

    /**
     * Affiche le dashboard de statistiques clients
     */
    public function index(Request $request)
    {
        // Filtres de date
        $dateFrom = $request->input('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));

        $filters = [
            'date_from' => Carbon::parse($dateFrom),
            'date_to' => Carbon::parse($dateTo)->endOfDay(),
        ];

        // Obtenir les statistiques
        $statistics = $this->clientStatisticsService->getGlobalClientStatistics($filters);

        return view('admin.clients.statistics', compact('statistics', 'dateFrom', 'dateTo'));
    }
}
