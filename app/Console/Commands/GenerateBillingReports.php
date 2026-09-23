<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Services\BalanceCalculationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Commande pour générer les rapports de facturation
 * 
 * Cette commande génère :
 * - Rapports de commission par utilisateur
 * - Rapports de balance hiérarchique
 * - Rapports de performance
 * - Exports CSV/PDF
 */
class GenerateBillingReports extends Command
{
    protected $signature = 'billing:reports 
                            {--type=all : Type de rapport (all, commissions, balances, performance)}
                            {--format=csv : Format d\'export (csv, pdf, json)}
                            {--period=monthly : Période (daily, weekly, monthly, yearly)}
                            {--user= : ID utilisateur spécifique}
                            {--output= : Dossier de sortie}';

    protected $description = 'Génère les rapports de facturation et les exports';

    protected BalanceCalculationService $balanceService;

    public function __construct(BalanceCalculationService $balanceService)
    {
        parent::__construct();
        $this->balanceService = $balanceService;
    }

    public function handle()
    {
        $type = $this->option('type');
        $format = $this->option('format');
        $period = $this->option('period');
        $userId = $this->option('user');
        $outputDir = $this->option('output') ?? storage_path('app/reports');

        $this->info("📊 Génération des rapports de facturation");
        $this->info("Type: {$type}, Format: {$format}, Période: {$period}");

        $results = [
            'started_at' => now()->toISOString(),
            'type' => $type,
            'format' => $format,
            'period' => $period,
            'files_generated' => [],
            'errors' => []
        ];

        try {
            // Créer le dossier de sortie si nécessaire
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            switch ($type) {
                case 'commissions':
                    $results['files_generated'] = $this->generateCommissionReports($format, $period, $userId, $outputDir);
                    break;
                case 'balances':
                    $results['files_generated'] = $this->generateBalanceReports($format, $period, $userId, $outputDir);
                    break;
                case 'performance':
                    $results['files_generated'] = $this->generatePerformanceReports($format, $period, $userId, $outputDir);
                    break;
                case 'all':
                default:
                    $results['files_generated'] = array_merge(
                        $this->generateCommissionReports($format, $period, $userId, $outputDir),
                        $this->generateBalanceReports($format, $period, $userId, $outputDir),
                        $this->generatePerformanceReports($format, $period, $userId, $outputDir)
                    );
                    break;
            }

            $results['completed_at'] = now()->toISOString();
            $results['status'] = 'success';

            $this->info("✅ Rapports générés avec succès");
            $this->displayResults($results);

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $results['status'] = 'error';
            $results['completed_at'] = now()->toISOString();

            $this->error("❌ Erreur lors de la génération: " . $e->getMessage());
            Log::error('Erreur génération rapports', $results);
        }

        return $results['status'] === 'success' ? 0 : 1;
    }

    /**
     * Génère les rapports de commission
     * 
     * @param string $format
     * @param string $period
     * @param string|null $userId
     * @param string $outputDir
     * @return array
     */
    private function generateCommissionReports(string $format, string $period, ?string $userId, string $outputDir): array
    {
        $this->info("💰 Génération des rapports de commission...");

        $dateFrom = $this->getDateFromPeriod($period);
        $filename = "commissions_report_{$period}_" . now()->format('Y_m_d_H_i_s');

        // Récupérer les données de commission
        $query = Transaction::where('created_at', '>=', $dateFrom)
            ->where('payment_status', 'paid')
            ->with(['user', 'businessProfile', 'chargingPoint']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $transactions = $query->get();

        $data = $transactions->map(function ($transaction) {
            return [
                'transaction_id' => $transaction->id,
                'user_name' => $transaction->user->name ?? 'N/A',
                'user_email' => $transaction->user->email ?? 'N/A',
                'charging_point' => $transaction->chargingPoint->name ?? 'N/A',
                'amount' => $transaction->price_total,
                'admin_commission' => $transaction->admin_commission ?? 0,
                'integrator_commission' => $transaction->integrator_commission ?? 0,
                'partner_commission' => $transaction->partner_commission ?? 0,
                'total_commission' => ($transaction->admin_commission ?? 0) + 
                                   ($transaction->integrator_commission ?? 0) + 
                                   ($transaction->partner_commission ?? 0),
                'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                'paid_at' => $transaction->paid_at ? $transaction->paid_at->format('Y-m-d H:i:s') : 'N/A'
            ];
        });

        $filePath = $this->exportData($data, $format, $outputDir, $filename);

        return [$filePath];
    }

    /**
     * Génère les rapports de balance
     * 
     * @param string $format
     * @param string $period
     * @param string|null $userId
     * @param string $outputDir
     * @return array
     */
    private function generateBalanceReports(string $format, string $period, ?string $userId, string $outputDir): array
    {
        $this->info("💳 Génération des rapports de balance...");

        $filename = "balances_report_{$period}_" . now()->format('Y_m_d_H_i_s');

        // Récupérer les utilisateurs
        $query = User::with(['roles']);
        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $data = [];

        foreach ($users as $user) {
            $balance = $this->balanceService->calculateUserBalance($user);
            $hierarchicalBalance = $this->balanceService->calculateHierarchicalBalance($user);

            $data[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'current_balance' => $balance['current_balance'],
                'total_earnings' => $balance['total_earnings'],
                'pending_commissions' => $balance['pending_commissions'],
                'paid_commissions' => $balance['paid_commissions'],
                'transaction_count' => $balance['transaction_count'],
                'hierarchy_balance' => $hierarchicalBalance['hierarchy_balance'] ?? 0,
                'subordinates_count' => count($hierarchicalBalance['subordinates'] ?? []),
                'last_transaction' => $balance['last_transaction_date'],
                'calculated_at' => $balance['calculated_at']
            ];
        }

        $filePath = $this->exportData($data, $format, $outputDir, $filename);

        return [$filePath];
    }

    /**
     * Génère les rapports de performance
     * 
     * @param string $format
     * @param string $period
     * @param string|null $userId
     * @param string $outputDir
     * @return array
     */
    private function generatePerformanceReports(string $format, string $period, ?string $userId, string $outputDir): array
    {
        $this->info("📈 Génération des rapports de performance...");

        $filename = "performance_report_{$period}_" . now()->format('Y_m_d_H_i_s');
        $dateFrom = $this->getDateFromPeriod($period);

        // Statistiques globales
        $globalStats = [
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => now()->toISOString(),
            'total_transactions' => Transaction::where('created_at', '>=', $dateFrom)->count(),
            'total_revenue' => Transaction::where('created_at', '>=', $dateFrom)
                ->where('payment_status', 'paid')
                ->sum('price_total'),
            'total_commissions' => Transaction::where('created_at', '>=', $dateFrom)
                ->where('payment_status', 'paid')
                ->selectRaw('SUM(admin_commission + integrator_commission + partner_commission) as total')
                ->value('total') ?? 0,
            'active_users' => User::where('is_active', true)->count(),
            'active_charging_points' => \App\Models\ChargingPoint::where('status', 'online')->count()
        ];

        // Statistiques par rôle
        $roleStats = [];
        $roles = ['admin', 'integrator', 'operator'];
        
        foreach ($roles as $role) {
            $roleUsers = User::whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })->get();

            $roleStats[$role] = [
                'user_count' => $roleUsers->count(),
                'total_balance' => $roleUsers->sum('balance'),
                'avg_balance' => $roleUsers->avg('balance') ?? 0
            ];
        }

        $data = [
            'global_statistics' => $globalStats,
            'role_statistics' => $roleStats,
            'generated_at' => now()->toISOString()
        ];

        $filePath = $this->exportData($data, $format, $outputDir, $filename);

        return [$filePath];
    }

