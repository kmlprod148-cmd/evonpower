<?php

namespace App\Services;

use App\Models\Integrator;
use App\Models\IntegratorContractProfile;
use App\Models\IntegratorBillingLineItem;
use App\Models\IntegratorBillingInvoice;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Integrator Billing Service
 * 
 * Handles all billing operations for integrators including:
 * - Maintenance fee calculation and generation
 * - Terminal fee calculation based on active terminals
 * - Transaction commission calculation
 * - Invoice generation and management
 */
class IntegratorBillingService
{
    /**
     * @var CurrencyService
     */
    protected CurrencyService $currencyService;

    /**
     * Default tax rate (can be configured)
     */
    protected float $defaultTaxRate = 20.0;

    /**
     * Constructor
     */
    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Process maintenance fee for a contract.
     * Creates a billing line item for the maintenance fee.
     */
    public function processMaintenanceFee(IntegratorContractProfile $contract): ?IntegratorBillingLineItem
    {
        if (!$contract->maintenance_fee_enabled || !$contract->isActive()) {
            return null;
        }

        // Check if already processed for current period
        $period = $this->getBillingPeriod($contract->maintenance_fee_period);
        
        $existingItem = IntegratorBillingLineItem::where('integrator_contract_profile_id', $contract->id)
            ->where('type', IntegratorBillingLineItem::TYPE_MAINTENANCE)
            ->where('billing_period_start', $period['start'])
            ->where('billing_period_end', $period['end'])
            ->first();

        if ($existingItem) {
            return $existingItem;
        }

        // Create new line item
        $lineItem = IntegratorBillingLineItem::create([
            'integrator_contract_profile_id' => $contract->id,
            'integrator_id' => $contract->integrator_id,
            'type' => IntegratorBillingLineItem::TYPE_MAINTENANCE,
            'billing_period_start' => $period['start'],
            'billing_period_end' => $period['end'],
            'quantity' => 1,
            'unit_price' => $contract->maintenance_fee_amount,
            'tax_rate' => $this->defaultTaxRate,
            'currency' => $contract->currency,
            'status' => IntegratorBillingLineItem::STATUS_CALCULATED,
            'description' => sprintf(
                'Frais de maintenance - Période %s',
                $period['start']->format('d/m/Y') . ' - ' . $period['end']->format('d/m/Y')
            ),
        ]);

        $lineItem->calculateTotals();
        $lineItem->save();

        // Update next due date
        $nextDueDate = $contract->calculateNextMaintenanceDueDate();
        if ($nextDueDate) {
            $contract->update(['maintenance_fee_next_due_date' => $nextDueDate]);
        }

        Log::info('Maintenance fee processed', [
            'contract_id' => $contract->id,
            'integrator_id' => $contract->integrator_id,
            'amount' => $lineItem->total_amount,
            'period' => $period,
        ]);

        return $lineItem;
    }

    /**
     * Process terminal fee for a contract based on active terminals.
     */
    public function processTerminalFee(IntegratorContractProfile $contract): ?IntegratorBillingLineItem
    {
        if (!$contract->terminal_fee_enabled || !$contract->isActive()) {
            return null;
        }

        // Get active terminal count for integrator
        $activeTerminalCount = $this->getActiveTerminalCount($contract->integrator_id);
        
        // Get previous period for comparison
        $period = $this->getBillingPeriod($contract->terminal_fee_period);

        // Check if already processed for current period
        $existingItem = IntegratorBillingLineItem::where('integrator_contract_profile_id', $contract->id)
            ->where('type', IntegratorBillingLineItem::TYPE_TERMINAL)
            ->where('billing_period_start', $period['start'])
            ->where('billing_period_end', $period['end'])
            ->first();

        if ($existingItem) {
            return $existingItem;
        }

        // Calculate billable terminals
        $billableTerminals = max(0, $activeTerminalCount - $contract->terminal_fee_free_count);
        $billableTerminals = max(0, $billableTerminals - $contract->terminal_fee_minimum);

        if ($billableTerminals <= 0) {
            return null;
        }

        // Create new line item
        $lineItem = IntegratorBillingLineItem::create([
            'integrator_contract_profile_id' => $contract->id,
            'integrator_id' => $contract->integrator_id,
            'type' => IntegratorBillingLineItem::TYPE_TERMINAL,
            'billing_period_start' => $period['start'],
            'billing_period_end' => $period['end'],
            'quantity' => $billableTerminals,
            'unit_price' => $contract->terminal_fee_amount,
            'tax_rate' => $this->defaultTaxRate,
            'currency' => $contract->currency,
            'status' => IntegratorBillingLineItem::STATUS_CALCULATED,
            'terminal_count' => $activeTerminalCount,
            'active_terminal_count' => $activeTerminalCount,
            'description' => sprintf(
                'Frais par borne active (%d bornes) - Période %s',
                $billableTerminals,
                $period['start']->format('d/m/Y') . ' - ' . $period['end']->format('d/m/Y')
            ),
        ]);

        $lineItem->calculateTotals();
        $lineItem->save();

        Log::info('Terminal fee processed', [
            'contract_id' => $contract->id,
            'integrator_id' => $contract->integrator_id,
            'active_terminals' => $activeTerminalCount,
            'billable_terminals' => $billableTerminals,
            'amount' => $lineItem->total_amount,
        ]);

        return $lineItem;
    }

