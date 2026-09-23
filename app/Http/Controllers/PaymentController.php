<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Services\PaymentService;
use App\Services\UnifiedPaymentService;
use App\Services\PaymentValidationService;
use App\Services\OfflineCardPaymentService;
use App\Services\ReservationService;
use App\Services\CreditPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $unifiedPaymentService;
    protected $paymentValidationService;
    protected $offlineCardPaymentService;
    protected $reservationService;
    protected $creditPaymentService;

    public function __construct(
        PaymentService $paymentService,
        UnifiedPaymentService $unifiedPaymentService,
        PaymentValidationService $paymentValidationService,
        OfflineCardPaymentService $offlineCardPaymentService,
        ReservationService $reservationService,
        CreditPaymentService $creditPaymentService
    ) {
        $this->paymentService = $paymentService;
        $this->unifiedPaymentService = $unifiedPaymentService;
        $this->paymentValidationService = $paymentValidationService;
        $this->offlineCardPaymentService = $offlineCardPaymentService;
        $this->reservationService = $reservationService;
        $this->creditPaymentService = $creditPaymentService;
    }

    /**
     * Initiate CMI payment
     */
    public function initiateCMIPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'charging_point_id' => 'required|exists:charging_points,id',
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'reservation_type' => 'required|in:kwh,minute',
            'reservation_value' => 'required|numeric|min:1',
            'start_time' => 'nullable|string|max:20',
            'estimated_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Create reservation first
            $reservation = $this->reservationService->createReservation($request->all());
            
            if (!isset($reservation['reservation']) || !$reservation['reservation']) {
                return redirect()->back()->with('error', $reservation['message'] ?? 'Erreur lors de la création de la réservation');
            }
            
            $reservationModel = $reservation['reservation'];
            
            // Process CMI payment using PaymentService
            $result = $this->paymentService->processPayment($reservationModel, 'cmi', $request->all());
            
            if ($result['success']) {
                if (isset($result['redirect_url'])) {
                    return redirect($result['redirect_url']);
                }
                return redirect()->route('payment.success')
                    ->with('success', 'Paiement initié avec succès');
            } else {
                return redirect()->back()->with('error', $result['message'] ?? 'Erreur lors de l\'initiation du paiement CMI');
            }
        } catch (\Exception $e) {
            Log::error('CMI Payment initiation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Erreur lors de l\'initiation du paiement CMI: ' . $e->getMessage());
        }
    }

    /**
     * Initiate Stripe payment
     */
    public function initiateStripePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'charging_point_id' => 'required|exists:charging_points,id',
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'reservation_type' => 'required|in:kwh,minute',
            'reservation_value' => 'required|numeric|min:1',
            'start_time' => 'nullable|string|max:20',
            'estimated_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Create reservation first
            $reservation = $this->reservationService->createReservation($request->all());
            
            if (!isset($reservation['reservation']) || !$reservation['reservation']) {
                return redirect()->back()->with('error', $reservation['message'] ?? 'Erreur lors de la création de la réservation');
            }
            
            $reservationModel = $reservation['reservation'];
            
            // Process Stripe payment using PaymentService
            $result = $this->paymentService->processPayment($reservationModel, 'stripe', $request->all());
            
            if ($result['success']) {
                if (isset($result['redirect_url'])) {
                    return redirect($result['redirect_url']);
                }
                return redirect()->route('payment.success')
                    ->with('success', 'Paiement initié avec succès');
            } else {
                return redirect()->back()->with('error', $result['message'] ?? 'Erreur lors de l\'initiation du paiement Stripe');
            }
        } catch (\Exception $e) {
            Log::error('Stripe Payment initiation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Erreur lors de l\'initiation du paiement Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Initiate payment for a reservation
     */
    public function initiatePayment(Request $request, Reservation $reservation)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:cmi,stripe',
            'payment_data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if reservation can be paid (gérer enum ou string)
        $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
            ? $reservation->status->value
            : $reservation->status;
        if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation ne peut pas être payée'
            ], 400);
        }

        $result = $this->paymentService->processPayment(
            $reservation,
            $request->payment_method,
            $request->payment_data ?? []
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'redirect_url' => $result['redirect_url'],
                'message' => $result['message']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error']
            ], 400);
        }
    }

    /**
     * Handle payment success
     */
    public function paymentSuccess(Request $request)
    {
        // Pour CMI, récupérer l'ID de réservation depuis oid ou reservation_id
        $reservationId = $request->get('reservation_id') ?? $request->get('oid');
        $paymentReference = $request->get('payment_reference') ?? $request->get('transaction_id') ?? $request->get('TransId') ?? $reservationId;
        
        if (!$paymentReference && !$reservationId) {
            return redirect()->route('reservations.index')
                ->with('error', 'Référence de paiement manquante');
        }

        $result = $this->paymentService->handlePaymentSuccess($paymentReference ?? $reservationId, $request->all());

        if ($result['success']) {
            $reservation = $result['reservation'];
            return redirect()->route('reservations.thank-you', $reservation)
                ->with('success', 'Paiement effectué avec succès. Votre réservation a été confirmée automatiquement.');
        } else {
            return redirect()->route('reservations.index')
                ->with('error', 'Erreur lors de la confirmation du paiement: ' . ($result['error'] ?? 'Erreur inconnue'));
        }
    }

    /**
     * Handle payment cancellation
     */
    public function paymentCancel(Request $request)
    {
        $paymentReference = $request->get('payment_reference') ?? $request->get('transaction_id');
        
        if ($paymentReference) {
            $this->paymentService->handlePaymentFailure($paymentReference, 'Paiement annulé par l\'utilisateur');
        }

        return redirect()->route('reservations.index')
            ->with('warning', 'Paiement annulé. Vous pouvez réessayer plus tard.');
    }

    /**
     * Handle payment failure
     */
    public function paymentFail(Request $request)
    {
        $paymentReference = $request->get('payment_reference') ?? $request->get('transaction_id');
        $reason = $request->get('reason', 'Paiement échoué');
        
        if ($paymentReference) {
            $this->paymentService->handlePaymentFailure($paymentReference, $reason);
        }

        return redirect()->route('reservations.index')
            ->with('error', 'Le paiement a échoué. Veuillez réessayer.');
    }

    /**
     * CMI Success Callback
     */
    public function cmiSuccess(Request $request)
    {
        Log::info('CMI Success callback received', $request->all());
        
        try {
            $callbackData = $request->all();
            
            // Validation du callback
            $validation = $this->paymentValidationService->validateCmiCallback($callbackData);
            if (!$validation['valid']) {
                return redirect()->route('reservations.index')
                    ->with('error', $validation['message']);
            }

            // Vérifier la signature CMI
            if (!$this->unifiedPaymentService->verifyCmiSignature($callbackData)) {
                Log::warning('Signature CMI invalide', ['data' => $callbackData]);
                return redirect()->route('reservations.index')
                    ->with('error', 'Signature de paiement invalide');
            }

            // CMI envoie oid qui correspond à l'ID de réservation
            $reservationId = $request->get('oid') ?? $request->get('reservation_id');
            $procReturnCode = $request->get('ProcReturnCode');
            
            if (!$reservationId) {
                return redirect()->route('reservations.index')
                    ->with('error', 'Référence de paiement manquante');
            }

            // Vérifier le code de retour CMI (00 = succès)
            if ($procReturnCode === '00') {
                // Vérifier si c'est un paiement en mode hors ligne
                $reservation = Reservation::find($reservationId);
                $isOfflineMode = false;
                if ($reservation) {
                    $transaction = Transaction::where('reservation_id', $reservation->id)->first();
                    $isOfflineMode = $transaction && ($transaction->metadata['offline_mode'] ?? false);
                }

                // Utiliser le service approprié selon le mode
                if ($isOfflineMode) {
                    $result = $this->offlineCardPaymentService->handlePaymentSuccessForOfflineMode($reservationId, $callbackData);
                } else {
                    $result = $this->unifiedPaymentService->handlePaymentSuccess($reservationId, $callbackData);
                }
                
                if ($result['success']) {
                    $reservation = $result['reservation'] ?? Reservation::find($reservationId);
                    if ($isOfflineMode) {
                        return redirect()->route('reservations.thank-you', $reservation)
                            ->with('success', 'Paiement effectué avec succès. Votre demande est en attente de confirmation par un administrateur.');
                    } else {
                        return redirect()->route('reservations.thank-you', $reservation)
                            ->with('success', 'Paiement effectué avec succès. Votre réservation a été confirmée automatiquement.');
                    }
                } else {
                    return redirect()->route('reservations.index')
                        ->with('error', 'Erreur lors de la confirmation du paiement: ' . ($result['error'] ?? 'Erreur inconnue'));
                }
            } else {
                // Paiement échoué
                $errorMsg = $request->get('ErrMsg', 'Code retour CMI: ' . $procReturnCode);
                $this->unifiedPaymentService->handlePaymentFailure($reservationId, $errorMsg);
                return redirect()->route('reservations.index')
                    ->with('error', 'Le paiement a échoué: ' . $errorMsg);
            }
            
        } catch (\Exception $e) {
            Log::error('CMI Success callback processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('reservations.index')
                ->with('error', 'Erreur lors du traitement du paiement');
        }
    }

    /**
     * CMI Failure Callback
     */
    public function cmiFailure(Request $request)
    {
        Log::info('CMI Failure callback received', $request->all());
        
        try {
            $reservationId = $request->get('oid') ?? $request->get('reservation_id');
            $procReturnCode = $request->get('ProcReturnCode');
            $errorMessage = $request->get('ErrMsg', 'Paiement échoué');
            
            if ($reservationId) {
                $this->paymentService->handlePaymentFailure($reservationId, $errorMessage);
            }

            return redirect()->route('reservations.index')
                ->with('error', 'Le paiement a échoué: ' . $errorMessage);
                
        } catch (\Exception $e) {
            Log::error('CMI Failure callback processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->route('reservations.index')
                ->with('error', 'Erreur lors du traitement du paiement');
        }
    }

    /**
     * CMI Webhook
     */
    public function cmiWebhook(Request $request)
    {
        Log::info('CMI Webhook received', $request->all());

        try {
            // CMI envoie oid qui correspond à l'ID de réservation
            $reservationId = $request->get('oid');
            $procReturnCode = $request->get('ProcReturnCode');
            
            if (!$reservationId) {
                Log::error('CMI Webhook: Missing oid', $request->all());
                return response()->json(['error' => 'Missing oid'], 400);
            }

            // Vérifier le code de retour CMI (00 = succès)
            if ($procReturnCode === '00') {
                $result = $this->paymentService->handlePaymentSuccess($reservationId, $request->all());
                
                if ($result['success']) {
                    return response()->json(['status' => 'success']);
                } else {
                    return response()->json(['error' => $result['error'] ?? 'Processing failed'], 400);
                }
            } else {
                $errorMessage = $request->get('ErrMsg', 'Paiement échoué');
                $this->paymentService->handlePaymentFailure($reservationId, $errorMessage);
                return response()->json(['status' => 'failed', 'message' => $errorMessage]);
            }

        } catch (\Exception $e) {
            Log::error('CMI Webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Stripe Success Callback
     */
    public function stripeSuccess(Request $request)
    {
        Log::info('Stripe Success callback received', $request->all());
        
        try {
            $sessionId = $request->get('session_id');
            $reservationId = $request->get('reservation_id');
            
            if ($sessionId) {
                // Récupérer la session Stripe pour obtenir les metadata
                $stripeConfig = config('payments.stripe', []);
                \Stripe\Stripe::setApiKey($stripeConfig['secret_key'] ?? '');
                
                try {
                    $session = \Stripe\Checkout\Session::retrieve($sessionId);
                    $reservationId = $reservationId ?? ($session->metadata['reservation_id'] ?? null);
                } catch (\Exception $e) {
                    Log::warning('Could not retrieve Stripe session', ['error' => $e->getMessage()]);
                }
            }
            
            if (!$reservationId) {
                return redirect()->route('reservations.index')
                    ->with('error', 'Référence de paiement manquante');
            }

            $result = $this->paymentService->handlePaymentSuccess($reservationId, [
                'session_id' => $sessionId,
                'reservation_id' => $reservationId
            ]);

            if ($result['success']) {
                $reservation = $result['reservation'];
                return redirect()->route('reservations.thank-you', $reservation)
                    ->with('success', 'Paiement effectué avec succès. Votre réservation a été confirmée automatiquement.');
            } else {
                return redirect()->route('reservations.index')
                    ->with('error', 'Erreur lors de la confirmation du paiement: ' . ($result['error'] ?? 'Erreur inconnue'));
            }
            
        } catch (\Exception $e) {
            Log::error('Stripe Success callback processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->route('reservations.index')
                ->with('error', 'Erreur lors du traitement du paiement');
        }
    }

    /**
     * Stripe Cancel Callback
     */
    public function stripeCancel(Request $request)
    {
        Log::info('Stripe Cancel callback received', $request->all());
        
        try {
            $reservationId = $request->get('reservation_id');
            
            if ($reservationId) {
                $this->paymentService->handlePaymentFailure($reservationId, 'Paiement annulé par l\'utilisateur');
            }

            return redirect()->route('reservations.index')
                ->with('warning', 'Paiement annulé. Vous pouvez réessayer plus tard.');
                
        } catch (\Exception $e) {
            Log::error('Stripe Cancel callback processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->route('reservations.index')
                ->with('error', 'Erreur lors du traitement de l\'annulation');
        }
    }

    /**
     * Stripe Webhook
     */
    public function stripeWebhook(Request $request)
    {
        Log::info('Stripe Webhook received', [
            'headers' => $request->headers->all(),
            'has_content' => $request->hasContent()
        ]);

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $paymentKeysService = app(\App\Services\PaymentKeysService::class);
            $stripeKeys = $paymentKeysService->getActiveStripeKeys();
            $webhookSecret = $stripeKeys['webhook_secret'] ?? null;

            if ($webhookSecret) {
                try {
                    $stripeEvent = \Stripe\Webhook::constructEvent(
                        $payload,
                        $sigHeader,
                        $webhookSecret
                    );

                    $event = method_exists($stripeEvent, 'toArray')
                        ? $stripeEvent->toArray()
                        : json_decode(json_encode($stripeEvent), true);
                } catch (\Exception $e) {
                    Log::error('Stripe webhook signature verification failed', [
                        'error' => $e->getMessage()
                    ]);
                    return response()->json(['error' => 'Invalid signature'], 400);
                }
            } else {
                $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            }

            $validation = $this->paymentValidationService->validateStripeWebhook($event);
            if (!$validation['valid']) {
                Log::warning('Stripe webhook validation failed', [
                    'errors' => $validation['errors']
                ]);
                return response()->json(['error' => 'Validation failed'], 400);
            }

            $reservationId = data_get($event, 'data.object.metadata.reservation_id');
            $transaction = $reservationId
                ? Transaction::where('reservation_id', $reservationId)->latest('id')->first()
                : null;
            $isOfflineMode = $transaction ? (bool) ($transaction->metadata['offline_mode'] ?? false) : false;

            if ($isOfflineMode) {
                $eventType = (string) ($event['type'] ?? '');
                $paymentReference = data_get($event, 'data.object.id') ?: $reservationId;

                if (in_array($eventType, [
                    'checkout.session.completed',
                    'checkout.session.async_payment_succeeded',
                    'payment_intent.succeeded',
                ], true)) {
                    $result = $this->offlineCardPaymentService->handlePaymentSuccessForOfflineMode(
                        (string) $paymentReference,
                        $event
                    );

                    if ($result['success']) {
                        return response()->json([
                            'status' => 'success',
                            'message' => $result['message'] ?? null,
                        ]);
                    }

                    return response()->json([
                        'error' => $result['error'] ?? 'Webhook processing failed',
                    ], 400);
                }
            }

            $checkoutService = app(\App\Services\StripeReservationCheckoutService::class);
            $result = $checkoutService->handleWebhookEvent($event);

            if ($result['success']) {
                return response()->json([
                    'status' => ($result['ignored'] ?? false) ? 'ignored' : 'success',
                    'message' => $result['message'] ?? null,
                ]);
            }

            return response()->json([
                'error' => $result['error'] ?? 'Webhook processing failed',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Stripe Webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => substr($payload, 0, 500),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Reservation $reservation)
    {
        $transaction = \App\Models\Transaction::where('reservation_id', $reservation->id)->latest('id')->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'no_transaction',
                'message' => 'Aucune transaction trouvée'
            ]);
        }

        return response()->json([
            'status' => $transaction->status,
            'payment_method' => $transaction->payment_method,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'created_at' => $transaction->created_at,
            'completed_at' => $transaction->completed_at,
        ]);
    }

    /**
     * Process refund
     */
    public function processRefund(Request $request, Reservation $reservation)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user can refund this reservation
        if (!auth()->user()->can('refund_reservations') && 
            auth()->id() !== $reservation->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à rembourser cette réservation'
            ], 403);
        }

        $amount = $request->amount ?? $reservation->estimated_cost;
        $result = $this->paymentService->processRefund($reservation, $amount);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Remboursement effectué avec succès',
                'refund_reference' => $result['refund_reference']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du remboursement',
                'error' => $result['error']
            ], 400);
        }
    }

    /**
     * Process prepaid credit payment
     */
    public function payPrepaidCredit(Request $request, Reservation $reservation)
    {
        try {
            $reservationId = $reservation->id;
            
            // Vérifier que l'utilisateur est le propriétaire de la réservation
            if ($reservation->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Non autorisé à payer cette réservation'
                ], 403);
            }

            // Idempotence : déjà payée (tous modes) → redirection succès (évite double débit)
            if ($reservation->isPaid()) {
                if (!$request->expectsJson() && !$request->ajax()) {
                    return redirect()->route('reservations.thank-you', $reservationId)
                        ->with('success', 'Déjà payée');
                }
                return response()->json(['success' => true, 'already_paid' => true, 'message' => 'Déjà payée']);
            }

            // Vérifier que la réservation est en attente de paiement (gérer enum ou string)
            $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
                ? $reservation->status->value
                : $reservation->status;
            if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette réservation ne peut pas être payée'
                ], 400);
            }

            // Utiliser le service de paiement par crédit
            $result = $this->creditPaymentService->processPrepaidPayment($reservation);

            if ($result['success']) {
                // Redirection directe si requête formulaire (pas d'affichage JSON)
                if (!$request->expectsJson() && !$request->ajax()) {
                    return redirect()->route('reservations.thank-you', $reservationId)
                        ->with('success', $result['message'] ?? 'Paiement effectué avec succès');
                }
                return response()->json($result);
            } else {
                return response()->json($result, 400);
            }

        } catch (\Exception $e) {
            Log::error('Erreur paiement prépayé', [
                'reservation_id' => $reservationId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du paiement prépayé: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche la page de choix de paiement pour une réservation
     */
    public function show(Request $request, Reservation $reservation)
    {
        // Vérifier que l'utilisateur est le propriétaire de la réservation
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Vous n\'êtes pas autorisé à payer cette réservation.');
        }

        // Vérifier que la réservation peut être payée (gérer enum ou string)
        $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
            ? $reservation->status->value
            : $reservation->status;
        if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
            return redirect()->route('reservations.show', $reservation)
                ->with('error', 'Cette réservation ne peut pas être payée.');
        }

        $user = auth()->user();
        $wallet = $user->getOrCreateWallet();
        $wallet->refresh();
        $balance = $wallet->balance ?? 0;
        $formattedBalance = $wallet->getFormattedBalance() ?? '0.00 EUR';
        $estimatedCost = $reservation->estimated_cost ?? $reservation->amount ?? 0;
        $hasSufficientBalance = $balance >= $estimatedCost;

        // Obtenir les méthodes de paiement disponibles via le service unifié
        $paymentMethods = $this->unifiedPaymentService->getAvailablePaymentMethods();
        
        // Filtrer uniquement les méthodes activées
        $availableMethods = array_filter($paymentMethods, function($method) {
            return $method['enabled'] ?? false;
        });

        return view('payments.choose', compact(
            'reservation',
            'balance',
            'formattedBalance',
            'estimatedCost',
            'hasSufficientBalance',
            'availableMethods'
        ));
    }

    /**
     * Initie un paiement CMI pour une réservation
     */
    public function payReservationCmi(Request $request, Reservation $reservation)
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Vous n\'êtes pas autorisé à payer cette réservation.');
        }

        // Vérifier si le mode hors ligne est activé pour CMI
        $offlineMode = $this->offlineCardPaymentService->isOfflineModeEnabled('cmi');
        
        // Validation préalable
        $validation = $this->paymentValidationService->validatePaymentRequest($reservation, 'cmi', $request->all());
        if (!$validation['valid']) {
            return redirect()->route('payment.choose', $reservation)
                ->with('error', $validation['message']);
        }

        try {
            // Si le mode hors ligne est activé, utiliser le service de paiement hors ligne
            if ($offlineMode) {
                $result = $this->offlineCardPaymentService->processOfflineCardPayment($reservation, 'cmi', $request->all());
            } else {
                // Sinon, utiliser le service unifié normal
                $result = $this->unifiedPaymentService->initiatePayment($reservation, 'cmi', $request->all());
            }

            if ($result['success']) {
                if (isset($result['redirect_url'])) {
                    return redirect($result['redirect_url']);
                }
                if (isset($result['payment_url'])) {
                    // Rediriger vers la page d'envoi CMI avec les données
                    return redirect()->route('payment.cmi.reservation.send', $reservation->id);
                }
                return redirect()->route('payment.choose', $reservation)
                    ->with('error', 'URL de paiement non disponible');
            } else {
                return redirect()->route('payment.choose', $reservation)
                    ->with('error', $result['message'] ?? 'Erreur lors de l\'initiation du paiement CMI');
            }
        } catch (\Exception $e) {
            Log::error('Erreur paiement CMI réservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.choose', $reservation)
                ->with('error', 'Erreur lors de l\'initiation du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Initie un paiement Stripe pour une réservation
     */
    public function payReservationStripe(Request $request, Reservation $reservation)
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Vous n\'êtes pas autorisé à payer cette réservation.');
        }

        // Vérifier si le mode hors ligne est activé pour Stripe
        $offlineMode = $this->offlineCardPaymentService->isOfflineModeEnabled('stripe');
        
        // Validation préalable
        $validation = $this->paymentValidationService->validatePaymentRequest($reservation, 'stripe', $request->all());
        if (!$validation['valid']) {
            return redirect()->route('payment.choose', $reservation)
                ->with('error', $validation['message']);
        }

        try {
            // Si le mode hors ligne est activé, utiliser le service de paiement hors ligne
            if ($offlineMode) {
                $result = $this->offlineCardPaymentService->processOfflineCardPayment($reservation, 'stripe', $request->all());
            } else {
                // Sinon, utiliser le service unifié normal
                $result = $this->unifiedPaymentService->initiatePayment($reservation, 'stripe', $request->all());
            }

            if ($result['success']) {
                if (isset($result['redirect_url'])) {
                    return redirect($result['redirect_url']);
                }
                return redirect()->route('payment.choose', $reservation)
                    ->with('error', 'URL de redirection manquante');
            } else {
                return redirect()->route('payment.choose', $reservation)
                    ->with('error', $result['message'] ?? 'Erreur lors de l\'initiation du paiement Stripe');
            }
        } catch (\Exception $e) {
            Log::error('Erreur paiement Stripe réservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.choose', $reservation)
                ->with('error', 'Erreur lors de l\'initiation du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Envoie les données vers CMI pour une réservation
     */
    public function sendCmiReservationPayment(Reservation $reservation)
    {
        try {
            // Vérifier que la réservation peut être payée (gérer enum ou string)
            $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
                ? $reservation->status->value
                : $reservation->status;
            if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
                return redirect()->route('reservations.show', $reservation)
                    ->with('error', 'Cette réservation ne peut pas être payée');
            }

            // Utiliser le service CMI
            $cmiService = app(\App\Services\CMIReservationPaymentService::class);
            $paymentData = $cmiService->preparePaymentData($reservation);
            $paymentUrl = $cmiService->getPaymentUrl();

            Log::info('CMI Payment initiated for reservation', [
                'reservation_id' => $reservation->id,
                'amount' => $reservation->estimated_cost ?? $reservation->amount
            ]);

            // Afficher la vue avec auto-submit du formulaire
            return response()
                ->view('payments.cmi.reservation-form', [
                    'reservation' => $reservation,
                    'paymentData' => $paymentData,
                    'paymentUrl' => $paymentUrl
                ])
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('CMI Payment initiation failed for reservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.choose', $reservation)
                ->with('error', 'Erreur lors de l\'initiation du paiement CMI: ' . $e->getMessage());
        }
    }

    /**
     * Gère le callback CMI pour les paiements de réservations
     */
    public function handleCmiReservationCallback(Request $request)
    {
        try {
            $postData = $request->all();
            
            Log::info('CMI Callback received for reservation payment', [
                'post_data' => $postData
            ]);

            // Validation du callback
            $validation = $this->paymentValidationService->validateCmiCallback($postData);
            if (!$validation['valid']) {
                Log::warning('CMI callback validation failed', [
                    'errors' => $validation['errors']
                ]);
                return response('FAILURE', 400)
                    ->header('Content-Type', 'text/plain; charset=UTF-8');
            }

            // Récupérer l'ID de réservation depuis oid
            $reservationId = $postData['oid'] ?? null;
            if (!$reservationId) {
                return response('FAILURE', 400)
                    ->header('Content-Type', 'text/plain; charset=UTF-8');
            }

            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                Log::warning('Reservation not found in CMI callback', [
                    'reservation_id' => $reservationId
                ]);
                return response('FAILURE', 404)
                    ->header('Content-Type', 'text/plain; charset=UTF-8');
            }

            // Vérifier la signature avec le service unifié
            if (!$this->unifiedPaymentService->verifyCmiSignature($postData)) {
                Log::warning('Invalid CMI signature for reservation payment', [
                    'reservation_id' => $reservationId
                ]);
                return response('FAILURE', 400)
                    ->header('Content-Type', 'text/plain; charset=UTF-8');
            }

            // Vérifier le code de retour
            $procReturnCode = $postData['ProcReturnCode'] ?? '';
            if ($procReturnCode === '00') {
                // Vérifier si c'est un paiement en mode hors ligne
                $reservation = Reservation::find($reservationId);
                $isOfflineMode = false;
                if ($reservation) {
                    $transaction = Transaction::where('reservation_id', $reservation->id)->first();
                    $isOfflineMode = $transaction && ($transaction->metadata['offline_mode'] ?? false);
                }

                // Utiliser le service approprié selon le mode
                if ($isOfflineMode) {
                    $result = $this->offlineCardPaymentService->handlePaymentSuccessForOfflineMode((string)$reservationId, $postData);
                } else {
                    $result = $this->unifiedPaymentService->handlePaymentSuccess((string)$reservationId, $postData);
                }
                
                if ($result['success']) {
                    Log::info('CMI callback processed successfully for reservation', [
                        'reservation_id' => $reservationId,
                        'proc_return_code' => $procReturnCode,
                        'offline_mode' => $isOfflineMode
                    ]);
                    return response('ACTION=POSTAUTH', 200)
                        ->header('Content-Type', 'text/plain; charset=UTF-8');
                } else {
                    Log::error('CMI callback processing failed for reservation', [
                        'reservation_id' => $reservationId,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                    return response('FAILURE', 500)
                        ->header('Content-Type', 'text/plain; charset=UTF-8');
                }
            } else {
                // Paiement échoué
                $errorMsg = $postData['ErrMsg'] ?? 'Code retour CMI: ' . $procReturnCode;
                Log::warning('CMI payment failed for reservation', [
                    'reservation_id' => $reservationId,
                    'proc_return_code' => $procReturnCode,
                    'error' => $errorMsg
                ]);
                $this->unifiedPaymentService->handlePaymentFailure((string)$reservationId, $errorMsg);
                return response('FAILURE', 200)
                    ->header('Content-Type', 'text/plain; charset=UTF-8');
            }

        } catch (\Exception $e) {
            Log::error('CMI Callback processing failed for reservation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('FAILURE', 500)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }

    /**
     * Gère la page de succès CMI pour les réservations
     */
    public function handleCmiReservationSuccess(Request $request)
    {
        try {
            $reservationId = $request->get('oid') ?? $request->get('reservation_id');
            $procReturnCode = $request->get('ProcReturnCode');
            
            if (!$reservationId) {
                return redirect()->route('reservations.index')
                    ->with('error', 'Référence de paiement manquante');
            }

            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                return redirect()->route('reservations.index')
                    ->with('error', 'Réservation non trouvée');
            }

            // Vérifier le code de retour CMI (00 = succès)
            if ($procReturnCode === '00') {
                // Le callback serveur-à-serveur a déjà traité le paiement
                // On vérifie juste que le statut est correct (PAID/PAYE en majuscules en base)
                if ($reservation->isPaid()) {
                    return redirect()->route('reservations.show', $reservation)
                        ->with('success', 'Paiement effectué avec succès. Votre réservation a été confirmée automatiquement.');
                } else {
                    // Si le callback n'a pas encore été traité, le traiter maintenant
                    $result = $this->paymentService->handlePaymentSuccess($reservationId, $request->all());
                    
                    if ($result['success']) {
                        return redirect()->route('reservations.show', $reservation)
                            ->with('success', 'Paiement effectué avec succès. Votre réservation a été confirmée automatiquement.');
                    }
                }
            }

            return redirect()->route('reservations.show', $reservation)
                ->with('error', 'Le paiement n\'a pas pu être confirmé.');

        } catch (\Exception $e) {
            Log::error('CMI Success callback processing failed for reservation', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->route('reservations.index')
                ->with('error', 'Erreur lors du traitement du paiement.');
        }
    }

    /**
     * Gère la page d'échec CMI pour les réservations
     */
    public function handleCmiReservationFailure(Request $request)
    {
        try {
            $reservationId = $request->get('oid') ?? $request->get('reservation_id');
            
            if ($reservationId) {
                $this->paymentService->handlePaymentFailure($reservationId, 'Paiement CMI échoué');
            }

            return redirect()->route('reservations.index')
                ->with('error', 'Le paiement a échoué. Veuillez réessayer.');

        } catch (\Exception $e) {
            Log::error('CMI Failure callback processing failed for reservation', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->route('reservations.index')
                ->with('error', 'Erreur lors du traitement de l\'échec du paiement.');
        }
    }

    /**
     * Gère le succès du paiement Stripe pour les réservations
     */
    public function handleStripeReservationSuccess(Request $request)
    {
        try {
            $sessionId     = $request->get('session_id');
            $reservationId = $request->get('reservation_id');

            if (!$sessionId && !$reservationId) {
                Log::warning('Stripe success callback: missing session_id and reservation_id');
                return $this->redirectAfterPayment(null, 'error', 'Référence de paiement manquante.');
            }

            $checkoutService = app(\App\Services\StripeReservationCheckoutService::class);
            $reservation = $checkoutService->resolveReservationForSuccessRedirect($reservationId, $sessionId);

            if ($reservation) {
                $transaction = Transaction::where('reservation_id', $reservation->id)->latest('id')->first();
                $isOfflineMode = $transaction ? (bool) ($transaction->metadata['offline_mode'] ?? false) : false;

                if ($isOfflineMode) {
                    $this->offlineCardPaymentService->handlePaymentSuccessForOfflineMode(
                        (string) ($sessionId ?: $reservation->id),
                        [
                            'reservation_id' => $reservation->id,
                            'stripe_session_id' => $sessionId,
                        ]
                    );
                }

                return redirect()->route('reservations.thank-you', [
                    'id' => $reservation->id,
                    'session_id' => $sessionId,
                ])->with(
                    'success',
                    $isOfflineMode
                        ? 'Paiement reçu. Votre réservation attend maintenant la confirmation administrative prévue par le mode hors ligne.'
                        : 'Paiement reçu. Nous confirmons la transaction et préparons le démarrage de votre recharge.'
                );
            }

            Log::warning('Stripe success callback: reservation not resolved', [
                'reservation_id' => $reservationId,
                'session_id' => $sessionId,
            ]);

            return $this->redirectAfterPayment(
                $reservationId,
                'error',
                'Nous n\'avons pas pu retrouver votre réservation après le paiement.'
            );

        } catch (\Exception $e) {
            Log::error('Stripe Success callback processing failed for reservation', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            return $this->redirectAfterPayment(null, 'error', 'Erreur lors du traitement du paiement.');
        }
    }

    /**
     * Gère l'annulation du paiement Stripe pour les réservations
     */
    public function handleStripeReservationCancel(Request $request)
    {
        try {
            $reservationId = $request->get('reservation_id');

            if ($reservationId) {
                $this->paymentService->handlePaymentFailure($reservationId, 'Paiement annulé par l\'utilisateur');
                $reservation = \App\Models\Reservation::find($reservationId);
                if ($reservation) {
                    // Redirect guest back to the offer page so they can retry
                    return redirect()->route('public.charging-point.offer.reservation', $reservation->charging_point_id)
                        ->with('warning', 'Paiement annulé. Vous pouvez réessayer à tout moment.');
                }
            }

            return $this->redirectAfterPayment(null, 'warning', 'Paiement annulé. Vous pouvez réessayer à tout moment.');

        } catch (\Exception $e) {
            Log::error('Stripe Cancel callback processing failed for reservation', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            return $this->redirectAfterPayment(null, 'warning', 'Paiement annulé.');
        }
    }

    /**
     * Safe redirect that works for both guests and authenticated users.
     * Authenticated users go to their reservations list; guests go to the home/offer page.
     */
    private function redirectAfterPayment(?string $reservationId, string $flashType, string $message)
    {
        if (auth()->check()) {
            $route = $reservationId ? 'reservations.thank-you' : 'reservations.index';
            $args  = $reservationId ? [$reservationId] : [];
            return redirect()->route($route, $args)->with($flashType, $message);
        }

        // Guest fallback — redirect to home with flash in session
        return redirect('/')->with($flashType, $message);
    }

    /**
     * Process postpaid credit payment
     */
    public function payPostpaidCredit(Request $request, $reservationId)
    {
        try {
            $reservation = Reservation::findOrFail($reservationId);
            
            // Vérifier que l'utilisateur est le propriétaire de la réservation
            if ($reservation->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Non autorisé à payer cette réservation'
                ], 403);
            }

            // Vérifier que la réservation est en attente de paiement (gérer enum ou string)
            $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
                ? $reservation->status->value
                : $reservation->status;
            if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette réservation ne peut pas être payée'
                ], 400);
            }

            // Utiliser le service de paiement par crédit
            $result = $this->creditPaymentService->processPostpaidPayment($reservation);

            if ($result['success']) {
                return response()->json($result);
            } else {
                return response()->json($result, 400);
            }

        } catch (\Exception $e) {
            Log::error('Erreur paiement postpayé', [
                'reservation_id' => $reservationId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du paiement postpayé: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment methods
     */
    public function getPaymentMethods()
    {
        $methods = [
            [
                'id' => 'cmi',
                'name' => 'CMI',
                'description' => 'Paiement sécurisé par carte bancaire',
                'icon' => 'fas fa-university',
                'color' => 'blue',
                'enabled' => config('payment.cmi.enabled', true)
            ],
            [
                'id' => 'stripe',
                'name' => 'Stripe',
                'description' => 'Paiement international sécurisé',
                'icon' => 'fab fa-stripe',
                'color' => 'purple',
                'enabled' => config('payment.stripe.enabled', true)
            ],
            [
                'id' => 'prepaid_credit',
                'name' => 'Crédit Prépayé',
                'description' => 'Paiement anticipé avec votre solde',
                'icon' => 'fas fa-wallet',
                'color' => 'green',
                'enabled' => auth()->check()
            ],
            [
                'id' => 'postpaid_credit',
                'name' => 'Crédit Postpayé',
                'description' => 'Paiement après usage avec votre solde',
                'icon' => 'fas fa-credit-card',
                'color' => 'purple',
                'enabled' => auth()->check()
            ]
        ];

        return response()->json([
            'success' => true,
            'methods' => array_filter($methods, function($method) {
                return $method['enabled'];
            })
        ]);
    }

    /**
     * Redirect to CMI payment gateway
     */
    public function redirectToCmi(Request $request)
    {
        // Clear session data to prevent redirection loop
        $request->session()->forget('url.intended');
        
        // Get CMI redirect URL from session
        $redirectUrl = $request->session()->get('cmi_redirect_url');
        
        if ($redirectUrl) {
            // Return the view that handles the redirection
            return view('credit-recharge.cmi-redirect');
        }
        
        // Fallback if redirect URL is not found
        return redirect()->route('dashboard')->with('error', 'Payment session expired.');
    }
}
