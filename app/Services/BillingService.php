<?php

namespace App\Services;

use App\Models\BillingPlan;
use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    /**
     * Créer une facture pour un plan de facturation
     */
    public function createInvoice(BillingPlan $plan): ?BillingInvoice
    {
        try {
            DB::beginTransaction();

            // Vérifier si le plan est actif
            if (!$plan->isActiveAt(now())) {
                throw new \Exception("Le plan de facturation n'est pas actif");
            }

            // Calculer les dates de période
            $periodStart = $this->calculatePeriodStart($plan);
            $periodEnd = $this->calculatePeriodEnd($plan, $periodStart);
            $dueDate = $this->calculateDueDate($plan, $periodEnd);

            // Créer la facture
            $invoice = BillingInvoice::create([
                'invoice_number' => BillingInvoice::generateInvoiceNumber(),
                'billing_plan_id' => $plan->id,
                'billable_type' => $plan->billable_type,
                'billable_id' => $plan->billable_id,
                'billing_cycle_id' => $plan->billing_cycle_id,
                'amount' => $plan->amount,
                'tax_amount' => $plan->tax_amount,
                'total_amount' => $plan->amount_with_tax,
                'currency' => $plan->currency,
                'status' => 'pending',
                'billing_period_start' => $periodStart,
                'billing_period_end' => $periodEnd,
                'due_date' => $dueDate,
                'created_by' => auth()->id() ?? 1, // Admin par défaut
            ]);

            DB::commit();

            Log::info("Facture créée", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'plan_id' => $plan->id,
                'amount' => $invoice->total_amount
            ]);

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur création facture", [
                'plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Vérifier si une facture existe déjà pour une période donnée
     */
    public function getExistingInvoiceForPeriod(
        BillingPlan $plan, 
        Carbon $startDate, 
        Carbon $endDate
    ): ?BillingInvoice {
        return BillingInvoice::where('billing_plan_id', $plan->id)
            ->where('billable_type', $plan->billable_type)
            ->where('billable_id', $plan->billable_id)
            ->whereBetween('billing_period_start', [$startDate, $endDate])
            ->orWhereBetween('billing_period_end', [$startDate, $endDate])
            ->first();
    }

    /**
     * Traiter le paiement d'une facture
     */
    public function processPayment(
        BillingInvoice $invoice,
        string $paymentMethod,
        string $paymentReference = null,
        array $gatewayResponse = []
    ): BillingPayment {
        try {
            DB::beginTransaction();

            $payment = BillingPayment::create([
                'billing_invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'gateway_response' => $gatewayResponse,
                'status' => 'completed',
                'paid_at' => now(),
                'created_by' => auth()->id() ?? 1,
            ]);

            // Marquer la facture comme payée
            $invoice->markAsPaid($paymentMethod, $paymentReference);

            DB::commit();

            Log::info("Paiement traité", [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $payment->amount
            ]);

            return $payment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur traitement paiement", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Marquer les factures en retard
     */
    public function markOverdueInvoices(): int
    {
        $overdueInvoices = BillingInvoice::overdueByDate()->get();
        $count = 0;

        foreach ($overdueInvoices as $invoice) {
            $invoice->markAsOverdue();
            $count++;
        }

        Log::info("Factures marquées en retard", ['count' => $count]);
        return $count;
    }

    /**
     * Calculer le début de la période de facturation
     */
    private function calculatePeriodStart(BillingPlan $plan): Carbon
    {
        $cycle = $plan->billingCycle;
        
        return match($cycle->frequency) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            'yearly' => now()->startOfYear(),
            default => now()->startOfDay()
        };
    }

    /**
     * Calculer la fin de la période de facturation
     */
    private function calculatePeriodEnd(BillingPlan $plan, Carbon $startDate): Carbon
    {
        $cycle = $plan->billingCycle;
        
        return match($cycle->frequency) {
            'daily' => $startDate->copy()->addDays($cycle->interval)->endOfDay(),
            'weekly' => $startDate->copy()->addWeeks($cycle->interval)->endOfWeek(),
            'monthly' => $startDate->copy()->addMonths($cycle->interval)->endOfMonth(),
            'yearly' => $startDate->copy()->addYears($cycle->interval)->endOfYear(),
            default => $startDate->copy()->addDay()->endOfDay()
        };
    }

    /**
     * Calculer la date d'échéance
     */
    private function calculateDueDate(BillingPlan $plan, Carbon $periodEnd): Carbon
    {
        // Par défaut, échéance 30 jours après la fin de période
        return $periodEnd->copy()->addDays(30);
    }

    /**
     * Obtenir les statistiques de facturation
     */
    public function getBillingStats(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $invoices = BillingInvoice::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_invoices' => $invoices->count(),
            'pending_invoices' => $invoices->clone()->pending()->count(),
            'paid_invoices' => $invoices->clone()->paid()->count(),
            'overdue_invoices' => $invoices->clone()->overdue()->count(),
            'total_amount' => $invoices->clone()->sum('total_amount'),
            'paid_amount' => $invoices->clone()->paid()->sum('total_amount'),
            'pending_amount' => $invoices->clone()->pending()->sum('total_amount'),
        ];
    }

    /**
     * Générer un rapport de facturation
     */
    public function generateReport(Carbon $startDate, Carbon $endDate, string $format = 'array'): array
    {
        $invoices = BillingInvoice::with(['billingPlan', 'billable', 'payments'])
            ->whereBetween('billing_period_start', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'summary' => $this->getBillingStats($startDate, $endDate),
            'invoices' => $invoices->map(function($invoice) {
                return [
                    'invoice_number' => $invoice->invoice_number,
                    'billable' => $invoice->billable_type . ' #' . $invoice->billable_id,
                    'amount' => $invoice->total_amount,
                    'status' => $invoice->status_label,
                    'due_date' => $invoice->due_date->format('Y-m-d'),
                    'created_at' => $invoice->created_at->format('Y-m-d H:i:s')
                ];
            })
        ];

        return $report;
    }
}