    /**
     * Exporte les données dans le format demandé
     * 
     * @param mixed $data
     * @param string $format
     * @param string $outputDir
     * @param string $filename
     * @return string
     */
    private function exportData($data, string $format, string $outputDir, string $filename): string
    {
        $filePath = $outputDir . '/' . $filename;

        switch ($format) {
            case 'csv':
                $filePath .= '.csv';
                $this->exportToCsv($data, $filePath);
                break;
            case 'json':
                $filePath .= '.json';
                $this->exportToJson($data, $filePath);
                break;
            case 'pdf':
                $filePath .= '.pdf';
                $this->exportToPdf($data, $filePath);
                break;
            default:
                throw new \InvalidArgumentException("Format non supporté: {$format}");
        }

        return $filePath;
    }

    /**
     * Exporte en CSV
     * 
     * @param mixed $data
     * @param string $filePath
     */
    private function exportToCsv($data, string $filePath): void
    {
        $file = fopen($filePath, 'w');
        
        if (is_array($data) && !empty($data)) {
            // Écrire les en-têtes
            if (is_object($data[0]) || (is_array($data[0]) && isset($data[0]))) {
                fputcsv($file, array_keys((array) $data[0]));
            }
            
            // Écrire les données
            foreach ($data as $row) {
                fputcsv($file, (array) $row);
            }
        }
        
        fclose($file);
    }

    /**
     * Exporte en JSON
     * 
     * @param mixed $data
     * @param string $filePath
     */
    private function exportToJson($data, string $filePath): void
    {
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Exporte en PDF
     * 
     * @param mixed $data
     * @param string $filePath
     */
    private function exportToPdf($data, string $filePath): void
    {
        // Pour l'instant, on génère un fichier texte simple
        // Dans une implémentation complète, on utiliserait une librairie PDF comme TCPDF ou DomPDF
        $content = "Rapport EVON - " . now()->format('Y-m-d H:i:s') . "\n\n";
        $content .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        file_put_contents($filePath, $content);
    }

    /**
     * Obtient la date de début selon la période
     * 
     * @param string $period
     * @return string
     */
    private function getDateFromPeriod(string $period): string
    {
        switch ($period) {
            case 'daily':
                return now()->subDay()->startOfDay();
            case 'weekly':
                return now()->subWeek()->startOfDay();
            case 'monthly':
                return now()->subMonth()->startOfDay();
            case 'yearly':
                return now()->subYear()->startOfDay();
            default:
                return now()->subMonth()->startOfDay();
        }
    }

    /**
     * Affiche les résultats de la génération
     * 
     * @param array $results
     */
    private function displayResults(array $results): void
    {
        $this->info("\n📋 Fichiers générés:");
        
        foreach ($results['files_generated'] as $file) {
            $this->info("  - " . basename($file));
        }

        if (!empty($results['errors'])) {
            $this->warn("\n⚠️ Erreurs rencontrées:");
            foreach ($results['errors'] as $error) {
                $this->warn("  - {$error}");
            }
        }
    }
}
