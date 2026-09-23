<?php

namespace App\Exports;

use App\Models\EnhancedTransaction;
use App\Models\EnhancedUser;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransactionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $transactions;
    protected $includeFeeDetails;
    protected $user;

    public function __construct($transactions, bool $includeFeeDetails = false, EnhancedUser $user = null)
    {
        $this->transactions = $transactions;
        $this->includeFeeDetails = $includeFeeDetails;
        $this->user = $user;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        $headings = [
            'ID',
            'Référence',
            'Type de Transaction',
            'Statut',
            'Montant',
            'Devise',
            'Utilisateur Source',
            'Rôle Source',
            'Email Source',
            'Utilisateur Cible',
            'Rôle Cible',
            'Email Cible',
            'Business Profile',
            'Date de Création',
            'Date de Traitement',
            'Description',
            'Localisation'
        ];

        if ($this->includeFeeDetails) {
            $headings = array_merge($headings, [
                'Frais Admin',
                'Frais Integrator',
                'Frais Operator',
                'Total des Frais',
                'Montant Net'
            ]);
        }

        return $headings;
    }

    public function map($transaction): array
    {
        $data = [
            $transaction->id,
            $transaction->transaction_reference,
            $this->getTransactionTypeLabel($transaction->transaction_type),
            $this->getStatusLabel($transaction->status),
            $transaction->amount,
            $transaction->currency,
            $transaction->sourceUser->name ?? 'N/A',
            $transaction->sourceUser->role ?? 'N/A',
            $transaction->sourceUser->email ?? 'N/A',
            $transaction->targetUser->name ?? 'N/A',
            $transaction->targetUser->role ?? 'N/A',
            $transaction->targetUser->email ?? 'N/A',
            $transaction->businessProfile->name ?? 'N/A',
            $transaction->created_at->format('Y-m-d H:i:s'),
            $transaction->processed_at ? $transaction->processed_at->format('Y-m-d H:i:s') : 'N/A',
            $transaction->description ?? '',
            $transaction->location ?? ''
        ];

        if ($this->includeFeeDetails) {
            $data = array_merge($data, [
                $transaction->admin_fee ?? 0,
                $transaction->integrator_fee ?? 0,
                $transaction->operator_fee ?? 0,
                $transaction->getTotalFees(),
                $transaction->total_amount ?? $transaction->amount
            ]);
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style de l'en-tête
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E86AB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 8,   // ID
            'B' => 20,  // Référence
            'C' => 25,  // Type
            'D' => 12,  // Statut
            'E' => 15,  // Montant
            'F' => 8,   // Devise
            'G' => 20,  // Utilisateur Source
            'H' => 12,  // Rôle Source
            'I' => 25,  // Email Source
            'J' => 20,  // Utilisateur Cible
            'K' => 12,  // Rôle Cible
            'L' => 25,  // Email Cible
            'M' => 20,  // Business Profile
            'N' => 20,  // Date Création
            'O' => 20,  // Date Traitement
            'P' => 30,  // Description
            'Q' => 20,  // Localisation
        ];

        if ($this->includeFeeDetails) {
            $widths = array_merge($widths, [
                'R' => 12,  // Frais Admin
                'S' => 15,  // Frais Integrator
                'T' => 15,  // Frais Operator
                'U' => 15,  // Total Frais
                'V' => 15,  // Montant Net
            ]);
        }

        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Appliquer les bordures à toutes les cellules
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ]
                    ]
                ]);

                // Appliquer l'alignement du texte
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Aligner les montants à droite
                $amountColumns = ['E']; // Montant
                if ($this->includeFeeDetails) {
                    $amountColumns = array_merge($amountColumns, ['R', 'S', 'T', 'U', 'V']);
                }
                
                foreach ($amountColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Appliquer le formatage conditionnel pour les statuts
                $this->applyConditionalFormatting($sheet, $highestRow);

                // Ajouter des filtres automatiques
                $sheet->setAutoFilter('A1:' . $highestColumn . '1');
            },
        ];
    }

    private function applyConditionalFormatting($sheet, int $highestRow)
    {
        // Formatage conditionnel pour les statuts
        $statusColumn = 'D'; // Colonne Statut
        
        // Vert pour "Terminé"
        $sheet->getStyle($statusColumn . '2:' . $statusColumn . $highestRow)
            ->getConditionalStyles()
            ->addConditionalStyle(
                new \PhpOffice\PhpSpreadsheet\Style\Conditional([
                    'conditionType' => \PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS,
                    'operatorType' => \PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL,
                    'condition' => '"Terminé"',
                    'style' => [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => 'D4EDDA']
                        ],
                        'font' => [
                            'color' => ['rgb' => '155724']
                        ]
                    ]
                ])
            );

        // Jaune pour "En attente"
        $sheet->getStyle($statusColumn . '2:' . $statusColumn . $highestRow)
            ->getConditionalStyles()
            ->addConditionalStyle(
                new \PhpOffice\PhpSpreadsheet\Style\Conditional([
                    'conditionType' => \PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS,
                    'operatorType' => \PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL,
                    'condition' => '"En attente"',
                    'style' => [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => 'FFF3CD']
                        ],
                        'font' => [
                            'color' => ['rgb' => '856404']
                        ]
                    ]
                ])
            );

        // Rouge pour "Annulé"
        $sheet->getStyle($statusColumn . '2:' . $statusColumn . $highestRow)
            ->getConditionalStyles()
            ->addConditionalStyle(
                new \PhpOffice\PhpSpreadsheet\Style\Conditional([
                    'conditionType' => \PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS,
                    'operatorType' => \PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL,
                    'condition' => '"Annulé"',
                    'style' => [
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => 'F8D7DA']
                        ],
                        'font' => [
                            'color' => ['rgb' => '721C24']
                        ]
                    ]
                ])
            );
    }

    private function getTransactionTypeLabel(string $type): string
    {
        return match($type) {
            'admin_to_integrator' => 'Admin → Integrator',
            'integrator_to_operator' => 'Integrator → Operator',
            'recharge' => 'Recharge',
            'refund' => 'Remboursement',
            'commission' => 'Commission',
            default => ucfirst(str_replace('_', ' ', $type))
        };
    }

    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'completed' => 'Terminé',
            'pending' => 'En attente',
            'canceled' => 'Annulé',
            'failed' => 'Échoué',
            default => ucfirst($status)
        };
    }
}
