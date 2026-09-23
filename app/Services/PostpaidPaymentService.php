<?php

namespace App\Services;

use App\Enums\PostpaidStatus;
use App\Models\User;
use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\Transaction;
use App\Models\BillingInvoice;
use App\Models\BillingInvoiceItem;
use App\Exceptions\PostpaidCharging\InsufficientBalanceException;
use App\Exceptions\PostpaidCharging\PostpaidChargingException;
use App\Exceptions\PostpaidCharging\ChargingPointUnavailableException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service de gestion du mode Postpayé avec suivi de crédit
 * 
 * Fonctionnalités:
 * - Autorisation des utilisateurs pour le postpayé
 * - Suivi du crédit utilisé
 * - Facturation différée
 * - Génération automatique des factures
 * - Gestion des limites de crédit
 */
class PostpaidPaymentService
{
    /**
     * Facteur de sécurité pour le calcul du crédit disponible
     */
    public const CREDIT_SAFETY_FACTOR = 0.8;

    /**
     * Jour de facturation par défaut (fin de mois)
     */
    public const DEFAULT_BILLING_DAY = 30;

    /**
     * Délai de paiement par défaut (jours)
     */
    public const DEFAULT_PAYMENT_DUE_DAYS = 30;

    public function __construct(
        protected TransactionService $transactionService,
        protected MoneyService $moneyService,
        protected BillingService $billingService
    ) {}

    /**
     * Vérifier si un utilisateur est autorisé pour le postpayé
     */
    public function isUserAuthorized(User $user): bool
    {
        return $user->postpaid_status === PostpaidStatus::APPROVED->value;
    }

    /**
     * Obtenir le crédit disponible pour un utilisateur
     */
    public function getAvailableCredit(User $user): float
    {
        $creditLimit = $user->postpaid_credit_limit ?? 0;
        $currentUsage = $this->getCurrentUsage($user);
        
        $available = $creditLimit - $currentUsage;
        
        // Appliquer le facteur de sécurité
        return max(0, $available * self::CREDIT_SAFETY_FACTOR);
    }

    /**
     * Obtenir l'utilisation actuelle du crédit
     */
    public function getCurrentUsage(User $user): float
    {
        // Somme des factures impayées
        $unpaidInvoices = BillingInvoice::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->get();

        return $unpaidInvoices->sum('total_amount');
    }

    /**
     * Autoriser un utilisateur pour le postpayé
     */
    public function authorizeUser(User $user, float $creditLimit, ?User $approvedBy = null): User
    {
        return DB::transaction(function () use ($user, $creditLimit, $approvedBy) {
            $user->update([
                'postpaid_status' => PostpaidStatus::APPROVED->value,
                'postpaid_credit_limit' => $creditLimit,
                'postpaid_approved_at' => now(),
                'postpaid_approved_by' => $approvedBy?->id,
                'postpaid_billing_day' => self::DEFAULT_BILLING_DAY,
            ]);

            Log::info('PostpaidPaymentService: Utilisateur autorisé pour le postpayé', [
                'user_id' => $user->id,
                'credit_limit' => $creditLimit,
                'approved_by' => $approvedBy?->id,
            ]);

            return $user;
        });
    }

    /**
     * Révoquer l'autorisation postpayé d'un utilisateur
     */
    public function revokeUser(User $user, string $reason, ?User $revokedBy = null): User
    {
        return DB::transaction(function () use ($user, $reason, $revokedBy) {
            $user->update([
                'postpaid_status' => PostpaidStatus::REVOKED->value,
                'postpaid_revoked_at' => now(),
                'postpaid_revoked_by' => $revokedBy?->id,
                'postpaid_revocation_reason' => $reason,
            ]);

            Log::info('PostpaidPaymentService: Autorisation postpayé révoquée', [
                'user_id' => $user->id,
                'reason' => $reason,
                'revoked_by' => $revokedBy?->id,
            ]);

            return $user;
        });
    }

    /**
     * Suspendre temporairement l'autorisation postpayé
     */
    public function suspendUser(User $user, string $reason): User
    {
        return DB::transaction(function () use ($user, $reason) {
            $user->update([
                'postpaid_status' => PostpaidStatus::SUSPENDED->value,
                'postpaid_suspended_at' => now(),
                'postpaid_suspension_reason' => $reason,
            ]);

            Log::info('PostpaidPaymentService: Utilisateur postpayé suspendu', [
                'user_id' => $user->id,
                'reason' => $reason,
            ]);

            return $user;
        });
    }