    /**
     * Process transaction commission for a single transaction.
     */
    public function processTransactionCommission(
        Transaction $transaction,
        ?int $userId = null
    ): ?IntegratorBillingLineItem {
        // Get integrator from transaction
        $integratorId = $transaction->integrator_id;
        
        if (!$integratorId) {
            return null;
        }

        // Get active contract for integrator
        $contract = IntegratorContractProfile::where('integrator_id', $integratorId)
            ->active()
            ->where('transaction_commission_enabled', true)
            ->first();

        if (!$contract) {
            return null;
        }

        // Check if commission already processed for this transaction
        $existingItem = IntegratorBillingLineItem::where('integrator_contract_profile_id', $contract->id)
            ->where('type', IntegratorBillingLineItem::TYPE_COMMISSION)
            ->where('transaction_id', $transaction->id)
            ->first();

        if ($existingItem) {
            return $existingItem;
        }

        // Calculate commission
        $commissionAmount = $contract->calculateTransactionCommission($transaction->amount);

        if ($commissionAmount <= 0) {
            return null;
        }

        // Create new line item
        $lineItem = IntegratorBillingLineItem::create([
            'integrator_contract_profile_id' => $contract->id,
            'integrator_id' => $integratorId,
            'type' => IntegratorBillingLineItem::TYPE_COMMISSION,
            'transaction_id' => $transaction->id,
            'billing_period_start' => $transaction->created_at->copy()->startOfDay(),
            'billing_period_end' => $transaction->created_at->copy()->endOfDay(),
            'quantity' => 1,
            'unit_price' => $commissionAmount,
            'tax_rate' => $this->defaultTaxRate,
            'currency' => $contract->currency,
            'status' => IntegratorBillingLineItem::STATUS_CALCULATED,
            'commission_type' => $contract->transaction_commission_type,
            'commission_percentage' => $contract->transaction_commission_percentage,
            'commission_fixed_amount' => $contract->transaction_commission_fixed_amount,
            'transaction_amount' => $transaction->amount,
            'description' => sprintf(
                'Commission sur transaction #%s - Montant: %s',
                $transaction->id,
                $this->currencyService->format($transaction->amount, $contract->currency)
            ),
        ]);

        $lineItem->calculateTotals();
        $lineItem->save();

        Log::info('Transaction commission processed', [
            'contract_id' => $contract->id,
            'integrator_id' => $integratorId,
            'transaction_id' => $transaction->id,
            'transaction_amount' => $transaction->amount,
            'commission_amount' => $commissionAmount,
        ]);

        return $lineItem;
    }

