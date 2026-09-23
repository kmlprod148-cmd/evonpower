<?php

namespace App\Services;

use App\Models\ChargingSession;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class ChargingSessionValidationService
{
    /**
     * Validate if a user can start a charging session
     */
    public static function validateSessionStart(ChargingSession $session): array
    {
        $validation = [
            'can_start' => false,
            'errors' => [],
            'warnings' => [],
            'wallet_balance' => 0,
            'required_amount' => 0,
        ];

        try {
            // Check if user exists
            if (!$session->user) {
                $validation['errors'][] = 'No user associated with session';
                return $validation;
            }

            // Check if charging point exists and is available
            if (!$session->chargingPoint) {
                $validation['errors'][] = 'Charging point not found';
                return $validation;
            }

            if ($session->chargingPoint->status !== 'online') {
                $validation['errors'][] = 'Charging point is not available';
                return $validation;
            }

            // Get user's wallet
            $wallet = $session->getUserWallet();
            if (!$wallet) {
                $validation['errors'][] = 'User wallet not found';
                return $validation;
            }

            $validation['wallet_balance'] = $wallet->balance;

            // Validate based on session mode
            if ($session->isPrepaid()) {
                $validation = self::validatePrepaidSession($session, $validation);
            } elseif ($session->isPostpaid()) {
                $validation = self::validatePostpaidSession($session, $validation);
            } else {
                $validation['errors'][] = 'Invalid session mode';
                return $validation;
            }

            // Check for any blocking errors
            if (empty($validation['errors'])) {
                $validation['can_start'] = true;
            }

        } catch (\Exception $e) {
            Log::error('Session validation failed', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);
            
            $validation['errors'][] = 'Validation failed: ' . $e->getMessage();
        }

        return $validation;
    }

    /**
     * Validate prepaid session
     */
    protected static function validatePrepaidSession(ChargingSession $session, array $validation): array
    {
        $wallet = $session->getUserWallet();
        
        // Check if estimated cost is set
        if (!$session->estimated_cost || $session->estimated_cost <= 0) {
            $validation['errors'][] = 'Estimated cost not set for prepaid session';
            return $validation;
        }

        $validation['required_amount'] = $session->estimated_cost;

        // Check if wallet has sufficient balance
        if (!$wallet->hasSufficientBalance($session->estimated_cost)) {
            $validation['errors'][] = 'Insufficient balance for prepaid session';
            $validation['errors'][] = 'Required: ' . number_format($session->estimated_cost, 2) . ' EUR';
            $validation['errors'][] = 'Available: ' . $wallet->getFormattedBalance();
        }

        // Check minimum balance constraint
        $newBalance = $wallet->balance - $session->estimated_cost;
        if ($wallet->min_balance && $newBalance < $wallet->min_balance) {
            $validation['warnings'][] = 'Transaction will bring balance below minimum threshold';
        }

        return $validation;
    }

    /**
     * Validate postpaid session
     */
    protected static function validatePostpaidSession(ChargingSession $session, array $validation): array
    {
        $wallet = $session->getUserWallet();
        
        // Get minimum threshold
        $threshold = $session->min_threshold ?? config('charging.postpaid_min_threshold', 10.00);
        $validation['required_amount'] = $threshold;

        // Check if wallet has sufficient balance for threshold
        if (!$wallet->hasSufficientBalance($threshold)) {
            $validation['errors'][] = 'Insufficient balance for postpaid session';
            $validation['errors'][] = 'Required threshold: ' . number_format($threshold, 2) . ' EUR';
            $validation['errors'][] = 'Available: ' . $wallet->getFormattedBalance();
        }

        // Check if wallet is active
        if (!$wallet->is_active) {
            $validation['errors'][] = 'Wallet is not active';
        }

        return $validation;
    }

    /**
     * Validate session parameters
     */
    public static function validateSessionParameters(array $parameters): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
        ];

        // Check required parameters
        if (!isset($parameters['charging_point_id'])) {
            $validation['errors'][] = 'Charging point ID is required';
            $validation['valid'] = false;
        }

        if (!isset($parameters['user_id'])) {
            $validation['errors'][] = 'User ID is required';
            $validation['valid'] = false;
        }

        if (!isset($parameters['mode'])) {
            $validation['errors'][] = 'Session mode is required';
            $validation['valid'] = false;
        }

        // Validate mode
        if (isset($parameters['mode']) && !in_array($parameters['mode'], ['prepaid', 'postpaid'])) {
            $validation['errors'][] = 'Invalid session mode. Must be prepaid or postpaid';
            $validation['valid'] = false;
        }

        // Validate prepaid parameters
        if (isset($parameters['mode']) && $parameters['mode'] === 'prepaid') {
            if (!isset($parameters['estimated_cost']) || $parameters['estimated_cost'] <= 0) {
                $validation['errors'][] = 'Estimated cost is required for prepaid sessions';
                $validation['valid'] = false;
            }
        }

        // Validate postpaid parameters
        if (isset($parameters['mode']) && $parameters['mode'] === 'postpaid') {
            if (isset($parameters['min_threshold']) && $parameters['min_threshold'] < 0) {
                $validation['errors'][] = 'Minimum threshold cannot be negative';
                $validation['valid'] = false;
            }
        }

        return $validation;
    }

    /**
     * Check if user can afford a session
     */
    public static function canUserAffordSession(User $user, ChargingPoint $chargingPoint, string $mode, float $estimatedCost = null): array
    {
        $result = [
            'can_afford' => false,
            'wallet_balance' => 0,
            'required_amount' => 0,
            'shortfall' => 0,
            'warnings' => [],
        ];

        try {
            $wallet = $user->getOrCreateWallet();
            $result['wallet_balance'] = $wallet->balance;

            if ($mode === 'prepaid') {
                $result['required_amount'] = $estimatedCost ?? 0;
                $result['can_afford'] = $wallet->hasSufficientBalance($result['required_amount']);
                $result['shortfall'] = max(0, $result['required_amount'] - $wallet->balance);
            } elseif ($mode === 'postpaid') {
                $threshold = config('charging.postpaid_min_threshold', 10.00);
                $result['required_amount'] = $threshold;
                $result['can_afford'] = $wallet->hasSufficientBalance($threshold);
                $result['shortfall'] = max(0, $threshold - $wallet->balance);
            }

            // Check for warnings
            if ($wallet->min_balance && $wallet->balance <= $wallet->min_balance) {
                $result['warnings'][] = 'Wallet balance is at minimum threshold';
            }

            if ($wallet->auto_recharge && $wallet->needsAutoRecharge()) {
                $result['warnings'][] = 'Wallet needs auto-recharge';
            }

        } catch (\Exception $e) {
            Log::error('Failed to check user affordability', [
                'user_id' => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Get session validation summary
     */
    public static function getValidationSummary(ChargingSession $session): array
    {
        $validation = self::validateSessionStart($session);
        
        return [
            'session_id' => $session->session_id,
            'mode' => $session->mode,
            'can_start' => $validation['can_start'],
            'wallet_balance' => $validation['wallet_balance'],
            'required_amount' => $validation['required_amount'],
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
            'validation_passed' => $session->wallet_validation_passed,
            'validated_at' => $session->wallet_validated_at,
        ];
    }

    /**
     * Validate session completion
     */
    public static function validateSessionCompletion(ChargingSession $session): array
    {
        $validation = [
            'can_complete' => false,
            'errors' => [],
            'warnings' => [],
        ];

        try {
            // Check if session is in progress
            if ($session->status !== 'in_progress') {
                $validation['errors'][] = 'Session is not in progress';
                return $validation;
            }

            // Check if session has started
            if (!$session->started_at) {
                $validation['errors'][] = 'Session has not started';
                return $validation;
            }

            // Check if cost is calculated
            if (!$session->cost || $session->cost <= 0) {
                $validation['errors'][] = 'Session cost not calculated';
                return $validation;
            }

            // For postpaid sessions, check if user can afford the actual cost
            if ($session->isPostpaid()) {
                $wallet = $session->getUserWallet();
                if (!$wallet->hasSufficientBalance($session->cost)) {
                    $validation['errors'][] = 'Insufficient balance for session completion';
                    $validation['errors'][] = 'Required: ' . number_format($session->cost, 2) . ' EUR';
                    $validation['errors'][] = 'Available: ' . $wallet->getFormattedBalance();
                    return $validation;
                }
            }

            $validation['can_complete'] = true;

        } catch (\Exception $e) {
            Log::error('Session completion validation failed', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);
            
            $validation['errors'][] = 'Validation failed: ' . $e->getMessage();
        }

        return $validation;
    }

    /**
     * Get user's session eligibility
     */
    public static function getUserSessionEligibility(User $user): array
    {
        $wallet = $user->getOrCreateWallet();
        
        return [
            'user_id' => $user->id,
            'wallet_balance' => $wallet->balance,
            'formatted_balance' => $wallet->getFormattedBalance(),
            'wallet_active' => $wallet->is_active,
            'can_start_prepaid' => $wallet->balance > 0,
            'can_start_postpaid' => $wallet->balance >= config('charging.postpaid_min_threshold', 10.00),
            'min_threshold' => config('charging.postpaid_min_threshold', 10.00),
            'warnings' => [],
        ];
    }
}
