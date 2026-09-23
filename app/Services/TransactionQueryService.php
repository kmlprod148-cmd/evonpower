<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for building transaction queries based on user roles.
 * Rule: every role sees ALL transactions linked to charging points they own/manage.
 * No reservation-status filtering — if a transaction exists, it is visible.
 */
class TransactionQueryService
{
    public const DEFAULT_PER_PAGE = 20;
    public const BALANCE_THRESHOLD = 0.01;
    public const BATCH_SIZE = 100;
    public const CACHE_TTL = 300;

    /**
     * Get wallet transactions for a user
     */
    public function getWalletTransactions(WalletTransaction $wallet, ?bool $excludeReservationRelated = false): Collection
    {
        $query = $wallet->transactions();

        if ($excludeReservationRelated) {
            $query->notRelatedToReservations();
        }

        return $query->latest()->get();
    }

    /**
     * Get reservation-based transactions based on user role
     */
    public function getReservationTransactions(\Illuminate\Contracts\Auth\Authenticatable $user): Collection
    {
        return $this->buildRoleBasedQuery($user)->latest()->get();
    }

    /**
     * Build the base query with eager loading
     */
    protected function buildBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Transaction::query()->with([
            'reservation',
            'reservation.user',
            'reservation.chargingPoint',
            'reservation.chargingPoint.group',
            'reservation.chargingPoint.partner',
            'reservation.chargingPoint.group.partner',
            'chargingPoint',
            'chargingPoint.businessProfile',
            'chargingPoint.group',
            'chargingPoint.group.partner',
            'chargingPoint.group.operator',
            'chargingPoint.integrator',
            'chargingPoint.partner',
            'chargingPoint.user',
            'transactionDetail',
            'transactionDetail.adminCreator',
            'transactionDetail.integratorCreator',
            'transactionDetail.operator',
            'user',
        ]);
    }

    /**
     * Build role-based transaction query.
     * Each role sees ALL transactions linked to their charging points — no status gating.
     */
    public function buildRoleBasedQuery(\Illuminate\Contracts\Auth\Authenticatable $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->buildBaseQuery();

        if ($user->hasRole(['admin', 'super_admin'])) {
            // Admin sees every transaction in the system
            return $query;
        }

        if ($user->hasRole('integrator')) {
            return $this->applyIntegratorFilters($query, $user);
        }

        if ($user->hasRole('partner')) {
            return $this->applyPartnerFilters($query, $user);
        }

        if ($user->hasRole('operator')) {
            return $this->applyOperatorFilters($query, $user);
        }

        return $this->applyUserFilters($query, $user);
    }

    /**
     * Integrator: all transactions where the charging point belongs to their integrator scope.
     * Comprehensive check for:
     * - Direct integrator_id match
     * - Charging points of operators created by this integrator
     * - Charging points of partners created by this integrator
     * - Charging points in groups of this integrator's structure
     * - Transformations via reservations (includes all above)
     */
    protected function applyIntegratorFilters($query, \Illuminate\Contracts\Auth\Authenticatable $user): \Illuminate\Database\Eloquent\Builder
    {
        // Resolve the integrator model ID
        $integratorId = $this->resolveIntegratorId($user);
        
        if (!$integratorId) {
            Log::warning('TransactionQueryService: integrator user has no integrator_id — falling back to user-scoped query', ['user_id' => $user->id]);
            // Fall back to showing this user's own transactions rather than returning zero rows.
            // This covers the case where the Integrator model record is missing or not yet linked.
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('reservation', function ($res) use ($user) {
                      $res->where('user_id', $user->id);
                  });
            });
        }

        // 1. Fetch all users associated with this integrator (direct or via created_by chain)
        // For 'regardless of depth', we include all users who have this integrator_id.
        // We also include users created by these operators.
        $allUserIds = User::where('integrator_id', $integratorId)
            ->orWhere('id', $user->id)
            ->pluck('id')
            ->toArray();

        // 2. Fetch all partners associated with this integrator
        $partnerIds = \App\Models\Partner::where('integrator_id', $integratorId)
            ->pluck('id')
            ->toArray();

        return $query->where(function ($q) use ($integratorId, $allUserIds, $partnerIds, $user) {
            // A. Direct fields on the transaction
            $q->where('integrator_id', $integratorId)
              ->orWhereIn('user_id', $allUserIds)
              ->orWhereIn('operator_id', $allUserIds)

            // B. Via Charging Point relationship
            ->orWhereHas('chargingPoint', function ($cp) use ($integratorId, $allUserIds, $partnerIds) {
                $cp->where('integrator_id', $integratorId)
                   ->orWhereIn('user_id', $allUserIds)
                   ->orWhereIn('partner_id', $partnerIds)
                   ->orWhereIn('created_by', $allUserIds)
                   ->orWhereIn('created_by_id', $allUserIds)
                   ->orWhereHas('group', function($g) use ($partnerIds, $integratorId) {
                        $g->whereIn('partner_id', $partnerIds)
                          ->orWhere('integrator_id', $integratorId);
                   });
            })

            // C. Via Reservation relationship (comprehensive)
            ->orWhereHas('reservation', function ($res) use ($integratorId, $allUserIds, $partnerIds) {
                $res->where(function($resSub) use ($integratorId, $allUserIds, $partnerIds) {
                    $resSub->whereIn('user_id', $allUserIds)
                        ->orWhereHas('chargingPoint', function ($cp) use ($integratorId, $allUserIds, $partnerIds) {
                            $cp->where('integrator_id', $integratorId)
                               ->orWhereIn('user_id', $allUserIds)
                               ->orWhereIn('partner_id', $partnerIds)
                               ->orWhereIn('created_by', $allUserIds)
                               ->orWhereIn('created_by_id', $allUserIds);
                        });
                });
            })

            // D. Via TransactionDetail.
            // NOTE: integrator_creator_id stores the integrator USER id (not the model id),
            // so we must check BOTH the model id and the user's own id.
            ->orWhereHas('transactionDetail', function ($td) use ($integratorId, $allUserIds, $user) {
                $td->where('integrator_creator_id', $integratorId)
                   ->orWhere('integrator_creator_id', $user->id)
                   ->orWhereIn('operator_id', $allUserIds);
            });
        });
    }

    /**
     * Partner: all transactions where the charging point belongs to their partner scope.
     * Checks: charging_point.partner_id, group.partner_id, reservation's charging point.
     */
    protected function applyPartnerFilters($query, \Illuminate\Contracts\Auth\Authenticatable $user): \Illuminate\Database\Eloquent\Builder
    {
        $partnerId = $user->partner_id;

        if (!$partnerId) {
            Log::warning('TransactionQueryService: partner user has no partner_id', ['user_id' => $user->id]);
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($partnerId) {
            // 1. Transaction directly linked to a charging point of this partner
            $q->whereHas('chargingPoint', function ($cp) use ($partnerId) {
                $cp->where('partner_id', $partnerId)
                   ->orWhereHas('group', function ($g) use ($partnerId) {
                       $g->where('partner_id', $partnerId);
                   });
            })

            // 2. Transaction linked via reservation → charging point of this partner
            ->orWhereHas('reservation', function ($res) use ($partnerId) {
                $res->whereHas('chargingPoint', function ($cp) use ($partnerId) {
                    $cp->where('partner_id', $partnerId)
                       ->orWhereHas('group', function ($g) use ($partnerId) {
                           $g->where('partner_id', $partnerId);
                       });
                });
            });
        });
    }

    /**
     * Operator: all transactions where the charging point was created/owned by them,
     * or they made the reservation, or their TransactionDetail links them as operator.
     */
    protected function applyOperatorFilters($query, \Illuminate\Contracts\Auth\Authenticatable $user): \Illuminate\Database\Eloquent\Builder
    {
        $operatorId = $user->id;

        return $query->where(function ($q) use ($operatorId) {
            // 1. Transaction's charging point owned/created by this operator
            $q->whereHas('chargingPoint', function ($cp) use ($operatorId) {
                $cp->where('user_id', $operatorId)
                   ->orWhere('created_by', $operatorId)
                   ->orWhere('created_by_id', $operatorId)
                   ->orWhereHas('group', function ($g) use ($operatorId) {
                       $g->where('user_id', $operatorId);
                   });
            })

            // 2. Transaction linked via reservation → charging point owned by this operator
            ->orWhereHas('reservation', function ($res) use ($operatorId) {
                $res->whereHas('chargingPoint', function ($cp) use ($operatorId) {
                    $cp->where('user_id', $operatorId)
                       ->orWhere('created_by', $operatorId)
                       ->orWhere('created_by_id', $operatorId);
                });
            })

            // 3. Operator made the reservation
            ->orWhereHas('reservation', function ($res) use ($operatorId) {
                $res->where('user_id', $operatorId);
            })

            // 4. TransactionDetail links this user as operator
            ->orWhereHas('transactionDetail', function ($td) use ($operatorId) {
                $td->where('operator_id', $operatorId)
                   ->where('operator_share_amount', '>', 0);
            });
        });
    }

    /**
     * Regular user: only their own transactions / reservations.
     */
    protected function applyUserFilters($query, \Illuminate\Contracts\Auth\Authenticatable $user): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereHas('reservation', function ($res) use ($user) {
                  $res->where('user_id', $user->id);
              });
        });
    }

    /**
     * Return charging points visible to the given user according to the current
     * hierarchical access rules.
     */
    public function getScopedChargingPoints(\Illuminate\Contracts\Auth\Authenticatable $user): \Illuminate\Database\Eloquent\Builder
    {
        return ChargingPoint::query()->visibleToUser($user);
    }

    /**
     * Return charging point IDs visible to the given user.
     */
    public function getScopedChargingPointIds(\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        return $this->getScopedChargingPoints($user)
            ->pluck('charging_points.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Return users that belong to the current user's transaction scope.
     */
    public function getScopedUsers(\Illuminate\Contracts\Auth\Authenticatable $user): SupportCollection
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return User::select('id', 'name', 'email')->orderBy('name')->get();
        }

        if ($user->hasRole('integrator')) {
            $integratorId = $this->resolveIntegratorId($user);

            if (!$integratorId) {
                // Fallback: return only the integrator user themselves
                return User::select('id', 'name', 'email')
                    ->where('id', $user->id)
                    ->get();
            }

            $partnerIds = \App\Models\Partner::where('integrator_id', $integratorId)
                ->pluck('id');

            return User::query()
                ->select('id', 'name', 'email')
                ->where(function ($query) use ($user, $integratorId, $partnerIds) {
                    $query->where('id', $user->id)
                        ->orWhere('integrator_id', $integratorId);

                    if ($partnerIds->isNotEmpty()) {
                        $query->orWhereIn('partner_id', $partnerIds);
                    }
                })
                ->orderBy('name')
                ->get();
        }

        if ($user->hasRole('partner') && $user->partner_id) {
            return User::query()
                ->select('id', 'name', 'email')
                ->where(function ($query) use ($user) {
                    $query->where('id', $user->id)
                        ->orWhere('partner_id', $user->partner_id);
                })
                ->orderBy('name')
                ->get();
        }

        if ($user->hasRole('operator')) {
            return User::query()
                ->select('id', 'name', 'email')
                ->where('id', $user->id)
                ->orderBy('name')
                ->get();
        }

        return User::query()
            ->select('id', 'name', 'email')
            ->where('id', $user->id)
            ->get();
    }

    /**
     * Return wallet transactions visible to an integrator across:
     * - integrator-owned wallets
     * - operator wallets under the integrator
     * - partner wallets under the integrator
     * - reservation-linked wallet transactions tied to visible transactions
     */
    public function getIntegratorWalletTransactions(
        \Illuminate\Contracts\Auth\Authenticatable $user,
        array $filters = [],
        ?Collection $reservationTransactions = null
    ): SupportCollection {
        $integratorId = $this->resolveIntegratorId($user);

        if (!$integratorId) {
            Log::warning('TransactionQueryService::getIntegratorWalletTransactions — no integrator_id, falling back to user wallet', ['user_id' => $user->id]);
            // Return the user's own wallet transactions as a best-effort fallback.
            $userWallet = method_exists($user, 'getOrCreateWallet') ? $user->getOrCreateWallet() : null;
            if (!$userWallet) {
                return collect();
            }
            return WalletTransaction::where('wallet_id', $userWallet->id)
                ->with('wallet.owner')
                ->latest()
                ->get();
        }

        $reservationTransactions = $reservationTransactions
            ?? $this->applyFilters($this->buildRoleBasedQuery($user), $filters)->latest()->get();

        $ownedWalletTransactions = Wallet::getIntegratorScopeTransactions($integratorId)
            ->loadMissing('wallet.owner');

        $visibleTransactionIds = $reservationTransactions
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $visibleReservationIds = $reservationTransactions
            ->pluck('reservation_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $transactionsById = $reservationTransactions->keyBy('id');
        $transactionsByReservationId = $reservationTransactions
            ->filter(fn ($transaction) => !empty($transaction->reservation_id))
            ->keyBy('reservation_id');

        $linkedWalletTransactions = collect();

        if ($visibleTransactionIds->isNotEmpty() || $visibleReservationIds->isNotEmpty()) {
            $linkedWalletTransactions = WalletTransaction::with('wallet.owner')
                ->relatedToReservations()
                ->when(!empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
                ->when(!empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
                ->when(!empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
                ->when(!empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
                ->when(!empty($filters['search']), function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('description', 'like', '%' . $search . '%')
                            ->orWhere('reference', 'like', '%' . $search . '%')
                            ->orWhere('external_id', 'like', '%' . $search . '%');
                    });
                })
                ->latest()
                ->get()
                ->filter(function (WalletTransaction $walletTransaction) use (
                    $filters,
                    $visibleTransactionIds,
                    $visibleReservationIds,
                    $transactionsById,
                    $transactionsByReservationId
                ) {
                    $metadata = $walletTransaction->metadata ?? [];
                    $relatedTransactionId = (int) ($metadata['transaction_id'] ?? 0);
                    $relatedReservationId = $walletTransaction->getReservationId();

                    $isInScope = ($relatedTransactionId > 0 && $visibleTransactionIds->contains($relatedTransactionId))
                        || ($relatedReservationId !== null && $visibleReservationIds->contains((int) $relatedReservationId));

                    if (!$isInScope) {
                        return false;
                    }

                    $relatedTransaction = null;

                    if ($relatedTransactionId > 0 && $transactionsById->has($relatedTransactionId)) {
                        $relatedTransaction = $transactionsById->get($relatedTransactionId);
                    } elseif ($relatedReservationId !== null && $transactionsByReservationId->has($relatedReservationId)) {
                        $relatedTransaction = $transactionsByReservationId->get($relatedReservationId);
                    }

                    if (!empty($filters['reservation_id']) && (int) $relatedReservationId !== (int) $filters['reservation_id']) {
                        return false;
                    }

                    if (!empty($filters['charging_point_id'])) {
                        $relatedChargingPointId = $relatedTransaction?->charging_point_id
                            ?? $relatedTransaction?->reservation?->charging_point_id;

                        if ((int) $relatedChargingPointId !== (int) $filters['charging_point_id']) {
                            return false;
                        }
                    }

                    if (!empty($filters['user_id'])) {
                        $relatedUserId = $relatedTransaction?->user_id
                            ?? $relatedTransaction?->reservation?->user_id;

                        if ((int) $relatedUserId !== (int) $filters['user_id']) {
                            return false;
                        }
                    }

                    if (!empty($filters['payment_method'])) {
                        $relatedPaymentMethod = $metadata['payment_method']
                            ?? $relatedTransaction?->payment_method
                            ?? $relatedTransaction?->reservation?->payment_method;

                        if ($relatedPaymentMethod !== $filters['payment_method']) {
                            return false;
                        }
                    }

                    if ($relatedTransaction) {
                        $walletTransaction->setRelation('scopedTransaction', $relatedTransaction);
                    }

                    return true;
                });
        }

        $walletTransactions = $ownedWalletTransactions
            ->merge($linkedWalletTransactions)
            ->unique('id')
            ->values();

        return $this->filterWalletTransactionCollection($walletTransactions, $filters)
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Return the aggregate wallet balance for all wallets inside a user's scope.
     */
    public function getScopedWalletBalance(\Illuminate\Contracts\Auth\Authenticatable $user): float
    {
        if ($user->hasRole('integrator')) {
            $integratorId = $this->resolveIntegratorId($user);

            if (!$integratorId) {
                // Fallback: return the user's own wallet balance
                $userWallet = method_exists($user, 'getOrCreateWallet') ? $user->getOrCreateWallet() : null;
                return round((float) ($userWallet?->balance ?? 0), 2);
            }

            return round(
                (float) Wallet::getIntegratorScopeWallets($integratorId)
                    ->sum(fn ($wallet) => (float) ($wallet->balance ?? 0)),
                2
            );
        }

        $wallet = $user->getOrCreateWallet();

        return round((float) ($wallet->balance ?? 0), 2);
    }

    /**
     * Resolve the integrator model ID for a user (public alias for diagnostics).
     */
    public function resolveIntegratorIdPublic(\Illuminate\Contracts\Auth\Authenticatable $user): ?int
    {
        return $this->resolveIntegratorId($user);
    }

    /**
     * Resolve the integrator model ID for a user.
     *
     * Tries three strategies in order:
     *  1. user.integrator_id column (fastest – already denormalised)
     *  2. Integrator record with user_id = user.id (owner FK)
     *  3. Integrator record whose email matches the user's email (legacy/orphaned accounts)
     *
     * On a hit via strategy 2 or 3, back-fills user.integrator_id so the next
     * call is instant and the warning log is silenced permanently.
     */
    protected function resolveIntegratorId(\Illuminate\Contracts\Auth\Authenticatable $user): ?int
    {
        // Strategy 1 – denormalised FK already set
        if (!empty($user->integrator_id)) {
            return (int) $user->integrator_id;
        }

        // Strategy 2 – Integrator record that names this user as owner
        $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
        if ($integratorModel) {
            // Self-repair: write integrator_id back so future calls hit strategy 1
            try {
                \App\Models\User::where('id', $user->id)
                    ->update(['integrator_id' => $integratorModel->id]);
            } catch (\Throwable $e) {
                Log::warning('resolveIntegratorId: could not backfill user.integrator_id', [
                    'user_id'        => $user->id,
                    'integrator_id'  => $integratorModel->id,
                    'error'          => $e->getMessage(),
                ]);
            }
            return $integratorModel->id;
        }

        // Strategy 3 – email match (handles accounts created before the user_id FK was added)
        if (!empty($user->email)) {
            $integratorByEmail = \App\Models\Integrator::where('email', $user->email)->first();
            if ($integratorByEmail) {
                Log::info('resolveIntegratorId: resolved via email match', [
                    'user_id'       => $user->id,
                    'integrator_id' => $integratorByEmail->id,
                ]);
                return $integratorByEmail->id;
            }
        }

        // Strategy 4 – follow TransactionDetail.integrator_creator_id (user FK) → charging point → integrator_id
        // This covers the case where the integrator model exists and is linked to charging points,
        // but the user record has no integrator_id and the Integrator row has no user_id.
        $transactionDetail = \App\Models\TransactionDetail::where('integrator_creator_id', $user->id)
            ->where(function ($q) {
                $q->whereNotNull('integrator_share_amount')
                  ->where('integrator_share_amount', '>', 0);
            })
            ->latest('id')
            ->first();

        if ($transactionDetail) {
            $transaction = $transactionDetail->transaction()
                ->with(['chargingPoint', 'reservation.chargingPoint'])
                ->first();

            $integratorId = $transaction?->chargingPoint?->integrator_id
                ?? $transaction?->reservation?->chargingPoint?->integrator_id;

            if ($integratorId) {
                Log::info('resolveIntegratorId: resolved via TransactionDetail chain', [
                    'user_id'               => $user->id,
                    'transaction_detail_id' => $transactionDetail->id,
                    'integrator_id'         => $integratorId,
                ]);
                // Self-repair: write integrator_id back to avoid this expensive lookup next time
                try {
                    \App\Models\User::where('id', $user->id)
                        ->update(['integrator_id' => $integratorId]);
                } catch (\Throwable $e) {
                    // Non-fatal – continue with the resolved ID
                }
                return (int) $integratorId;
            }
        }

        return null;
    }

    /**
     * Apply collection-level filters to wallet transactions.
     */
    protected function filterWalletTransactionCollection(SupportCollection $walletTransactions, array $filters): SupportCollection
    {
        return $walletTransactions
            ->filter(function (WalletTransaction $walletTransaction) use ($filters) {
                if (!empty($filters['date_from']) && $walletTransaction->created_at->lt(Carbon::parse($filters['date_from'])->startOfDay())) {
                    return false;
                }

                if (!empty($filters['date_to']) && $walletTransaction->created_at->gt(Carbon::parse($filters['date_to'])->endOfDay())) {
                    return false;
                }

                if (!empty($filters['type']) && $walletTransaction->type !== $filters['type']) {
                    return false;
                }

                if (!empty($filters['status']) && $walletTransaction->status !== $filters['status']) {
                    return false;
                }

                if (!empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $description = mb_strtolower((string) ($walletTransaction->description ?? ''));
                    $reference = mb_strtolower((string) ($walletTransaction->reference ?? ''));
                    $externalId = mb_strtolower((string) ($walletTransaction->external_id ?? ''));

                    if (!str_contains($description, $search)
                        && !str_contains($reference, $search)
                        && !str_contains($externalId, $search)
                    ) {
                        return false;
                    }
                }

                if (!empty($filters['reservation_id']) && (int) $walletTransaction->getReservationId() !== (int) $filters['reservation_id']) {
                    return false;
                }

                if (!empty($filters['exclude_reservation_wallet_transactions']) && $walletTransaction->isRelatedToReservation()) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    /**
     * Apply common UI filters to a query (date, status, search, charging point)
     */
    public function applyFilters($query, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['reservation_id'])) {
            $query->where('reservation_id', $filters['reservation_id']);
        }

        if (!empty($filters['charging_point_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('charging_point_id', $filters['charging_point_id'])
                  ->orWhereHas('reservation', function ($res) use ($filters) {
                      $res->where('charging_point_id', $filters['charging_point_id']);
                  });
            });
        }

        if (!empty($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id'])
                  ->orWhereHas('reservation', function ($res) use ($filters) {
                      $res->where('user_id', $filters['user_id']);
                });
            });
        }

        if (!empty($filters['payment_method'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('payment_method', $filters['payment_method'])
                  ->orWhereHas('reservation', function ($res) use ($filters) {
                      $res->where('payment_method', $filters['payment_method']);
                  });
            });
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('id', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('session_id', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('payment_reference', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('stripe_session_id', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('reservation', function ($res) use ($filters) {
                      $res->where('id', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        return $query;
    }

    /**
     * Count transactions for a user role
     */
    public function countTransactions(\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        return [
            'total_transactions'   => Transaction::count(),
            'with_reservation'     => Transaction::whereNotNull('reservation_id')->count(),
            'without_reservation'  => Transaction::whereNull('reservation_id')->count(),
            'user_transactions'    => Transaction::where('user_id', $user->id)->count(),
        ];
    }
}