    /**
     * Process all pending commissions for an integrator.
     */
    public function processPendingCommissions(
        Integrator $integrator,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): int {
        $contract = IntegratorContractProfile::where('integrator_id', $integrator->id)
            ->active()
            ->where('transaction_commission_enabled', true)
            ->first();

        if (!$contract) {
            return 0;
        }

        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        // Get transactions not yet processed for commission
        $transactionIds = Transaction::where('integrator_id', $integrator->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->pluck('id');

        // Filter out transactions that already have commission line items
        $processedTransactionIds = IntegratorBillingLineItem::where('integrator_contract_profile_id', $contract->id)
            ->where('type', IntegratorBillingLineItem::TYPE_COMMISSION)
            ->whereIn('transaction_id', $transactionIds)
            ->pluck('transaction_id');

        $transactionIds = $transactionIds->diff($processedTransactionIds);

        $transactions = Transaction::whereIn('id', $transactionIds)->get();

        $processedCount = 0;
        foreach ($transactions as $transaction) {
            $result = $this->processTransactionCommission($transaction);
            if ($result) {
                $processedCount++;
            }
        }

        return $processedCount;
    }

    /**
     * Generate invoice from line items.
     */
    public function generateInvoice(
        IntegratorContractProfile $contract,
        array $lineItemIds,
        string $invoiceType,
        ?Carbon $billingPeriodStart = null,
        ?Carbon $billingPeriodEnd = null
    ): IntegratorBillingInvoice {
        $lineItems = IntegratorBillingLineItem::whereIn('id', $lineItemIds)
            ->where('integrator_contract_profile_id', $contract->id)
            ->where('status', '!=', IntegratorBillingLineItem::STATUS_INVOICED)
            ->get();

        if ($lineItems->isEmpty()) {
            throw new \InvalidArgumentException('No valid line items found for invoice');
        }

        // Determine invoice type prefix
        $typePrefix = match($invoiceType) {
            IntegratorBillingInvoice::TYPE_MAINTENANCE => 'MAINT',
            IntegratorBillingInvoice::TYPE_TERMINAL => 'TERM',
            IntegratorBillingInvoice::TYPE_COMMISSION => 'COMM',
            IntegratorBillingInvoice::TYPE_CONSOLIDATED => 'INV',
            default => 'INV',
        };

        // Determine billing period
        if (!$billingPeriodStart || !$billingPeriodEnd) {
            $billingPeriodStart = $lineItems->min('billing_period_start');
            $billingPeriodEnd = $lineItems->max('billing_period_end');
        }

        // Calculate due date (typically 30 days from issue)
        $issueDate = now()->toDateString();
        $dueDate = now()->addDays(30)->toDateString();

        // Create invoice
        $invoice = IntegratorBillingInvoice::create([
            'integrator_contract_profile_id' => $contract->id,
            'integrator_id' => $contract->integrator_id,
            'invoice_number' => IntegratorBillingInvoice::generateInvoiceNumber($typePrefix),
            'invoice_type' => $invoiceType,
            'status' => IntegratorBillingInvoice::STATUS_PENDING,
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'currency' => $contract->currency,
        ]);

        // Add line items to invoice
        foreach ($lineItems as $item) {
            $invoice->addLineItem($item);
        }

        $invoice->save();

        Log::info('Invoice generated', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'contract_id' => $contract->id,
            'line_items_count' => $lineItems->count(),
            'total_amount' => $invoice->total_amount,
        ]);

        return $invoice;
    }

    /**
     * Generate consolidated invoice for an integrator.
     */
    public function generateConsolidatedInvoice(
        IntegratorContractProfile $contract,
        ?Carbon $billingPeriodStart = null,
        ?Carbon $billingPeriodEnd = null
    ): IntegratorBillingInvoice {
        $billingPeriodStart = $billingPeriodStart ?? now()->startOfMonth();
        $billingPeriodEnd = $billingPeriodEnd ?? now()->endOfMonth();

        // Get all pending line items for the period
        $lineItems = IntegratorBillingLineItem::where('integrator_contract_profile_id', $contract->id)
            ->whereIn('status', [
                IntegratorBillingLineItem::STATUS_CALCULATED,
                IntegratorBillingLineItem::STATUS_PENDING,
            ])
            ->whereBetween('billing_period_start', [$billingPeriodStart, $billingPeriodEnd])
            ->get();

        if ($lineItems->isEmpty()) {
            throw new \InvalidArgumentException('No pending line items found for the billing period');
        }

        return $this->generateInvoice(
            $contract,
            $lineItems->pluck('id')->toArray(),
            IntegratorBillingInvoice::TYPE_CONSOLIDATED,
            $billingPeriodStart,
            $billingPeriodEnd
        );
    }

