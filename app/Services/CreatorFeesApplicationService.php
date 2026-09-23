<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatorFeesApplicationService
{
    protected $chargingPointFeeCalculationService;

    public function __construct(ChargingPointFeeCalculationService $chargingPointFeeCalculationService)
    {
        $this->chargingPointFeeCalculationService = $chargingPointFeeCalculationService;
    }

    /**
     * Apply creator fees to all existing transactions that don't have them
     */
    public function applyCreatorFeesToExistingTransactions(): array
    {
        $stats = [
            'total_transactions' => 0,
            'processed_transactions' => 0,
            'successful_applications' => 0,
            'failed_applications' => 0,
            'errors' => []
        ];

        try {
            DB::beginTransaction();

            // Get all transactions that don't have creator fees applied
            $transactions = Transaction::whereNull('creator_fees_applied_at')
                ->orWhere('creator_fees_total', 0)
                ->where('transaction_type', 'client')
                ->where('status', 'completed')
                ->with(['chargingPoint'])
                ->get();

            $stats['total_transactions'] = $transactions->count();

            foreach ($transactions as $transaction) {
                $stats['processed_transactions']++;

                try {
                    if ($this->applyCreatorFeesToTransaction($transaction)) {
                        $stats['successful_applications']++;
                    } else {
                        $stats['failed_applications']++;
                    }
                } catch (\Exception $e) {
                    $stats['failed_applications']++;
                    $stats['errors'][] = [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ];

                    Log::error('Error applying creator fees to transaction', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            Log::info('Creator fees application completed', $stats);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $stats['errors'][] = [
                'transaction_id' => 'general',
                'error' => $e->getMessage()
            ];

            Log::error('Error in bulk creator fees application', [
                'error' => $e->getMessage(),
                'stats' => $stats
            ]);
        }

        return $stats;
    }

    /**
     * Apply creator fees to a specific transaction
     */
    public function applyCreatorFeesToTransaction(Transaction $transaction): bool
    {
        try {
            // Skip if no charging point
            if (!$transaction->chargingPoint) {
                Log::warning('Transaction has no charging point', [
                    'transaction_id' => $transaction->id
                ]);
                return false;
            }

            // Calculate creator fees
            $baseAmount = $transaction->price_total ?? 0;
            $creatorFees = $this->chargingPointFeeCalculationService->calculateChargingPointCreatorFees(
                $transaction->chargingPoint, 
                $baseAmount
            );

            // Extract fee amounts
            $chargingFees = $creatorFees['charging_fees']['total'] ?? 0;
            $transactionFees = $creatorFees['transaction_fees']['total'] ?? 0;
            $activationFees = $creatorFees['activation_fees']['total'] ?? 0;
            $adminFees = $creatorFees['admin_fees']['total'] ?? 0;
            $totalCreatorFees = $chargingFees + $transactionFees + $activationFees + $adminFees;

            // Update transaction with creator fees
            $transaction->update([
                'creator_charging_fees' => $chargingFees,
                'creator_transaction_fees' => $transactionFees,
                'creator_activation_fees' => $activationFees,
                'creator_admin_fees' => $adminFees,
                'creator_fees_total' => $totalCreatorFees,
                'creator_fees_applied_at' => now(),
                'creator_fees_source' => $creatorFees['creator_info']['type'] ?? 'unknown',
                'business_profile_fee_breakdown' => $creatorFees,
                // Update total price to include creator fees
                'price_total' => $baseAmount + $totalCreatorFees
            ]);

            Log::info('Creator fees applied to transaction', [
                'transaction_id' => $transaction->id,
                'charging_point_id' => $transaction->chargingPoint->id,
                'base_amount' => $baseAmount,
                'total_creator_fees' => $totalCreatorFees,
                'final_price_total' => $transaction->price_total
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error applying creator fees to transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Recalculate creator fees for all transactions
     */
    public function recalculateCreatorFeesForAllTransactions(): array
    {
        $stats = [
            'total_transactions' => 0,
            'processed_transactions' => 0,
            'successful_recalculations' => 0,
            'failed_recalculations' => 0,
            'errors' => []
        ];

        try {
            DB::beginTransaction();

            // Get all client transactions
            $transactions = Transaction::where('transaction_type', 'client')
                ->where('status', 'completed')
                ->with(['chargingPoint'])
                ->get();

            $stats['total_transactions'] = $transactions->count();

            foreach ($transactions as $transaction) {
                $stats['processed_transactions']++;

                try {
                    if ($this->applyCreatorFeesToTransaction($transaction)) {
                        $stats['successful_recalculations']++;
                    } else {
                        $stats['failed_recalculations']++;
                    }
                } catch (\Exception $e) {
                    $stats['failed_recalculations']++;
                    $stats['errors'][] = [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            Log::info('Creator fees recalculation completed', $stats);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $stats['errors'][] = [
                'transaction_id' => 'general',
                'error' => $e->getMessage()
            ];

            Log::error('Error in bulk creator fees recalculation', [
                'error' => $e->getMessage(),
                'stats' => $stats
            ]);
        }

        return $stats;
    }

    /**
     * Get creator fees statistics
     */
    public function getCreatorFeesStatistics(): array
    {
        $stats = [
            'total_transactions_with_creator_fees' => Transaction::where('creator_fees_total', '>', 0)->count(),
            'total_transactions_without_creator_fees' => Transaction::where('creator_fees_total', 0)->orWhereNull('creator_fees_total')->count(),
            'total_creator_fees_amount' => Transaction::where('creator_fees_total', '>', 0)->sum('creator_fees_total'),
            'average_creator_fees_per_transaction' => Transaction::where('creator_fees_total', '>', 0)->avg('creator_fees_total'),
            'creator_fees_by_source' => [],
            'creator_fees_by_month' => []
        ];

        // Get creator fees by source
        $creatorFeesBySource = Transaction::where('creator_fees_total', '>', 0)
            ->selectRaw('creator_fees_source, COUNT(*) as count, SUM(creator_fees_total) as total_amount')
            ->groupBy('creator_fees_source')
            ->get();

        foreach ($creatorFeesBySource as $source) {
            $stats['creator_fees_by_source'][$source->creator_fees_source] = [
                'count' => $source->count,
                'total_amount' => (float) $source->total_amount,
                'average_amount' => (float) $source->total_amount / $source->count
            ];
        }

        // Get creator fees by month (database-agnostic)
        $driver = \DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' 
            ? "strftime('%Y-%m', creator_fees_applied_at)" 
            : "DATE_FORMAT(creator_fees_applied_at, '%Y-%m')";
            
        $creatorFeesByMonth = Transaction::where('creator_fees_total', '>', 0)
            ->selectRaw($dateFormat . ' as month, COUNT(*) as count, SUM(creator_fees_total) as total_amount')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        foreach ($creatorFeesByMonth as $month) {
            $stats['creator_fees_by_month'][$month->month] = [
                'count' => $month->count,
                'total_amount' => (float) $month->total_amount,
                'average_amount' => (float) $month->total_amount / $month->count
            ];
        }

        return $stats;
    }
}
