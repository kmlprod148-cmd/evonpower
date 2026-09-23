<?php
namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'estimated_cost',
        'actual_cost',
        'estimated_energy',
        'actual_energy',
        'estimated_duration',
        'actual_duration',
        'notes',
        'user_id',
        'charging_point_id',
        'connector_id', // OCPP
        'ocpp_tag_id', // OCPP
        'pricing_plan_id',
        'confirmed_at',
        'approved_at', // OCPP
        'approved_by', // OCPP
        'start_time',
        'end_time',
        'amount',
        'reservation_type',
        'reservation_value',
        'max_duration',
        'max_energy',
        'max_kwh', // OCPP
        'max_minutes', // OCPP
        'payment_status', // OCPP: PENDING, PAID, FAILED, REFUNDED
        'payment_type',
        'order_id',
        'energy_kwh',
        'duration_minutes',
        'guest_email',
        'guest_phone',
        'payment_mode',
        'prepaid_amount',
        'refund_amount',
        'min_threshold',
        'wallet_validation_passed',
        'wallet_validated_at',
        'payment_method',
        // Guest checkout fields
        'is_guest',
        'guest_info',
        'integrator_id',
        'partner_id',
        'transaction_id',
        'ocpp_reservation_id', // OCPP reservation ID from charger
        'charging_session_id',
        'actual_start_time',
        'actual_end_time',
        'connector_locked',
        'connector_locked_at',
        'connector_unlocked_at',
        'lock_operation_status',
        'lock_operation_attempts',
        'lock_operation_last_attempt_at',
        'payment_confirmation_expires_at',
        'payment_confirmed_at',
        'payment_gateway_transaction_id',
        'payment_webhook_received_at',
        'session_initiated_at',
        'session_initiation_status',
        'start_initiation_status',
        'remote_start_sent_at',
        'transaction_received_at',
        'start_failure_reason',
        'expires_at',
        'expiration_processed_at',
        'ocpp_operations_log',
        'last_error',
        'last_error_at',
    ];
    
    protected $casts = [
        'status' => ReservationStatus::class,
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'estimated_energy' => 'decimal:2',
        'actual_energy' => 'decimal:2',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'confirmed_at' => 'datetime',
        'approved_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'amount' => 'decimal:2',
        'prepaid_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'min_threshold' => 'decimal:2',
        'max_kwh' => 'decimal:2',
        'wallet_validation_passed' => 'boolean',
        'wallet_validated_at' => 'datetime',
        // Guest checkout casts
        'is_guest' => 'boolean',
        'guest_info' => 'array',
        'metadata' => 'array',
        'charging_session_id' => 'integer',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'connector_locked' => 'boolean',
        'connector_locked_at' => 'datetime',
        'connector_unlocked_at' => 'datetime',
        'lock_operation_attempts' => 'integer',
        'lock_operation_last_attempt_at' => 'datetime',
        'payment_confirmation_expires_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
        'payment_webhook_received_at' => 'datetime',
        'session_initiated_at' => 'datetime',
        'expires_at' => 'datetime',
        'expiration_processed_at' => 'datetime',
        'ocpp_operations_log' => 'array',
        'last_error_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le point de charge
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    /**
     * Relation avec le plan tarifaire
     */
    public function pricingPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class);
    }

    /**
     * Relation avec le plan tarifaire (alias pour compatibilité)
     */
    public function tariffPlan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    /**
     * Relation avec le connecteur OCPP
     */
    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    /**
     * Relation avec le tag OCPP
     */
    public function ocppTag(): BelongsTo
    {
        return $this->belongsTo(OcppTag::class);
    }

    /**
     * Relation avec l'approbateur (admin qui a approuvé)
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relation avec la transaction
     * La relation utilise reservation_id dans transactions, pas transaction_id dans reservations
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'reservation_id', 'id');
    }

    /**
     * Relation avec les sessions de charge
     */
    public function chargingSessions()
    {
        return $this->hasMany(ChargingSession::class);
    }

    /**
     * Relation avec la session de charge active suivie sur la réservation.
     */
    public function activeChargingSession(): BelongsTo
    {
        return $this->belongsTo(ChargingSession::class, 'charging_session_id');
    }

    /**
     * Relation avec les logs de démarrage automatique.
     */
    public function autoRemoteStartLogs(): HasMany
    {
        return $this->hasMany(AutoRemoteStartLog::class);
    }

    /**
     * Dernier log de démarrage automatique connu.
     */
    public function latestAutoRemoteStartLog(): HasOne
    {
        return $this->hasOne(AutoRemoteStartLog::class)->latestOfMany('processed_at');
    }

    /**
     * Participants et parts pour cette réservation
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ReservationParticipant::class);
    }

    /**
     * Relation avec les détails de transaction
     */
    public function transactionDetails()
    {
        return $this->hasOneThrough(
            TransactionDetail::class,
            Transaction::class,
            'reservation_id', // Foreign key on transactions table
            'transaction_id', // Foreign key on transaction_details table
            'id', // Local key on reservations table
            'id' // Local key on transactions table
        );
    }

    /**
     * Corriger le statut de la transaction et de la réservation si payée (PAID) mais encore "pending".
     * Prépayé et postpayé - garantit "Confirmé/Approuvé" et non "En attente".
     */
    public function ensureTransactionConfirmedIfPaid(): bool
    {
        // Vérifier en base si payée (payment_status PAID, prepaid_amount > 0, payment_method prepaid_credit, ou prepaid+credit+estimated_cost>0)
        $row = \Illuminate\Support\Facades\DB::table('reservations')
            ->where('id', $this->id)
            ->first();
        if (!$row) {
            return false;
        }
        $paymentStatus = strtoupper((string) ($row->payment_status ?? ''));
        $paymentMethod = strtolower((string) ($row->payment_method ?? ''));
        $prepaidAmount = (float) ($row->prepaid_amount ?? 0);
        $paymentMode = $row->payment_mode ?? '';
        $estimatedCost = (float) ($row->estimated_cost ?? 0);

        $isPaid = in_array($paymentStatus, ['PAID', 'PAYE'])
            || ($paymentMode === 'prepaid' && $prepaidAmount > 0)
            || in_array($paymentMethod, ['prepaid_credit', 'postpaid_credit'])
            || ($paymentMode === 'prepaid' && $paymentMethod === 'credit' && $estimatedCost > 0);

        if (!$isPaid) {
            return false;
        }

        $synced = false;
        $now = now();

        // 0. Si prepaid+credit+estimated_cost>0 mais payment_status != PAID : normaliser UNIQUEMENT si le wallet a été débité
        $walletWasDebited = false;
        if ($paymentMode === 'prepaid' && $paymentMethod === 'credit' && $estimatedCost > 0
            && !in_array($paymentStatus, ['PAID', 'PAYE'])) {
            $userId = $row->user_id ?? null;
            if ($userId) {
                $wallet = \App\Models\Wallet::where('owner_id', $userId)->where('owner_type', \App\Models\User::class)->first();
                if ($wallet) {
                    $walletWasDebited = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                        ->where('type', 'debit')
                        ->where('metadata', 'like', '%"reservation_id":' . $this->id . '%')
                        ->exists();
                }
            }
        }
        if ($paymentMode === 'prepaid' && $paymentMethod === 'credit' && $estimatedCost > 0
            && !in_array($paymentStatus, ['PAID', 'PAYE']) && $walletWasDebited) {
            \Illuminate\Support\Facades\DB::table('reservations')
                ->where('id', $this->id)
                ->update([
                    'payment_status' => 'PAID',
                    'payment_method' => 'prepaid_credit',
                    'status' => 'confirmed',
                    'prepaid_amount' => $estimatedCost,
                ]);
            $row = (object) array_merge((array) $row, [
                'payment_status' => 'PAID',
                'payment_method' => 'prepaid_credit',
                'status' => 'confirmed',
                'prepaid_amount' => $estimatedCost,
            ]);
            $this->refresh();
            $synced = true;
        }

        // 1. Mise à jour transaction → completed (directe en base)
        $txUpdated = \Illuminate\Support\Facades\DB::table('transactions')
            ->where('reservation_id', $this->id)
            ->whereRaw('LOWER(COALESCE(status, \'\')) IN (?, ?)', ['pending', 'confirmed'])
            ->update(['status' => 'completed']);

        if ($txUpdated > 0) {
            $this->unsetRelation('transaction');
            $synced = true;
        }

        // 2. Mise à jour réservation → confirmed (directe en base)
        $statusStr = (string) ($row->status ?? '');
        if (in_array($statusStr, ['pending', 'pending_confirmation'])) {
            \Illuminate\Support\Facades\DB::table('reservations')
                ->where('id', $this->id)
                ->update([
                    'status' => 'confirmed',
                    'confirmed_at' => $row->confirmed_at ?? $now,
                    'approved_at' => $row->approved_at ?? $now,
                    'payment_status' => 'PAID',
                    'payment_method' => $paymentMethod ?: 'prepaid_credit',
                    'payment_mode' => $paymentMode ?: 'prepaid',
                ]);
            $this->refresh();
            $synced = true;
        }

        // 3. Sync via service
        $syncService = app(\App\Services\TransactionStatusSyncService::class);
        if ($syncService->syncForReservation($this->fresh())) {
            $synced = true;
        }

        // 3b. Synchroniser les soldes admin/intégrateur/opérateur
        if ($synced && $this->charging_point_id) {
            try {
                $reservationTransactionService = app(\App\Services\ReservationTransactionService::class);
                $hierarchy = $reservationTransactionService->getCompleteHierarchy($this->charging_point_id);
                if ($hierarchy) {
                    $balanceSyncService = app(\App\Services\BalanceSynchronizationService::class);
                    foreach (['admin', 'integrator', 'operator'] as $role) {
                        if (!empty($hierarchy[$role])) {
                            $u = $hierarchy[$role]->user ?? $hierarchy[$role];
                            if ($u instanceof \App\Models\User) {
                                $balanceSyncService->synchronizeUserBalance($u);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ensureTransactionConfirmedIfPaid: sync balances échouée', [
                    'reservation_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 4. Créer transaction si manquante
        if (!$this->transaction) {
            try {
                $reservationTransactionService = app(\App\Services\ReservationTransactionService::class);
                $result = $reservationTransactionService->processReservationTransaction($this);
                if ($result['success'] ?? false) {
                    $this->unsetRelation('transaction');
                    $syncService->syncForReservation($this->fresh());
                    $synced = true;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ensureTransactionConfirmedIfPaid: création transaction échouée', [
                    'reservation_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->unsetRelation('transaction');
        return $synced;
    }

    /**
     * Vérifier si la réservation est en mode prépayé
     */
    public function isPrepaid(): bool
    {
        return $this->payment_mode === 'prepaid';
    }

    /**
     * Vérifier si la réservation est en mode postpayé
     */
    public function isPostpaid(): bool
    {
        return $this->payment_mode === 'postpaid';
    }

    /**
     * Vérifier si le paiement par crédit est validé
     */
    public function isWalletValidated(): bool
    {
        return $this->wallet_validation_passed && $this->wallet_validated_at !== null;
    }

    /**
     * Obtenir le montant à débiter pour le mode prépayé
     */
    public function getPrepaidAmount(): float
    {
        return $this->prepaid_amount ?? $this->estimated_cost ?? 0;
    }

    /**
     * Obtenir le seuil minimum pour le mode postpayé
     */
    public function getMinThreshold(): float
    {
        return $this->min_threshold ?? config('charging.postpaid_min_threshold', 10.00);
    }

    /**
     * Calculer le montant de remboursement pour le mode prépayé
     */
    public function calculateRefundAmount(): float
    {
        if (!$this->isPrepaid() || !$this->prepaid_amount || !$this->actual_cost) {
            return 0;
        }

        $refund = $this->prepaid_amount - $this->actual_cost;
        return max(0, $refund);
    }

    // ====================================================================
    // MÉTHODES MÉTIER OCPP (selon spécifications)
    // ====================================================================

    /**
     * Vérifier si la réservation est payée (tous modes de paiement).
     * Utilisé pour : idempotence, pas de double débit, masquer bouton Approuver.
     */
    public function isPaid(): bool
    {
        $status = strtoupper((string) ($this->payment_status ?? ''));
        if (in_array($status, ['PAID', 'PAYE'])) {
            return true;
        }
        // Fallback : prepaid_amount > 0 + payment_mode prepaid = payé par solde
        if ($this->payment_mode === 'prepaid' && (float) ($this->prepaid_amount ?? 0) > 0) {
            return true;
        }
        // Fallback : payment_mode prepaid + payment_method credit + estimated_cost > 0 (données partielles)
        if ($this->payment_mode === 'prepaid' && strtolower((string) ($this->payment_method ?? '')) === 'credit'
            && (float) ($this->estimated_cost ?? 0) > 0) {
            return true;
        }
        // Fallback : payment_method prepaid_credit/postpaid_credit
        $method = strtolower((string) ($this->payment_method ?? ''));
        if (in_array($method, ['prepaid_credit', 'postpaid_credit'])) {
            return true;
        }
        return false;
    }

    /**
     * Vérifier si la réservation est payée par solde (prépayé ou postpayé débité).
     * Utilisé pour : afficher "Approuvé automatiquement" au lieu de "Confirmée".
     */
    public function isPaidByBalance(): bool
    {
        if (!$this->isPaid()) {
            return false;
        }
        $method = strtolower((string) ($this->payment_method ?? ''));
        if (in_array($method, ['prepaid_credit', 'postpaid_credit'])) {
            return true;
        }
        // Fallback : payment_mode prepaid avec prepaid_amount
        if ($this->payment_mode === 'prepaid' && (float) ($this->prepaid_amount ?? 0) > 0) {
            return true;
        }
        // Fallback : payment_mode prepaid + payment_method credit + estimated_cost > 0
        if ($this->payment_mode === 'prepaid' && $method === 'credit' && (float) ($this->estimated_cost ?? 0) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Vérifier si la réservation est approuvée
     * Une réservation est approuvée si:
     * - status = 'confirmed' OU 'active'
     * - payment_status = 'PAID'
     */
    public function isApproved(): bool
    {
        $statusValue = $this->status instanceof ReservationStatus ? $this->status->value : $this->status;
        $validStatuses = ['confirmed', 'active', 'pending_confirmation'];
        return in_array($statusValue, $validStatuses) && $this->isPaid();
    }

    /**
     * Libellé de statut pour l'affichage.
     * Pour les réservations payées par solde : "Approuvé automatiquement" / "Confirmé" au lieu de "En attente".
     */
    public function getDisplayStatusLabel(): string
    {
        $statusValue = $this->status instanceof ReservationStatus ? $this->status->value : (string) $this->status;

        if ($this->isPaidByBalance()) {
            return __('messages.status_approved_auto');
        }

        if ($this->isPaid() && in_array($statusValue, ['pending', 'pending_confirmation'], true)) {
            return __('messages.status_confirmed');
        }

        $labels = [
            'pending' => __('messages.status_pending'),
            'pending_confirmation' => __('messages.status_pending_confirmation'),
            'confirmed' => __('messages.status_confirmed'),
            'active' => __('messages.status_in_progress'),
            'completed' => __('messages.status_completed'),
            'canceled' => __('messages.status_cancelled'),
        ];
        return $labels[$statusValue] ?? ucfirst($statusValue);
    }

    /**
     * Vérifier si la réservation peut démarrer maintenant
     * (vérifie uniquement les conditions côté réservation, pas le connecteur)
     */
    public function canStartNow(): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        // Vérifier que l'heure de début est passée (ou proche)
        if ($this->start_time && now()->isBefore($this->start_time->subMinutes(5))) {
            return false;
        }

        // Vérifier que l'heure de fin n'est pas dépassée
        if ($this->end_time && now()->isAfter($this->end_time)) {
            return false;
        }

        return true;
    }

    /**
     * Approuver la réservation (admin ou paiement)
     */
    public function approve(?User $approver = null): void
    {
        $this->update([
            'status' => 'confirmed',
            'payment_status' => 'PAID',
            'approved_at' => now(),
            'approved_by' => $approver?->id,
        ]);
    }

    /**
     * Marquer comme payé (après paiement carte de crédit)
     */
    public function markAsPaid(): void
    {
        $this->update([
            'payment_status' => 'PAID',
            'approved_at' => now(),
        ]);
    }

    /**
     * Obtenir le tag OCPP à utiliser pour cette réservation
     * (soit celui assigné, soit le tag par défaut de l'utilisateur)
     */
    public function getOcppTag(): ?OcppTag
    {
        if ($this->ocpp_tag_id) {
            return $this->ocppTag;
        }

        return $this->user?->ocppTags()->active()->default()->first();
    }

    /**
     * Vérifier si l'utilisateur a un solde suffisant pour démarrer
     * Utilise le wallet (source de vérité) et non user.balance (legacy)
     */
    public function userHasSufficientBalance(): bool
    {
        if (!$this->user) {
            return false;
        }

        $wallet = $this->user->getOrCreateWallet();
        $balance = (float) ($wallet->fresh()->balance ?? 0);
        $minimumCost = (float) ($this->estimated_cost ?? 1.0);

        return $balance >= $minimumCost;
    }

    /**
     * Scope pour filtrer les réservations selon la visibilité de l'utilisateur
     * 
     * - Admin: voit toutes les réservations et peut les approuver
     * - Integrator: voit ses réservations et celles de ses opérateurs/partners créés par lui
     * - Operator: voit seulement ses propres réservations
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User|null $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleToUser($query, ?User $user = null)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0'); // Aucun accès si pas d'utilisateur
        }

        // Admin voit toutes les réservations
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $query;
        }

        // Intégrateur voit ses réservations et celles de ses opérateurs/partners créés par lui
        if ($user->hasRole('integrator')) {
            // Récupérer l'ID de l'intégrateur
            $integratorId = null;
            
            // Si l'utilisateur est directement un intégrateur
            if ($user->integrator_id) {
                $integratorId = $user->integrator_id;
            } else {
                // Chercher l'intégrateur lié à cet utilisateur
                $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
                $integratorId = $integrator ? $integrator->id : null;
            }

            if (!$integratorId) {
                return $query->whereRaw('1 = 0'); // Aucun accès si pas d'intégrateur
            }

            return $query->whereHas('chargingPoint', function($cpQuery) use ($integratorId) {
                $cpQuery->where('integrator_id', $integratorId)
                        ->orWhereHas('partner', function($p) use ($integratorId) {
                            $p->where('integrator_id', $integratorId);
                        })
                        ->orWhereHas('group', function($g) use ($integratorId) {
                            $g->where('integrator_id', $integratorId)
                              ->orWhereHas('partner', function($p) use ($integratorId) {
                                  $p->where('integrator_id', $integratorId);
                              });
                        });
            });
        }

        // Operator voit TOUTES les réservations sur les bornes qu'il gère (incl. intégrateur)
        if ($user->hasRole('operator')) {
            $groupIds = \App\Models\Group::where('user_id', $user->id)->pluck('id')->toArray();
            return $query->where(function($q) use ($user, $groupIds) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('chargingPoint', function($cp) use ($user, $groupIds) {
                      $cp->withTrashed()->where(function($cpq) use ($user, $groupIds) {
                          $cpq->where('user_id', $user->id)
                              ->orWhere('created_by', $user->id)
                              ->orWhere('created_by_id', $user->id);
                          if (!empty($groupIds)) {
                              $cpq->orWhereIn('group_id', $groupIds);
                          }
                          if ($user->integrator_id) {
                              $cpq->orWhere('integrator_id', $user->integrator_id);
                          }
                      });
                  });
            });
        }

        // Partenaire voit les réservations sur ses bornes
        if ($user->hasRole('partner') && $user->partner_id) {
            return $query->where(function($q) use ($user) {
                // Réservations sur les bornes directement liées au partenaire
                $q->whereHas('chargingPoint', function($cpQuery) use ($user) {
                    $cpQuery->where('partner_id', $user->partner_id);
                })
                // Réservations sur les bornes dans des groupes du partenaire
                ->orWhereHas('chargingPoint.group', function($groupQuery) use ($user) {
                    $groupQuery->where('partner_id', $user->partner_id);
                })
                // Réservations personnelles du partenaire (fallback)
                ->orWhere('user_id', $user->id);
            });
        }

        // Client / User : voit uniquement ses propres réservations
        $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
        $hasSystemRole = !empty(array_intersect($userRoles, $systemRoles));
        if (!$hasSystemRole) {
            return $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id);
                if (!empty(trim($user->email ?? ''))) {
                    $q->orWhereRaw('LOWER(TRIM(guest_email)) = ?', [strtolower(trim($user->email))]);
                }
                if (!empty(trim($user->phone ?? ''))) {
                    $q->orWhere('guest_phone', trim($user->phone));
                }
            });
        }

        // Par défaut, aucun accès
        return $query->whereRaw('1 = 0');
    }
}
