<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\CommissionPlan;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommissionReportService
{
    /**
     * Génère un rapport de commissions pour une période donnée.
     *
     * @param string $startDate Format Y-m-d
     * @param string $endDate Format Y-m-d
     * @param array $filters Filtres additionnels
     * @return array
     */
    public function generateReport(string $startDate, string $endDate, array $filters = [])
    {
        $query = Transaction::with(['chargingPoint', 'station', 'user', 'commissionPlan', 'tariffPlan'])->whereBetween('start_timestamp', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
        
        // Appliquer les filtres
        $this->applyFilters($query, $filters);
        
        // Récupérer les transactions
        $transactions = $query->get();
        
        // Calculer les totaux
        $totalTransactions = $transactions->count();
        $totalAmount = $transactions->sum('price_total');
        $totalAdminCommission = $transactions->sum('admin_commission');
        $totalIntegratorCommission = $transactions->sum('integrator_commission');
        $totalPartnerCommission = $transactions->sum('partner_commission');
        
        // Calculer les commissions payées et impayées
        $paidAdminCommission = $transactions->where('admin_commission_paid', true)->sum('admin_commission');
        $paidIntegratorCommission = $transactions->where('integrator_commission_paid', true)->sum('integrator_commission');
        $paidPartnerCommission = $transactions->where('partner_commission_paid', true)->sum('partner_commission');
        
        $unpaidAdminCommission = $totalAdminCommission - $paidAdminCommission;
        $unpaidIntegratorCommission = $totalIntegratorCommission - $paidIntegratorCommission;
        $unpaidPartnerCommission = $totalPartnerCommission - $paidPartnerCommission;
        
        // Préparer le rapport
        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_amount' => $totalAmount,
                'total_commissions' => [
                    'admin' => $totalAdminCommission,
                    'integrator' => $totalIntegratorCommission,
                    'partner' => $totalPartnerCommission,
                    'total' => $totalAdminCommission + $totalIntegratorCommission + $totalPartnerCommission,
                ],
                'paid_commissions' => [
                    'admin' => $paidAdminCommission,
                    'integrator' => $paidIntegratorCommission,
                    'partner' => $paidPartnerCommission,
                    'total' => $paidAdminCommission + $paidIntegratorCommission + $paidPartnerCommission,
                ],
                'unpaid_commissions' => [
                    'admin' => $unpaidAdminCommission,
                    'integrator' => $unpaidIntegratorCommission,
                    'partner' => $unpaidPartnerCommission,
                    'total' => $unpaidAdminCommission + $unpaidIntegratorCommission + $unpaidPartnerCommission,
                ],
            ],
            'details' => $this->generateDetailedReport($transactions, $filters),
        ];
        
        return $report;
    }
    
    /**
     * Applique des filtres à la requête de transactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters($query, array $filters)
    {
        // Filtre par intégrateur
        if (isset($filters['integrator_id'])) {
            $query->whereHas('chargingPoint', function ($q) use ($filters) {
                $q->where('integrator_id', $filters['integrator_id']);
            });
        }
        
        // Filtre par partenaire
        if (isset($filters['partner_id'])) {
            $query->whereHas('chargingPoint', function ($q) use ($filters) {
                $q->where('partner_id', $filters['partner_id']);
            });
        }
        
        // Filtre par groupe
        if (isset($filters['group_id'])) {
            $query->whereHas('chargingPoint', function ($q) use ($filters) {
                $q->where('group_id', $filters['group_id']);
            });
        }
        
        // Filtre par borne
        if (isset($filters['charging_point_id'])) {
            $query->where('charging_point_id', $filters['charging_point_id']);
        }
        
        // Filtre par plan de commission
        if (isset($filters['commission_plan_id'])) {
            $query->where('commission_plan_id', $filters['commission_plan_id']);
        }
        
        // Filtre par statut de paiement des commissions
        if (isset($filters['commission_paid_status'])) {
            $status = $filters['commission_paid_status'];
            $type = $filters['commission_type'] ?? 'all';
            
            if ($type === 'admin' || $type === 'all') {
                $query->where('admin_commission_paid', $status === 'paid');
            }
            
            if ($type === 'integrator' || $type === 'all') {
                $query->where('integrator_commission_paid', $status === 'paid');
            }
            
            if ($type === 'partner' || $type === 'all') {
                $query->where('partner_commission_paid', $status === 'paid');
            }
        }
    }
    
    /**
     * Génère un rapport détaillé basé sur les transactions.
     *
     * @param Collection $transactions
     * @param array $filters
     * @return array
     */
    protected function generateDetailedReport(Collection $transactions, array $filters)
    {
        $groupBy = $filters['group_by'] ?? 'day';
        $details = [];
        
        switch ($groupBy) {
            case 'day':
                $details = $this->groupByDay($transactions);
                break;
                
            case 'week':
                $details = $this->groupByWeek($transactions);
                break;
                
            case 'month':
                $details = $this->groupByMonth($transactions);
                break;
                
            case 'integrator':
                $details = $this->groupByIntegrator($transactions);
                break;
                
            case 'partner':
                $details = $this->groupByPartner($transactions);
                break;
                
            case 'commission_plan':
                $details = $this->groupByCommissionPlan($transactions);
                break;
                
            default:
                $details = $this->groupByDay($transactions);
        }
        
        return $details;
    }
    
    /**
     * Groupe les transactions par jour.
     *
     * @param Collection $transactions
     * @return array
     */
    protected function groupByDay(Collection $transactions)
    {
        $grouped = $transactions->groupBy(function ($transaction) {
            return Carbon::parse($transaction->start_timestamp)->format('Y-m-d');
        });
        
        return $this->formatGroupedData($grouped);
    }
    
    /**
     * Groupe les transactions par semaine.
     *
     * @param Collection $transactions
     * @return array
     */
    protected function groupByWeek(Collection $transactions)
    {
        $grouped = $transactions->groupBy(function ($transaction) {
            $date = Carbon::parse($transaction->start_timestamp);
            return $date->year . '-W' . $date->week;
        });
        
        return $this->formatGroupedData($grouped);
    }
    
    /**
     * Groupe les transactions par mois.
     *
     * @param Collection $transactions
     * @return array
     */
    protected function groupByMonth(Collection $transactions)
    {
        $grouped = $transactions->groupBy(function ($transaction) {
            return Carbon::parse($transaction->start_timestamp)->format('Y-m');
        });
        
        return $this->formatGroupedData($grouped);
    }
    
    /**
     * Groupe les transactions par intégrateur.
     *
     * @param Collection $transactions
     * @return array
     */
    protected function groupByIntegrator(Collection $transactions)
    {
        $grouped = $transactions->groupBy(function ($transaction) {
            $chargingPoint = $transaction->chargingPoint;
            if (!$chargingPoint || !$chargingPoint->integrator) {
                return 'unknown';
            }
            return $chargingPoint->integrator->id . ' - ' . $chargingPoint->integrator->name;
        });
        
        return $this->formatGroupedData($grouped);
    }
    
    /**
     * Groupe les transactions par partenaire.
     *
     * @param Collection $transactions
     * @return array
     */
    protected function groupByPartner(Collection $transactions)
    {
        $grouped = $transactions->groupBy(function ($transaction) {
            $chargingPoint = $transaction->chargingPoint;
            if (!$chargingPoint || !$chargingPoint->partner) {
                return 'unknown';
            }
            return $chargingPoint->partner->id . ' - ' . $chargingPoint->partner->name;
        });
        
        return $this->formatGroupedData($grouped);
    }
    
    /**
     * Groupe les transactions par plan de commission.
     *
     * @param Collection $transactions
     * @return array
     */
    protected function groupByCommissionPlan(Collection $transactions)
    {
        $grouped = $transactions->groupBy(function ($transaction) {
            if (!$transaction->commissionPlan) {
                return 'no_plan';
            }
            return $transaction->commissionPlan->id . ' - ' . $transaction->commissionPlan->name;
        });
        
        return $this->formatGroupedData($grouped);
    }
    
    /**
     * Formate les données groupées en un format standard pour le rapport.
     *
     * @param Collection $grouped
     * @return array
     */
    protected function formatGroupedData(Collection $grouped)
    {
        $result = [];
        
        foreach ($grouped as $key => $group) {
            $result[$key] = [
                'total_transactions' => $group->count(),
                'total_amount' => $group->sum('price_total'),
                'commissions' => [
                    'admin' => $group->sum('admin_commission'),
                    'integrator' => $group->sum('integrator_commission'),
                    'partner' => $group->sum('partner_commission'),
                    'total' => $group->sum('admin_commission') + $group->sum('integrator_commission') + $group->sum('partner_commission'),
                ],
                'paid_commissions' => [
                    'admin' => $group->where('admin_commission_paid', true)->sum('admin_commission'),
                    'integrator' => $group->where('integrator_commission_paid', true)->sum('integrator_commission'),
                    'partner' => $group->where('partner_commission_paid', true)->sum('partner_commission'),
                ],
                'unpaid_commissions' => [
                    'admin' => $group->where('admin_commission_paid', false)->sum('admin_commission'),
                    'integrator' => $group->where('integrator_commission_paid', false)->sum('integrator_commission'),
                    'partner' => $group->where('partner_commission_paid', false)->sum('partner_commission'),
                ],
            ];
        }
        
        return $result;
    }
    
    /**
     * Génère un rapport des commissions impayées.
     *
     * @param string $type 'admin', 'integrator', 'partner', ou 'all'
     * @param array $filters Filtres additionnels
     * @return array
     */
    public function generateUnpaidCommissionsReport(string $type = 'all', array $filters = [])
    {
        $query = Transaction::where(function ($q) use ($type) {
            if ($type === 'admin' || $type === 'all') {
                $q->orWhere('admin_commission_paid', false);
            }
            
            if ($type === 'integrator' || $type === 'all') {
                $q->orWhere('integrator_commission_paid', false);
            }
            
            if ($type === 'partner' || $type === 'all') {
                $q->orWhere('partner_commission_paid', false);
            }
        });
        
        // Appliquer les filtres
        $this->applyFilters($query, $filters);
        
        // Récupérer les transactions
        $transactions = $query->get();
        
        // Préparer le rapport
        $report = [
            'summary' => [
                'total_transactions' => $transactions->count(),
                'total_unpaid_commissions' => [
                    'admin' => $type === 'admin' || $type === 'all' ? $transactions->sum('admin_commission') : 0,
                    'integrator' => $type === 'integrator' || $type === 'all' ? $transactions->sum('integrator_commission') : 0,
                    'partner' => $type === 'partner' || $type === 'all' ? $transactions->sum('partner_commission') : 0,
                ],
            ],
            'details' => $this->generateDetailedReport($transactions, $filters),
        ];
        
        // Calculer le total
        $report['summary']['total_unpaid_commissions']['total'] = 
            $report['summary']['total_unpaid_commissions']['admin'] +
            $report['summary']['total_unpaid_commissions']['integrator'] +
            $report['summary']['total_unpaid_commissions']['partner'];
        
        return $report;
    }
    
    /**
     * Exporte les données de commissions au format CSV.
     *
     * @param Collection $transactions
     * @return string
     */
    public function exportToCsv(Collection $transactions)
    {
        $headers = [
            'ID Transaction',
            'Date',
            'Borne',
            'Intégrateur',
            'Partenaire',
            'Montant Total',
            'Commission Admin',
            'Commission Intégrateur',
            'Commission Partenaire',
            'Admin Payé',
            'Intégrateur Payé',
            'Partenaire Payé',
            'Date Paiement Admin',
            'Date Paiement Intégrateur',
            'Date Paiement Partenaire',
            'Plan de Commission',
        ];
        
        $csv = implode(',', $headers) . "\n";
        
        foreach ($transactions as $transaction) {
            $chargingPoint = $transaction->chargingPoint;
            $integrator = $chargingPoint ? $chargingPoint->integrator : null;
            $partner = $chargingPoint ? $chargingPoint->partner : null;
            $commissionPlan = $transaction->commissionPlan;
            
            $row = [
                $transaction->id,
                $transaction->start_timestamp ? Carbon::parse($transaction->start_timestamp)->format('Y-m-d H:i:s') : '',
                $chargingPoint ? $chargingPoint->name : '',
                $integrator ? $integrator->name : '',
                $partner ? $partner->name : '',
                $transaction->price_total,
                $transaction->admin_commission,
                $transaction->integrator_commission,
                $transaction->partner_commission,
                $transaction->admin_commission_paid ? 'Oui' : 'Non',
                $transaction->integrator_commission_paid ? 'Oui' : 'Non',
                $transaction->partner_commission_paid ? 'Oui' : 'Non',
                $transaction->admin_commission_paid_at ? Carbon::parse($transaction->admin_commission_paid_at)->format('Y-m-d') : '',
                $transaction->integrator_commission_paid_at ? Carbon::parse($transaction->integrator_commission_paid_at)->format('Y-m-d') : '',
                $transaction->partner_commission_paid_at ? Carbon::parse($transaction->partner_commission_paid_at)->format('Y-m-d') : '',
                $commissionPlan ? $commissionPlan->name : '',
            ];
            
            $csv .= implode(',', array_map(function ($value) {
                // Échapper les virgules et les guillemets
                if (is_string($value) && (strpos($value, ',') !== false || strpos($value, '"') !== false)) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }
                return $value;
            }, $row)) . "\n";
        }
        
        return $csv;
    }
}