<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service responsible for transaction balance synchronization.
 * Provides centralized balance calculation and wallet updates.
 */
class TransactionBalanceService
{
    /**
     * Float comparison threshold
     */
    public const BALANCE_THRESHOLD = 0.01;

    /**
     * Cache TTL in seconds
     */
    public const BALANCE_CACHE_TTL = 300;

    /**
     * @var BalanceSynchronizationService
     */
    protected $balanceSyncService;

    /**
     * @var TransactionQueryService
     */
    protected $queryService;

    public function __construct(
        BalanceSynchronizationService $balanceSyncService,
        TransactionQueryService $queryService
    ) {
        $this->balanceSyncService = $balanceSyncService;
        $this->queryService = $queryService;
    }

    /**
     * Synchronize user wallet balance with approved transactions
     */
    public function synchronizeBalance(User $user): array
    {
        $wallet = $user->getOrCreateWallet();
        
        // Ensure transaction details exist for approved transactions
        $this->ensureTransactionDetailsExist($user);

        // Perform synchronization
        $syncResult = $this->balanceSyncService->synchronizeUserBalance($user);
        
        $wallet->refresh();

        $calculatedBalance = $this->balanceSyncService->calculateBalanceFromApprovedTransactions($user);

        // Update wallet if different
        if (abs($wallet->balance - $calculatedBalance) > self::BALANCE_THRESHOLD) {
            $wallet->update(['balance' => $calculatedBalance]);
            $wallet->refresh();
        }

        return [
            'wallet' => $wallet,
            'calculated_balance' => $calculatedBalance,
            'sync_result' => $syncResult,
        ];
    }

    /**
     * Get current balance with caching
     */
    public function getCachedBalance(User $user): float
    {
        $cacheKey = "user_balance_{$user->id}";

        return Cache::remember($cacheKey, self::BALANCE_CACHE_TTL, function () use ($user) {
            return $this->balanceSyncService->calculateBalanceFromApprovedTransactions($user);
        });
    }

    /**
     * Invalidate balance cache for a user
     */
    public function invalidateCache(User $user): void
    {
        Cache::forget("user_balance_{$user->id}");
    }

    /**
     * Calculate transaction statistics for a user
     */
    public function calculateStats(\Illuminate\Contracts\Auth\Authenticatable $user, array $filters = []): array
    {
        $totals = $this->balanceSyncService->calculateTotalsFromApprovedTransactions($user);
        $calculatedBalance = $this->balanceSyncService->calculateBalanceFromApprovedTransactions($user);

        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');

        $totalCredits = max(0, (float) ($totals['total_credits'] ?? 0));
        $totalDebits = max(0, (float) ($totals['total_debits'] ?? 0));

        // For admins: net amount = credits (no debits)
        // For integrators: net = integrator_share (credits - debits)
        $netAmount = ($isIntegrator || $isAdmin) ? $totalCredits : 
            (float) ($totals['net_amount'] ?? $calculatedBalance ?? 0);

        return [
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'net_amount' => $netAmount,
            'current_balance' => $calculatedBalance,
            'calculated_balance' => $calculatedBalance,
        ];
    }

