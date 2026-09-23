<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Integrator;
use App\Models\IntegratorPaymentCredential;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Services\IntegratorCredentialService;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API Controller for Integrator Payment Operations
 * 
 * Provides endpoints for:
 * - Managing payment credentials
 * - Viewing payment transactions
 * - Initiating payments
 * - Webhook configuration
 */
class IntegratorPaymentController extends Controller
{
    protected IntegratorCredentialService $credentialService;
    protected PaymentGatewayService $paymentGatewayService;
    
    public function __construct(
        IntegratorCredentialService $credentialService,
        PaymentGatewayService $paymentGatewayService
    ) {
        $this->credentialService = $credentialService;
        $this->paymentGatewayService = $paymentGatewayService;
    }
    
    /**
     * Get all payment credentials for current integrator
     * 
     * GET /api/integrator/payments/credentials
     */
    public function getCredentials(Request $request): JsonResponse
    {
        $integrator = $request->user()?->integrator;
        
        if (!$integrator) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized - No integrator associated'
            ], 401);
        }
        
        $credentials = $this->credentialService->getAllIntegratorCredentials($integrator);
        
        return response()->json([
            'success' => true,
            'data' => $credentials,
        ]);
    }
    
    /**
     * Store payment credentials for integrator
     * 
     * POST /api/integrator/payments/credentials
     */
    public function storeCredentials(Request $request): JsonResponse
    {
        $integrator = $request->user()?->integrator;
        
        if (!$integrator) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized - No integrator associated'
            ], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'gateway_type' => 'required|in:stripe,cmi',
            'environment' => 'required|in:test,production',
            'credentials' => 'required|array',
            'public_key' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'merchant_id' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Validate credentials
            $this->credentialService->validateCredentials(
                $request->gateway_type,
                $request->credentials
            );
            
            // Store credentials
            $credential = $this->credentialService->storeCredentials(
                $integrator,
                $request->gateway_type,
                $request->credentials,
                $request->environment,
                [
                    'public_key' => $request->public_key,
                    'webhook_url' => $request->webhook_url,
                    'merchant_id' => $request->merchant_id,
                ]
            );
            
            // Test credentials
            $testResult = $this->credentialService->testCredentials($credential);
            $credential->markAsValidated($testResult['success'], $testResult['error'] ?? null);
            $credential->save();
            
            Log::info('Integrator payment credentials stored', [
                'integrator_id' => $integrator->id,
                'gateway_type' => $request->gateway_type,
                'environment' => $request->environment,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Credentials stored successfully',
                'data' => [
                    'id' => $credential->id,
                    'gateway_type' => $credential->gateway_type,
                    'environment' => $credential->environment,
                    'is_validated' => $credential->is_validated,
                    'public_key' => $credential->public_key,
                    'webhook_url' => $credential->webhook_url,
                ],
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to store integrator credentials', [
                'integrator_id' => $integrator->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to store credentials'
            ], 500);
        }
    }
    
    /**
     * Test payment credentials
     * 
     * POST /api/integrator/payments/credentials/{id}/test
     */
    public function testCredentials(Request $request, int $id): JsonResponse
    {
        $integrator = $request->user()?->integrator;
        
        if (!$integrator) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }
        
        $credential = IntegratorPaymentCredential::where('id', $id)
            ->where('integrator_id', $integrator->id)
            ->first();
        
        if (!$credential) {
            return response()->json([
                'success' => false,
                'error' => 'Credential not found'
            ], 404);
        }
        
        $result = $this->credentialService->testCredentials($credential);
        
        // Update validation status
        $credential->markAsValidated($result['success'], $result['error'] ?? null);
        $credential->save();
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? $result['error'],
            'validated_at' => $credential->last_validated_at?->toIso8601String(),
        ]);
    }
    
    /**
     * Delete payment credentials
     * 
     * DELETE /api/integrator/payments/credentials/{id}
     */
    public function deleteCredentials(Request $request, int $id): JsonResponse
    {
        $integrator = $request->user()?->integrator;
        
        if (!$integrator) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }
        
        $credential = IntegratorPaymentCredential::where('id', $id)
            ->where('integrator_id', $integrator->id)
            ->first();
        
        if (!$credential) {
            return response()->json([
                'success' => false,
                'error' => 'Credential not found'
            ], 404);
        }
        
        $credential->delete();
        
        // Clear cache
        $this->credentialService->clearCache(
            $integrator,
            $credential->gateway_type,
            $credential->environment
        );
        
        Log::info('Integrator payment credentials deleted', [
            'integrator_id' => $integrator->id,
            'credential_id' => $id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Credentials deleted successfully',
        ]);
    }
    
    /**
     * Get payment transactions for integrator
     * 
     * GET /api/integrator/payments/transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $integrator = $request->user()?->integrator;
        
        if (!$integrator) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }
        
        $query = Transaction::where('integrator_id', $integrator->id);
        
        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->gateway) {
            $query->where('gateway_type', $request->gateway);
        }
        
        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
    
    /**
     * Get payment statistics for integrator
     * 
     * GET /api/integrator/payments/statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $integrator = $request->user()?->integrator;
        
        if (!$integrator) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }
        
        $query = Transaction::where('integrator_id', $integrator->id);
        
        // Filter by date range
        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }
        
        $stats = [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'successful_transactions' => $query->where('status', 'completed')->count(),
            'failed_transactions' => $query->where('status', 'failed')->count(),
            'refunded_transactions' => $query->whereIn('status', ['refunded', 'partially_refunded'])->count(),
            'by_gateway' => $query->clone()
                ->groupBy('gateway_type')
                ->selectRaw('gateway_type, count(*) as count, sum(amount) as total')
                ->get(),
            'by_status' => $query->clone()
                ->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->get(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
    
    /**
     * Get webhook configuration URL
     * 
     * GET /api/integrator/payments/webhook-url
     */
    public function getWebhookUrl(Request $request): JsonResponse
    {
        $integrator = $request->user()?->integrator;
        
        if (!$integrator) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'stripe_webhook_url' => route('api.webhook.stripe', ['integrator' => $integrator->id]),
                'cmi_webhook_url' => route('api.webhook.cmi', ['integrator' => $integrator->id]),
            ],
        ]);
    }
    
    /**
     * Get available payment gateways
     * 
     * GET /api/integrator/payments/gateways
     */
    public function getGateways(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'type' => 'stripe',
                    'name' => 'Stripe',
                    'description' => 'International payment gateway',
                    'supported_features' => ['prepaid', 'postpaid', 'refunds'],
                ],
                [
                    'type' => 'cmi',
                    'name' => 'CMI',
                    'description' => 'Morocco payment gateway',
                    'supported_features' => ['prepaid', 'refunds'],
                ],
            ],
        ]);
    }
    
    /**
     * Resolve payment gateway for a charge point
     * 
     * GET /api/integrator/payments/resolve-gateway/{chargePointId}
     */
    public function resolveGateway(Request $request, int $chargePointId): JsonResponse
    {
        $integrator = $request->user()?->integrator;
        
        if (!$integrator) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }
        
        $chargePoint = \App\Models\ChargingPoint::find($chargePointId);
        
        if (!$chargePoint) {
            return response()->json([
                'success' => false,
                'error' => 'Charge point not found'
            ], 404);
        }
        
        // Verify charge point belongs to integrator
        if ($chargePoint->integrator_id !== $integrator->id) {
            return response()->json([
                'success' => false,
                'error' => 'Charge point not associated with this integrator'
            ], 403);
        }
        
        $gateway = $this->paymentGatewayService->resolveGateway($chargePoint);
        
        return response()->json([
            'success' => true,
            'data' => [
                'gateway_type' => $gateway->getGatewayType(),
                'charge_point_id' => $chargePoint->id,
            ],
        ]);
    }
}
