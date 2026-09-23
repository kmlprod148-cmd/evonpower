<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HierarchicalBalanceSynchronizationService;
use Illuminate\Support\Facades\Log;

class SynchronizeHierarchicalBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:sync-hierarchical 
                            {--user-id= : Synchronize specific user by ID}
                            {--role= : Synchronize all users with specific role (admin, integrator, operator, partner)}
                            {--force : Force synchronization even if already synchronized}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize hierarchical balances (Admin → Integrator → Operator/Partner)';

    /**
     * Execute the console command.
     */
    public function handle(HierarchicalBalanceSynchronizationService $service)
    {
        $this->info('🔄 Starting hierarchical balance synchronization...');
        
        $userId = $this->option('user-id');
        $role = $this->option('role');
        $force = $this->option('force');

        try {
            if ($userId) {
                // Synchroniser un utilisateur spécifique
                $user = \App\Models\User::find($userId);
                if (!$user) {
                    $this->error("User with ID {$userId} not found.");
                    return 1;
                }

                $this->info("Synchronizing user: {$user->name} (ID: {$user->id})");
                $results = $service->synchronizeUserWithHierarchy($user);
                
                $this->displayUserResults($user, $results);

            } elseif ($role) {
                // Synchroniser tous les utilisateurs d'un rôle spécifique
                $this->info("Synchronizing all users with role: {$role}");
                $results = $this->synchronizeByRole($service, $role);
                
                $this->displayRoleResults($role, $results);

            } else {
                // Synchroniser toutes les balances hiérarchiquement
                $this->info('Synchronizing all hierarchical balances...');
                $results = $service->synchronizeAllHierarchicalBalances();
                
                $this->displayAllResults($results);
            }

            $this->info('✅ Hierarchical balance synchronization completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error during synchronization: ' . $e->getMessage());
            Log::error('SynchronizeHierarchicalBalances error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Synchroniser par rôle
     */
    protected function synchronizeByRole(HierarchicalBalanceSynchronizationService $service, string $role): array
    {
        $results = [];

        if (in_array($role, ['admin', 'super_admin'])) {
            $results = $service->synchronizeAdmins();
        } elseif ($role === 'integrator') {
            $results = $service->synchronizeIntegrators();
        } elseif (in_array($role, ['operator', 'partner'])) {
            $results = $service->synchronizeOperatorsAndPartners();
        } else {
            throw new \InvalidArgumentException("Invalid role: {$role}");
        }

        return $results;
    }

    /**
     * Afficher les résultats pour un utilisateur
     */
    protected function displayUserResults($user, array $results): void
    {
        $this->newLine();
        $this->info("📊 Results for {$user->name} ({$user->getRoleNames()->first()}):");
        
        if ($user->hasRole(['admin', 'super_admin'])) {
            if (isset($results['admin'])) {
                $admin = $results['admin'];
                $this->line("  Balance: " . number_format($admin['new_balance'] ?? 0, 2) . " EUR");
                $this->line("  Money In: " . number_format($admin['total_credits'] ?? 0, 2) . " EUR");
                $this->line("  Money Out: " . number_format($admin['total_debits'] ?? 0, 2) . " EUR");
                if (isset($admin['integrators_count'])) {
                    $this->line("  Integrators: {$admin['integrators_count']}");
                }
            }
        } elseif ($user->hasRole('integrator')) {
            if (isset($results['integrator'])) {
                $integrator = $results['integrator'];
                $this->line("  Balance: " . number_format($integrator['new_balance'] ?? 0, 2) . " EUR");
                $this->line("  Money In: " . number_format($integrator['money_in'] ?? 0, 2) . " EUR");
                $this->line("  Money Out: " . number_format($integrator['money_out'] ?? 0, 2) . " EUR");
                if (isset($results['operators'])) {
                    $this->line("  Operators: " . count($results['operators']));
                }
            }
        } elseif ($user->hasRole(['operator', 'partner'])) {
            if (isset($results['operator'])) {
                $operator = $results['operator'];
                $this->line("  Balance: " . number_format($operator['new_balance'] ?? 0, 2) . " EUR");
                $this->line("  Total Credits: " . number_format($operator['total_credits'] ?? 0, 2) . " EUR");
            }
        }
    }

    /**
     * Afficher les résultats pour un rôle
     */
    protected function displayRoleResults(string $role, array $results): void
    {
        $this->newLine();
        $this->info("📊 Results for role: {$role}");
        
        $synchronized = 0;
        $totalBalance = 0;

        foreach ($results as $userId => $result) {
            if ($result['synchronized'] ?? false) {
                $synchronized++;
                $totalBalance += $result['new_balance'] ?? 0;
            }
        }

        $this->line("  Synchronized: {$synchronized} users");
        $this->line("  Total Balance: " . number_format($totalBalance, 2) . " EUR");
    }

    /**
     * Afficher tous les résultats
     */
    protected function displayAllResults(array $results): void
    {
        $this->newLine();
        $this->info('📊 Synchronization Summary:');
        
        $summary = $results['summary'] ?? [];
        $this->line("  Total Synchronized: {$summary['total_synchronized']} users");
        $this->line("  Errors: {$summary['total_errors']}");
        
        if (isset($results['operators'])) {
            $operatorsCount = count(array_filter($results['operators'], fn($r) => $r['synchronized'] ?? false));
            $this->line("  Operators/Partners: {$operatorsCount} synchronized");
        }
        
        if (isset($results['integrators'])) {
            $integratorsCount = count(array_filter($results['integrators'], fn($r) => $r['synchronized'] ?? false));
            $this->line("  Integrators: {$integratorsCount} synchronized");
        }
        
        if (isset($results['admins'])) {
            $adminsCount = count(array_filter($results['admins'], fn($r) => $r['synchronized'] ?? false));
            $this->line("  Admins: {$adminsCount} synchronized");
        }
    }
}

