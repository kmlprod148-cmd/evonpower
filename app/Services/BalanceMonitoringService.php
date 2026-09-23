<?php

namespace App\Services;

use App\Models\User;
use App\Models\TransactionDetail;
use App\Services\BalanceValidationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service de monitoring des balances
 * 
 * Surveille la santé du système de balances et génère des alertes
 */
class BalanceMonitoringService
{
    protected BalanceValidationService $validationService;
    protected BalanceSynchronizationService $syncService;

    public function __construct(
        BalanceValidationService $validationService,
        BalanceSynchronizationService $syncService
    ) {
        $this->validationService = $validationService;
        $this->syncService = $syncService;
    }

    /**
     * Génère un rapport de santé des balances
     * 
     * @return array
     */
    public function generateHealthReport(): array
    {
        $cacheKey = 'balance_health_report';
        $cacheDuration = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheDuration, function () {
            $report = [
                'timestamp' => now()->toIso8601String(),
                'summary' => [],
                'statistics' => [],
                'alerts' => [],
            ];

            // Statistiques générales
            $report['statistics'] = $this->getStatistics();

            // Validation des balances
            $validationReport = $this->validationService->validateAllBalances();
            $report['summary']['total_users'] = $validationReport['total_users'];
            $report['summary']['valid_balances'] = $validationReport['valid_count'];
            $report['summary']['invalid_balances'] = $validationReport['invalid_count'];
            $report['summary']['validity_rate'] = $validationReport['total_users'] > 0 
                ? round(($validationReport['valid_count'] / $validationReport['total_users']) * 100, 2)
                : 100;

            // Générer des alertes
            if ($validationReport['invalid_count'] > 0) {
                $report['alerts'][] = [
                    'level' => 'warning',
                    'message' => "{$validationReport['invalid_count']} utilisateur(s) avec des balances incohérentes",
                    'action' => 'Exécuter: php artisan balances:sync --fix',
                ];
            }

            // Vérifier les TransactionDetail manquants
            $missingDetails = $this->countMissingTransactionDetails();
            if ($missingDetails > 0) {
                $report['alerts'][] = [
                    'level' => 'warning',
                    'message' => "{$missingDetails} transaction(s) sans TransactionDetail",
                    'action' => 'Exécuter: php artisan transactions:fix-details --all',
                ];
            }

            // Vérifier les transactions récentes
            $recentIssues = $this->checkRecentTransactions();
            if (!empty($recentIssues)) {
                $report['alerts'] = array_merge($report['alerts'], $recentIssues);
            }

            return $report;
        });
    }

    /**
     * Récupère les statistiques générales
     */
    protected function getStatistics(): array
    {
        $totalUsers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
        })->count();

        $totalTransactionDetails = TransactionDetail::count();
        
        $totalAdminBalance = TransactionDetail::sum('admin_share_amount');
        $totalIntegratorBalance = TransactionDetail::sum('integrator_share_amount');
        $totalOperatorBalance = TransactionDetail::sum('operator_share_amount');

        $recentTransactionDetails = TransactionDetail::where('created_at', '>=', now()->subDays(7))->count();

        return [
            'total_users' => $totalUsers,
            'total_transaction_details' => $totalTransactionDetails,
            'total_admin_balance' => round($totalAdminBalance, 2),
            'total_integrator_balance' => round($totalIntegratorBalance, 2),
            'total_operator_balance' => round($totalOperatorBalance, 2),
            'recent_transaction_details_7d' => $recentTransactionDetails,
        ];
    }

    /**
     * Compte les TransactionDetail manquants
     */
    protected function countMissingTransactionDetails(): int
    {
        return DB::table('transactions')
            ->whereIn('status', ['completed', 'confirmed'])
            ->whereDoesntExist(function($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_details')
                    ->whereColumn('transaction_details.transaction_id', 'transactions.id');
            })
            ->count();
    }

    /**
     * Vérifie les transactions récentes pour des problèmes
     */
    protected function checkRecentTransactions(): array
    {
        $alerts = [];

        // Vérifier les transactions avec des parts à 0
        $zeroShareCount = TransactionDetail::where('created_at', '>=', now()->subDays(1))
            ->where(function($q) {
                $q->where('admin_share_amount', 0)
                  ->orWhere('integrator_share_amount', 0)
                  ->orWhere('operator_share_amount', 0);
            })
            ->count();

        if ($zeroShareCount > 0) {
            $alerts[] = [
                'level' => 'info',
                'message' => "{$zeroShareCount} TransactionDetail récent(s) avec des parts à 0",
                'action' => 'Vérifier les Business Profiles et la configuration des frais',
            ];
        }

        return $alerts;
    }

    /**
     * Nettoie le cache du rapport de santé
     */
    public function clearHealthReportCache(): void
    {
        Cache::forget('balance_health_report');
    }
}

