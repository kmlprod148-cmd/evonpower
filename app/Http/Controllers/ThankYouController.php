<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\ChargingSession;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ThankYouController extends Controller
{
    /**
     * Display the thank you page
     */
    public function index(Request $request)
    {
        try {
            // Get user if authenticated, otherwise null
            $user = Auth::check() ? Auth::user() : null;
            
            // Get transaction data from session or request
            $transactionId = $request->get('transaction_id') ?? session('last_transaction_id');
            $sessionId = $request->get('session_id') ?? session('last_session_id');
            $chargingPointId = $request->get('charging_point_id') ?? session('last_charging_point_id');
            
            // Load transaction if available
            $transaction = null;
            if ($transactionId) {
                try {
                    $transaction = Transaction::find($transactionId);
                } catch (\Exception $e) {
                    Log::warning('ThankYouController: Error loading transaction', [
                        'transaction_id' => $transactionId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Load charging session if available
            $session = null;
            if ($sessionId) {
                try {
                    $session = ChargingSession::find($sessionId);
                } catch (\Exception $e) {
                    Log::warning('ThankYouController: Error loading session', [
                        'session_id' => $sessionId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Load charging point if available
            $chargingPoint = null;
            if ($chargingPointId) {
                try {
                    $chargingPoint = ChargingPoint::find($chargingPointId);
                } catch (\Exception $e) {
                    Log::warning('ThankYouController: Error loading charging point', [
                        'charging_point_id' => $chargingPointId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Get payment method from session or default
            $paymentMethod = session('payment_method') ?? 'Carte Bancaire';
            
            // Calculate estimated time based on amount or default
            $estimatedTime = '30-45 minutes';
            try {
                $estimatedTime = $this->calculateEstimatedTime($transaction);
            } catch (\Exception $e) {
                Log::warning('ThankYouController: Error calculating estimated time', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Force mobile view for all devices (mobile-first responsive design)
            $isMobile = true;
            
            // Get order/transaction amount for display
            $orderAmount = null;
            if ($transaction && isset($transaction->amount)) {
                $orderAmount = $transaction->amount;
            } elseif ($request->has('amount')) {
                $orderAmount = $request->get('amount');
            }
            
            // Get user credit balance if authenticated
            $userCreditBalance = null;
            if ($user) {
                try {
                    // Try to get wallet balance
                    if ($user->wallet) {
                        $userCreditBalance = $user->wallet->balance ?? 0;
                    } else {
                        // Fallback to user balance if wallet doesn't exist
                        $userCreditBalance = $user->balance ?? 0;
                    }
                } catch (\Exception $e) {
                    // If any error occurs, set to null
                    $userCreditBalance = null;
                    Log::warning('ThankYouController: Error getting user balance', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Get reference if available
            $reference = null;
            if ($transaction && isset($transaction->reference)) {
                $reference = $transaction->reference;
            } elseif ($request->has('reference')) {
                $reference = $request->get('reference');
            }
            
            $data = [
                'transaction' => $transaction,
                'session' => $session,
                'sessionId' => $sessionId,
                'chargingPoint' => $chargingPoint,
                'paymentMethod' => $paymentMethod,
                'estimatedTime' => $estimatedTime,
                'user' => $user,
                'isMobile' => $isMobile,
                'order' => (object)[
                    'amount' => $orderAmount,
                    'reference' => $reference
                ],
                'rechargeStarted' => ($session && isset($session->status) && $session->status === 'active') ? true : false,
                'userCreditBalance' => $userCreditBalance
            ];
            
            // Always return mobile-first responsive view
            return view('thank-you-mobile', $data);
            
        } catch (Exception $e) {
            Log::error('ThankYouController: Error displaying thank you page', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback to basic mobile-first thank you page
            return view('thank-you-mobile', [
                'transaction' => null,
                'session' => null,
                'sessionId' => null,
                'chargingPoint' => null,
                'paymentMethod' => 'Carte Bancaire',
                'estimatedTime' => '30-45 minutes',
                'user' => null,
                'isMobile' => true,
                'order' => (object)[
                    'amount' => null,
                    'reference' => null
                ],
                'rechargeStarted' => false,
                'userCreditBalance' => null
            ]);
        }
    }

    /**
     * Get session status for real-time updates
     */
    public function getSessionStatus(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID required'
                ], 400);
            }
            
            $session = ChargingSession::find($sessionId);
            
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $session->id,
                    'status' => $session->status,
                    'progress' => $this->calculateSessionProgress($session),
                    'energy_delivered' => $session->energy_delivered ?? 0,
                    'duration' => $this->calculateSessionDuration($session),
                    'estimated_completion' => $this->getEstimatedCompletion($session)
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('ThankYouController: Error getting session status', [
                'session_id' => $request->get('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting session status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop charging session
     */
    public function stopSession(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID required'
                ], 400);
            }
            
            $session = ChargingSession::find($sessionId);
            
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }
            
            // Update session status
            $session->update([
                'status' => 'stopped',
                'ended_at' => now()
            ]);
            
            // Here you would typically call the Steve API to stop the charging
            // $steveApiService->stopCharge($session->charging_point_id, $session->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Session stopped successfully',
                'data' => [
                    'id' => $session->id,
                    'status' => $session->status,
                    'ended_at' => $session->ended_at
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('ThankYouController: Error stopping session', [
                'session_id' => $request->get('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error stopping session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate estimated charging time based on transaction amount
     */
    protected function calculateEstimatedTime($transaction)
    {
        if (!$transaction || !$transaction->amount) {
            return '30-45 minutes';
        }
        
        // Simple calculation: assume €0.50 per kWh and 50kW charging speed
        $kwh = $transaction->amount / 0.5; // Convert amount to kWh
        $minutes = ($kwh / 50) * 60; // Convert to minutes
        
        if ($minutes < 15) {
            return '10-15 minutes';
        } elseif ($minutes < 30) {
            return '15-30 minutes';
        } elseif ($minutes < 60) {
            return '30-45 minutes';
        } else {
            return '45-60 minutes';
        }
    }

    /**
     * Calculate session progress percentage
     */
    protected function calculateSessionProgress($session)
    {
        if (!$session->started_at) {
            return 0;
        }
        
        $duration = now()->diffInMinutes($session->started_at);
        $estimatedDuration = 30; // Default 30 minutes
        
        $progress = min(($duration / $estimatedDuration) * 100, 100);
        
        return round($progress);
    }

    /**
     * Calculate session duration
     */
    protected function calculateSessionDuration($session)
    {
        if (!$session->started_at) {
            return '0 minutes';
        }
        
        $duration = now()->diffInMinutes($session->started_at);
        
        if ($duration < 60) {
            return $duration . ' minutes';
        } else {
            $hours = floor($duration / 60);
            $minutes = $duration % 60;
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Get estimated completion time
     */
    protected function getEstimatedCompletion($session)
    {
        if (!$session->started_at) {
            return null;
        }
        
        $estimatedDuration = 30; // Default 30 minutes
        $completionTime = $session->started_at->addMinutes($estimatedDuration);
        
        return $completionTime->format('H:i');
    }

    /**
     * Check if request is from mobile device
     */
    protected function isMobileDevice(Request $request)
    {
        $userAgent = $request->header('User-Agent');
        
        return preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
    }
}
