<?php

namespace App\Console\Commands\Billing;

use App\Services\BillingService;
use App\Services\ReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GenerateReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-reports {--start-date=} {--end-date=} {--format=csv} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère des rapports de facturation en CSV, PDF ou JSON';

    protected BillingService $billingService;
    protected ReportService $reportService;

    public function __construct(BillingService $billingService, ReportService $reportService)
    {
        parent::__construct();
        $this->billingService = $billingService;
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('start-date') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->option('end-date') ?? now()->endOfMonth()->format('Y-m-d');
        $format = $this->option('format');
        $outputPath = $this->option('output');

        $this->info("🚀 Génération du rapport de facturation");
        $this->info("📅 Période: {$startDate} à {$endDate}");
        $this->info("📄 Format: " . strtoupper($format));

        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            // Générer le rapport
            $report = $this->billingService->generateReport($start, $end, 'array');

            // Déterminer le chemin de sortie
            $filename = $this->generateFilename($format, $start, $end);
            $fullPath = $outputPath ?? storage_path("app/reports/{$filename}");

            // Créer le répertoire si nécessaire
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Générer le fichier selon le format
            switch (strtolower($format)) {
                case 'csv':
                    $this->generateCsvReport($report, $fullPath);
                    break;
                case 'pdf':
                    $this->generatePdfReport($report, $fullPath);
                    break;
                case 'json':
                    $this->generateJsonReport($report, $fullPath);
                    break;
                default:
                    $this->error("❌ Format non supporté: {$format}");
                    return;
            }

            $this->info("✅ Rapport généré avec succès: {$fullPath}");
            $this->info("📊 Statistiques:");
            $this->info("   📋 Total factures: {$report['summary']['total_invoices']}");
            $this->info("   💰 Montant total: {$report['summary']['total_amount']} EUR");
            $this->info("   ✅ Factures payées: {$report['summary']['paid_invoices']}");
            $this->info("   ⏳ Factures en attente: {$report['summary']['pending_invoices']}");

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la génération du rapport: " . $e->getMessage());
        }
    }

    /**
     * Générer un rapport CSV
     */
    private function generateCsvReport(array $report, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        
        // En-têtes
        fputcsv($handle, [
            'Numéro Facture',
            'Entité Facturable',
            'Montant',
            'Statut',
            'Date d\'Échéance',
            'Date de Création'
        ]);

        // Données
        foreach ($report['invoices'] as $invoice) {
            fputcsv($handle, [
                $invoice['invoice_number'],
                $invoice['billable'],
                $invoice['amount'],
                $invoice['status'],
                $invoice['due_date'],
                $invoice['created_at']
            ]);
        }

        fclose($handle);
    }

    /**
     * Générer un rapport PDF
     */
    private function generatePdfReport(array $report, string $filePath): void
    {
        $html = $this->generateHtmlReport($report);
        
        // Utiliser DomPDF pour générer le PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->save($filePath);
    }

    /**
     * Générer un rapport JSON
     */
    private function generateJsonReport(array $report, string $filePath): void
    {
        file_put_contents($filePath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Générer le HTML pour le rapport PDF
     */
    private function generateHtmlReport(array $report): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Rapport de Facturation</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .summary { background: #f5f5f5; padding: 15px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Rapport de Facturation</h1>
                <p>Période: ' . $report['period']['start'] . ' à ' . $report['period']['end'] . '</p>
            </div>
            
            <div class="summary">
                <h2>Résumé</h2>
                <p>Total factures: ' . $report['summary']['total_invoices'] . '</p>
                <p>Montant total: ' . $report['summary']['total_amount'] . ' EUR</p>
                <p>Factures payées: ' . $report['summary']['paid_invoices'] . '</p>
                <p>Factures en attente: ' . $report['summary']['pending_invoices'] . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Numéro Facture</th>
                        <th>Entité Facturable</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date d\'Échéance</th>
                        <th>Date de Création</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($report['invoices'] as $invoice) {
            $html .= '<tr>
                <td>' . $invoice['invoice_number'] . '</td>
                <td>' . $invoice['billable'] . '</td>
                <td>' . $invoice['amount'] . '</td>
                <td>' . $invoice['status'] . '</td>
                <td>' . $invoice['due_date'] . '</td>
                <td>' . $invoice['created_at'] . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
        </body>
        </html>';

        return $html;
    }

    /**
     * Générer un nom de fichier unique
     */
    private function generateFilename(string $format, Carbon $start, Carbon $end): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "billing_report_{$start->format('Y-m-d')}_{$end->format('Y-m-d')}_{$timestamp}.{$format}";
    }
}
