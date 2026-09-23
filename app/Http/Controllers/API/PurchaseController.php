<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\PurchaseService;
use App\Services\PricingPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PurchaseController extends ApiController
{
    use ApiResponse;

    /**
     * Le service pour les achats
     * 
     * @var PurchaseService
     */
    protected $purchaseService;
    
    /**
     * Le service pour les plans tarifaires
     * 
     * @var PricingPlanService
     */
    protected $pricingPlanService;
    
    /**
     * Constructor
     * 
     * @param PurchaseService $purchaseService
     * @param PricingPlanService $pricingPlanService
     */
    public function __construct(
        PurchaseService $purchaseService,
        PricingPlanService $pricingPlanService
    ) {
        $this->purchaseService = $purchaseService;
        $this->pricingPlanService = $pricingPlanService;
    }
    
    /**
     * Souscrit à un abonnement
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|exists:pricing_plans,id',
                'payment_method' => 'required|string|in:card,paypal,apple_pay,google_pay',
                'payment_token' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('VALIDATION_ERROR', 'Erreur de validation', 422, $validator->errors());
            }
            
            $userId = $request->user()->id;
            $planId = $request->input('plan_id');
            $paymentMethod = $request->input('payment_method');
            $paymentToken = $request->input('payment_token');
            
            $result = $this->purchaseService->subscribe($userId, $planId, $paymentMethod, $paymentToken);
            
            return $this->sendSuccess([
                'subscription' => $result['subscription'],
                'payment' => $result['payment'],
            ], $result['message'], 201);
        } catch (ValidationException $e) {
            return $this->sendError('VALIDATION_ERROR', 'Erreur de validation', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('SUBSCRIPTION_ERROR', 'Erreur lors de la souscription', 500, ['exception' => $e->getMessage()]);
        }
    }
    
    /**
     * Achète du crédit
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function purchaseCredit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:5',
                'payment_method' => 'required|string|in:card,paypal,apple_pay,google_pay',
                'payment_token' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('VALIDATION_ERROR', 'Erreur de validation', 422, $validator->errors());
            }
            
            $userId = $request->user()->id;
            $amount = $request->input('amount');
            $paymentMethod = $request->input('payment_method');
            $paymentToken = $request->input('payment_token');
            
            $result = $this->purchaseService->purchaseCredit($userId, $amount, $paymentMethod, $paymentToken);
            
            return $this->sendSuccess([
                'payment' => $result['payment'],
                'new_balance' => $result['new_balance'],
            ], $result['message'], 201);
        } catch (ValidationException $e) {
            return $this->sendError('VALIDATION_ERROR', 'Erreur de validation', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('CREDIT_PURCHASE_ERROR', 'Erreur lors de l\'achat de crédit', 500, ['exception' => $e->getMessage()]);
        }
    }
    
    /**
     * Annule un abonnement
     * 
     * @param Request $request
     * @param int $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSubscription(Request $request, int $subscriptionId)
    {
        try {
            $userId = $request->user()->id;
            
            $result = $this->purchaseService->cancelSubscription($userId, $subscriptionId);
            
            return $this->sendSuccess([
                'subscription' => $result['subscription'],
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->sendError('SUBSCRIPTION_CANCEL_ERROR', 'Erreur lors de l\'annulation de l\'abonnement', 500, ['exception' => $e->getMessage()]);
        }
    }
    
    /**
     * Vérifie si l'utilisateur peut démarrer une recharge
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function canStartCharging(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'charging_point_id' => 'required|exists:charging_points,id',
                'connector_id' => 'required|integer',
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('VALIDATION_ERROR', 'Erreur de validation', 422, $validator->errors());
            }
            
            $userId = $request->user()->id;
            $chargingPointId = $request->input('charging_point_id');
            $connectorId = $request->input('connector_id');
            
            $result = $this->purchaseService->canStartCharging($userId, $chargingPointId, $connectorId);
            
            return $this->sendSuccess($result);
        } catch (\Exception $e) {
            return $this->sendError('VERIFICATION_ERROR', 'Erreur lors de la vérification', 500, ['exception' => $e->getMessage()]);
        }
    }
    
    /**
     * Traite le paiement après une recharge
     * 
     * @param Request $request
     * @param int $transactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function processChargingPayment(Request $request, int $transactionId)
    {
        try {
            $userId = $request->user()->id;
            
            $result = $this->purchaseService->processChargingPayment($userId, $transactionId);
            
            return $this->sendSuccess([
                'payment' => $result['payment'],
                'new_balance' => $result['new_balance'] ?? null,
            ], $result['message']);
        } catch (\Exception $e) {
            return $this->sendError('PAYMENT_PROCESSING_ERROR', 'Erreur lors du traitement du paiement', 500, ['exception' => $e->getMessage()]);
        }
    }
    
    /**
     * Récupère le solde actuel de l'utilisateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance(Request $request)
    {
        try {
            $user = $request->user();
            
            return $this->sendSuccess([
                'balance' => $user->credit_balance ?? 0,
                'currency' => 'EUR',
            ]);
        } catch (\Exception $e) {
            return $this->sendError('BALANCE_RETRIEVAL_ERROR', 'Erreur lors de la récupération du solde', 500, ['exception' => $e->getMessage()]);
        }
    }
    
    /**
     * Récupère les abonnements de l'utilisateur
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubscriptions(Request $request)
    {
        try {
            $user = $request->user();
            
            $subscriptions = \App\Models\Subscription::where('user_id', $user->id)
                ->with('plan')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return $this->sendSuccess($subscriptions);
        } catch (\Exception $e) {
            return $this->sendError('SUBSCRIPTIONS_RETRIEVAL_ERROR', 'Erreur lors de la récupération des abonnements', 500, ['exception' => $e->getMessage()]);
        }
    }
}