<?php

namespace App\Examples;

use App\Models\Transaction;
use App\Services\TransactionCalculatorService;
use Illuminate\Support\Facades\Log;

/**
 * Example usage of TransactionCalculatorService
 * 
 * This example demonstrates how to use the TransactionCalculatorService
 * to process transactions with hierarchical fee calculations.
 */
class TransactionCalculatorUsageExample
{
    protected $transactionCalculatorService;

    public function __construct(TransactionCalculatorService $transactionCalculatorService)
    {
        $this->transactionCalculatorService = $transactionCalculatorService;
    }

    /**
     * Process a single transaction
     */
    public function processTransaction(Transaction $transaction): array
    {
        try {
            $result = $this->transactionCalculatorService->process($transaction);
            
            Log::info('Transaction processed successfully', [
                'transaction_id' => $transaction->id,
                'calculation' => $result['calculation']
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Transaction processing failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Process multiple transactions in batch
     */
    public function processBatch(array $transactions): array
    {
        $results = [];
        $errors = [];

        foreach ($transactions as $transaction) {
            try {
                $result = $this->processTransaction($transaction);
                $results[] = $result;
            } catch (\Exception $e) {
                $errors[] = [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'successful' => $results,
            'failed' => $errors,
            'total_processed' => count($transactions),
            'success_count' => count($results),
            'error_count' => count($errors)
        ];
    }

    /**
     * Get transaction calculation summary
     */
    public function getCalculationSummary(Transaction $transaction): array
    {
        $result = $this->transactionCalculatorService->process($transaction);
        
        return [
            'transaction_id' => $transaction->id,
            'amount_ht' => $result['calculation']['total_paid_ht'],
            'operator_share' => $result['calculation']['operator_share'],
            'integrator_share' => $result['calculation']['integrator_share'],
            'admin_share' => $result['calculation']['admin_share'],
            'integrator_fee' => $result['calculation']['integrator_fee'],
            'admin_fee' => $result['calculation']['admin_fee'],
            'repartition_id' => $result['repartition']->id,
            'is_balanced' => $result['repartition']->isBalanced()
        ];
    }
}
