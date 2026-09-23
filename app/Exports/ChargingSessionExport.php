<?php

namespace App\Exports;

use App\Models\ChargingSession;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ChargingSessionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $sessions;

    public function __construct($sessions)
    {
        $this->sessions = $sessions;
    }

    public function collection()
    {
        return $this->sessions;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Utilisateur',
            'Email',
            'Point de Charge',
            'Statut',
            'Énergie (kWh)',
            'Durée (min)',
            'Coût Total',
            'Date Début',
            'Date Fin',
        ];
    }

    public function map($session): array
    {
        return [
            $session->id,
            $session->user?->name ?? 'N/A',
            $session->user?->email ?? 'N/A',
            $session->chargingPoint?->name ?? 'N/A',
            $session->status,
            number_format($session->energy_delivered, 2, ',', ' '),
            $session->duration ?? 0,
            number_format($session->total_cost ?? 0, 2, ',', ' '),
            $session->created_at->format('d/m/Y H:i'),
            $session->stopped_at?->format('d/m/Y H:i') ?? 'En cours',
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
            'D' => 25,
            'E' => 15,
            'F' => 15,
            'G' => 12,
            'H' => 15,
            'I' => 20,
            'J' => 20,
        ];
    }
}
