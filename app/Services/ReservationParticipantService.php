<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationParticipant;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationParticipantService
{
    public function syncParticipants(Reservation $reservation, ?array $participants = null, ?float $totalAmount = null): Collection
    {
        $participants = $this->normalizeParticipants($reservation, $participants);
        $totalAmount = $this->resolveTotalAmount($reservation, $totalAmount);

        if ($totalAmount <= 0) {
            Log::warning('ReservationParticipantService: total amount is zero', [
                'reservation_id' => $reservation->id,
            ]);
        }

        $computed = $this->computeShareAmounts($participants, $totalAmount, $reservation->user_id);

        return DB::transaction(function () use ($reservation, $participants, $computed) {
            $userIds = collect($participants)->pluck('user_id')->filter()->unique()->values();

            if ($userIds->isEmpty() && $reservation->user_id) {
                $userIds = collect([$reservation->user_id]);
            }

            // Cancel participants removed from list
            if ($userIds->isNotEmpty()) {
                ReservationParticipant::where('reservation_id', $reservation->id)
                    ->whereNotIn('user_id', $userIds->all())
                    ->update(['status' => 'cancelled']);
            }

            $records = collect();

            foreach ($participants as $participant) {
                $userId = $participant['user_id'] ?? null;
                if (!$userId) {
                    continue;
                }

                $shareAmount = $computed[$userId]['share_amount'] ?? 0;
                $shareType = $computed[$userId]['share_type'] ?? $participant['share_type'];
                $shareValue = $computed[$userId]['share_value'] ?? $participant['share_value'];

                $record = ReservationParticipant::firstOrNew([
                    'reservation_id' => $reservation->id,
                    'user_id' => $userId,
                ]);

                $record->share_type = $shareType;
                $record->share_value = $shareValue;
                $record->share_amount = $shareAmount;

                $record->metadata = array_merge(
                    $record->metadata ?? [],
                    $participant['metadata'] ?? []
                );

                // If no external payments recorded, compute from wallet transactions
                $walletPaid = $this->calculateWalletPaidAmount($reservation, $userId);
                $externalPaid = (float) ($record->metadata['external_paid_amount'] ?? 0);
                if ($walletPaid > 0 || $externalPaid > 0) {
                    $record->paid_amount = $walletPaid + $externalPaid;
                }

                $record->status = $this->resolveParticipantStatus(
                    (float) $record->paid_amount,
                    (float) $record->share_amount,
                    $record->status
                );

                $record->save();
                $records->push($record);
            }

            return $records;
        });
    }

    public function ensureParticipants(Reservation $reservation): Collection
    {
        return $this->syncParticipants($reservation, null, null);
    }

    public function applyWalletCharges(Reservation $reservation, string $paymentMode = 'prepaid', bool $allowPartial = false): array
    {
        $participants = $this->ensureParticipants($reservation);
        $results = [];

        return DB::transaction(function () use ($participants, $reservation, $paymentMode, $allowPartial, &$results) {
            foreach ($participants as $participant) {
                if (!$participant->user_id) {
                    continue;
                }

                $remaining = max(0, (float) $participant->share_amount - (float) $participant->paid_amount);
                if ($remaining <= 0.01) {
                    $results[] = [
                        'participant_id' => $participant->id,
                        'status' => 'already_paid',
                    ];
                    continue;
                }

                $user = User::find($participant->user_id);
                if (!$user) {
                    continue;
                }

                $wallet = $user->getOrCreateWallet();
                $wallet->refresh();

                if (!$wallet->hasSufficientBalance($remaining)) {
                    if (!$allowPartial) {
                        throw new \Exception("Solde insuffisant pour {$user->name}. Requis: {$remaining}, Disponible: {$wallet->balance}");
                    }

                    $remaining = max(0, $wallet->getAvailableBalance());
                    if ($remaining <= 0) {
                        $results[] = [
                            'participant_id' => $participant->id,
                            'status' => 'insufficient_balance',
                        ];
                        continue;
                    }
                }

                $transaction = $wallet->debit(
                    $remaining,
                    "Part réservation #{$reservation->id}",
                    [
                        'reservation_id' => $reservation->id,
                        'participant_user_id' => $participant->user_id,
                        'participant_share_amount' => (float) $participant->share_amount,
                        'participant_share_type' => $participant->share_type,
                        'participant_share_value' => (float) $participant->share_value,
                        'payment_mode' => $paymentMode,
                        'type' => $paymentMode === 'postpaid' ? 'postpaid_payment' : 'prepaid_payment',
                        'participant_action' => 'share_debit',
                    ]
                );

                $participant->paid_amount = (float) $participant->paid_amount + $remaining;
                $participant->last_wallet_transaction_id = $transaction->id;
                $participant->status = $this->resolveParticipantStatus(
                    (float) $participant->paid_amount,
                    (float) $participant->share_amount,
                    $participant->status
                );

                $metadata = $participant->metadata ?? [];
                $metadata['wallet_payment_transactions'] = array_values(array_unique(array_merge(
                    $metadata['wallet_payment_transactions'] ?? [],
                    [$transaction->id]
                )));
                $participant->metadata = $metadata;
                $participant->save();

                $results[] = [
                    'participant_id' => $participant->id,
                    'wallet_transaction_id' => $transaction->id,
                    'charged_amount' => $remaining,
                    'status' => $participant->status,
                ];
            }

            return [
                'success' => true,
                'results' => $results,
            ];
        });
    }

    public function markExternalPayment(Reservation $reservation, string $paymentMethod = 'external'): array
    {
        $participants = $this->ensureParticipants($reservation);

        return DB::transaction(function () use ($participants, $paymentMethod) {
            foreach ($participants as $participant) {
                $participant->paid_amount = (float) $participant->share_amount;
                $participant->status = 'paid';
                $metadata = $participant->metadata ?? [];
                $metadata['external_paid_amount'] = (float) $participant->share_amount;
                $metadata['payment_method'] = $paymentMethod;
                $metadata['participant_action'] = 'external_payment';
                $participant->metadata = $metadata;
                $participant->save();
            }

            return ['success' => true];
        });
    }

    public function handlePaymentConfirmation(Reservation $reservation): array
    {
        if ($this->isWalletPayment($reservation)) {
            $mode = strtolower((string) ($reservation->payment_mode ?? 'prepaid'));
            $mode = $mode === 'postpaid' ? 'postpaid' : 'prepaid';
            return $this->applyWalletCharges($reservation, $mode);
        }

        return $this->markExternalPayment($reservation, (string) ($reservation->payment_method ?? 'external'));
    }

    public function recalculateShares(Reservation $reservation, ?float $newTotal = null): array
    {
        $participants = $reservation->participants()->get();
        if ($participants->isEmpty()) {
            $participants = $this->ensureParticipants($reservation);
        }

        $computed = $this->computeShareAmounts(
            $participants->map(function ($participant) {
                return [
                    'user_id' => $participant->user_id,
                    'share_type' => $participant->share_type,
                    'share_value' => $participant->share_value,
                    'metadata' => $participant->metadata ?? [],
                ];
            })->all(),
            $this->resolveTotalAmount($reservation, $newTotal),
            $reservation->user_id
        );

        $walletPayment = $this->isWalletPayment($reservation);

        return DB::transaction(function () use ($participants, $computed, $reservation, $walletPayment) {
            $adjustments = [];

            foreach ($participants as $participant) {
                $userId = $participant->user_id;
                if (!$userId) {
                    continue;
                }

                $newShare = (float) ($computed[$userId]['share_amount'] ?? $participant->share_amount);
                $oldShare = (float) $participant->share_amount;
                $delta = round($newShare - $oldShare, 2);

                $participant->share_amount = $newShare;
                $participant->share_type = $computed[$userId]['share_type'] ?? $participant->share_type;
                $participant->share_value = $computed[$userId]['share_value'] ?? $participant->share_value;

                if ($walletPayment && strtoupper((string) $reservation->payment_status) === 'PAID' && abs($delta) > 0.01) {
                    $user = User::find($participant->user_id);
                    if ($user) {
                        $wallet = $user->getOrCreateWallet();
                        $wallet->refresh();

                        if ($delta > 0) {
                            if (!$wallet->hasSufficientBalance($delta)) {
                                throw new \Exception("Solde insuffisant pour ajustement. Requis: {$delta}, Disponible: {$wallet->balance}");
                            }
                            $tx = $wallet->debit(
                                $delta,
                                "Ajustement part réservation #{$reservation->id}",
                                [
                                    'reservation_id' => $reservation->id,
                                    'participant_user_id' => $participant->user_id,
                                    'participant_action' => 'share_adjustment_debit',
                                ]
                            );
                            $participant->paid_amount = (float) $participant->paid_amount + $delta;
                            $participant->last_wallet_transaction_id = $tx->id;
                        } else {
                            $refund = abs($delta);
                            if ($refund > 0.01 && $participant->paid_amount > 0) {
                                $tx = $wallet->credit(
                                    $refund,
                                    "Remboursement part réservation #{$reservation->id}",
                                    [
                                        'reservation_id' => $reservation->id,
                                        'participant_user_id' => $participant->user_id,
                                        'participant_action' => 'share_adjustment_refund',
                                    ]
                                );
                                $participant->paid_amount = max(0, (float) $participant->paid_amount - $refund);
                                $participant->last_refund_transaction_id = $tx->id;
                            }
                        }
                    }
                }

                if (!$walletPayment && strtoupper((string) $reservation->payment_status) === 'PAID') {
                    $participant->paid_amount = (float) $participant->share_amount;
                }

                $participant->status = $this->resolveParticipantStatus(
                    (float) $participant->paid_amount,
                    (float) $participant->share_amount,
                    $participant->status
                );

                $participant->save();

                if (abs($delta) > 0.01) {
                    $adjustments[] = [
                        'participant_id' => $participant->id,
                        'delta' => $delta,
                    ];
                }
            }

            return [
                'success' => true,
                'adjustments' => $adjustments,
            ];
        });
    }

    public function cancelReservation(Reservation $reservation, string $reason = 'cancelled'): array
    {
        $participants = $reservation->participants()->get();
        if ($participants->isEmpty()) {
            return ['success' => true];
        }

        $walletPayment = $this->isWalletPayment($reservation);

        return DB::transaction(function () use ($participants, $reservation, $walletPayment, $reason) {
            foreach ($participants as $participant) {
                $refunded = false;
                if ($walletPayment && $participant->paid_amount > 0 && $participant->user_id) {
                    $user = User::find($participant->user_id);
                    if ($user) {
                        $wallet = $user->getOrCreateWallet();
                        $wallet->refresh();

                        $refundAmount = (float) $participant->paid_amount;
                        $tx = $wallet->credit(
                            $refundAmount,
                            "Annulation réservation #{$reservation->id}",
                            [
                                'reservation_id' => $reservation->id,
                                'participant_user_id' => $participant->user_id,
                                'participant_action' => 'reservation_cancel_refund',
                                'reason' => $reason,
                            ]
                        );

                        $participant->paid_amount = 0;
                        $participant->last_refund_transaction_id = $tx->id;
                        $refunded = true;
                    }
                }

                if ($walletPayment && $refunded) {
                    $participant->status = 'refunded';
                } else {
                    $participant->status = 'cancelled';
                }
                $participant->save();
            }

            return ['success' => true];
        });
    }

    public function recordParticipantPayment(
        ReservationParticipant $participant,
        float $amount,
        string $paymentMode = 'manual',
        string $paymentMethod = 'wallet'
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        $remaining = max(0, (float) $participant->share_amount - (float) $participant->paid_amount);
        $amount = min($amount, $remaining);

        if ($paymentMethod === 'external') {
            $participant->paid_amount = (float) $participant->paid_amount + $amount;
            $participant->status = $this->resolveParticipantStatus(
                (float) $participant->paid_amount,
                (float) $participant->share_amount,
                $participant->status
            );
            $metadata = $participant->metadata ?? [];
            $metadata['external_paid_amount'] = (float) ($metadata['external_paid_amount'] ?? 0) + $amount;
            $metadata['payment_method'] = 'external';
            $metadata['participant_action'] = 'external_partial_payment';
            $participant->metadata = $metadata;
            $participant->save();

            return ['success' => true, 'status' => $participant->status];
        }

        $user = $participant->user;
        if (!$user) {
            throw new \Exception('Participant sans utilisateur associé.');
        }

        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();

        if (!$wallet->hasSufficientBalance($amount)) {
            throw new \Exception("Solde insuffisant. Requis: {$amount}, Disponible: {$wallet->balance}");
        }

        $transaction = $wallet->debit(
            $amount,
            "Paiement part réservation #{$participant->reservation_id}",
            [
                'reservation_id' => $participant->reservation_id,
                'participant_user_id' => $participant->user_id,
                'payment_mode' => $paymentMode,
                'participant_action' => 'share_manual_payment',
            ]
        );

        $participant->paid_amount = (float) $participant->paid_amount + $amount;
        $participant->last_wallet_transaction_id = $transaction->id;
        $participant->status = $this->resolveParticipantStatus(
            (float) $participant->paid_amount,
            (float) $participant->share_amount,
            $participant->status
        );
        $participant->save();

        return [
            'success' => true,
            'wallet_transaction_id' => $transaction->id,
            'status' => $participant->status,
        ];
    }

    protected function normalizeParticipants(Reservation $reservation, ?array $participants): array
    {
        if ($participants === null) {
            $existing = $reservation->participants()->get();
            if ($existing->isNotEmpty()) {
                return $existing->map(function ($participant) {
                    return [
                        'user_id' => $participant->user_id,
                        'share_type' => $participant->share_type,
                        'share_value' => (float) $participant->share_value,
                        'metadata' => $participant->metadata ?? [],
                    ];
                })->values()->all();
            }
        }

        if (empty($participants)) {
            if ($reservation->user_id) {
                return [[
                    'user_id' => $reservation->user_id,
                    'share_type' => 'percentage',
                    'share_value' => 100,
                    'metadata' => ['is_primary' => true],
                ]];
            }
            return [];
        }

        return collect($participants)
            ->filter(fn ($p) => !empty($p['user_id']))
            ->map(function ($p) {
                return [
                    'user_id' => (int) $p['user_id'],
                    'share_type' => $p['share_type'] ?? 'percentage',
                    'share_value' => (float) ($p['share_value'] ?? 0),
                    'metadata' => $p['metadata'] ?? [],
                ];
            })
            ->values()
            ->tap(function ($collection) use ($reservation) {
                if ($collection->isEmpty() && $reservation->user_id) {
                    $collection->push([
                        'user_id' => $reservation->user_id,
                        'share_type' => 'percentage',
                        'share_value' => 100,
                        'metadata' => ['is_primary' => true],
                    ]);
                }
            })
            ->all();
    }

    protected function computeShareAmounts(array $participants, float $totalAmount, ?int $primaryUserId = null): array
    {
        $computed = [];
        $sum = 0;

        foreach ($participants as $participant) {
            $shareType = $participant['share_type'] ?? 'percentage';
            $shareValue = (float) ($participant['share_value'] ?? 0);

            $amount = 0;
            if ($shareType === 'fixed') {
                $amount = $shareValue;
            } else {
                $amount = round($totalAmount * ($shareValue / 100), 2);
            }

            $computed[$participant['user_id']] = [
                'share_amount' => $amount,
                'share_type' => $shareType,
                'share_value' => $shareValue,
            ];
            $sum += $amount;
        }

        if ($sum > 0 && $sum - $totalAmount > 0.01) {
            $ratio = $totalAmount / $sum;
            $sum = 0;
            foreach ($computed as $userId => $values) {
                $adjusted = round($values['share_amount'] * $ratio, 2);
                $computed[$userId]['share_amount'] = $adjusted;
                $sum += $adjusted;
            }
        }

        $difference = round($totalAmount - $sum, 2);
        if (abs($difference) > 0.01 && !empty($participants)) {
            $primaryId = $primaryUserId;
            if (!$primaryId || !isset($computed[$primaryId])) {
                $primaryId = $participants[0]['user_id'];
            }
            $computed[$primaryId]['share_amount'] = round($computed[$primaryId]['share_amount'] + $difference, 2);
        }

        return $computed;
    }

    protected function resolveTotalAmount(Reservation $reservation, ?float $override = null): float
    {
        if ($override !== null) {
            return round($override, 2);
        }

        $amount = (float) ($reservation->actual_cost ?? 0);
        if ($amount > 0) {
            return round($amount, 2);
        }

        $amount = (float) ($reservation->estimated_cost ?? 0);
        if ($amount > 0) {
            return round($amount, 2);
        }

        $transaction = $reservation->transaction;
        if ($transaction) {
            $amount = (float) ($transaction->price_total ?? $transaction->amount ?? 0);
        }

        return round($amount, 2);
    }

    protected function isWalletPayment(Reservation $reservation): bool
    {
        $method = strtolower((string) ($reservation->payment_method ?? ''));
        return in_array($method, ['credit', 'prepaid_credit', 'postpaid_credit'], true);
    }

    protected function calculateWalletPaidAmount(Reservation $reservation, int $userId): float
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        $wallet = $user->getOrCreateWallet();

        $debits = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('status', 'completed')
            ->where('type', 'debit')
            ->where('metadata->reservation_id', $reservation->id)
            ->sum('amount');

        $credits = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('status', 'completed')
            ->where('type', 'credit')
            ->where('metadata->reservation_id', $reservation->id)
            ->sum('amount');

        return max(0, (float) $debits - (float) $credits);
    }

    protected function resolveParticipantStatus(float $paidAmount, float $shareAmount, ?string $currentStatus = null): string
    {
        if ($shareAmount <= 0) {
            return $currentStatus ?? 'pending';
        }

        if ($paidAmount >= $shareAmount - 0.01) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return $currentStatus === 'refunded' ? 'refunded' : 'pending';
    }
}
