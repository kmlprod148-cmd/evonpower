<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HierarchicalBalanceService;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Commande pour recalculer les balances hiérarchiques
 * 
 * Cette commande gère :
 * - Recalcul automatique des balances Admin → Intégrateur → Opérateur
 * - Nettoyage du cache
 * - Génération de rapports de performance
 * - Notifications d'erreurs
 */
class RecalculateHierarchicalBalances extends Command
{
    protected $signature = 'balances:recalculate 
                            {--user= : ID utilisateur spécifique à recalculer}
                            {--role= : Rôle spécifique à recalculer (admin, integrator, operator)}
                            {--force : Forcer le recalcul même si le cache est récent}
                            {--clear-cache : Nettoyer le cache avant le recalcul}
                            {--report : Générer un rapport détaillé}';

    protected $description = 'Recalcule les balances hiérarchiques pour tous les utilisateurs ou un utilisateur spécifique';

    protected HierarchicalBalanceService $balanceService;

    public function __construct(HierarchicalBalanceService $balanceService)
    {
        parent::__construct();
        $this->balanceService = $balanceService;
    }

    public function handle()
    {
        $this->info('🔄 Début du recalcul des balances hiérarchiques');
        
        $userId = $this->option('user');
        $role = $this->option('role');
        $force = $this->option('force');
        $clearCache = $this->option('clear-cache');
        $report = $this->option('report');

        $startTime = microtime(true);

        try {
            // Nettoyer le cache si demandé
            if ($clearCache) {
                $this->info('🧹 Nettoyage du cache...');
                $this->balanceService->clearBalanceCache();
                $this->info('✅ Cache nettoyé');
            }

            $results = [];

            if ($userId) {
                // Recalcul pour un utilisateur spécifique
                $results = $this->recalculateSingleUser($userId, $force);
            } elseif ($role) {
                // Recalcul pour un rôle spécifique
                $results = $this->recalculateByRole($role, $force);
            } else {
                // Recalcul pour tous les utilisateurs
                $results = $this->recalculateAllUsers($force);
            }

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            // Afficher les résultats
            $this->displayResults($results, $executionTime);

            // Générer un rapport si demandé
            if ($report) {
                $this->generateReport($results, $executionTime);
            }

            // Log des résultats
            Log::info('Recalcul des balances terminé', [
                'execution_time' => $executionTime,
                'processed_users' => $results['processed_users'] ?? 0,
                'errors' => count($results['errors'] ?? []),
                'success' => $results['success'] ?? false
            ]);

            return $results['success'] ? 0 : 1;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du recalcul des balances: ' . $e->getMessage());
            Log::error('Erreur lors du recalcul des balances', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Recalcule les balances pour un utilisateur spécifique
     * 
     * @param string $userId
     * @param bool $force
     * @return array
     */
    private function recalculateSingleUser(string $userId, bool $force): array
    {
        $this->info("👤 Recalcul pour l'utilisateur ID: {$userId}");

        try {
            $user = User::findOrFail($userId);
            $balance = $this->balanceService->calculateUserBalance($user, $force);

            $this->info("✅ Balance recalculée pour {$user->name}");
            $this->line("💰 Balance actuelle: {$balance['current_balance']} EUR");
            $this->line("📊 Total des gains: {$balance['total_earnings']} EUR");

            return [
                'success' => true,
                'processed_users' => 1,
                'user_balances' => [$balance],
                'errors' => []
            ];

        } catch (\Exception $e) {
            $this->error("❌ Erreur pour l'utilisateur {$userId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'processed_users' => 0,
                'errors' => [
                    [
                        'user_id' => $userId,
                        'error' => $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Recalcule les balances pour un rôle spécifique
     * 
     * @param string $role
     * @param bool $force
     * @return array
     */
    private function recalculateByRole(string $role, bool $force): array
    {
        $this->info("👥 Recalcul pour le rôle: {$role}");

        $users = User::role($role)->get();
        $results = [
            'success' => true,
            'processed_users' => 0,
            'user_balances' => [],
            'errors' => []
        ];

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            try {
                $balance = $this->balanceService->calculateUserBalance($user, $force);
                $results['user_balances'][] = $balance;
                $results['processed_users']++;
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'error' => $e->getMessage()
                ];
                $results['success'] = false;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("✅ Recalcul terminé pour {$results['processed_users']} utilisateurs du rôle {$role}");
        
        if (!empty($results['errors'])) {
            $this->warn("⚠️  {$results['errors']} erreurs rencontrées");
        }

        return $results;
    }

    /**
     * Recalcule les balances pour tous les utilisateurs
     * 
     * @param bool $force
     * @return array
     */
    private function recalculateAllUsers(bool $force): array
    {
        $this->info('🌐 Recalcul pour tous les utilisateurs');

        $results = $this->balanceService->recalculateAllBalances($force);

        if ($results['success']) {
            $this->info("✅ Recalcul terminé pour {$results['processed_users']} utilisateurs");
        } else {
            $this->error("❌ Recalcul terminé avec des erreurs");
            $this->warn("⚠️  {$results['errors']} erreurs rencontrées");
        }

        return $results;
    }

    /**
     * Affiche les résultats du recalcul
     * 
     * @param array $results
     * @param float $executionTime
     */
    private function displayResults(array $results, float $executionTime): void
    {
        $this->newLine();
        $this->info('📊 Résultats du recalcul:');
        $this->line("⏱️  Temps d'exécution: {$executionTime}s");
        $this->line("👥 Utilisateurs traités: {$results['processed_users']}");
        
        if (!empty($results['errors'])) {
            $this->line("❌ Erreurs: " . count($results['errors']));
            
            if ($this->option('verbose')) {
                $this->newLine();
                $this->warn('Détails des erreurs:');
                foreach ($results['errors'] as $error) {
                    $this->line("  - {$error['user_id']}: {$error['error']}");
                }
            }
        }

        if (!empty($results['user_balances'])) {
            $this->newLine();
            $this->info('💰 Balances calculées:');
            
            $totalBalance = 0;
            foreach ($results['user_balances'] as $balance) {
                $totalBalance += $balance['current_balance'];
                $this->line("  - {$balance['user_name']} ({$balance['user_role']}): {$balance['current_balance']} EUR");
            }
            
            $this->line("💎 Total des balances: {$totalBalance} EUR");
        }
    }

    /**
     * Génère un rapport détaillé
     * 
     * @param array $results
     * @param float $executionTime
     */
    private function generateReport(array $results, float $executionTime): void
    {
        $this->newLine();
        $this->info('📋 Génération du rapport détaillé...');

        $reportData = [
            'generated_at' => now()->toISOString(),
            'execution_time' => $executionTime,
            'processed_users' => $results['processed_users'] ?? 0,
            'errors_count' => count($results['errors'] ?? []),
            'success' => $results['success'] ?? false,
            'errors' => $results['errors'] ?? [],
            'user_balances' => $results['user_balances'] ?? []
        ];

        // Sauvegarder le rapport dans un fichier
        $reportPath = storage_path('logs/balance_recalculation_report_' . now()->format('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));

        $this->info("📄 Rapport sauvegardé: {$reportPath}");

        // Afficher un résumé
        $this->newLine();
        $this->info('📊 Résumé du rapport:');
        $this->line("📅 Généré le: " . now()->format('Y-m-d H:i:s'));
        $this->line("⏱️  Temps d'exécution: {$executionTime}s");
        $this->line("👥 Utilisateurs traités: {$results['processed_users']}");
        $this->line("❌ Erreurs: " . count($results['errors'] ?? []));
        $this->line("✅ Succès: " . ($results['success'] ? 'Oui' : 'Non'));
    }
}
