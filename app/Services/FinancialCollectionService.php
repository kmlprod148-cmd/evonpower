<?php

namespace App\Services;

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Jobs\SendPaymentReminder;
use App\Jobs\ProcessPaymentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service de Collecte Financière
 * 
 * Fonctionnalités:
 * - Gestion des encaissements
 * - Gestion des factures
 * - Rappels de paiement
 * - Intégration passerelles de paiement
 * - Tableau de bord analytique
 * - Rapports financiers
 */
class FinancialCollectionService
{
    /**
     * Délai avant premier rappel (jours après échéance)
     */
    public const FIRST_REMINDER_DELAY = 3;

    /**
     * Délai avant deuxième rappel (jours après échéance)
     */
    public const SECOND_REMINDER_DELAY = 7;

    /**
     * Délai avant mise en retard (jours après échéance)
     */
    public const OVERDUE_DELAY = 14;

    public function __construct(
        protected MoneyService $moneyService,
        protected StripePaymentService $stripeService
    ) {}

    /**
     * Créer une facture pour un utilisateur
     */
    public function createInvoice(User $user, array $data): BillingInvoice
    {
        return DB::transaction(function () use ($user, $data) {
            $invoice = BillingInvoice::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'billable_type' => User::class,
                'billable_id' => $user->id,
                'amount' => $data['amount'],
                'tax_amount' => $data['tax_amount'] ?? 0,
                'total_amount' => $data['total_amount'] ?? $data['amount'],
                'currency' => $data['currency'] ?? 'EUR',
                'status' => 'pending',
                'billing_period_start' => $data['period_start'] ?? now()->startOfMonth(),
                'billing_period_end' => $data['period_end'] ?? now()->endOfMonth(),
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            Log::info('FinancialCollectionService: Facture créée', [
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'amount' => $invoice->total_amount,
            ]);

            return $invoice;
        });
    }

    /**
     * Traiter un paiement
     */
    public function processPayment(
        BillingInvoice $invoice,
        string $paymentMethod,
        array $paymentData = []
    ): BillingPayment {
        return DB::transaction(function () use ($invoice, $paymentMethod, $paymentData) {
            $payment = BillingPayment::create([
                'billing_invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentData['reference'] ?? null,
                'gateway_response' => $paymentData['gateway_response'] ?? [],
                'status' => 'pending',
                'created_by' => $paymentData['created_by'] ?? auth()->id(),
            ]);

            // Traiter selon la méthode de paiement
            $result = $this->processPaymentByMethod($payment, $paymentMethod, $paymentData);

            if ($result['success']) {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'payment_reference' => $result['reference'] ?? $payment->payment_reference,
                    'gateway_response' => $result['data'] ?? [],
                ]);

                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $result['reference'] ?? null,
                ]);