    /**
     * Ensure transaction details exist for all approved transactions
     */
    public function ensureTransactionDetailsExist(User $user): void
    {
        try {
            $transactionService = app(\App\Services\ReservationTransactionService::class);
            
            // Get approved transactions without details
            $transactionsWithoutDetails = $this->getTransactionsWithoutDetails($user);

            $createdCount = 0;
            $failedCount = 0;

            foreach ($transactionsWithoutDetails as $transaction) {
                try {
                    $transaction->loadMissing([
                        'reservation',
                        'chargingPoint',
                        'chargingPoint.businessProfile',
                        'chargingPoint.integrator',
                        'chargingPoint.partner',
                        'user'
                    ]);

                    $result = $transactionService->createRetroactiveTransactionDetail($transaction);

                    if ($result['success']) {
                        $createdCount++;
                    } else {
                        $this->handleDetailCreationFailure($transaction, $result['errors'] ?? []);
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Transaction detail creation failed', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failedCount++;
                }
            }

            if ($createdCount > 0 || $failedCount > 0) {
                Log::info('Transaction details creation completed', [
                    'user_id' => $user->id,
                    'created' => $createdCount,
                    'failed' => $failedCount,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to ensure transaction details exist', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get transactions without details
     */
    protected function getTransactionsWithoutDetails(User $user)
    {
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);

        $query = \App\Models\Transaction::where(function ($statusQuery) {
            $statusQuery->whereIn('status', ['completed', 'confirmed'])
                ->orWhereHas('reservation', function ($resQuery) {
                    $resQuery->whereIn('status', ['confirmed', 'active', 'completed']);
                });
        })
        ->where('status', '!=', 'pending')
        ->where(function ($amountQuery) {
            $amountQuery->where('amount', '>', 0)
                       ->orWhere('price_total', '>', 0);
        })
        ->whereDoesntHave('transactionDetail');

        if ($isIntegrator) {
            $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
            $integratorModelId = $integratorModel?->id;

            // Build operator user IDs and partner IDs for full hierarchical scope
            $allUserIds = \App\Models\User::where('integrator_id', $integratorModelId ?? 0)
                ->orWhere('id', $user->id)
                ->pluck('id')
                ->toArray();
            $partnerIds = \App\Models\Partner::where('integrator_id', $integratorModelId ?? 0)
                ->pluck('id')
                ->toArray();

            $query->where(function ($q) use ($user, $integratorModelId, $allUserIds, $partnerIds) {
                $q->whereHas('chargingPoint', function ($cpQuery) use ($integratorModelId, $allUserIds, $partnerIds) {
                    $cpQuery->where(function ($cpSubQuery) use ($integratorModelId, $allUserIds, $partnerIds) {
                        if ($integratorModelId) {
                            $cpSubQuery->where('integrator_id', $integratorModelId);
                        }
                        $cpSubQuery->orWhereIn('user_id', $allUserIds)
                                   ->orWhereIn('partner_id', $partnerIds);
                    });
                })
                ->orWhereHas('reservation.user', function ($userQuery) use ($integratorModelId) {
                    if ($integratorModelId) {
                        $userQuery->where('integrator_id', $integratorModelId);
                    }
                });
            });
        } elseif ($isOperator) {
            $partnerId = $user->partner_id ?? null;
            $query->whereHas('chargingPoint', function ($q) use ($user, $partnerId) {
                $q->where(function ($q2) use ($user, $partnerId) {
                    $q2->where('user_id', $user->id)
                       ->orWhere('created_by_id', $user->id)
                       ->orWhere('created_by', $user->id);
                    if ($partnerId) {
                        $q2->orWhere('partner_id', $partnerId);
                    }
                })
                ->orWhereHas('group', function ($q3) use ($user, $partnerId) {
                    $q3->where('user_id', $user->id);
                    if ($partnerId) {
                        $q3->orWhere('partner_id', $partnerId);
                    }
                });
            });
        }
        // Admin sees all, no additional filter

        return $query->get();
    }

    /**
     * Handle transaction detail creation failure
     */
    protected function handleDetailCreationFailure($transaction, array $errors): void
    {
        $hasMissingBusinessProfiles = collect($errors)->contains(function ($error) {
            return stripos($error, 'business profile') !== false ||
                   stripos($error, 'business_profile') !== false ||
                   stripos($error, 'hierarchy') !== false;
        });

        if ($hasMissingBusinessProfiles) {
            try {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'transaction_fee_percentage' => 0.0,
                    'transaction_fee_fixed' => 0.0,
                    'transaction_fee_total' => 0.0,
                    'admin_share_amount' => 0.0,
                    'integrator_share_amount' => 0.0,
                    'operator_share_amount' => 0.0,
                    'admin_share_percentage' => 0.0,
                    'integrator_share_percentage' => 0.0,
                    'admin_paid' => false,
                    'integrator_paid' => false,
                    'operator_paid' => false,
                    'calculation_details' => [
                        'created_at' => now()->toIso8601String(),
                        'creation_reason' => 'Created with defaults - Missing Business Profiles',
                        'errors' => $errors,
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create default transaction detail', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Recalculate zero-share transaction details
     */
    public function recalculateZeroShareDetails(User $user): void
    {
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);

        $query = TransactionDetail::whereHas('transaction', function ($q) {
            $q->where(function ($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                    ->orWhereHas('reservation', function ($resQuery) {
                        $resQuery->whereIn('status', ['confirmed', 'active', 'completed']);
                    });
            })
            ->where('status', '!=', 'pending')
            ->where(function ($amountQuery) {
                $amountQuery->where('amount', '>', 0)
                           ->orWhere('price_total', '>', 0);
            });
        })
        ->where(function ($zeroShareQuery) use ($isAdmin, $isIntegrator, $isOperator, $user) {
            if ($isAdmin) {
                $zeroShareQuery->where('admin_share_amount', '<=', 0);
            } elseif ($isIntegrator) {
                $zeroShareQuery->where('integrator_share_amount', '<=', 0)
                              ->where('integrator_creator_id', $user->id);
            } elseif ($isOperator) {
                $partnerId = $user->partner_id ?? null;
                $zeroShareQuery->where('operator_share_amount', '<=', 0)
                              ->where(function ($opQuery) use ($user, $partnerId) {
                                  $opQuery->where('operator_id', $user->id)
                                         ->orWhereHas('transaction.chargingPoint', function ($cpQuery) use ($user, $partnerId) {
                                             $cpQuery->where(function ($cpSubQuery) use ($user, $partnerId) {
                                                 $cpSubQuery->where('user_id', $user->id)
                                                           ->orWhere('created_by_id', $user->id)
                                                           ->orWhere('created_by', $user->id);
                                                 if ($partnerId) {
                                                     $cpSubQuery->orWhere('partner_id', $partnerId);
                                                 }
                                             });
                                             if ($partnerId) {
                                                 $cpQuery->orWhereHas('group', function ($gq) use ($partnerId) {
                                                     $gq->where('partner_id', $partnerId);
                                                 });
                                             }
                                         });
                              });
            }
        });

        $zeroShareDetails = $query->limit(50)->get();

        if ($zeroShareDetails->isEmpty()) {
            return;
        }

        $transactionService = app(\App\Services\ReservationTransactionService::class);

        foreach ($zeroShareDetails as $detail) {
            try {
                $transaction = $detail->transaction;
                if ($transaction) {
                    $result = $transactionService->recalculateTransactionShares($transaction, true);

                    if ($result['success']) {
                        Log::info('Transaction detail recalculated', [
                            'transaction_id' => $transaction->id,
                            'admin_share' => $result['transaction_detail']->admin_share_amount ?? 0,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to recalculate transaction detail', [
                    'transaction_detail_id' => $detail->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
