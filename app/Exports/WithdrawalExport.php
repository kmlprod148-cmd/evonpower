<?php

namespace App\Exports;

use App\Models\WithdrawalRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class WithdrawalExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $withdrawals;

    public function __construct($withdrawals)
    {
        $this->withdrawals = $withdrawals;
    }

    public function collection()
    {
        return $this->withdrawals;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Utilisateur',
            'Email',
            'Montant',
            'Frais',
            'Montant Net',
            'Devise',
            'Statut',
            'Méthode',
            'Banque',
            'Compte',
            'Date de création',
            'Date de traitement',
        ];
    }

    public function map($withdrawal): array
    {
        return [
            $withdrawal->id,
            $withdrawal->owner?->name ?? 'N/A',
            $withdrawal->owner?->email ?? 'N/A',
            number_format($withdrawal->amount, 2, ',', ' '),
            number_format($withdrawal->fee, 2, ',', ' '),
            number_format($withdrawal->net_amount, 2, ',', ' '),
            $withdrawal->currency,
            $withdrawal->status,
            $withdrawal->withdrawal_method,
            $withdrawal->bank_name,
            $withdrawal->bank_account,
            $withdrawal->created_at->format('d/m/Y H:i'),
            $withdrawal->processed_at?->format('d/m/Y H:i') ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4acf7b'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 25,
            'C' => 30,
            'D' => 15,
            'E' => 12,
            'F' => 15,
            'G' => 10,
            'H' => 15,
            'I' => 20,
            'J' => 20,
            'K' => 25,
            'L' => 20,
            'M' => 20,
        ];
    }
}