                Log::info('FinancialCollectionService: Paiement réussi', [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                ]);
            } else {
                $payment->update([
                    'status' => 'failed',
                    'gateway_response' => ['error' => $result['error']],
                ]);

                Log::error('FinancialCollectionService: Paiement échoué', [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'error' => $result['error'],
                ]);
            }

            return $payment;
        });
    }

    /**
     * Traiter le paiement selon la méthode
     */
    protected function processPaymentByMethod(
        BillingPayment $payment,
        string $method,
        array $data
    ): array {
        return match ($method) {
            'stripe' => $this->processStripePayment($payment, $data),
            'card' => $this->processCardPayment($payment, $data),
            'bank_transfer' => $this->processBankTransfer($payment, $data),
            'wallet' => $this->processWalletPayment($payment, $data),
            default => ['success' => false, 'error' => 'Méthode de paiement non supportée'],
        };
    }

    /**
     * Traiter paiement Stripe
     */
    protected function processStripePayment(BillingPayment $payment, array $data): array
    {
        try {
            $invoice = $payment->billingInvoice;
            
            // Créer un PaymentIntent Stripe
            $stripeResult = $this->stripeService->createPaymentIntent(
                $invoice->total_amount,
                $invoice->currency,
                [
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'billing_invoice_id' => $invoice->id,
                    ],
                ]
            );

            return [
                'success' => true,
                'reference' => $stripeResult['id'] ?? null,
                'data' => $stripeResult,
            ];

        } catch (\Exception $e) {
            Log::error('FinancialCollectionService: Erreur Stripe', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Traiter paiement par carte (hors ligne)
     */
    protected function processCardPayment(BillingPayment $payment, array $data): array
    {
        // Simulation - en production, intégration avec plateforme de paiement
        return [
            'success' => true,
            'reference' => 'CARD_' . time() . '_' . rand(1000, 9999),
            'data' => [
                'card_last4' => $data['card_last4'] ?? '****',
                'processed_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Traiter virement bancaire
     */
    protected function processBankTransfer(BillingPayment $payment, array $data): array
    {
        return [
            'success' => true,
            'reference' => 'BT_' . time() . '_' . rand(1000, 9999),
            'data' => [
                'bank_reference' => $data['bank_reference'] ?? null,
                'processed_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Traiter paiement par wallet
     */
    protected function processWalletPayment(BillingPayment $payment, array $data): array
    {
        try {
            $invoice = $payment->billingInvoice;
            $user = $invoice->billable;

            if (!$user || !$user->wallet) {
                return ['success' => false, 'error' => 'Utilisateur ou wallet non trouvé'];
            }

            $wallet = $user->wallet;

            if (!$wallet->hasSufficientBalance($invoice->total_amount)) {
                return ['success' => false, 'error' => 'Solde insuffisant'];
            }

            // Débiter le wallet
            $wallet->debit(
                $invoice->total_amount,
                "Paiement facture {$invoice->invoice_number}",
                [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'payment_type' => 'invoice',
                ]
            );

            return [
                'success' => true,
                'reference' => 'WALLET_' . $payment->id,
                'data' => [
                    'wallet_transaction_id' => $wallet->transactions()->latest()->first()?->id,
                    'processed_at' => now()->toISOString(),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('FinancialCollectionService: Erreur wallet', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer un rappel de paiement
     */
    public function sendPaymentReminder(BillingInvoice $invoice): bool
    {
        $user = $invoice->billable;
        
        if (!$user) {
            return false;
        }

        // Créer une notification de rappel
        $reminderNumber = $this->getReminderNumber($invoice);

        // Job asynchrone pour l'envoi
        SendPaymentReminder::dispatch($invoice, $reminderNumber);

        // Enregistrer le rappel
        $this->logReminder($invoice, $reminderNumber);

        Log::info('FinancialCollectionService: Rappel envoyé', [
            'invoice_id' => $invoice->id,
            'reminder_number' => $reminderNumber,
        ]);

        return true;
    }

    /**
     * Traiter les rappels automatiques
     */
    public function processAutomaticReminders(): int
    {
        $count = 0;

        // Factures en retard nécessitant un rappel
        $overdueInvoices = BillingInvoice::where('status', 'pending')
            ->where('due_date', '<', now()->subDays(self::FIRST_REMINDER_DELAY))
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $reminderNumber = $this->getReminderNumber($invoice);
            
            // Vérifier si le rappel a déjà été envoyé récemment
            $lastReminder = $invoice->metadata['last_reminder_sent'] ?? null;
            if ($lastReminder && Carbon::parse($lastReminder)->isAfter(now()->subDays(3))) {
                continue;
            }

            $this->sendPaymentReminder($invoice);
            $count++;
        }

        Log::info('FinancialCollectionService: Rappels automatiques traités', [
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Marquer les factures en retard
     */
    public function markOverdueInvoices(): int
    {
        $count = BillingInvoice::where('status', 'pending')
            ->where('due_date', '<', now()->subDays(self::OVERDUE_DELAY))
            ->update(['status' => 'overdue']);

        Log::info('FinancialCollectionService: Factures marquées en retard', [
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Obtenir le tableau de bord analytique
     */
    public function getAnalyticsDashboard(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        // Statistiques des factures
        $invoices = BillingInvoice::whereBetween('created_at', [$startDate, $endDate]);
        $payments = BillingPayment::whereBetween('created_at', [$startDate, $endDate]);

        // Statistiques des transactions wallet
        $walletCredits = WalletTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'credit');
        $walletDebits = WalletTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'debit');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'invoices' => [
                'total_count' => $invoices->count(),
                'pending_count' => (clone $invoices)->pending()->count(),
                'paid_count' => (clone $invoices)->paid()->count(),
                'overdue_count' => (clone $invoices)->overdue()->count(),
                'total_amount' => (clone $invoices)->sum('total_amount'),
                'paid_amount' => (clone $invoices)->paid()->sum('total_amount'),
                'pending_amount' => (clone $invoices)->pending()->sum('total_amount'),
                'overdue_amount' => (clone $invoices)->overdue()->sum('total_amount'),
            ],
            'payments' => [
                'total_count' => $payments->count(),
                'completed_count' => (clone $payments)->completed()->count(),
                'failed_count' => (clone $payments)->failed()->count(),
                'total_collected' => (clone $payments)->completed()->sum('amount'),
            ],
            'wallet' => [
                'total_credits' => $walletCredits->sum('amount'),
                'total_debits' => $walletDebits->sum('amount'),
                'net' => $walletCredits->sum('amount') - $walletDebits->sum('amount'),
            ],
            'collection_rate' => $this->calculateCollectionRate($startDate, $endDate),
            'average_invoice_amount' => $this->calculateAverageInvoiceAmount($startDate, $endDate),
            'top_debtors' => $this->getTopDebtors($startDate, $endDate, 5),
        ];
    }

    /**
     * Générer un rapport financier
     */
    public function generateFinancialReport(
        Carbon $startDate,
        Carbon $endDate,
        string $groupBy = 'day'
    ): array {
        $invoices = BillingInvoice::whereBetween('billing_period_start', [$startDate, $endDate])
            ->with(['payments', 'billable'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Grouper les données
        $grouped = $invoices->groupBy(function ($invoice) use ($groupBy) {
            return match ($groupBy) {
                'day' => $invoice->created_at->format('Y-m-d'),
                'week' => $invoice->created_at->format('Y-W'),
                'month' => $invoice->created_at->format('Y-m'),
                'year' => $invoice->created_at->format('Y'),
                default => $invoice->created_at->format('Y-m-d'),
            };
        });

        $report = [];
        foreach ($grouped as $period => $periodInvoices) {
            $report[] = [
                'period' => $period,
                'invoices_count' => $periodInvoices->count(),
                'total_amount' => $periodInvoices->sum('total_amount'),
                'paid_amount' => $periodInvoices->where('status', 'paid')->sum('total_amount'),
                'pending_amount' => $periodInvoices->where('status', 'pending')->sum('total_amount'),
                'overdue_amount' => $periodInvoices->where('status', 'overdue')->sum('total_amount'),
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'group_by' => $groupBy,
            ],
            'summary' => [
                'total_invoices' => $invoices->count(),
                'total_amount' => $invoices->sum('total_amount'),
                'paid_amount' => $invoices->where('status', 'paid')->sum('total_amount'),
                'pending_amount' => $invoices->where('status', 'pending')->sum('total_amount'),
                'overdue_amount' => $invoices->where('status', 'overdue')->sum('total_amount'),
            ],
            'data' => $report,
        ];
    }

    /**
     * Obtenir les factures impayées d'un utilisateur
     */
    public function getUnpaidInvoices(User $user): array
    {
        $invoices = BillingInvoice::where('billable_type', User::class)
            ->where('billable_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        return $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date,
                'status' => $invoice->status,
                'days_overdue' => $invoice->due_date->diffInDays(now(), false),
                'can_pay' => $invoice->status === 'pending' || $invoice->status === 'overdue',
            ];
        })->toArray();
    }

    // ========================================
    // Méthodes privées
    // ========================================

    /**
     * Générer un numéro de facture
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->year;
        $month = now()->format('m');
        $sequence = str_pad(BillingInvoice::count() + 1, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}{$month}-{$sequence}";
    }

    /**
     * Obtenir le numéro de rappel
     */
    protected function getReminderNumber(BillingInvoice $invoice): int
    {
        $daysOverdue = now()->diffInDays($invoice->due_date, false);
        
        if ($daysOverdue >= self::SECOND_REMINDER_DELAY) {
            return 3;
        } elseif ($daysOverdue >= self::FIRST_REMINDER_DELAY) {
            return 2;
        }
        
        return 1;
    }

    /**
     * Enregistrer le rappel envoyé
     */
    protected function logReminder(BillingInvoice $invoice, int $reminderNumber): void
    {
        $reminders = $invoice->metadata['reminders_sent'] ?? [];
        $reminders[] = [
            'number' => $reminderNumber,
            'sent_at' => now()->toISOString(),
        ];

        $invoice->update([
            'metadata' => array_merge($invoice->metadata ?? [], [
                'reminders_sent' => $reminders,
                'last_reminder_sent' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Calculer le taux d'encaissement
     */
    protected function calculateCollectionRate(Carbon $startDate, Carbon $endDate): float
    {
        $total = BillingInvoice::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
        
        if ($total == 0) {
            return 0;
        }

        $paid = BillingInvoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('total_amount');

        return round(($paid / $total) * 100, 2);
    }

    /**
     * Calculer le montant moyen des factures
     */
    protected function calculateAverageInvoiceAmount(Carbon $startDate, Carbon $endDate): float
    {
        return BillingInvoice::whereBetween('created_at', [$startDate, $endDate])
            ->avg('total_amount') ?? 0;
    }

    /**
     * Obtenir les plus gros débiteurs
     */
    protected function getTopDebtors(Carbon $startDate, Carbon $endDate, int $limit): array
    {
        return BillingInvoice::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'overdue'])
            ->groupBy('billable_id')
            ->selectRaw('billable_id, SUM(total_amount) as total_due')
            ->orderByDesc('total_due')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->billable_id);
                return [
                    'user_id' => $item->billable_id,
                    'user_name' => $user?->name,
                    'user_email' => $user?->email,
                    'total_due' => $item->total_due,
                ];
            })
            ->toArray();
    }
}
