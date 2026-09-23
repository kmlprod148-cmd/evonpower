<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TransactionsExport;

class TransactionExportController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->middleware('auth');
        $this->transactionService = $transactionService;
    }

    /**
     * Exporter les transactions
     */
    public function export(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }
            
            $filters = $request->only([
                'transaction_type',
                'transaction_category',
                'business_profile_id',
                'business_profile_owner_type',
                'start_date',
                'end_date',
                'status',
                'format'
            ]);

            $query = Transaction::query();

            if ($user->hasRole('admin')) {
                // Admin voit toutes les transactions
            } elseif ($user->hasRole('integrator')) {
                $integratorId = $user->integrator_id ?? $user->id;
                $query->where(function($q) use ($user, $integratorId) {
                    $q->where('integrator_id', $integratorId)
                      ->orWhereHas('chargingPoint', function($cpQuery) use ($integratorId) {
                          $cpQuery->where('integrator_id', $integratorId);
                      })
                      ->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                          $cpQuery->whereHas('group', function($gQuery) use ($user) {
                              $gQuery->where('integrator_id', $user->integrator_id ?? $user->id);
                          });
                      })
                      ->orWhereHas('reservation', function($resQuery) use ($user) {
                          $resQuery->whereHas('chargingPoint', function($cpQuery) use ($user) {
                              $cpQuery->where('integrator_id', $user->integrator_id ?? $user->id);
                          });
                      })
                      ->orWhereHas('reservation.user', function($resUserQuery) use ($user) {
                          $resUserQuery->where('integrator_id', $user->integrator_id ?? $user->id);
                      });
                });
            } elseif ($user->hasRole('partner')) {
                $query->whereHas('chargingPoint', function ($q) use ($user) {
                    $q->where('partner_id', $user->partner_id);
                });
            } elseif ($user->hasRole('operator')) {
                $query->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                          $cpQuery->where(function($cpSubQuery) use ($user) {
                              $cpSubQuery->where('created_by', $user->id)
                                        ->orWhere('created_by_id', $user->id)
                                        ->orWhere('user_id', $user->id);
                          });
                      });
                });
            } else {
                $query->whereHas('reservation', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            if (!empty($filters['transaction_type'])) {
                $query->where('transaction_type', $filters['transaction_type']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['business_profile_id'])) {
                $query->where('business_profile_id', $filters['business_profile_id']);
            }

            if (!empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            $transactions = $query->with([
                'chargingPoint',
                'reservation.user',
                'businessProfile'
            ])->get();

            $walletTransactions = collect();
            if ($user->hasRole('integrator')) {
                $integratorId = $user->integrator_id ?? $user->id;
                $walletTransactions = \App\Models\Wallet::getIntegratorScopeTransactions($integratorId);
                
                if (!empty($filters['start_date'])) {
                    $walletTransactions = $walletTransactions->filter(function ($t) use ($filters) {
                        return $t->created_at->gte($filters['start_date']);
                    });
                }
                if (!empty($filters['end_date'])) {
                    $walletTransactions = $walletTransactions->filter(function ($t) use ($filters) {
                        return $t->created_at->lte($filters['end_date']);
                    });
                }
            }

            $format = $filters['format'] ?? 'csv';

            switch ($format) {
                case 'excel':
                    return $this->exportExcel($transactions, $walletTransactions, $filters);
                case 'pdf':
                    return $this->exportPdf($transactions, $walletTransactions, $filters);
                case 'csv':
                default:
                    return $this->exportCsv($transactions, $walletTransactions, $filters);
            }

        } catch (\Exception $e) {
            Log::error('Error exporting transactions: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'export des transactions: ' . $e->getMessage());
        }
    }

    /**
     * Export CSV
     */
    private function exportCsv($transactions, $walletTransactions = collect(), $filters = [])
    {
        $filename = 'transactions_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transactions, $walletTransactions) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'ID',
                'Catégorie',
                'Type',
                'Statut',
                'Client',
                'Point de Charge',
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
                'Devise',
                'Méthode Paiement',
                'Début Session',
                'Fin Session',
                'Compteur Début',
                'Compteur Fin',
                'Description',
                'Date Création',
                'Date Modification'
            ]);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    'Réservation',
                    $transaction->transaction_type ?? 'client',
                    $transaction->status ?? '',
                    $transaction->reservation?->user?->name ?? 'N/A',
                    $transaction->chargingPoint?->name ?? 'N/A',
                    $transaction->businessProfile?->name ?? 'N/A',
                    $transaction->price_total ?? $transaction->amount ?? 0,
                    $transaction->price_energy ?? 0,
                    $transaction->price_time ?? 0,
                    $transaction->price_service ?? 0,
                    $transaction->price_tax ?? 0,
                    $transaction->activation_fee ?? 0,
                    $transaction->admin_commission ?? 0,
                    $transaction->integrator_commission ?? 0,
                    $transaction->partner_commission ?? 0,
                    $transaction->currency ?? 'EUR',
                    $transaction->payment_method ?? '',
                    $transaction->start_timestamp ?? '',
                    $transaction->stop_timestamp ?? '',
                    $transaction->meter_start ?? '',
                    $transaction->meter_stop ?? '',
                    $transaction->description ?? '',
                    $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : '',
                    $transaction->updated_at ? $transaction->updated_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            foreach ($walletTransactions as $wt) {
                fputcsv($file, [
                    $wt->id,
                    'Wallet',
                    $wt->type,
                    $wt->status,
                    '',
                    '',
                    '',
                    $wt->type === 'credit' ? $wt->amount : -$wt->amount,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    $wt->metadata['currency'] ?? 'EUR',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $wt->description ?? $wt->getDescriptiveLabel(),
                    $wt->created_at ? $wt->created_at->format('Y-m-d H:i:s') : '',
                    $wt->updated_at ? $wt->updated_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Excel
     */
    private function exportExcel($transactions, $walletTransactions = collect(), $filters = [])
    {
        $filename = 'transactions_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new TransactionsExport($transactions, $walletTransactions), $filename);
    }

    /**
     * Export PDF
     */
    private function exportPdf($transactions, $walletTransactions = collect(), $filters = [])
    {
        $filename = 'transactions_' . date('Y-m-d_H-i-s') . '.pdf';
        
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('transactions.export-pdf', compact('transactions', 'walletTransactions', 'filters'));
        
        return $pdf->download($filename);
    }

    /**
     * Statistiques d'export
     */
    public function exportStats(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Vérifier que l'utilisateur est authentifié
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être connecté pour accéder à cette page.'
                ], 401);
            }
            
            $filters = $request->only([
                'start_date',
                'end_date',
                'business_profile_id'
            ]);

            $stats = $this->transactionService->getExportStatistics($filters, $user);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting export statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
}
