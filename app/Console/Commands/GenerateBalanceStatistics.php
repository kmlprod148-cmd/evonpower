<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HierarchicalBalanceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Commande pour générer les statistiques globales des balances
 * 
 * Cette commande génère :
 * - Statistiques globales détaillées
 * - Rapports de performance par rôle
 * - Métriques de performance
 * - Exports en différents formats
 */
class GenerateBalanceStatistics extends Command
{
    protected $signature = 'balances:statistics 
                            {--format=json : Format de sortie (json, csv, pdf)}
                            {--period=all : Période (all, daily, weekly, monthly)}
                            {--export : Exporter les statistiques dans un fichier}
                            {--email : Envoyer par email (nécessite configuration SMTP)}';

    protected $description = 'Génère les statistiques globales des balances hiérarchiques';

    protected HierarchicalBalanceService $balanceService;

    public function __construct(HierarchicalBalanceService $balanceService)
    {
        parent::__construct();
        $this->balanceService = $balanceService;
    }

    public function handle()
    {
        $this->info('📊 Génération des statistiques globales des balances');
        
        $format = $this->option('format');
        $period = $this->option('period');
        $export = $this->option('export');
        $email = $this->option('email');

        try {
            // Calculer les statistiques globales
            $this->info('🔄 Calcul des statistiques...');
            $statistics = $this->balanceService->calculateGlobalStatistics(true);

            // Afficher les statistiques
            $this->displayStatistics($statistics);

            // Exporter si demandé
            if ($export) {
                $this->exportStatistics($statistics, $format, $period);
            }

            // Envoyer par email si demandé
            if ($email) {
                $this->sendStatisticsByEmail($statistics, $format, $period);
            }

            Log::info('Statistiques des balances générées avec succès', [
                'format' => $format,
                'period' => $period,
                'export' => $export,
                'email' => $email
            ]);

            $this->info('✅ Statistiques générées avec succès');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la génération des statistiques: ' . $e->getMessage());
            Log::error('Erreur lors de la génération des statistiques', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Affiche les statistiques dans la console
     * 
     * @param array $statistics
     */
    private function displayStatistics(array $statistics): void
    {
        $this->newLine();
        $this->info('📈 Statistiques Globales des Balances Hiérarchiques');
        $this->line('═' . str_repeat('═', 60));

        // Informations générales
        $this->newLine();
        $this->info('🌐 Vue d\'ensemble:');
        $this->line("📅 Calculé le: {$statistics['calculated_at']}");
        $this->line("👥 Total utilisateurs: {$statistics['total_users']}");
        $this->line("💳 Total transactions: {$statistics['total_transactions']}");
        $this->line("💰 Total revenus: {$statistics['total_revenue']} EUR");

        // Breakdown par rôle
        $this->newLine();
        $this->info('👥 Répartition par rôle:');
        foreach ($statistics['hierarchy_breakdown'] as $role) {
            $this->line("  🔹 {$role['role']}:");
            $this->line("    👥 Utilisateurs: {$role['user_count']}");
            $this->line("    💳 Transactions: {$role['transaction_count']}");
            $this->line("    💰 Montant total: {$role['total_amount']} EUR");
        }

        // Métriques de performance
        if (!empty($statistics['performance_metrics'])) {
            $this->newLine();
            $this->info('📊 Métriques de performance:');
            $metrics = $statistics['performance_metrics'];
            $this->line("  💳 Total transactions: {$metrics['total_transactions']}");
            $this->line("  💰 Montant total: {$metrics['total_amount']} EUR");
            $this->line("  📈 Montant moyen: {$metrics['average_amount']} EUR");
            $this->line("  📉 Montant minimum: {$metrics['min_amount']} EUR");
            $this->line("  📊 Montant maximum: {$metrics['max_amount']} EUR");
            $this->line("  📅 Jours actifs: {$metrics['active_days']}");
            $this->line("  👥 Utilisateurs actifs: {$metrics['active_users']}");
        }

        // Activité récente
        if (!empty($statistics['recent_activity'])) {
            $this->newLine();
            $this->info('🕒 Activité récente (7 derniers jours):');
            foreach (array_slice($statistics['recent_activity'], 0, 5) as $activity) {
                $this->line("  💳 Transaction #{$activity['id']}: {$activity['amount']} EUR - {$activity['user_name']} ({$activity['created_at']})");
            }
            
            if (count($statistics['recent_activity']) > 5) {
                $this->line("  ... et " . (count($statistics['recent_activity']) - 5) . " autres transactions");
            }
        }
    }

    /**
     * Exporte les statistiques dans un fichier
     * 
     * @param array $statistics
     * @param string $format
     * @param string $period
     */
    private function exportStatistics(array $statistics, string $format, string $period): void
    {
        $this->info("📤 Export des statistiques en format {$format}...");

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "balance_statistics_{$period}_{$timestamp}";

        switch ($format) {
            case 'json':
                $this->exportToJson($statistics, $filename);
                break;
            case 'csv':
                $this->exportToCsv($statistics, $filename);
                break;
            case 'pdf':
                $this->exportToPdf($statistics, $filename);
                break;
            default:
                $this->warn("Format {$format} non supporté, utilisation de JSON");
                $this->exportToJson($statistics, $filename);
        }
    }

    /**
     * Exporte en format JSON
     * 
     * @param array $statistics
     * @param string $filename
     */
    private function exportToJson(array $statistics, string $filename): void
    {
        $filepath = storage_path("app/reports/{$filename}.json");
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        file_put_contents($filepath, json_encode($statistics, JSON_PRETTY_PRINT));
        $this->info("✅ Export JSON: {$filepath}");
    }

    /**
     * Exporte en format CSV
     * 
     * @param array $statistics
     * @param string $filename
     */
    private function exportToCsv(array $statistics, string $filename): void
    {
        $filepath = storage_path("app/reports/{$filename}.csv");
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $csvData = [];
        
        // En-têtes
        $csvData[] = ['Métrique', 'Valeur'];
        
        // Données générales
        $csvData[] = ['Total utilisateurs', $statistics['total_users']];
        $csvData[] = ['Total transactions', $statistics['total_transactions']];
        $csvData[] = ['Total revenus (EUR)', $statistics['total_revenue']];
        
        // Breakdown par rôle
        foreach ($statistics['hierarchy_breakdown'] as $role) {
            $csvData[] = ["Utilisateurs {$role['role']}", $role['user_count']];
            $csvData[] = ["Transactions {$role['role']}", $role['transaction_count']];
            $csvData[] = ["Montant {$role['role']} (EUR)", $role['total_amount']];
        }

        // Écrire le fichier CSV
        $file = fopen($filepath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        $this->info("✅ Export CSV: {$filepath}");
    }

    /**
     * Exporte en format PDF
     * 
     * @param array $statistics
     * @param string $filename
     */
    private function exportToPdf(array $statistics, string $filename): void
    {
        $this->warn('⚠️  Export PDF non implémenté, utilisation du format JSON');
        $this->exportToJson($statistics, $filename);
    }

    /**
     * Envoie les statistiques par email
     * 
     * @param array $statistics
     * @param string $format
     * @param string $period
     */
    private function sendStatisticsByEmail(array $statistics, string $format, string $period): void
    {
        $this->info('📧 Envoi des statistiques par email...');
        
        // TODO: Implémenter l'envoi par email
        // Cela nécessiterait la configuration SMTP et la création d'un mail template
        
        $this->warn('⚠️  Envoi par email non implémenté dans cette version');
        $this->line('💡 Pour implémenter l\'envoi par email, configurez SMTP et créez un template de mail');
    }
}
