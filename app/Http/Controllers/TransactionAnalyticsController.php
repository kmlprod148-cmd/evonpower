<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionHierarchy;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionAnalyticsController extends Controller
{
    /**
     * Afficher le tableau de bord principal des transactions
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $period = $request->get('period', 'month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        // Calculer les dates selon la période
        $dates = $this->calculatePeriodDates($period, $dateFrom, $dateTo);
        
        // Données générales
        $generalStats = $this->getGeneralStats($dates);
        
        // Analyses hiérarchiques
        $hierarchyAnalytics = $this->getHierarchyAnalytics($dates);
        
        // Analyses par utilisateur
        $userAnalytics = $this->getUserAnalytics($dates, $user);
        
        // Analyses par business profile
        $businessProfileAnalytics = $this->getBusinessProfileAnalytics($dates);
        
        // Analyses géographiques
        $geographicAnalytics = $this->getGeographicAnalytics($dates);
        
        // Tendances temporelles
        $temporalAnalytics = $this->getTemporalAnalytics($dates);
        
        // Analyses financières
        $financialAnalytics = $this->getFinancialAnalytics($dates);
        
        return view('transactions.analytics-dashboard', compact(
            'generalStats',
            'hierarchyAnalytics', 
            'userAnalytics',
            'businessProfileAnalytics',
            'geographicAnalytics',
            'temporalAnalytics',
            'financialAnalytics',
            'period',
            'dates'
        ));
    }

    /**
     * API pour obtenir les données du tableau de bord en temps réel
     */
    public function getDashboardData(Request $request)
    {
        $period = $request->get('period', 'month');
        $dates = $this->calculatePeriodDates($period);
        
        return response()->json([
            'general_stats' => $this->getGeneralStats($dates),
            'hierarchy_analytics' => $this->getHierarchyAnalytics($dates),
            'user_analytics' => $this->getUserAnalytics($dates, auth()->user()),
            'business_profile_analytics' => $this->getBusinessProfileAnalytics($dates),
            'temporal_analytics' => $this->getTemporalAnalytics($dates),
            'financial_analytics' => $this->getFinancialAnalytics($dates),
        ]);
    }

    /**
     * Stream Server-Sent Events pour les mises à jour en temps réel
     */
    public function stream(Request $request)
    {
        $response = response()->stream(function () use ($request) {
            $period = $request->get('period', 'month');
            $dates = $this->calculatePeriodDates($period);
            
            // Envoyer les données initiales
            $data = [
                'general_stats' => $this->getGeneralStats($dates),
                'hierarchy_analytics' => $this->getHierarchyAnalytics($dates),
                'user_analytics' => $this->getUserAnalytics($dates, auth()->user()),
                'business_profile_analytics' => $this->getBusinessProfileAnalytics($dates),
                'temporal_analytics' => $this->getTemporalAnalytics($dates),
                'financial_analytics' => $this->getFinancialAnalytics($dates),
            ];
            
            echo "data: " . json_encode($data) . "\n\n";
            
            // Envoyer des mises à jour périodiques
            while (true) {
                sleep(30); // Attendre 30 secondes
                
                $updatedData = [
                    'general_stats' => $this->getGeneralStats($dates),
                    'hierarchy_analytics' => $this->getHierarchyAnalytics($dates),
                    'user_analytics' => $this->getUserAnalytics($dates, auth()->user()),
                    'business_profile_analytics' => $this->getBusinessProfileAnalytics($dates),
                    'temporal_analytics' => $this->getTemporalAnalytics($dates),
                    'financial_analytics' => $this->getFinancialAnalytics($dates),
                ];
                
                echo "data: " . json_encode($updatedData) . "\n\n";
                
                if (connection_aborted()) {
                    break;
                }
            }
        });
        
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        
        return $response;
    }

    /**
     * Obtenir les statistiques générales
     */
    private function getGeneralStats(array $dates): array
    {
        $query = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']]);
        
        return [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'total_energy_delivered' => $query->sum('energy_delivered'),
            'average_transaction_amount' => $query->avg('amount'),
            'average_energy_per_transaction' => $query->avg('energy_delivered'),
            'completed_transactions' => $query->where('status', 'completed')->count(),
            'pending_transactions' => $query->where('status', 'pending')->count(),
            'failed_transactions' => $query->where('status', 'failed')->count(),
            'total_duration' => $query->sum('duration'),
            'average_duration' => $query->avg('duration'),
        ];
    }

    /**
     * Obtenir les analyses hiérarchiques détaillées
     */
    private function getHierarchyAnalytics(array $dates): array
    {
        // Transactions hiérarchiques
        $hierarchyQuery = TransactionHierarchy::whereBetween('created_at', [$dates['from'], $dates['to']]);
        
        $hierarchyStats = [
            'total_hierarchy_transactions' => $hierarchyQuery->count(),
            'admin_integrator_transactions' => $hierarchyQuery->where('transaction_type', 'admin_integrator')->count(),
            'integrator_operator_transactions' => $hierarchyQuery->where('transaction_type', 'integrator_operator')->count(),
            'total_hierarchy_amount' => $hierarchyQuery->sum('amount'),
            'total_hierarchy_fees' => $hierarchyQuery->sum('fees_amount'),
            'completed_hierarchy_transactions' => $hierarchyQuery->where('status', 'completed')->count(),
            'pending_hierarchy_transactions' => $hierarchyQuery->where('status', 'pending')->count(),
        ];

        // Top payeurs et bénéficiaires
        $topPayers = $hierarchyQuery->selectRaw('payer_id, COUNT(*) as transaction_count, SUM(amount) as total_paid')
            ->with('payer:id,name,role')
            ->groupBy('payer_id')
            ->orderBy('total_paid', 'desc')
            ->limit(10)
            ->get();

        $topPayees = $hierarchyQuery->selectRaw('payee_id, COUNT(*) as transaction_count, SUM(amount) as total_received')
            ->with('payee:id,name,role')
            ->groupBy('payee_id')
            ->orderBy('total_received', 'desc')
            ->limit(10)
            ->get();

        // Flux entre rôles
        $roleFlows = $hierarchyQuery->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('transaction_type')
            ->get();

        return [
            'stats' => $hierarchyStats,
            'top_payers' => $topPayers,
            'top_payees' => $topPayees,
            'role_flows' => $roleFlows,
        ];
    }

    /**
     * Obtenir les analyses par utilisateur
     */
    private function getUserAnalytics(array $dates, User $user): array
    {
        $userRole = $user->role;
        
        // Statistiques par rôle
        $roleStats = [];
        $roles = ['admin', 'integrator', 'operator', 'partner'];
        
        foreach ($roles as $role) {
            $roleUsers = User::where('role', $role)->pluck('id');
            
            $roleStats[$role] = [
                'user_count' => $roleUsers->count(),
                'total_transactions' => Transaction::whereIn('user_id', $roleUsers)
                    ->whereBetween('created_at', [$dates['from'], $dates['to']])
                    ->count(),
                'total_amount' => Transaction::whereIn('user_id', $roleUsers)
                    ->whereBetween('created_at', [$dates['from'], $dates['to']])
                    ->sum('amount'),
                'average_per_user' => 0,
            ];
            
            if ($roleStats[$role]['user_count'] > 0) {
                $roleStats[$role]['average_per_user'] = $roleStats[$role]['total_amount'] / $roleStats[$role]['user_count'];
            }
        }

        // Top utilisateurs par activité
        $topActiveUsers = Transaction::selectRaw('user_id, COUNT(*) as transaction_count, SUM(amount) as total_amount, AVG(amount) as avg_amount')
            ->with('user:id,name,role,email')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('user_id')
            ->orderBy('transaction_count', 'desc')
            ->limit(20)
            ->get();

        // Utilisateurs les plus rentables
        $topRevenueUsers = Transaction::selectRaw('user_id, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->with('user:id,name,role,email')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit(20)
            ->get();

        return [
            'role_stats' => $roleStats,
            'top_active_users' => $topActiveUsers,
            'top_revenue_users' => $topRevenueUsers,
            'user_role' => $userRole,
        ];
    }

    /**
     * Obtenir les analyses par business profile
     */
    private function getBusinessProfileAnalytics(array $dates): array
    {
        $businessProfileQuery = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']])
            ->whereNotNull('business_profile_id');

        $businessProfileStats = [
            'transactions_with_business_profile' => $businessProfileQuery->count(),
            'total_amount_with_business_profile' => $businessProfileQuery->sum('amount'),
            'average_amount_with_business_profile' => $businessProfileQuery->avg('amount'),
        ];

        // Top business profiles par utilisation
        $topBusinessProfiles = $businessProfileQuery->selectRaw('business_profile_id, COUNT(*) as usage_count, SUM(amount) as total_amount')
            ->with('businessProfile:id,name,description')
            ->groupBy('business_profile_id')
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();

        // Répartition par type de business profile
        $businessProfileTypes = DB::table('transactions')
            ->join('business_profiles', 'transactions.business_profile_id', '=', 'business_profiles.id')
            ->whereBetween('transactions.created_at', [$dates['from'], $dates['to']])
            ->selectRaw('business_profiles.type, COUNT(*) as count, SUM(transactions.amount) as total_amount')
            ->groupBy('business_profiles.type')
            ->get();

        return [
            'stats' => $businessProfileStats,
            'top_business_profiles' => $topBusinessProfiles,
            'business_profile_types' => $businessProfileTypes,
        ];
    }

    /**
     * Obtenir les analyses géographiques
     */
    private function getGeographicAnalytics(array $dates): array
    {
        // Statistiques par station
        $stationStats = Transaction::selectRaw('charging_points.station_id, stations.name as station_name, stations.city, stations.country, COUNT(*) as transaction_count, SUM(transactions.amount) as total_amount')
            ->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->whereBetween('transactions.created_at', [$dates['from'], $dates['to']])
            ->groupBy('charging_points.station_id', 'stations.name', 'stations.city', 'stations.country')
            ->orderBy('transaction_count', 'desc')
            ->limit(20)
            ->get();

        // Statistiques par ville
        $cityStats = Transaction::selectRaw('stations.city, stations.country, COUNT(*) as transaction_count, SUM(transactions.amount) as total_amount')
            ->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->whereBetween('transactions.created_at', [$dates['from'], $dates['to']])
            ->groupBy('stations.city', 'stations.country')
            ->orderBy('transaction_count', 'desc')
            ->limit(15)
            ->get();

        // Statistiques par pays
        $countryStats = Transaction::selectRaw('stations.country, COUNT(*) as transaction_count, SUM(transactions.amount) as total_amount')
            ->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->whereBetween('transactions.created_at', [$dates['from'], $dates['to']])
            ->groupBy('stations.country')
            ->orderBy('transaction_count', 'desc')
            ->get();

        return [
            'station_stats' => $stationStats,
            'city_stats' => $cityStats,
            'country_stats' => $countryStats,
        ];
    }

    /**
     * Obtenir les analyses temporelles
     */
    private function getTemporalAnalytics(array $dates): array
    {
        // Tendances quotidiennes
        $dailyTrends = Transaction::selectRaw('DATE(created_at) as date, COUNT(*) as transaction_count, SUM(amount) as total_amount, SUM(energy_delivered) as total_energy')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Tendances hebdomadaires
        $weeklyTrends = Transaction::selectRaw('WEEK(created_at) as week, YEAR(created_at) as year, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('week', 'year')
            ->orderBy('year', 'week')
            ->get();

        // Tendances mensuelles
        $monthlyTrends = Transaction::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('month', 'year')
            ->orderBy('year', 'month')
            ->get();

        // Analyse par heure de la journée
        $hourlyDistribution = Transaction::selectRaw('HOUR(created_at) as hour, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Analyse par jour de la semaine
        $dayOfWeekDistribution = Transaction::selectRaw('DAYOFWEEK(created_at) as day_of_week, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        return [
            'daily_trends' => $dailyTrends,
            'weekly_trends' => $weeklyTrends,
            'monthly_trends' => $monthlyTrends,
            'hourly_distribution' => $hourlyDistribution,
            'day_of_week_distribution' => $dayOfWeekDistribution,
        ];
    }

    /**
     * Obtenir les analyses financières
     */
    private function getFinancialAnalytics(array $dates): array
    {
        $query = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']]);

        // Statistiques financières générales
        $financialStats = [
            'total_revenue' => $query->sum('amount'),
            'total_commissions' => $query->sum('admin_commission') + $query->sum('integrator_commission') + $query->sum('partner_commission'),
            'admin_commissions' => $query->sum('admin_commission'),
            'integrator_commissions' => $query->sum('integrator_commission'),
            'partner_commissions' => $query->sum('partner_commission'),
            'average_commission_rate' => 0,
            'net_revenue' => 0,
        ];

        if ($financialStats['total_revenue'] > 0) {
            $financialStats['average_commission_rate'] = ($financialStats['total_commissions'] / $financialStats['total_revenue']) * 100;
            $financialStats['net_revenue'] = $financialStats['total_revenue'] - $financialStats['total_commissions'];
        }

        // Répartition des revenus par type de transaction
        $revenueByType = $query->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount')
            ->groupBy('transaction_type')
            ->get();

        // Répartition des commissions par rôle
        $commissionByRole = [
            'admin' => $query->sum('admin_commission'),
            'integrator' => $query->sum('integrator_commission'),
            'partner' => $query->sum('partner_commission'),
        ];

        // Analyse des frais d'activation
        $activationFees = $query->where('transaction_type', 'activation_fee')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount')
            ->first();

        return [
            'stats' => $financialStats,
            'revenue_by_type' => $revenueByType,
            'commission_by_role' => $commissionByRole,
            'activation_fees' => $activationFees,
        ];
    }

    /**
     * Calculer les dates selon la période
     */
    private function calculatePeriodDates(string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if ($dateFrom && $dateTo) {
            return [
                'from' => Carbon::parse($dateFrom)->startOfDay(),
                'to' => Carbon::parse($dateTo)->endOfDay()
            ];
        }

        switch ($period) {
            case 'today':
                return [
                    'from' => now()->startOfDay(),
                    'to' => now()->endOfDay()
                ];
            case 'week':
                return [
                    'from' => now()->startOfWeek(),
                    'to' => now()->endOfWeek()
                ];
            case 'month':
                return [
                    'from' => now()->startOfMonth(),
                    'to' => now()->endOfMonth()
                ];
            case 'quarter':
                return [
                    'from' => now()->startOfQuarter(),
                    'to' => now()->endOfQuarter()
                ];
            case 'year':
                return [
                    'from' => now()->startOfYear(),
                    'to' => now()->endOfYear()
                ];
            case 'all':
            default:
                return [
                    'from' => Carbon::create(2020, 1, 1),
                    'to' => Carbon::create(2030, 12, 31)
                ];
        }
    }

    /**
     * Exporter les données du tableau de bord
     */
    public function export(Request $request)
    {
        $period = $request->get('period', 'month');
        $format = $request->get('format', 'csv');
        $dates = $this->calculatePeriodDates($period);
        
        // Générer les données d'export
        $exportData = [
            'general_stats' => $this->getGeneralStats($dates),
            'hierarchy_analytics' => $this->getHierarchyAnalytics($dates),
            'user_analytics' => $this->getUserAnalytics($dates, auth()->user()),
            'business_profile_analytics' => $this->getBusinessProfileAnalytics($dates),
            'geographic_analytics' => $this->getGeographicAnalytics($dates),
            'temporal_analytics' => $this->getTemporalAnalytics($dates),
            'financial_analytics' => $this->getFinancialAnalytics($dates),
        ];

        if ($format === 'json') {
            return response()->json($exportData);
        }

        // Pour CSV, on peut créer un fichier CSV avec les données principales
        $csvData = $this->generateCsvData($exportData);
        
        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="transaction_analytics_' . $period . '.csv"');
    }

    /**
     * Générer les données CSV
     */
    private function generateCsvData(array $data): string
    {
        $csv = "Type,Statistique,Valeur\n";
        
        // Statistiques générales
        foreach ($data['general_stats'] as $key => $value) {
            $csv .= "Général," . ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }
        
        // Statistiques hiérarchiques
        foreach ($data['hierarchy_analytics']['stats'] as $key => $value) {
            $csv .= "Hiérarchie," . ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }
        
        // Statistiques financières
        foreach ($data['financial_analytics']['stats'] as $key => $value) {
            $csv .= "Financier," . ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }
        
        return $csv;
    }
}
