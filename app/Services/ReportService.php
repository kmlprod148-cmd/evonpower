<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    /**
     * Générer un rapport personnalisé
     */
    public function generateCustomReport(array $filters = []): array
    {
        $query = BillingInvoice::with(['billingPlan', 'billable', 'payments']);

        // Appliquer les filtres
        if (isset($filters['start_date'])) {
            $query->where('billing_period_start', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('billing_period_end', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['billable_type'])) {
            $query->where('billable_type', $filters['billable_type']);
        }

        if (isset($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        return [
            'filters' => $filters,
            'total_count' => $invoices->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'invoices' => $invoices->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'billable_type' => $invoice->billable_type,
                    'billable_id' => $invoice->billable_id,
                    'amount' => $invoice->amount,
                    'tax_amount' => $invoice->tax_amount,
                    'total_amount' => $invoice->total_amount,
                    'currency' => $invoice->currency,
                    'status' => $invoice->status,
                    'status_label' => $invoice->status_label,
                    'billing_period_start' => $invoice->billing_period_start->format('Y-m-d'),
                    'billing_period_end' => $invoice->billing_period_end->format('Y-m-d'),
                    'due_date' => $invoice->due_date->format('Y-m-d'),
                    'paid_at' => $invoice->paid_at?->format('Y-m-d H:i:s'),
                    'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                    'payments' => $invoice->payments->map(function($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'payment_method' => $payment->payment_method,
                            'status' => $payment->status,
                            'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s')
                        ];
                    })
                ];
            })
        ];
    }

    /**
     * Générer un rapport de revenus par période
     */
    public function generateRevenueReport(Carbon $startDate, Carbon $endDate, string $groupBy = 'month'): array
    {
        $query = BillingInvoice::whereBetween('billing_period_start', [$startDate, $endDate]);

        // Database-agnostic date formatting
        $driver = \DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';
        
        $groupByClause = match($groupBy) {
            'day' => $isSqlite ? "date(billing_period_start)" : "DATE(billing_period_start)",
            'week' => $isSqlite ? "strftime('%Y-%W', billing_period_start)" : "YEARWEEK(billing_period_start)",
            'month' => $isSqlite ? "strftime('%Y-%m', billing_period_start)" : "DATE_FORMAT(billing_period_start, '%Y-%m')",
            'year' => $isSqlite ? "strftime('%Y', billing_period_start)" : "YEAR(billing_period_start)",
            default => $isSqlite ? "strftime('%Y-%m', billing_period_start)" : "DATE_FORMAT(billing_period_start, '%Y-%m')"
        };

        $revenueData = $query->selectRaw("
                {$groupByClause} as period,
                COUNT(*) as invoice_count,
                SUM(total_amount) as total_revenue,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
                SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_revenue,
                SUM(CASE WHEN status = 'overdue' THEN total_amount ELSE 0 END) as overdue_revenue
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'group_by' => $groupBy
            ],
            'summary' => [
                'total_invoices' => $revenueData->sum('invoice_count'),
                'total_revenue' => $revenueData->sum('total_revenue'),
                'paid_revenue' => $revenueData->sum('paid_revenue'),
                'pending_revenue' => $revenueData->sum('pending_revenue'),
                'overdue_revenue' => $revenueData->sum('overdue_revenue')
            ],
            'data' => $revenueData->map(function($item) {
                return [
                    'period' => $item->period,
                    'invoice_count' => $item->invoice_count,
                    'total_revenue' => $item->total_revenue,
                    'paid_revenue' => $item->paid_revenue,
                    'pending_revenue' => $item->pending_revenue,
                    'overdue_revenue' => $item->overdue_revenue
                ];
            })
        ];
    }

    /**
     * Générer un rapport de facturation par entité
     */
    public function generateBillingByEntityReport(Carbon $startDate, Carbon $endDate): array
    {
        $data = BillingInvoice::whereBetween('billing_period_start', [$startDate, $endDate])
            ->selectRaw("
                billable_type,
                billable_id,
                COUNT(*) as invoice_count,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'overdue' THEN total_amount ELSE 0 END) as overdue_amount
            ")
            ->groupBy('billable_type', 'billable_id')
            ->orderBy('total_amount', 'desc')
            ->get();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'entities' => $data->map(function($item) {
                return [
                    'billable_type' => $item->billable_type,
                    'billable_id' => $item->billable_id,
                    'invoice_count' => $item->invoice_count,
                    'total_amount' => $item->total_amount,
                    'paid_amount' => $item->paid_amount,
                    'pending_amount' => $item->pending_amount,
                    'overdue_amount' => $item->overdue_amount
                ];
            })
        ];
    }

    /**
     * Exporter un rapport en fichier
     */
    public function exportReport(array $report, string $format, string $filename = null): string
    {
        $filename = $filename ?? 'report_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        $filePath = storage_path("app/reports/{$filename}");

        // Créer le répertoire si nécessaire
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        switch (strtolower($format)) {
            case 'csv':
                $this->exportToCsv($report, $filePath);
                break;
            case 'json':
                $this->exportToJson($report, $filePath);
                break;
            case 'xml':
                $this->exportToXml($report, $filePath);
                break;
            default:
                throw new \InvalidArgumentException("Format non supporté: {$format}");
        }

        return $filePath;
    }

    /**
     * Exporter en CSV
     */
    private function exportToCsv(array $report, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        
        if (isset($report['invoices'])) {
            // Rapport de factures
            fputcsv($handle, [
                'ID', 'Numéro Facture', 'Type Entité', 'ID Entité', 
                'Montant', 'Taxes', 'Total', 'Devise', 'Statut',
                'Période Début', 'Période Fin', 'Échéance', 'Payé le', 'Créé le'
            ]);

            foreach ($report['invoices'] as $invoice) {
                fputcsv($handle, [
                    $invoice['id'],
                    $invoice['invoice_number'],
                    $invoice['billable_type'],
                    $invoice['billable_id'],
                    $invoice['amount'],
                    $invoice['tax_amount'],
                    $invoice['total_amount'],
                    $invoice['currency'],
                    $invoice['status_label'],
                    $invoice['billing_period_start'],
                    $invoice['billing_period_end'],
                    $invoice['due_date'],
                    $invoice['paid_at'],
                    $invoice['created_at']
                ]);
            }
        } elseif (isset($report['data'])) {
            // Rapport de revenus
            fputcsv($handle, ['Période', 'Nombre Factures', 'Revenus Total', 'Revenus Payés', 'Revenus En Attente', 'Revenus En Retard']);
            
            foreach ($report['data'] as $item) {
                fputcsv($handle, [
                    $item['period'],
                    $item['invoice_count'],
                    $item['total_revenue'],
                    $item['paid_revenue'],
                    $item['pending_revenue'],
                    $item['overdue_revenue']
                ]);
            }
        }

        fclose($handle);
    }

    /**
     * Exporter en JSON
     */
    private function exportToJson(array $report, string $filePath): void
    {
        file_put_contents($filePath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Exporter en XML
     */
    private function exportToXml(array $report, string $filePath): void
    {
        $xml = new \SimpleXMLElement('<report></report>');
        $this->arrayToXml($report, $xml);
        $xml->asXML($filePath);
    }

    /**
     * Convertir un tableau en XML
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}