    /**
     * Process billing for all active contracts.
     */
    public function processAllBilling(?Carbon $processingDate = null): array
    {
        $processingDate = $processingDate ?? now();
        
        $results = [
            'contracts_processed' => 0,
            'maintenance_fees' => 0,
            'terminal_fees' => 0,
            'invoices_generated' => 0,
            'errors' => [],
        ];

        $contracts = IntegratorContractProfile::active()->get();

        foreach ($contracts as $contract) {
            try {
                DB::beginTransaction();

                // Process maintenance fee
                if ($contract->maintenance_fee_enabled) {
                    $maintenanceFee = $this->processMaintenanceFee($contract);
                    if ($maintenanceFee) {
                        $results['maintenance_fees']++;
                    }
                }

                // Process terminal fee
                if ($contract->terminal_fee_enabled) {
                    $terminalFee = $this->processTerminalFee($contract);
                    if ($terminalFee) {
                        $results['terminal_fees']++;
                    }
                }

                $results['contracts_processed']++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $results['errors'][] = [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ];
                
                Log::error('Error processing contract billing', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get active terminal count for an integrator.
     */
    public function getActiveTerminalCount(int $integratorId): int
    {
        return ChargingPoint::where('integrator_id', $integratorId)
            ->where('status', '!=', 'offline')
            ->count();
    }

    /**
     * Get billing period dates based on period type.
     */
    public function getBillingPeriod(string $period): array
    {
        $now = now();
        
        return match($period) {
            IntegratorContractProfile::PERIOD_MONTHLY => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            IntegratorContractProfile::PERIOD_QUARTERLY => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            IntegratorContractProfile::PERIOD_YEARLY => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Get billing summary for an integrator.
     */
    public function getBillingSummary(Integrator $integrator, ?int $year = null, ?int $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $contract = IntegratorContractProfile::where('integrator_id', $integrator->id)
            ->active()
            ->first();

        $summary = [
            'contract' => $contract ? [
                'id' => $contract->id,
                'name' => $contract->name,
                'status' => $contract->status,
                'currency' => $contract->currency,
            ] : null,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'active_terminals' => $contract ? $this->getActiveTerminalCount($integrator->id) : 0,
            'maintenance_fee' => [
                'enabled' => $contract?->maintenance_fee_enabled ?? false,
                'amount' => $contract?->maintenance_fee_amount ?? 0,
                'period' => $contract?->maintenance_fee_period,
            ],
            'terminal_fee' => [
                'enabled' => $contract?->terminal_fee_enabled ?? false,
                'amount' => $contract?->terminal_fee_amount ?? 0,
                'free_count' => $contract?->terminal_fee_free_count ?? 0,
                'minimum' => $contract?->terminal_fee_minimum ?? 0,
            ],
            'transaction_commission' => [
                'enabled' => $contract?->transaction_commission_enabled ?? false,
                'type' => $contract?->transaction_commission_type,
                'percentage' => $contract?->transaction_commission_percentage ?? 0,
                'fixed_amount' => $contract?->transaction_commission_fixed_amount ?? 0,
            ],
            'invoices' => [],
            'totals' => [
                'maintenance' => 0,
                'terminal' => 0,
                'commission' => 0,
                'total' => 0,
            ],
        ];

        if ($contract) {
            // Get invoices for the period
            $invoices = IntegratorBillingInvoice::where('integrator_contract_profile_id', $contract->id)
                ->whereBetween('billing_period_start', [$startDate, $endDate])
                ->get();

            foreach ($invoices as $invoice) {
                $summary['invoices'][] = [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'type' => $invoice->invoice_type,
                    'status' => $invoice->status,
                    'total_amount' => $invoice->total_amount,
                    'currency' => $invoice->currency,
                ];

                $summary['totals']['total'] += $invoice->total_amount;
            }

            // Get line items for the period
            $lineItems = IntegratorBillingLineItem::where('integrator_contract_profile_id', $contract->id)
                ->whereBetween('billing_period_start', [$startDate, $endDate])
                ->whereIn('status', [
                    IntegratorBillingLineItem::STATUS_CALCULATED,
                    IntegratorBillingLineItem::STATUS_INVOICED,
                    IntegratorBillingLineItem::STATUS_PAID,
                ])
                ->get();

            foreach ($lineItems as $item) {
                switch ($item->type) {
                    case IntegratorBillingLineItem::TYPE_MAINTENANCE:
                        $summary['totals']['maintenance'] += $item->total_amount;
                        break;
                    case IntegratorBillingLineItem::TYPE_TERMINAL:
                        $summary['totals']['terminal'] += $item->total_amount;
                        break;
                    case IntegratorBillingLineItem::TYPE_COMMISSION:
                        $summary['totals']['commission'] += $item->total_amount;
                        break;
                }
            }
        }

        return $summary;
    }
}
