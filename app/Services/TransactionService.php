<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Create a new transaction record
     * 
     * @param array $data Transaction data
     * @return Transaction
     */
    public function create(array $data): Transaction
    {
        // Generate a unique transaction ID if not provided
        if (!isset($data['transaction_id'])) {
            $data['transaction_id'] = 'TXN_' . uniqid() . '_' . time();
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        // Set default currency if not provided
        if (!isset($data['currency'])) {
            $data['currency'] = config('app.currency', 'EUR');
        }

        // Handle price_total field - ensure it's set from amount if not provided
        if (!isset($data['price_total']) && isset($data['amount'])) {
            $data['price_total'] = $data['amount'];
        }

        // Filter data to only include fillable fields
        $fillableFields = [
            'transaction_id',
            'charging_point_id',
            'user_id',
            'reservation_id',
            'session_id',
            'amount',
            'price_total',
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
            'admin_id',
            'integrator_id',
            'operator_id',
            'admin_wallet_id',
            'integrator_wallet_id',
            'operator_wallet_id',
            'admin_share_amount',
            'integrator_share_amount',
            'operator_share_amount',
            'price_tax',
            'amount_ht',
            'admin_commission',
            'integrator_commission',
            'partner_commission',
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
        ];

        $filteredData = array_intersect_key($data, array_flip($fillableFields));

        // Create the transaction record
        $transaction = Transaction::create($filteredData);

        Log::info('Transaction created via TransactionService', [
            'transaction_id' => $transaction->id,
            'transaction_uid' => $transaction->transaction_id,
            'reservation_id' => $transaction->reservation_id ?? null,
            'amount' => $transaction->amount,
            'status' => $transaction->status,
        ]);

        return $transaction;
    }

    /**
     * Update an existing transaction
     * 
     * @param Transaction|int $transaction Transaction model or ID
     * @param array $data Data to update
     * @return Transaction
     */
    public function update($transaction, array $data): Transaction
    {
        if (is_int($transaction)) {
            $transaction = Transaction::findOrFail($transaction);
        }

        $transaction->update($data);

        Log::info('Transaction updated via TransactionService', [
            'transaction_id' => $transaction->id,
            'updated_fields' => array_keys($data),
        ]);

        return $transaction->fresh();
    }

    /**
     * Find a transaction by ID
     * 
     * @param int $id
     * @return Transaction|null
     */
    public function find(int $id): ?Transaction
    {
        return Transaction::find($id);
    }

    /**
     * Find a transaction by transaction_id (string UID)
     * 
     * @param string $transactionId
     * @return Transaction|null
     */
    public function findByTransactionId(string $transactionId): ?Transaction
    {
        return Transaction::where('transaction_id', $transactionId)->first();
    }

    /**
     * Process a complete charging transaction
     */
    public static function processChargingTransaction(array $transactionData): array
    {
        $transactionId = uniqid('TXN_');
        
        try {
            return DB::transaction(function () use ($transactionData, $transactionId) {
                // Step 1: Identify the charging point and hierarchy
                $hierarchy = self::identifyHierarchy($transactionData['charging_point_id']);
                
                // Step 2: Calculate pricing based on business profile
                $pricing = self::calculatePricing($hierarchy, $transactionData);
                
                // Step 3: Process wallet debits with rollback capability
                $debitResults = self::processWalletDebits($hierarchy, $pricing, $transactionData);
                
                // Step 4: Create transaction record
                $transaction = self::createTransactionRecord($transactionId, $hierarchy, $pricing, $debitResults, $transactionData);
                
                // Step 5: Log successful transaction
                self::logTransaction($transaction, 'completed');
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'transaction' => $transaction,
                    'hierarchy' => $hierarchy,
                    'pricing' => $pricing,
                    'debit_results' => $debitResults,
                ];
            });
        } catch (\Exception $e) {
            // Log failed transaction
            self::logFailedTransaction($transactionId, $e, $transactionData);
            
            return [
                'success' => false,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'rollback_required' => true,
            ];
        }
    }

    /**
     * Identify the complete hierarchy for a charging point
     */
    public static function identifyHierarchy(int $chargingPointId): array
    {
        $chargingPoint = ChargingPoint::with(['group.partner.integrator', 'businessProfile'])->findOrFail($chargingPointId);
        
        $hierarchy = [
            'charging_point' => $chargingPoint,
            'group' => $chargingPoint->group,
            'partner' => null,
            'integrator' => null,
            'operator' => null,
            'business_profile' => $chargingPoint->businessProfile,
        ];
        
        // Get partner from group
        if ($chargingPoint->group && $chargingPoint->group->partner) {
            $hierarchy['partner'] = $chargingPoint->group->partner;
            
            // Get integrator from partner
            if ($chargingPoint->group->partner->integrator) {
                $hierarchy['integrator'] = $chargingPoint->group->partner->integrator;
            }
        }
        
        // Get operator (if any) - find user with operator role in this integrator
        if ($hierarchy['integrator']) {
            $operator = User::where('integrator_id', $hierarchy['integrator']->id)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'operator');
                })
                ->first();
            
            if ($operator) {
                $hierarchy['operator'] = $operator;
            }
        }
        
        return $hierarchy;
    }

    /**
     * Calculate pricing based on business profile and hierarchy
     */
    public static function calculatePricing(array $hierarchy, array $transactionData): array
    {
        $businessProfile = $hierarchy['business_profile'];
        
        if (!$businessProfile) {
            throw new \Exception('No business profile found for charging point');
        }
        
        $baseAmount = $transactionData['amount'] ?? 0;
        $energyDelivered = $transactionData['energy_delivered'] ?? 0;
        $duration = $transactionData['duration_minutes'] ?? 0;
        
        // Calculate base pricing
        $pricing = [
            'base_amount' => $baseAmount,
            'energy_delivered' => $energyDelivered,
            'duration_minutes' => $duration,
            'currency' => $businessProfile->currency ?? 'EUR',
        ];
        
        // Calculate commissions and fees
        $pricing['admin_fee'] = self::calculateAdminFee($businessProfile, $baseAmount);
        $pricing['integrator_fee'] = self::calculateIntegratorFee($businessProfile, $baseAmount);
        $pricing['partner_fee'] = self::calculatePartnerFee($businessProfile, $baseAmount);
        $pricing['operator_fee'] = self::calculateOperatorFee($businessProfile, $baseAmount);
        
        // Calculate net amounts for each party
        $pricing['admin_amount'] = $pricing['admin_fee'];
        $pricing['integrator_amount'] = $pricing['integrator_fee'];
        $pricing['partner_amount'] = $pricing['partner_fee'];
        $pricing['operator_amount'] = $pricing['operator_fee'];
        
        // Calculate total fees
        $pricing['total_fees'] = $pricing['admin_fee'] + $pricing['integrator_fee'] + 
                                 $pricing['partner_fee'] + $pricing['operator_fee'];
        
        // Calculate net amount for user
        $pricing['user_amount'] = $baseAmount - $pricing['total_fees'];
        
        return $pricing;
    }

    /**
     * Calculate admin fee
     */
    protected static function calculateAdminFee(BusinessProfile $businessProfile, float $baseAmount): float
    {
        $fixedFee = $businessProfile->admin_fee_fixed ?? 0;
        $percentageFee = $businessProfile->admin_fee_percentage ?? 0;
        
        return $fixedFee + ($baseAmount * $percentageFee / 100);
    }

    /**
     * Calculate integrator fee
     */
    protected static function calculateIntegratorFee(BusinessProfile $businessProfile, float $baseAmount): float
    {
        $fixedFee = $businessProfile->integrator_fee_fixed ?? 0;
        $percentageFee = $businessProfile->integrator_fee_percentage ?? 0;
        
        return $fixedFee + ($baseAmount * $percentageFee / 100);
    }

    /**
     * Calculate partner fee
     */
    protected static function calculatePartnerFee(BusinessProfile $businessProfile, float $baseAmount): float
    {
        $fixedFee = $businessProfile->partner_fee_fixed ?? 0;
        $percentageFee = $businessProfile->partner_fee_percentage ?? 0;
        
        return $fixedFee + ($baseAmount * $percentageFee / 100);
    }

    /**
     * Calculate operator fee
     */
    protected static function calculateOperatorFee(BusinessProfile $businessProfile, float $baseAmount): float
    {
        // Operator fee might be calculated differently
        // This is a placeholder - adjust based on your business logic
        return 0; // Implement based on your requirements
    }

    /**
     * Process wallet debits with rollback capability
     */
    public static function processWalletDebits(array $hierarchy, array $pricing, array $transactionData): array
    {
        $debitResults = [];
        $debitTransactions = [];
        
        try {
            // Get user wallet (the one paying)
            $user = User::findOrFail($transactionData['user_id']);
            $userWallet = $user->getOrCreateWallet();
            
            // Check if user has sufficient balance
            if (!$userWallet->hasSufficientBalance($pricing['base_amount'])) {
                throw new \Exception('Insufficient balance in user wallet');
            }
            
            // Debit from user wallet
            $sessionId = $transactionData['session_id'] ?? 'Unknown';
            $userTransaction = $userWallet->debit(
                $pricing['base_amount'],
                "Charging session payment - {$sessionId}",
                [
                    'transaction_type' => 'charging_session',
                    'session_id' => $transactionData['session_id'] ?? null,
                    'charging_point_id' => $transactionData['charging_point_id'],
                ]
            );
            
            $debitResults['user'] = [
                'wallet_id' => $userWallet->id,
                'amount' => $pricing['base_amount'],
                'transaction' => $userTransaction,
                'success' => true,
            ];
            $debitTransactions[] = $userTransaction;
            
            // Credit to admin wallet (if exists)
            if ($pricing['admin_amount'] > 0) {
                $adminWallet = self::getOrCreateAdminWallet();
                $adminTransaction = $adminWallet->credit(
                    $pricing['admin_amount'],
                    "Admin fee from charging session",
                    [
                        'transaction_type' => 'admin_fee',
                        'session_id' => $transactionData['session_id'] ?? null,
                        'charging_point_id' => $transactionData['charging_point_id'],
                    ]
                );
                
                $debitResults['admin'] = [
                    'wallet_id' => $adminWallet->id,
                    'amount' => $pricing['admin_amount'],
                    'transaction' => $adminTransaction,
                    'success' => true,
                ];
            }
            
            // Credit to integrator wallet (if exists)
            if ($pricing['integrator_amount'] > 0 && $hierarchy['integrator']) {
                $integratorWallet = $hierarchy['integrator']->getOrCreateWallet();
                $integratorTransaction = $integratorWallet->credit(
                    $pricing['integrator_amount'],
                    "Integrator commission from charging session",
                    [
                        'transaction_type' => 'integrator_commission',
                        'session_id' => $transactionData['session_id'] ?? null,
                        'charging_point_id' => $transactionData['charging_point_id'],
                    ]
                );
                
                $debitResults['integrator'] = [
                    'wallet_id' => $integratorWallet->id,
                    'amount' => $pricing['integrator_amount'],
                    'transaction' => $integratorTransaction,
                    'success' => true,
                ];
            }
            
            // Credit to partner wallet (if exists)
            if ($pricing['partner_amount'] > 0 && $hierarchy['partner']) {
                $partnerWallet = $hierarchy['partner']->getOrCreateWallet();
                $partnerTransaction = $partnerWallet->credit(
                    $pricing['partner_amount'],
                    "Partner commission from charging session",
                    [
                        'transaction_type' => 'partner_commission',
                        'session_id' => $transactionData['session_id'] ?? null,
                        'charging_point_id' => $transactionData['charging_point_id'],
                    ]
                );
                
                $debitResults['partner'] = [
                    'wallet_id' => $partnerWallet->id,
                    'amount' => $pricing['partner_amount'],
                    'transaction' => $partnerTransaction,
                    'success' => true,
                ];
            }
            
            // Credit to operator wallet (if exists)
            if ($pricing['operator_amount'] > 0 && $hierarchy['operator']) {
                $operatorWallet = $hierarchy['operator']->getOrCreateWallet();
                $operatorTransaction = $operatorWallet->credit(
                    $pricing['operator_amount'],
                    "Operator commission from charging session",
                    [
                        'transaction_type' => 'operator_commission',
                        'session_id' => $transactionData['session_id'] ?? null,
                        'charging_point_id' => $transactionData['charging_point_id'],
                    ]
                );
                
                $debitResults['operator'] = [
                    'wallet_id' => $operatorWallet->id,
                    'amount' => $pricing['operator_amount'],
                    'transaction' => $operatorTransaction,
                    'success' => true,
                ];
            }
            
            return $debitResults;
            
        } catch (\Exception $e) {
            // Rollback all successful transactions
            self::rollbackDebits($debitTransactions);
            throw $e;
        }
    }

    /**
     * Rollback debit transactions
     */
    protected static function rollbackDebits(array $transactions): void
    {
        foreach ($transactions as $transaction) {
            try {
                // Reverse the transaction
                $wallet = $transaction->wallet;
                $reverseAmount = $transaction->type === 'debit' ? $transaction->amount : -$transaction->amount;
                
                $wallet->credit(
                    $reverseAmount,
                    "Rollback of transaction {$transaction->id}",
                    [
                        'rollback' => true,
                        'original_transaction_id' => $transaction->id,
                    ]
                );
                
                Log::info('Transaction rolled back', [
                    'original_transaction_id' => $transaction->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $reverseAmount,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to rollback transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get or create admin wallet
     */
    protected static function getOrCreateAdminWallet(): Wallet
    {
        // Find or create a system admin user
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();
        
        if (!$admin) {
            throw new \Exception('No admin user found for system wallet');
        }
        
        return $admin->getOrCreateWallet([
            'name' => 'System Admin Wallet',
            'description' => 'System wallet for admin fees',
        ]);
    }

    /**
     * Create transaction record
     */
    protected static function createTransactionRecord(string $transactionId, array $hierarchy, array $pricing, array $debitResults, array $transactionData): array
    {
        $transaction = [
            'id' => $transactionId,
            'charging_point_id' => $transactionData['charging_point_id'],
            'user_id' => $transactionData['user_id'],
            'session_id' => $transactionData['session_id'] ?? null,
            'amount' => $pricing['base_amount'],
            'currency' => $pricing['currency'],
            'status' => 'completed',
            'hierarchy' => [
                'charging_point' => $hierarchy['charging_point']->id,
                'group' => $hierarchy['group']?->id,
                'partner' => $hierarchy['partner']?->id,
                'integrator' => $hierarchy['integrator']?->id,
                'operator' => $hierarchy['operator']?->id,
                'business_profile' => $hierarchy['business_profile']?->id,
            ],
            'pricing' => $pricing,
            'debit_results' => $debitResults,
            'created_at' => now(),
        ];
        
        // Store in database or cache as needed
        // This could be stored in a transactions table or cache
        
        return $transaction;
    }

    /**
     * Log transaction
     */
    protected static function logTransaction(array $transaction, string $status): void
    {
        Log::info('Transaction processed', [
            'transaction_id' => $transaction['id'],
            'status' => $status,
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'user_id' => $transaction['user_id'],
            'charging_point_id' => $transaction['charging_point_id'],
        ]);
    }

    /**
     * Log failed transaction
     */
    protected static function logFailedTransaction(string $transactionId, \Exception $e, array $transactionData): void
    {
        Log::error('Transaction failed', [
            'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            'transaction_data' => $transactionData,
        ]);
    }

    /**
     * Get transaction history for a user
     */
    public static function getUserTransactionHistory(int $userId, int $limit = 50): array
    {
        $user = User::findOrFail($userId);
        $wallet = $user->getOrCreateWallet();
        
        $transactions = $wallet->transactions()
            ->where('metadata->transaction_type', 'charging_session')
            ->latest()
            ->limit($limit)
            ->get();
        
        return $transactions->map(function ($transaction) {
                return [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at,
                'metadata' => $transaction->metadata,
            ];
        })->toArray();
    }

    /**
     * Get transaction statistics for hierarchy
     */
    public static function getHierarchyStatistics(array $hierarchy, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();
        
        $stats = [];
        
        // Get statistics for each level of hierarchy
        if ($hierarchy['integrator']) {
            $integratorWallet = $hierarchy['integrator']->getOrCreateWallet();
            $stats['integrator'] = WalletService::getWalletStatistics($integratorWallet, $startDate, $endDate);
        }
        
        if ($hierarchy['partner']) {
            $partnerWallet = $hierarchy['partner']->getOrCreateWallet();
            $stats['partner'] = WalletService::getWalletStatistics($partnerWallet, $startDate, $endDate);
        }
        
        if ($hierarchy['operator']) {
            $operatorWallet = $hierarchy['operator']->getOrCreateWallet();
            $stats['operator'] = WalletService::getWalletStatistics($operatorWallet, $startDate, $endDate);
        }

        return $stats;
    }

    /**
     * Validate transaction data
     */
    public static function validateTransactionData(array $transactionData): array
    {
        $errors = [];
        
        // Required fields
        $requiredFields = ['charging_point_id', 'user_id', 'amount'];
        foreach ($requiredFields as $field) {
            if (!isset($transactionData[$field]) || empty($transactionData[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        // Validate charging point exists
        if (isset($transactionData['charging_point_id'])) {
            $chargingPoint = ChargingPoint::find($transactionData['charging_point_id']);
            if (!$chargingPoint) {
                $errors[] = 'Charging point not found';
            }
        }
        
        // Validate user exists
        if (isset($transactionData['user_id'])) {
            $user = User::find($transactionData['user_id']);
            if (!$user) {
                $errors[] = 'User not found';
            }
        }
        
        // Validate amount
        if (isset($transactionData['amount'])) {
            if (!is_numeric($transactionData['amount']) || $transactionData['amount'] <= 0) {
                $errors[] = 'Amount must be a positive number';
            }
        }
        
        return $errors;
    }
}