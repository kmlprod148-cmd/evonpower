<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FinancialReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $transactions;
    protected $withdrawals;
    protected $sessions;
    protected $summary;

    public function __construct($transactions, $withdrawals, $sessions, $summary)
    {
        $this->transactions = $transactions;
        $this->withdrawals = $withdrawals;
        $this->sessions = $sessions;
        $this->summary = $summary;
    }

    public function collection()
    {
        $data = [];

        // Section Transactions
        $data[] = ['=== TRANSACTIONS ==='];
        $data[] = ['ID', 'Date', 'Type', 'Montant', 'Statut', 'Utilisateur'];

        foreach ($this->transactions as $transaction) {
            $data[] = [
                $transaction->id,
                $transaction->created_at->format('d/m/Y H:i'),
                $transaction->type,
                number_format($transaction->amount, 2, ',', ' '),
                $transaction->status,
                $transaction->user?->name ?? 'N/A',
            ];
        }

        // Espace
        $data[] = [];
        $data[] = ['=== RETRAITS ==='];
        $data[] = ['ID', 'Date', 'Montant', 'Frais', 'Net', 'Statut', 'Utilisateur'];

        foreach ($this->withdrawals as $withdrawal) {
            $data[] = [
                $withdrawal->id,
                $withdrawal->created_at->format('d/m/Y H:i'),
                number_format($withdrawal->amount, 2, ',', ' '),
                number_format($withdrawal->fee, 2, ',', ' '),
                number_format($withdrawal->net_amount, 2, ',', ' '),
                $withdrawal->status,
                $withdrawal->owner?->name ?? 'N/A',
            ];
        }

        // Espace
        $data[] = [];
        $data[] = ['=== SESSIONS DE RECHARGE ==='];
        $data[] = ['ID', 'Date', 'Énergie (kWh)', 'Durée (min)', 'Coût', 'Statut'];

        foreach ($this->sessions as $session) {
            $data[] = [
                $session->id,
                $session->created_at->format('d/m/Y H:i'),
                number_format($session->energy_delivered, 2, ',', ' '),
                $session->duration ?? 0,
                number_format($session->total_cost ?? 0, 2, ',', ' '),
                $session->status,
            ];
        }

        // Espace
        $data[] = [];
        $data[] = ['=== RÉSUMÉ FINANCIER ==='];
        
        $data[] = ['Revenus'];
        $data[] = ['Transactions', number_format($this->summary['revenus']['transactions'] ?? 0, 2, ',', ' ')];
        $data[] = ['Sessions', number_format($this->summary['revenus']['sessions'] ?? 0, 2, ',', ' ')];
        
        $data[] = ['Dépenses'];
        $data[] = ['Retraits', number_format($this->summary['depenses']['retraits'] ?? 0, 2, ',', ' ')];
        $data[] = ['Frais Retraits', number_format($this->summary['depenses']['frais_retraits'] ?? 0, 2, ',', ' ')];
        
        $data[] = ['Solde Net', number_format($this->summary['totals']['solde_net'] ?? 0, 2, ',', ' ')];

        return collect($data);
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4acf7b'],
                ],
            ],
            'A' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
        ];
    }
}
