<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionHierarchy;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Station;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionAnalyticsService
{
    /**
     * Générer un rapport complet des interactions entre utilisateurs
     */
    public function generateUserInteractionReport(array $dates): array
    {
        return [
            'hierarchy_flows' => $this->analyzeHierarchyFlows($dates),
            'user_relationships' => $this->analyzeUserRelationships($dates),
            'commission_distribution' => $this->analyzeCommissionDistribution($dates),
            'business_profile_impact' => $this->analyzeBusinessProfileImpact($dates),
            'geographic_patterns' => $this->analyzeGeographicPatterns($dates),
            'temporal_patterns' => $this->analyzeTemporalPatterns($dates),
        ];
    }

    /**
     * Analyser les flux hiérarchiques détaillés
     */
    private function analyzeHierarchyFlows(array $dates): array
    {
        $flows = [];
        
        // Flux Admin → Intégrateur
        $adminIntegratorFlows = TransactionHierarchy::where('transaction_type', 'admin_integrator')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->with(['payer:id,name,role', 'payee:id,name,role'])
            ->get()
            ->groupBy('payer_id');

        foreach ($adminIntegratorFlows as $payerId => $transactions) {
            $payer = $transactions->first()->payer;
            $totalPaid = $transactions->sum('amount');
            $transactionCount = $transactions->count();
            
            $flows[] = [
                'type' => 'admin_integrator',
                'payer' => $payer,
                'total_paid' => $totalPaid,
                'transaction_count' => $transactionCount,
                'average_amount' => $totalPaid / $transactionCount,
                'recipients' => $transactions->groupBy('payee_id')->map(function ($group) {
                    $payee = $group->first()->payee;
                    return [
                        'payee' => $payee,
                        'amount' => $group->sum('amount'),
                        'count' => $group->count(),
                    ];
                }),
            ];
        }

        // Flux Intégrateur → Opérateur
        $integratorOperatorFlows = TransactionHierarchy::where('transaction_type', 'integrator_operator')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->with(['payer:id,name,role', 'payee:id,name,role'])
            ->get()
            ->groupBy('payer_id');

        foreach ($integratorOperatorFlows as $payerId => $transactions) {
            $payer = $transactions->first()->payer;
            $totalPaid = $transactions->sum('amount');
            $transactionCount = $transactions->count();
            
            $flows[] = [
                'type' => 'integrator_operator',
                'payer' => $payer,
                'total_paid' => $totalPaid,
                'transaction_count' => $transactionCount,
                'average_amount' => $totalPaid / $transactionCount,
                'recipients' => $transactions->groupBy('payee_id')->map(function ($group) {
                    $payee = $group->first()->payee;
                    return [
                        'payee' => $payee,
                        'amount' => $group->sum('amount'),
                        'count' => $group->count(),
                    ];
                }),
            ];
        }

        return $flows;
    }

    /**
     * Analyser les relations entre utilisateurs
     */
    private function analyzeUserRelationships(array $dates): array
    {
        // Relations basées sur les transactions hiérarchiques
        $relationships = [];
        
        $hierarchyTransactions = TransactionHierarchy::whereBetween('created_at', [$dates['from'], $dates['to']])
            ->with(['payer:id,name,role', 'payee:id,name,role'])
            ->get();

        foreach ($hierarchyTransactions as $transaction) {
            $payerId = $transaction->payer_id;
            $payeeId = $transaction->payee_id;
            
            if (!isset($relationships[$payerId])) {
                $relationships[$payerId] = [
                    'user' => $transaction->payer,
                    'relationships' => [],
                ];
            }
            
            if (!isset($relationships[$payerId]['relationships'][$payeeId])) {
                $relationships[$payerId]['relationships'][$payeeId] = [
                    'partner' => $transaction->payee,
                    'total_amount' => 0,
                    'transaction_count' => 0,
                    'transaction_types' => [],
                ];
            }
            
            $relationships[$payerId]['relationships'][$payeeId]['total_amount'] += $transaction->amount;
            $relationships[$payerId]['relationships'][$payeeId]['transaction_count']++;
            $relationships[$payerId]['relationships'][$payeeId]['transaction_types'][] = $transaction->transaction_type;
        }

        return $relationships;
    }

    /**
     * Analyser la distribution des commissions
     */
    private function analyzeCommissionDistribution(array $dates): array
    {
        $query = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']]);
        
        $totalCommissions = $query->sum('admin_commission') + 
                           $query->sum('integrator_commission') + 
                           $query->sum('partner_commission');
        
        $adminCommissions = $query->sum('admin_commission');
        $integratorCommissions = $query->sum('integrator_commission');
        $partnerCommissions = $query->sum('partner_commission');
        
        return [
            'total_commissions' => $totalCommissions,
            'admin_share' => [
                'amount' => $adminCommissions,
                'percentage' => $totalCommissions > 0 ? ($adminCommissions / $totalCommissions) * 100 : 0,
            ],
            'integrator_share' => [
                'amount' => $integratorCommissions,
                'percentage' => $totalCommissions > 0 ? ($integratorCommissions / $totalCommissions) * 100 : 0,
            ],
            'partner_share' => [
                'amount' => $partnerCommissions,
                'percentage' => $totalCommissions > 0 ? ($partnerCommissions / $totalCommissions) * 100 : 0,
            ],
            'commission_by_transaction_type' => $this->getCommissionByTransactionType($dates),
        ];
    }

    /**
     * Obtenir les commissions par type de transaction
     */
    private function getCommissionByTransactionType(array $dates): array
    {
        return Transaction::whereBetween('created_at', [$dates['from'], $dates['to']])
            ->selectRaw('transaction_type, 
                        SUM(admin_commission) as admin_commission,
                        SUM(integrator_commission) as integrator_commission,
                        SUM(partner_commission) as partner_commission,
                        COUNT(*) as transaction_count')
            ->groupBy('transaction_type')
            ->get()
            ->map(function ($item) {
                $totalCommission = $item->admin_commission + $item->integrator_commission + $item->partner_commission;
                return [
                    'transaction_type' => $item->transaction_type,
                    'transaction_count' => $item->transaction_count,
                    'admin_commission' => $item->admin_commission,
                    'integrator_commission' => $item->integrator_commission,
                    'partner_commission' => $item->partner_commission,
                    'total_commission' => $totalCommission,
                    'average_commission_per_transaction' => $item->transaction_count > 0 ? $totalCommission / $item->transaction_count : 0,
                ];
            });
    }

    /**
     * Analyser l'impact des business profiles
     */
    private function analyzeBusinessProfileImpact(array $dates): array
    {
        $query = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']])
            ->whereNotNull('business_profile_id')
            ->with('businessProfile:id,name,type,description');

        $businessProfileStats = $query->selectRaw('business_profile_id, 
                                                  COUNT(*) as usage_count,
                                                  SUM(amount) as total_amount,
                                                  AVG(amount) as average_amount,
                                                  SUM(admin_commission) as total_admin_commission,
                                                  SUM(integrator_commission) as total_integrator_commission,
                                                  SUM(partner_commission) as total_partner_commission')
            ->groupBy('business_profile_id')
            ->get();

        $businessProfileTypes = $query->join('business_profiles', 'transactions.business_profile_id', '=', 'business_profiles.id')
            ->selectRaw('business_profiles.type,
                       COUNT(*) as usage_count,
                       SUM(transactions.amount) as total_amount,
                       AVG(transactions.amount) as average_amount')
            ->groupBy('business_profiles.type')
            ->get();

        return [
            'business_profile_stats' => $businessProfileStats,
            'business_profile_types' => $businessProfileTypes,
            'total_transactions_with_bp' => $query->count(),
            'total_amount_with_bp' => $query->sum('amount'),
            'average_amount_with_bp' => $query->avg('amount'),
        ];
    }

    /**
     * Analyser les patterns géographiques
     */
    private function analyzeGeographicPatterns(array $dates): array
    {
        // Patterns par station
        $stationPatterns = Transaction::selectRaw('charging_points.station_id,
                                                stations.name as station_name,
                                                stations.city,
                                                stations.country,
                                                stations.latitude,
                                                stations.longitude,
                                                COUNT(*) as transaction_count,
                                                SUM(transactions.amount) as total_amount,
                                                AVG(transactions.amount) as average_amount,
                                                SUM(transactions.energy_delivered) as total_energy')
            ->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->whereBetween('transactions.created_at', [$dates['from'], $dates['to']])
            ->groupBy('charging_points.station_id', 'stations.name', 'stations.city', 'stations.country', 'stations.latitude', 'stations.longitude')
            ->orderBy('transaction_count', 'desc')
            ->get();

        // Patterns par ville
        $cityPatterns = Transaction::selectRaw('stations.city,
                                             stations.country,
                                             COUNT(*) as transaction_count,
                                             SUM(transactions.amount) as total_amount,
                                             AVG(transactions.amount) as average_amount,
                                             COUNT(DISTINCT charging_points.station_id) as station_count')
            ->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->whereBetween('transactions.created_at', [$dates['from'], $dates['to']])
            ->groupBy('stations.city', 'stations.country')
            ->orderBy('transaction_count', 'desc')
            ->get();

        // Patterns par pays
        $countryPatterns = Transaction::selectRaw('stations.country,
                                                 COUNT(*) as transaction_count,
                                                 SUM(transactions.amount) as total_amount,
                                                 AVG(transactions.amount) as average_amount,
                                                 COUNT(DISTINCT stations.city) as city_count,
                                                 COUNT(DISTINCT charging_points.station_id) as station_count')
            ->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->whereBetween('transactions.created_at', [$dates['from'], $dates['to']])
            ->groupBy('stations.country')
            ->orderBy('transaction_count', 'desc')
            ->get();

        return [
            'station_patterns' => $stationPatterns,
            'city_patterns' => $cityPatterns,
            'country_patterns' => $countryPatterns,
        ];
    }

    /**
     * Analyser les patterns temporels
     */
    private function analyzeTemporalPatterns(array $dates): array
    {
        // Patterns par heure
        $hourlyPatterns = Transaction::selectRaw('HOUR(created_at) as hour,
                                               COUNT(*) as transaction_count,
                                               SUM(amount) as total_amount,
                                               AVG(amount) as average_amount,
                                               SUM(energy_delivered) as total_energy')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Patterns par jour de la semaine
        $dayOfWeekPatterns = Transaction::selectRaw('DAYOFWEEK(created_at) as day_of_week,
                                                   COUNT(*) as transaction_count,
                                                   SUM(amount) as total_amount,
                                                   AVG(amount) as average_amount')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        // Patterns par mois
        $monthlyPatterns = Transaction::selectRaw('MONTH(created_at) as month,
                                                 YEAR(created_at) as year,
                                                 COUNT(*) as transaction_count,
                                                 SUM(amount) as total_amount,
                                                 AVG(amount) as average_amount')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('month', 'year')
            ->orderBy('year', 'month')
            ->get();

        // Patterns par saison
        $seasonalPatterns = Transaction::selectRaw('CASE 
                                                   WHEN MONTH(created_at) IN (12, 1, 2) THEN "Hiver"
                                                   WHEN MONTH(created_at) IN (3, 4, 5) THEN "Printemps"
                                                   WHEN MONTH(created_at) IN (6, 7, 8) THEN "Été"
                                                   WHEN MONTH(created_at) IN (9, 10, 11) THEN "Automne"
                                                   END as season,
                                                   COUNT(*) as transaction_count,
                                                   SUM(amount) as total_amount,
                                                   AVG(amount) as average_amount')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('season')
            ->get();

        return [
            'hourly_patterns' => $hourlyPatterns,
            'day_of_week_patterns' => $dayOfWeekPatterns,
            'monthly_patterns' => $monthlyPatterns,
            'seasonal_patterns' => $seasonalPatterns,
        ];
    }

    /**
     * Générer des insights avancés
     */
    public function generateAdvancedInsights(array $dates): array
    {
        $insights = [];
        
        // Insight 1: Utilisateurs les plus actifs
        $mostActiveUsers = Transaction::selectRaw('user_id, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->with('user:id,name,role')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('user_id')
            ->orderBy('transaction_count', 'desc')
            ->limit(5)
            ->get();

        $insights['most_active_users'] = $mostActiveUsers;

        // Insight 2: Stations les plus rentables
        $mostProfitableStations = Transaction::selectRaw('charging_points.station_id, stations.name, SUM(transactions.amount) as total_revenue')
            ->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->whereBetween('transactions.created_at', [$dates['from'], $dates['to']])
            ->groupBy('charging_points.station_id', 'stations.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();

        $insights['most_profitable_stations'] = $mostProfitableStations;

        // Insight 3: Patterns de croissance
        $growthPatterns = $this->analyzeGrowthPatterns($dates);
        $insights['growth_patterns'] = $growthPatterns;

        // Insight 4: Anomalies détectées
        $anomalies = $this->detectAnomalies($dates);
        $insights['anomalies'] = $anomalies;

        return $insights;
    }

    /**
     * Analyser les patterns de croissance
     */
    private function analyzeGrowthPatterns(array $dates): array
    {
        $dailyGrowth = Transaction::selectRaw('DATE(created_at) as date, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $growthRate = 0;
        if ($dailyGrowth->count() > 1) {
            $firstDay = $dailyGrowth->first();
            $lastDay = $dailyGrowth->last();
            $growthRate = (($lastDay->transaction_count - $firstDay->transaction_count) / $firstDay->transaction_count) * 100;
        }

        return [
            'daily_growth' => $dailyGrowth,
            'overall_growth_rate' => $growthRate,
            'trend_direction' => $growthRate > 0 ? 'croissance' : ($growthRate < 0 ? 'décroissance' : 'stable'),
        ];
    }

    /**
     * Détecter les anomalies
     */
    private function detectAnomalies(array $dates): array
    {
        $anomalies = [];

        // Anomalie 1: Transactions avec des montants anormalement élevés
        $avgAmount = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']])->avg('amount');
        $stdDev = DB::select("SELECT STDDEV(amount) as std_dev FROM transactions WHERE created_at BETWEEN ? AND ?", 
                            [$dates['from'], $dates['to']])[0]->std_dev ?? 0;
        
        $threshold = $avgAmount + (3 * $stdDev);
        
        $highAmountTransactions = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']])
            ->where('amount', '>', $threshold)
            ->with(['user:id,name', 'chargingPoint.station:id,name'])
            ->get();

        if ($highAmountTransactions->count() > 0) {
            $anomalies['high_amount_transactions'] = [
                'count' => $highAmountTransactions->count(),
                'transactions' => $highAmountTransactions,
                'threshold' => $threshold,
                'description' => 'Transactions avec des montants anormalement élevés',
            ];
        }

        // Anomalie 2: Utilisateurs avec une activité anormalement élevée
        $avgTransactionsPerUser = Transaction::whereBetween('created_at', [$dates['from'], $dates['to']])
            ->selectRaw('user_id, COUNT(*) as transaction_count')
            ->groupBy('user_id')
            ->avg('transaction_count');

        $highActivityThreshold = $avgTransactionsPerUser * 3;
        
        $highActivityUsers = Transaction::selectRaw('user_id, COUNT(*) as transaction_count')
            ->with('user:id,name,role')
            ->whereBetween('created_at', [$dates['from'], $dates['to']])
            ->groupBy('user_id')
            ->having('transaction_count', '>', $highActivityThreshold)
            ->get();

        if ($highActivityUsers->count() > 0) {
            $anomalies['high_activity_users'] = [
                'count' => $highActivityUsers->count(),
                'users' => $highActivityUsers,
                'threshold' => $highActivityThreshold,
                'description' => 'Utilisateurs avec une activité anormalement élevée',
            ];
        }

        return $anomalies;
    }

    /**
     * Générer un rapport de performance des utilisateurs
     */
    public function generateUserPerformanceReport(array $dates): array
    {
        $users = User::whereIn('role', ['admin', 'integrator', 'operator', 'partner'])->get();
        $performanceReport = [];

        foreach ($users as $user) {
            $userTransactions = Transaction::where('user_id', $user->id)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->get();

            $performanceReport[] = [
                'user' => $user,
                'total_transactions' => $userTransactions->count(),
                'total_amount' => $userTransactions->sum('amount'),
                'average_amount' => $userTransactions->avg('amount'),
                'total_energy' => $userTransactions->sum('energy_delivered'),
                'success_rate' => $userTransactions->count() > 0 ? 
                    ($userTransactions->where('status', 'completed')->count() / $userTransactions->count()) * 100 : 0,
                'commission_earned' => $this->calculateUserCommission($user, $userTransactions),
                'performance_score' => $this->calculatePerformanceScore($user, $userTransactions),
            ];
        }

        return collect($performanceReport)->sortByDesc('performance_score')->values()->all();
    }

    /**
     * Calculer la commission d'un utilisateur
     */
    private function calculateUserCommission(User $user, $transactions): float
    {
        switch ($user->role) {
            case 'admin':
                return $transactions->sum('admin_commission');
            case 'integrator':
                return $transactions->sum('integrator_commission');
            case 'operator':
            case 'partner':
                return $transactions->sum('partner_commission');
            default:
                return 0;
        }
    }

    /**
     * Calculer le score de performance d'un utilisateur
     */
    private function calculatePerformanceScore(User $user, $transactions): float
    {
        $transactionCount = $transactions->count();
        $totalAmount = $transactions->sum('amount');
        $successRate = $transactionCount > 0 ? 
            ($transactions->where('status', 'completed')->count() / $transactionCount) * 100 : 0;
        
        $commissionEarned = $this->calculateUserCommission($user, $transactions);
        
        // Score basé sur: nombre de transactions (40%), montant total (30%), taux de réussite (20%), commissions (10%)
        $score = ($transactionCount * 0.4) + 
                 ($totalAmount * 0.3) + 
                 ($successRate * 0.2) + 
                 ($commissionEarned * 0.1);
        
        return round($score, 2);
    }
}