    /**
     * Réactiver l'autorisation postpayé
     */
    public function reactivateUser(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $user->update([
                'postpaid_status' => PostpaidStatus::APPROVED->value,
                'postpaid_suspended_at' => null,
                'postpaid_suspension_reason' => null,
            ]);

            Log::info('PostpaidPaymentService: Utilisateur postpayé réactivé', [
                'user_id' => $user->id,
            ]);

            return $user;
        });
    }

    /**
     * Vérifier si l'utilisateur peut démarrer une session postpayé
     */
    public function canStartSession(User $user, float $estimatedCost): bool
    {
        if (!$this->isUserAuthorized($user)) {
            return false;
        }

        $availableCredit = $this->getAvailableCredit($user);
        
        return $availableCredit >= $estimatedCost;
    }

    /**
     * Démarrer une session de charge en mode postpayé
     * 
     * @throws InsufficientBalanceException
     * @throws ChargingPointUnavailableException
     */
    public function startPostpaidSession(
        User $user,
        Reservation $reservation,
        ChargingSession $session
    ): ChargingSession {
        if (!$this->isUserAuthorized($user)) {
            throw new PostpaidChargingException('Utilisateur non autorisé pour le mode postpayé');
        }

        $estimatedCost = $reservation->estimated_cost ?? $this->estimateSessionCost($session);

        if (!$this->canStartSession($user, $estimatedCost)) {
            throw new InsufficientBalanceException(
                'Crédit postpayé insuffisant. Crédit disponible: ' . 
                number_format($this->getAvailableCredit($user), 2) . ' EUR'
            );
        }

        // Marquer la session comme postpayé
        $session->update([
            'payment_method' => 'postpaid',
            'payment_status' => 'postpaid_pending',
            'postpaid_authorized_at' => now(),
            'estimated_cost' => $estimatedCost,
        ]);

        // Réserver le crédit estimé (pending)
        $this->reserveCredit($user, $estimatedCost, $session);

        Log::info('PostpaidPaymentService: Session postpayé démarrée', [
            'user_id' => $user->id,
            'session_id' => $session->id,
            'estimated_cost' => $estimatedCost,
        ]);

        return $session;
    }

    /**
     * Finaliser une session de charge postpayée
     * 
     * Cette méthode est appelée à la fin de la session de charge
     * pour calculer le coût réel et créer la transaction
     */
    public function finalizeSession(
        ChargingSession $session,
        float $actualCost,
        float $energyKwh,
        int $durationMinutes
    ): array {
        return DB::transaction(function () use ($session, $actualCost, $energyKwh, $durationMinutes) {
            $user = $session->user;
            
            if (!$user) {
                throw new PostpaidChargingException('Utilisateur non trouvé pour cette session');
            }

            // Libérer la réservation précédente
            $previousReserved = $session->estimated_cost ?? 0;
            $this->releaseReservedCredit($user, $previousReserved, $session);

            // Créer la transaction
            $transaction = $this->createPostpaidTransaction(
                $session,
                $actualCost,
                $energyKwh,
                $durationMinutes
            );

            // Mettre à jour le coût réel dans la session
            $session->update([
                'actual_cost' => $actualCost,
                'actual_energy' => $energyKwh,
                'actual_duration' => $durationMinutes,
                'payment_status' => 'postpaid_completed',
            ]);

            // Mettre à jour la réservation
            if ($session->reservation) {
                $session->reservation->update([
                    'actual_cost' => $actualCost,
                    'actual_energy' => $energyKwh,
                    'actual_duration' => $durationMinutes,
                    'payment_status' => 'postpaid_to_invoice',
                ]);
            }

            Log::info('PostpaidPaymentService: Session postpayé finalisée', [
                'session_id' => $session->id,
                'actual_cost' => $actualCost,
                'transaction_id' => $transaction->id,
            ]);

            return [
                'success' => true,
                'session_id' => $session->id,
                'transaction_id' => $transaction->id,
                'actual_cost' => $actualCost,
                'energy_kwh' => $energyKwh,
                'duration_minutes' => $durationMinutes,
            ];
        });
    }

    /**
     * Créer une facture pour les sessions postpayées
     */
    public function createInvoice(User $user, array $sessions): BillingInvoice
    {
        return DB::transaction(function () use ($user, $sessions) {
            $totalAmount = collect($sessions)->sum('actual_cost');
            
            // Créer la facture
            $invoice = BillingInvoice::create([
                'user_id' => $user->id,
                'invoice_number' => $this->generateInvoiceNumber($user),
                'status' => 'pending',
                'issue_date' => now(),
                'due_date' => now()->addDays(self::DEFAULT_PAYMENT_DUE_DAYS),
                'subtotal_amount' => $totalAmount,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'EUR',
                'metadata' => [
                    'billing_type' => 'postpaid',
                    'sessions_count' => count($sessions),
                ],
            ]);

            // Créer les lignes de facture
            foreach ($sessions as $sessionData) {
                $session = ChargingSession::find($sessionData['session_id']);
                
                BillingInvoiceItem::create([
                    'billing_invoice_id' => $invoice->id,
                    'item_type' => ChargingSession::class,
                    'item_id' => $session->id,
                    'description' => "Session de recharge - {$session->chargingPoint->name}",
                    'quantity' => 1,
                    'unit_price' => $sessionData['actual_cost'],
                    'total_price' => $sessionData['actual_cost'],
                    'metadata' => [
                        'charging_point_id' => $session->charging_point_id,
                        'energy_kwh' => $session->actual_energy,
                        'duration_minutes' => $session->actual_duration,
                    ],
                ]);
            }

            Log::info('PostpaidPaymentService: Facture créée', [
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'sessions_count' => count($sessions),
            ]);

            return $invoice;
        });
    }

    /**
     * Générer les factures mensuelles pour les utilisateurs postpayés
     */
    public function generateMonthlyInvoices(): int
    {
        $users = User::where('postpaid_status', PostpaidStatus::APPROVED->value)
            ->where('postpaid_billing_day', now()->day)
            ->get();

        $invoicesCount = 0;

        foreach ($users as $user) {
            // Récupérer les sessions non facturées
            $unbilledSessions = ChargingSession::where('user_id', $user->id)
                ->where('payment_status', 'postpaid_completed')
                ->whereDoesntHave('billingInvoiceItems')
                ->get();

            if ($unbilledSessions->isNotEmpty()) {
                $this->createInvoice($user, $unbilledSessions->toArray());
                $invoicesCount++;
            }
        }

        Log::info('PostpaidPaymentService: Factures mensuelles générées', [
            'invoices_count' => $invoicesCount,
        ]);

        return $invoicesCount;
    }

    /**
     * Réserver du crédit pour une session
     */
    protected function reserveCredit(User $user, float $amount, ChargingSession $session): void
    {
        // Créer une transaction en attente
        Transaction::create([
            'user_id' => $user->id,
            'charging_point_id' => $session->charging_point_id,
            'charging_session_id' => $session->id,
            'transaction_id' => 'POSTPAID_RESERVE_' . $session->id . '_' . time(),
            'amount' => $amount,
            'currency' => 'EUR',
            'status' => 'pending',
            'type' => 'postpaid_reserve',
            'metadata' => [
                'postpaid_type' => 'reservation',
                'reserved_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Libérer le crédit réservé
     */
    protected function releaseReservedCredit(User $user, float $amount, ChargingSession $session): void
    {
        if ($amount <= 0) {
            return;
        }

        // Mettre à jour la transaction de réservation
        Transaction::where('charging_session_id', $session->id)
            ->where('type', 'postpaid_reserve')
            ->where('status', 'pending')
            ->update([
                'status' => 'released',
                'metadata->released_at' => now()->toISOString(),
            ]);
    }

    /**
     * Créer une transaction postpayée
     */
    protected function createPostpaidTransaction(
        ChargingSession $session,
        float $actualCost,
        float $energyKwh,
        int $durationMinutes
    ): Transaction {
        $user = $session->user;
        
        // Créer la transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'charging_point_id' => $session->charging_point_id,
            'charging_session_id' => $session->id,
            'reservation_id' => $session->reservation_id,
            'transaction_id' => 'POSTPAID_' . $session->id . '_' . time(),
            'amount' => $actualCost,
            'price_total' => $actualCost,
            'currency' => 'EUR',
            'status' => 'completed',
            'type' => 'postpaid_charge',
            'energy_delivered' => $energyKwh,
            'duration' => $durationMinutes,
            'metadata' => [
                'postpaid_type' => 'charge',
                'estimated_cost' => $session->estimated_cost,
                'charged_at' => now()->toISOString(),
            ],
            'start_timestamp' => $session->start_timestamp,
            'stop_timestamp' => $session->stopped_at,
        ]);

        return $transaction;
    }

    /**
     * Estimer le coût d'une session
     */
    protected function estimateSessionCost(ChargingSession $session): float
    {
        $plan = $session->pricingPlan;
        
        if (!$plan) {
            return 25.00; // Estimation par défaut
        }

        // Estimer sur 1 heure
        $estimatedMinutes = 60;
        $estimatedKwh = 22;
        
        return ($plan->activation_fee ?? 0) 
            + ($estimatedKwh * ($plan->price_per_kwh ?? 0))
            + ($estimatedMinutes * ($plan->price_per_minute ?? 0) / 60);
    }

    /**
     * Générer un numéro de facture
     */
    protected function generateInvoiceNumber(User $user): string
    {
        $prefix = 'POSTPAID';
        $year = date('Y');
        $month = date('m');
        $sequence = str_pad(BillingInvoice::count() + 1, 6, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}{$month}-{$sequence}";
    }

    /**
     * Obtenir l'historique d'utilisation du postpayé
     */
    public function getUsageHistory(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ChargingSession::where('user_id', $user->id)
            ->where('payment_method', 'postpaid');

        if ($startDate) {
            $query->where('start_timestamp', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('start_timestamp', '<=', $endDate);
        }

        $sessions = $query->orderBy('start_timestamp', 'desc')->get();

        return [
            'sessions' => $sessions->map(fn($s) => [
                'id' => $s->id,
                'charging_point' => $s->chargingPoint->name ?? 'N/A',
                'start_timestamp' => $s->start_timestamp,
                'stopped_at' => $s->stopped_at,
                'energy_kwh' => $s->actual_energy,
                'duration_minutes' => $s->actual_duration,
                'cost' => $s->actual_cost,
            ]),
            'total_sessions' => $sessions->count(),
            'total_energy_kwh' => $sessions->sum('actual_energy'),
            'total_cost' => $sessions->sum('actual_cost'),
        ];
    }
}
