<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'transaction_type',
        'transaction_category',
        'charging_point_id',
        'user_id',
        'reservation_id',
        'session_id',
        'amount',
        'current_balance',
        'admin_current_balance',
        'integrator_current_balance',
        'operator_current_balance',
        'currency',
        'status',
        'hierarchy_data',
        'pricing_data',
        'debit_results',
        'metadata',
        'processed_at',
        'failed_at',
        'failure_reason',
        // IDs des acteurs
        'admin_id',
        'integrator_id',
        'operator_id',
        // IDs des wallets
        'admin_wallet_id',
        'integrator_wallet_id',
        'operator_wallet_id',
        // Parts détaillées
        'admin_share_amount',
        'integrator_share_amount',
        'operator_share_amount',
        // Montants
        'price_total',
        'price_tax',
        'amount_ht', // Montant HT (hors taxes)
        // Commissions (déjà existantes)
        'admin_commission',
        'integrator_commission',
        'partner_commission',
        // Champs Steve OCPP
        'steve_transaction_id',
        'ocpp_id_tag',
        'ocpp_tag_pk',
        'charge_box_pk',
        'connector_id',
        'start_timestamp',
        'stop_timestamp',
        'start_value',
        'stop_value',
        'stop_reason',
        'stop_event_actor',
        'energy_consumed_wh',
        'duration_minutes',
        'meter_start',
        'pricing_plan_id',
        // Champs de collecte
        'collect_user_type',
        'collect_user_id',
        'collect_status',
        'collect_date',
        'collect_reference',
        'is_collectable',
        // Nouveaux champs financiers
        'payer_id',
        'beneficiary_id',
        'vat_amount',
        'total_amount',
        'payment_method',
        // Stripe / Gateway fields
        'payment_reference',
        'stripe_session_id',
        'gateway_response',
        'completed_at',
        'webhook_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'admin_current_balance' => 'decimal:2',
        'integrator_current_balance' => 'decimal:2',
        'operator_current_balance' => 'decimal:2',
        'hierarchy_data' => 'array',
        'pricing_data' => 'array',
        'debit_results' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'collect_date' => 'datetime',
        'is_collectable' => 'boolean',
        // Casts pour les champs Steve OCPP
        'start_timestamp' => 'datetime',
        'stop_timestamp' => 'datetime',
        'energy_consumed_wh' => 'decimal:2',
        // Nouveaux champs financiers
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        // Stripe / Gateway casts
        'gateway_response' => 'array',
        'webhook_data' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the charging point for this transaction
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    /**
     * Get the user for this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reservation for this transaction
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Reservation::class, 'reservation_id', 'id');
    }

    /**
     * Utilisateur/entité qui collecte les fonds de cette transaction
     */
    public function collectUser()
    {
        return $this->morphTo(__FUNCTION__, 'collect_user_type', 'collect_user_id');
    }

    /**
     * Get the wallet transactions for this transaction
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'transaction_id', 'transaction_id');
    }
    /**
     * Get the repartition record for this transaction
     */
    public function repartition(): HasOne
    {
        return $this->hasOne(\App\Models\TransactionRepartition::class);
    }

    /**
     * Get the transaction detail record for this transaction
     */
    public function transactionDetail(): HasOne
    {
        return $this->hasOne(\App\Models\TransactionDetail::class);
    }

    /**
     * Get the payer (user who paid)
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Get the beneficiary (user who receives the funds)
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_id');
    }

    /**
     * Get the transaction logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }



    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for transactions in a date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for transactions by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for transactions by charging point
     */
    public function scopeForChargingPoint($query, $chargingPointId)
    {
        return $query->where('charging_point_id', $chargingPointId);
    }

    /**
     * Scope for transactions by collect status
     */
    public function scopeByCollectStatus($query, string $status)
    {
        return $query->where('collect_status', $status);
    }

    /**
     * Scope for transactions by collect user
     */
    public function scopeForCollectUser($query, int $userId, string $userType)
    {
        return $query->where('collect_user_id', $userId)
                     ->where('collect_user_type', $userType);
    }

    /**
     * Scope for transactions that can be withdrawn
     */
    public function scopeCanWithdraw($query)
    {
        return $query->whereIn('collect_status', ['to_collect', 'collected']);
    }

    /**
     * Scope for transactions by payment method
     */
    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope for transactions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for pending collect
     */
    public function scopePendingCollect($query)
    {
        return $query->where('collect_status', 'pending');
    }

    /**
     * Scope for withdrawn transactions
     */
    public function scopeWithdrawn($query)
    {
        return $query->where('collect_status', 'withdrawn');
    }

    /**
     * Get the integrator from hierarchy data
     */
    public function getIntegratorAttribute()
    {
        $integratorId = $this->hierarchy_data['integrator'] ?? null;
        return $integratorId ? Integrator::find($integratorId) : null;
    }

    /**
     * Get the partner from hierarchy data
     */
    public function getPartnerAttribute()
    {
        $partnerId = $this->hierarchy_data['partner'] ?? null;
        return $partnerId ? Partner::find($partnerId) : null;
    }

    /**
     * Get the operator from hierarchy data
     */
    public function getOperatorAttribute()
    {
        $operatorId = $this->hierarchy_data['operator'] ?? null;
        return $operatorId ? User::find($operatorId) : null;
    }

    /**
     * Get the business profile from hierarchy data
     */
    public function getBusinessProfileAttribute()
    {
        $businessProfileId = $this->hierarchy_data['business_profile'] ?? null;
        return $businessProfileId ? BusinessProfile::find($businessProfileId) : null;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'confirmed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            default => 'gray'
        };
    }

    /**
     * Check if transaction is completed or confirmed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'confirmed']);
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark transaction as pending
     */
    public function markAsPending(): bool
    {
        return $this->update([
            'status' => 'pending',
        ]);
    }

    /**
     * Get transaction summary
     */
    public function getSummaryAttribute(): string
    {
        $userName = $this->user->name ?? 'Unknown User';
        $amount = $this->formatted_amount;

        return "Transaction for {$userName} - {$amount}";
    }

    /**
     * Get hierarchy summary
     */
    public function getHierarchySummaryAttribute(): string
    {
        $parts = [];

        if ($this->integrator) {
            $parts[] = "Integrator: {$this->integrator->name}";
        }

        if ($this->partner) {
            $parts[] = "Partner: {$this->partner->name}";
        }

        if ($this->operator) {
            $parts[] = "Operator: {$this->operator->name}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Get pricing summary
     */
    public function getPricingSummaryAttribute(): string
    {
        $pricing = $this->pricing_data;
        if (!$pricing) {
            return 'No pricing data';
        }

        $parts = [];
        $parts[] = "Base: {$pricing['base_amount']} {$pricing['currency']}";

        if ($pricing['admin_fee'] > 0) {
            $parts[] = "Admin: {$pricing['admin_fee']}";
        }

        if ($pricing['integrator_fee'] > 0) {
            $parts[] = "Integrator: {$pricing['integrator_fee']}";
        }

        if ($pricing['partner_fee'] > 0) {
            $parts[] = "Partner: {$pricing['partner_fee']}";
        }

        if ($pricing['operator_fee'] > 0) {
            $parts[] = "Operator: {$pricing['operator_fee']}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Get total fees
     */
    public function getTotalFeesAttribute(): float
    {
        $pricing = $this->pricing_data;
        if (!$pricing) {
            return 0;
        }

        return $pricing['total_fees'] ?? 0;
    }

    /**
     * Get net amount for user
     */
    public function getUserAmountAttribute(): float
    {
        $pricing = $this->pricing_data;
        if (!$pricing) {
            return $this->amount;
        }

        return $pricing['user_amount'] ?? $this->amount;
    }

    /**
     * Get debit results summary
     */
    public function getDebitSummaryAttribute(): string
    {
        $results = $this->debit_results;
        if (!$results) {
            return 'No debit results';
        }

        $summary = [];
        foreach ($results as $party => $result) {
            if ($result['success']) {
                $summary[] = ucfirst($party) . ": {$result['amount']}";
            } else {
                $summary[] = ucfirst($party) . ": Failed";
            }
        }

        return implode(' | ', $summary);
    }

    /**
     * Get transaction duration
     */
    public function getDurationAttribute(): ?int
    {
        return $this->pricing_data['duration_minutes'] ?? null;
    }

    /**
     * Get energy delivered
     */
    public function getEnergyDeliveredAttribute(): ?float
    {
        return $this->pricing_data['energy_delivered'] ?? null;
    }

    /**
     * Get session information
     */
    public function getSessionInfoAttribute(): array
    {
        return [
            'session_id' => $this->session_id,
            'charging_point_id' => $this->charging_point_id,
            'duration_minutes' => $this->duration,
            'energy_delivered' => $this->energy_delivered,
        ];
    }

    /**
     * Obtient les montants à afficher selon le rôle de l'utilisateur
     * Utilise le TransactionDisplayService pour garantir la cohérence
     * 
     * @param \App\Models\User|null $user
     * @return array
     */
    public function getDisplayAmounts(?User $user = null): array
    {
        $service = app(\App\Services\TransactionDisplayService::class);
        return $service->getDisplayAmounts($this, $user);
    }

    /**
     * Obtient les détails de répartition selon le rôle de l'utilisateur
     * 
     * @param \App\Models\User|null $user
     * @return array
     */
    public function getRepartitionDetails(?User $user = null): array
    {
        $service = app(\App\Services\TransactionDisplayService::class);
        return $service->getRepartitionDetails($this, $user);
    }

    /**
     * Obtient le montant que l'utilisateur doit voir selon son rôle
     * 
     * @param \App\Models\User|null $user
     * @return float
     */
    public function getUserAmount(?User $user = null): float
    {
        $amounts = $this->getDisplayAmounts($user);
        return $amounts['your_amount'] ?? ($this->price_total ?? $this->amount ?? 0);
    }

    /**
     * Obtient les frais admin pour cette transaction
     * 
     * PRIORITÉ : TransactionDetail (calculé selon Business Profiles) > TransactionRepartition > Calcul direct
     * 
     * @return float
     */
    public function getAdminFee(): float
    {
        // PRIORITÉ 1 : TransactionDetail (montants calculés selon Business Profiles)
        if ($this->transactionDetail) {
            return (float) ($this->transactionDetail->admin_share_amount ?? 0);
        }
        
        // PRIORITÉ 2 : TransactionRepartition (fallback)
        if ($this->repartition) {
            return $this->repartition->admin_fee ?? 0;
        }
        
        // PRIORITÉ 3 : Calculer depuis les données de la transaction
        if ($this->admin_commission ?? null) {
            return (float) $this->admin_commission;
        }
        
        return 0;
    }

    /**
     * Obtient les frais intégrateur pour cette transaction
     * 
     * PRIORITÉ : TransactionDetail (calculé selon Business Profiles) > TransactionRepartition > Calcul direct
     * 
     * @return float
     */
    public function getIntegratorFee(): float
    {
        // PRIORITÉ 1 : TransactionDetail (montants calculés selon Business Profiles)
        if ($this->transactionDetail) {
            return (float) ($this->transactionDetail->integrator_share_amount ?? 0);
        }
        
        // PRIORITÉ 2 : TransactionRepartition (fallback)
        if ($this->repartition) {
            return $this->repartition->integrator_fee ?? 0;
        }
        
        // PRIORITÉ 3 : Calculer depuis les données de la transaction
        if ($this->integrator_commission ?? null) {
            return (float) $this->integrator_commission;
        }
        
        return 0;
    }

    /**
     * Obtient le montant total des frais
     * 
     * @return float
     */
    public function getTotalFees(): float
    {
        $adminFee = $this->getAdminFee();
        $integratorFee = $this->getIntegratorFee();
        
        return $adminFee + $integratorFee;
    }

    /**
     * Obtient la part opérateur pour cette transaction
     * 
     * PRIORITÉ : TransactionDetail (calculé selon Business Profiles) > TransactionRepartition > Calcul direct
     * 
     * @return float
     */
    public function getOperatorShare(): float
    {
        // PRIORITÉ 1 : TransactionDetail (montants calculés selon Business Profiles)
        if ($this->transactionDetail) {
            return (float) ($this->transactionDetail->operator_share_amount ?? 0);
        }
        
        // PRIORITÉ 2 : TransactionRepartition (fallback)
        if ($this->repartition) {
            return (float) ($this->repartition->operator_amount ?? 0);
        }
        
        // PRIORITÉ 3 : Calculer depuis les montants disponibles
        $totalAmount = $this->amount ?? $this->price_total ?? 0;
        $adminFee = $this->getAdminFee();
        $integratorFee = $this->getIntegratorFee();
        
        // La part opérateur = total - frais intégrateur (car admin prend sa part sur la part intégrateur)
        // Selon la logique hiérarchique: operator_share = total - integrator_fee
        // Note: admin_fee est déduit de integrator_fee, pas du total
        $operatorShare = $totalAmount - $integratorFee;
        
        return max(0, round($operatorShare, 2));
    }

    /**
     * Relation avec les répartitions (HasMany pour compatibilité)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function repartitions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionRepartition::class);
    }

    /**
     * Scope pour filtrer les transactions selon la visibilité de l'utilisateur
     * 
     * - Admin: voit toutes les transactions
     * - Integrator: voit ses transactions et celles de ses opérateurs/partners créés par lui
     * - Operator: voit seulement ses propres transactions
     * - Partner: voit ses transactions
     * - User: voit seulement ses propres transactions
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

        // Admin voit toutes les transactions
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $query;
        }

        // Intégrateur voit ses transactions et celles de ses opérateurs/partners créés par lui
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

            // Pre-fetch IDs to avoid N+1 queries
            $operatorIds = \App\Models\User::where('integrator_id', $integratorId)
                ->whereHas('roles', function($roleQuery) {
                    $roleQuery->where('name', 'operator');
                })
                ->pluck('id')
                ->toArray();

            $partnerIds = \App\Models\Partner::where('integrator_id', $integratorId)
                ->pluck('id')
                ->toArray();

            return $query->where(function($q) use ($integratorId, $operatorIds, $partnerIds) {
                // 1. Direct integrator_id match
                $q->where('integrator_id', $integratorId)

                // 2. Transactions on charging points with integrator_id match
                ->orWhereHas('chargingPoint', function($cpQuery) use ($integratorId) {
                    $cpQuery->where('integrator_id', $integratorId);
                })

                // 3. Transactions on charging points belonging to operators of this integrator.
                // Guard is required: an empty orWhereHas without inner constraints would match
                // ALL transactions that have any charging point, leaking data.
                ->when(!empty($operatorIds), function($q) use ($operatorIds) {
                    $q->orWhereHas('chargingPoint', function($cpQuery) use ($operatorIds) {
                        $cpQuery->whereIn('user_id', $operatorIds)
                                ->orWhereIn('created_by', $operatorIds)
                                ->orWhereIn('created_by_id', $operatorIds);
                    });
                })

                // 4. Transactions on charging points belonging to partners of this integrator.
                ->when(!empty($partnerIds), function($q) use ($partnerIds) {
                    $q->orWhereHas('chargingPoint', function($cpQuery) use ($partnerIds) {
                        $cpQuery->whereIn('partner_id', $partnerIds);
                    });
                })

                // 5. Transactions on charging points in groups of this integrator's partners
                ->orWhereHas('chargingPoint', function($cpQuery) use ($partnerIds, $integratorId) {
                    $cpQuery->whereHas('group', function($g) use ($partnerIds, $integratorId) {
                        $g->where('integrator_id', $integratorId);
                        if (!empty($partnerIds)) {
                            $g->orWhereIn('partner_id', $partnerIds);
                        }
                    });
                })

                // 6. Transactions by operators of this integrator
                ->orWhereHas('user', function($userQuery) use ($integratorId) {
                    $userQuery->where('integrator_id', $integratorId)
                              ->whereHas('roles', function($roleQuery) {
                                  $roleQuery->where('name', 'operator');
                              });
                })

                // 7. Transactions linked via reservations
                ->orWhereHas('reservation', function($resQuery) use ($integratorId, $operatorIds, $partnerIds) {
                    $resQuery->whereHas('chargingPoint', function($cpQuery) use ($integratorId, $operatorIds, $partnerIds) {
                        $cpQuery->where('integrator_id', $integratorId);
                        if (!empty($operatorIds)) {
                            $cpQuery->orWhereIn('user_id', $operatorIds)
                                    ->orWhereIn('created_by', $operatorIds)
                                    ->orWhereIn('created_by_id', $operatorIds);
                        }
                        if (!empty($partnerIds)) {
                            $cpQuery->orWhereIn('partner_id', $partnerIds);
                        }
                    })
                    ->orWhereHas('user', function($userQuery) use ($integratorId) {
                        $userQuery->where('integrator_id', $integratorId);
                    });
                });
            });
        }

        // Operator voit seulement ses propres transactions
        if ($user->hasRole('operator')) {
            return $query->where('user_id', $user->id)
                        ->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                            $cpQuery->where(function($q) use ($user) {
                                $q->where('created_by', $user->id)
                                  ->orWhere('created_by_id', $user->id)
                                  ->orWhere('user_id', $user->id);
                            });
                        })
                        // Operators can also see transactions in their integrator's scope if attached
                        ->orWhere(function($q) use ($user) {
                            if ($user->integrator_id) {
                                $q->where('integrator_id', $user->integrator_id);
                            }
                        });
        }

        // Partenaire voit ses transactions
        if ($user->hasRole('partner') && $user->partner_id) {
            return $query->whereHas('chargingPoint', function($cpQuery) use ($user) {
                $cpQuery->where('partner_id', $user->partner_id)
                        ->orWhereHas('group', function($g) use ($user) {
                            $g->where('partner_id', $user->partner_id);
                        });
            })
            ->orWhere('user_id', $user->id);
        }

        // Client (rôle user) : voit uniquement ses propres transactions
        if ($user->hasRole(['user', 'User'])) {
            return $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('reservation', function($resQuery) use ($user) {
                      $resQuery->where('user_id', $user->id);
                  });
            });
        }

        // Par défaut, aucun accès
        return $query->whereRaw('1 = 0');
    }
}