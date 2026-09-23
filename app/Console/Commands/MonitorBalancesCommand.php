<?php

namespace App\Console\Commands;

use App\Services\BalanceMonitoringService;
use Illuminate\Console\Command;

class MonitorBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:monitor 
                            {--json : Sortir le rapport en format JSON}
                            {--clear-cache : Nettoyer le cache avant de générer le rapport}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère un rapport de santé du système de balances';

    /**
     * Execute the console command.
     */
    public function handle(BalanceMonitoringService $monitoringService): int
    {
        if ($this->option('clear-cache')) {
            $monitoringService->clearHealthReportCache();
            $this->info('🗑️  Cache nettoyé');
        }

        $this->info('📊 Génération du rapport de santé...');
        $this->newLine();

        $report = $monitoringService->generateHealthReport();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        // Afficher le résumé
        $this->info('📈 Résumé:');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total utilisateurs', $report['summary']['total_users']],
                ['Balances valides', $report['summary']['valid_balances']],
                ['Balances invalides', $report['summary']['invalid_balances']],
                ['Taux de validité', $report['summary']['validity_rate'] . '%'],
            ]
        );

        $this->newLine();

        // Afficher les statistiques
        $this->info('📊 Statistiques:');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total TransactionDetail', $report['statistics']['total_transaction_details']],
                ['Balance Admin totale', number_format($report['statistics']['total_admin_balance'], 2) . ' EUR'],
                ['Balance Intégrateur totale', number_format($report['statistics']['total_integrator_balance'], 2) . ' EUR'],
                ['Balance Opérateur totale', number_format($report['statistics']['total_operator_balance'], 2) . ' EUR'],
                ['TransactionDetail récents (7j)', $report['statistics']['recent_transaction_details_7d']],
            ]
        );

        // Afficher les alertes
        if (!empty($report['alerts'])) {
            $this->newLine();
            $this->warn('⚠️  Alertes:');
            
            foreach ($report['alerts'] as $alert) {
                $icon = match($alert['level']) {
                    'error' => '❌',
                    'warning' => '⚠️',
                    'info' => 'ℹ️',
                    default => '📌',
                };
                
                $this->line("  {$icon} {$alert['message']}");
                if (isset($alert['action'])) {
                    $this->line("     → {$alert['action']}");
                }
            }
        } else {
            $this->newLine();
            $this->info('✅ Aucune alerte - Système en bonne santé');
        }

        $this->newLine();
        $this->comment("🕐 Rapport généré le: {$report['timestamp']}");

        return Command::SUCCESS;
    }
}

