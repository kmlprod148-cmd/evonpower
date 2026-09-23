<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $transactions;
    protected $walletTransactions;

    public function __construct($transactions, $walletTransactions = [])
    {
        $this->transactions = $transactions;
        $this->walletTransactions = $walletTransactions ?? collect();
    }

    public function collection()
    {
        $all = $this->transactions->map(function ($t) {
            $t->export_category = 'Réservation';
            return $t;
        });

        $walletData = $this->walletTransactions->map(function ($wt) {
            return (object) [
                'id' => $wt->id,
                'transaction_id' => '',
                'transaction_type' => 'wallet',
                'status' => $wt->status,
                'reservation' => null,
                'user' => null,
                'chargingPoint' => null,
                'businessProfile' => null,
                'price_total' => $wt->type === 'credit' ? $wt->amount : -$wt->amount,
                'price_energy' => 0,
                'price_time' => 0,
                'price_service' => 0,
                'price_tax' => 0,
                'activation_fee' => 0,
                'admin_commission' => 0,
                'integrator_commission' => 0,
                'partner_commission' => 0,
                'currency' => $wt->metadata['currency'] ?? 'EUR',
                'payment_method' => '',
                'start_timestamp' => null,
                'stop_timestamp' => null,
                'meter_start' => null,
                'meter_stop' => null,
                'description' => $wt->description ?? $wt->getDescriptiveLabel(),
                'notes' => '',
                'created_at' => $wt->created_at,
                'updated_at' => $wt->updated_at,
                'export_category' => 'Wallet',
            ];
        });

        return $all->merge($walletData)->sortByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'ID Transaction',
            'Type',
            'Statut',
            'Client',
            'Email Client',
            'Point de Charge',
            'Localisation',
            'Business Profile',
            'Montant Total',
            'Prix Énergie',
            'Prix Temps',
            'Prix Service',
            'Taxes',
            'Frais Activation',
            'Commission Admin',
            'Commission Intégrateur',
            'Commission Partenaire',
            'Total Commissions',
            'Montant Net',
            'Devise',
            'Méthode Paiement',
            'Début Session',
            'Fin Session',
            'Durée (minutes)',
            'Compteur Début (kWh)',
            'Compteur Fin (kWh)',
            'Énergie Consommée (kWh)',
            'Description',
            'Notes',
            'Date Création',
            'Date Modification'
        ];
    }

    public function map($transaction): array
    {
        $clientName = 'N/A';
        $clientEmail = 'N/A';
        if ($transaction->reservation && $transaction->reservation->user) {
            $clientName = $transaction->reservation->user->name;
            $clientEmail = $transaction->reservation->user->email;
        }

        $chargingPointName = 'N/A';
        $chargingPointLocation = 'N/A';
        if ($transaction->chargingPoint) {
            $chargingPointName = $transaction->chargingPoint->name;
            $chargingPointLocation = $transaction->chargingPoint->location ?? 'N/A';
        }

        $businessProfileName = 'N/A';
        if ($transaction->businessProfile) {
            $businessProfileName = $transaction->businessProfile->name;
        }

        $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;
        $adminCommission = $transaction->admin_commission ?? 0;
        $integratorCommission = $transaction->integrator_commission ?? 0;
        $partnerCommission = $transaction->partner_commission ?? 0;
        $totalCommissions = $adminCommission + $integratorCommission + $partnerCommission;
        $netAmount = $totalAmount - $totalCommissions;

        $duration = 0;
        if ($transaction->start_timestamp && $transaction->stop_timestamp) {
            $start = \Carbon\Carbon::parse($transaction->start_timestamp);
            $stop = \Carbon\Carbon::parse($transaction->stop_timestamp);
            $duration = $start->diffInMinutes($stop);
        }

        $energyConsumed = 0;
        if ($transaction->meter_start && $transaction->meter_stop) {
            $energyConsumed = $transaction->meter_stop - $transaction->meter_start;
        }

        return [
            $transaction->id,
            $transaction->transaction_id ?? '',
            $transaction->transaction_type ?? 'client',
            $transaction->status ?? '',
            $clientName,
            $clientEmail,
            $chargingPointName,
            $chargingPointLocation,
            $businessProfileName,
            $totalAmount,
            $transaction->price_energy ?? 0,
            $transaction->price_time ?? 0,
            $transaction->price_service ?? 0,
            $transaction->price_tax ?? 0,
            $transaction->activation_fee ?? 0,
            $adminCommission,
            $integratorCommission,
            $partnerCommission,
            $totalCommissions,
            $netAmount,
            $transaction->currency ?? 'EUR',
            $transaction->payment_method ?? '',
            $transaction->start_timestamp ? \Carbon\Carbon::parse($transaction->start_timestamp)->format('Y-m-d H:i:s') : '',
            $transaction->stop_timestamp ? \Carbon\Carbon::parse($transaction->stop_timestamp)->format('Y-m-d H:i:s') : '',
            $duration,
            $transaction->meter_start ?? '',
            $transaction->meter_stop ?? '',
            $energyConsumed,
            $transaction->description ?? '',
            $transaction->notes ?? '',
            $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : '',
            $transaction->updated_at ? $transaction->updated_at->format('Y-m-d H:i:s') : ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour les en-têtes
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 15,  // ID Transaction
            'C' => 15,  // Type
            'D' => 12,  // Statut
            'E' => 20,  // Client
            'F' => 25,  // Email Client
            'G' => 20,  // Point de Charge
            'H' => 25,  // Localisation
            'I' => 20,  // Business Profile
            'J' => 12,  // Montant Total
            'K' => 12,  // Prix Énergie
            'L' => 12,  // Prix Temps
            'M' => 12,  // Prix Service
            'N' => 12,  // Taxes
            'O' => 12,  // Frais Activation
            'P' => 12,  // Commission Admin
            'Q' => 12,  // Commission Intégrateur
            'R' => 12,  // Commission Partenaire
            'S' => 12,  // Total Commissions
            'T' => 12,  // Montant Net
            'U' => 8,   // Devise
            'V' => 15,  // Méthode Paiement
            'W' => 20,  // Début Session
            'X' => 20,  // Fin Session
            'Y' => 12,  // Durée
            'Z' => 12,  // Compteur Début
            'AA' => 12, // Compteur Fin
            'AB' => 12, // Énergie Consommée
            'AC' => 30, // Description
            'AD' => 30, // Notes
            'AE' => 20, // Date Création
            'AF' => 20, // Date Modification
        ];
    }
}
