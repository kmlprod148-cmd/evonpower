<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Reservation;
use App\Models\Order;
use App\Services\EnhancedCmiPaymentService;
use App\Services\EnhancedStripePaymentService;
use App\Services\CurrencyService;
use App\Services\SteVeChargingTriggerService;

/**
 * Contrôleur sécurisé pour les paiements
 * 
 * Gestion sécurisée des callbacks et des paiements
 */
class SecurePaymentController extends Controller
{
    protected EnhancedCmiPaymentService $cmiService;
    protected EnhancedStripePaymentService $stripeService;
    protected CurrencyService $currencyService;
    protected SteVeChargingTriggerService $chargingTriggerService;

    public function __construct(
        EnhancedCmiPaymentService $cmiService,
        EnhancedStripePaymentService $stripeService,
        CurrencyService $currencyService,
        SteVeChargingTriggerService $chargingTriggerService
    ) {
        $this->cmiService = $cmiService;
        $this->stripeService = $stripeService;
        $this->currencyService = $currencyService;
        $this->chargingTriggerService = $chargingTriggerService;
    }

    /**
     * Afficher la page de sélection de paiement
     */
    public function showPaymentMethods(Request $request, Reservation $reservation)
    {
        // Vérifier les permissions
        if (Auth::check() && $reservation->user_id !== Auth::id()) {
            abort(403, 'Accès non autorisé à cette réservation');
        }

        // Calculer le montant total
        $totalAmount = $reservation->pricing_plan->price ?? 0;
        $currency = $request->get('currency', config('payments.currencies.default', 'EUR'));

        // Obtenir les méthodes de paiement disponibles
        $availableMethods = $this->getAvailablePaymentMethods($currency);

        return view('payments.methods', compact(
            'reservation',
            'totalAmount',
            'currency',
            'availableMethods'
        ));
    }

    /**
     * Initier un paiement CMI sécurisé
     */
    public function initiateCmiPayment(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservations,id',
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3',
                'customer_email' => 'required|email',
                'customer_name' => 'nullable|string|max:255',
                'customer_phone' => 'nullable|string|max:20'
            ]);

            $reservation = Reservation::findOrFail($request->reservation_id);
            
            // Vérifier les permissions
            if (Auth::check() && $reservation->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à cette réservation'
                ], 403);
            }

            // Créer une commande
            $order = Order::create([
                'user_id' => $reservation->user_id,
                'plan_id' => $reservation->pricing_plan_id,
                'charging_point_id' => $reservation->charging_point_id,
                'amount' => $request->amount,
                'status' => 'pending',
                'payment_method' => 'cmi',
                'payment_reference' => 'CMI_' . $reservation->id . '_' . time(),
                'details' => json_encode([
                    'reservation_id' => $reservation->id,
                    'currency' => $request->currency,
                    'customer_email' => $request->customer_email,
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone
                ])
            ]);

            // Préparer les données de paiement
            $paymentData = [
                'amount' => $request->amount,
                'currency' => $request->currency,
                'order_id' => $order->id,
                'customer_email' => $request->customer_email,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id
            ];

            // Initier le paiement CMI
            $result = $this->cmiService->initiatePayment($paymentData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'initiation du paiement CMI: ' . $result['error']
                ], 500);
            }

            // Mettre à jour la commande avec la référence de transaction
            $order->update([
                'payment_reference' => $result['transaction_id'],
                'details' => json_encode(array_merge(
                    json_decode($order->details, true),
                    ['cmi_transaction_id' => $result['transaction_id']]
                ))
            ]);

            Log::info('CMI Payment initiated successfully', [
                'reservation_id' => $reservation->id,
                'order_id' => $order->id,
                'transaction_id' => $result['transaction_id']
            ]);

            return response()->json([
                'success' => true,
                'transaction_id' => $result['transaction_id'],
                'redirect_url' => $result['redirect_url'],
                'form_data' => $result['form_data']
            ]);

        } catch (\Exception $e) {
            Log::error('CMI Payment initiation failed', [
                'error' => $e->getMessage(),
                'reservation_id' => $request->reservation_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Callback sécurisé CMI
     */
    public function handleCmiCallback(Request $request)
    {
        try {
            // Log de sécurité
            Log::info('CMI Callback received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data' => $request->all()
            ]);

            // Traiter le callback
            $result = $this->cmiService->handleCallback($request->all());

            if (!$result['success']) {
                Log::error('CMI Callback processing failed', [
                    'error' => $result['error'],
                    'data' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }

            // Mettre à jour la commande
            $this->updateOrderFromCallback($result['transaction_id'], $result['status']);

            // Déclencher la recharge si le paiement est réussi
            if ($result['status'] === 'success') {
                $this->triggerChargingAfterPayment($result['transaction_id']);
            }

            Log::info('CMI Callback processed successfully', [
                'transaction_id' => $result['transaction_id'],
                'status' => $result['status']
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);

        } catch (\Exception $e) {
            Log::error('CMI Callback error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du callback'
            ], 500);
        }
    }

    /**
     * Callback de succès CMI
     */
    public function cmiSuccess(Request $request)
    {
        try {
            $transactionId = $request->get('transaction_id');
            
            if (!$transactionId) {
                return redirect()->route('payment.failure')
                    ->with('error', 'Transaction non trouvée');
            }

            // Vérifier le statut de la transaction
            $result = $this->cmiService->checkTransactionStatus($transactionId);

            if (!$result['success']) {
                return redirect()->route('payment.failure')
                    ->with('error', 'Transaction non trouvée');
            }

            $transaction = $result['transaction'];
            
            // Mettre à jour la commande
            $order = Order::where('payment_reference', $transactionId)->first();
            if ($order) {
                $order->update([
                    'status' => 'completed',
                    'payment_status' => 'completed'
                ]);

                // Mettre à jour la réservation
                $reservation = Reservation::find($order->details['reservation_id'] ?? null);
                if ($reservation) {
                    $reservation->update([
                        'status' => 'confirmed',
                        'payment_confirmed' => true
                    ]);

                    // Déclencher la recharge automatiquement
                    $this->triggerChargingAfterPayment($transactionId, $reservation);
                }
            }

            return redirect()->route('payment.success')
                ->with('success', 'Paiement effectué avec succès');

        } catch (\Exception $e) {
            Log::error('CMI Success callback error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('payment.failure')
                ->with('error', 'Erreur lors du traitement du paiement');
        }
    }

    /**
     * Callback d'échec CMI
     */
    public function cmiFailure(Request $request)
    {
        Log::info('CMI Payment failed', [
            'request_data' => $request->all()
        ]);

        return redirect()->route('payment.failure')
            ->with('error', 'Paiement échoué');
    }

    /**
     * Initier un paiement Stripe sécurisé
     */
    public function initiateStripePayment(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservations,id',
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3',
                'customer_email' => 'required|email'
            ]);

            $reservation = Reservation::findOrFail($request->reservation_id);
            
            // Vérifier les permissions
            if (Auth::check() && $reservation->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à cette réservation'
                ], 403);
            }

            // Créer une commande
            $order = Order::create([
                'user_id' => $reservation->user_id,
                'plan_id' => $reservation->pricing_plan_id,
                'charging_point_id' => $reservation->charging_point_id,
                'amount' => $request->amount,
                'status' => 'pending',
                'payment_method' => 'stripe',
                'payment_reference' => 'STRIPE_' . $reservation->id . '_' . time(),
                'details' => json_encode([
                    'reservation_id' => $reservation->id,
                    'currency' => $request->currency,
                    'customer_email' => $request->customer_email
                ])
            ]);

            // Préparer les données de paiement
            $paymentData = [
                'amount' => $request->amount,
                'currency' => $request->currency,
                'order_id' => $order->id,
                'customer_email' => $request->customer_email,
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'product_name' => 'Réservation de borne de recharge',
                'product_description' => 'Paiement pour réservation de borne de recharge EvonPower'
            ];

            // Créer la session Stripe
            $result = $this->stripeService->createPaymentSession($paymentData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'initiation du paiement Stripe: ' . $result['error']
                ], 500);
            }

            // Mettre à jour la commande
            $order->update([
                'payment_reference' => $result['session_id'],
                'details' => json_encode(array_merge(
                    json_decode($order->details, true),
                    ['stripe_session_id' => $result['session_id']]
                ))
            ]);

            Log::info('Stripe Payment initiated successfully', [
                'reservation_id' => $reservation->id,
                'order_id' => $order->id,
                'session_id' => $result['session_id']
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $result['session_id'],
                'checkout_url' => $result['checkout_url']
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe Payment initiation failed', [
                'error' => $e->getMessage(),
                'reservation_id' => $request->reservation_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook sécurisé Stripe
     */
    public function handleStripeWebhook(Request $request)
    {
        try {
            $payload = $request->getContent();
            $signature = $request->header('Stripe-Signature');

            if (!$signature) {
                return response()->json(['error' => 'Signature manquante'], 400);
            }

            // Traiter le webhook
            $result = $this->stripeService->handleWebhook($payload, $signature);

            if (!$result['success']) {
                Log::error('Stripe webhook processing failed', [
                    'error' => $result['error']
                ]);

                return response()->json([
                    'error' => $result['error']
                ], 400);
            }

            Log::info('Stripe webhook processed successfully', [
                'event_type' => $result['event_type']
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Erreur lors du traitement du webhook'
            ], 500);
        }
    }

    /**
     * Callback de succès Stripe
     */
    public function stripeSuccess(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');
            
            if (!$sessionId) {
                return redirect()->route('payment.failure')
                    ->with('error', 'Session de paiement invalide');
            }

            // Vérifier le statut de la session
            $result = $this->stripeService->verifyPayment($sessionId);

            if (!$result['success'] || !$result['paid']) {
                return redirect()->route('payment.failure')
                    ->with('error', 'Paiement non confirmé');
            }

            // Mettre à jour la commande
            $order = Order::where('payment_reference', $sessionId)->first();
            if ($order) {
                $order->update([
                    'status' => 'completed',
                    'payment_status' => 'completed'
                ]);

                // Mettre à jour la réservation
                $reservation = Reservation::find($order->details['reservation_id'] ?? null);
                if ($reservation) {
                    $reservation->update([
                        'status' => 'confirmed',
                        'payment_confirmed' => true
                    ]);

                    // Déclencher la recharge automatiquement
                    $this->triggerChargingAfterPayment($sessionId, $reservation);
                }
            }

            return redirect()->route('payment.success')
                ->with('success', 'Paiement effectué avec succès');

        } catch (\Exception $e) {
            Log::error('Stripe Success callback error', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('payment.failure')
                ->with('error', 'Erreur lors du traitement du paiement');
        }
    }

    /**
     * Callback d'annulation Stripe
     */
    public function stripeCancel(Request $request)
    {
        Log::info('Stripe Payment cancelled', [
            'request_data' => $request->all()
        ]);

        return redirect()->route('payment.failure')
            ->with('error', 'Paiement annulé');
    }

    /**
     * Obtenir les méthodes de paiement disponibles
     */
    protected function getAvailablePaymentMethods(string $currency): array
    {
        $methods = [];

        // CMI pour le Maroc
        if ($this->currencyService->isCurrencySupportedByPaymentMethod($currency, 'cmi')) {
            $methods['cmi'] = [
                'name' => 'CMI (Maroc)',
                'description' => 'Paiement sécurisé par carte bancaire',
                'icon' => 'cmi-logo.png',
                'supported_currencies' => ['EUR']
            ];
        }

        // Stripe pour l'international
        if ($this->currencyService->isCurrencySupportedByPaymentMethod($currency, 'stripe')) {
            $methods['stripe'] = [
                'name' => 'Stripe (International)',
                'description' => 'Paiement international par carte bancaire',
                'icon' => 'stripe-logo.png',
                'supported_currencies' => ['MAD', 'EUR', 'USD', 'GBP']
            ];
        }

        return $methods;
    }

    /**
     * Page de succès du paiement
     */
    public function paymentSuccess(Request $request)
    {
        $message = $request->session()->get('success', 'Paiement effectué avec succès');
        
        return view('payments.success', compact('message'));
    }

    /**
     * Page d'échec du paiement
     */
    public function paymentFailure(Request $request)
    {
        $error = $request->session()->get('error', 'Paiement échoué');
        
        return view('payments.failure', compact('error'));
    }

    /**
     * Mettre à jour une commande depuis un callback
     */
    protected function updateOrderFromCallback(string $transactionId, string $status): void
    {
        $order = Order::where('payment_reference', $transactionId)->first();
        
        if ($order) {
            $order->update([
                'status' => $status === 'success' ? 'completed' : 'failed',
                'payment_status' => $status === 'success' ? 'completed' : 'failed'
            ]);

            // Mettre à jour la réservation si le paiement est réussi
            if ($status === 'success') {
                $reservation = Reservation::find($order->details['reservation_id'] ?? null);
                if ($reservation) {
                    $reservation->update([
                        'status' => 'confirmed',
                        'payment_confirmed' => true
                    ]);

                    // Déclencher la recharge automatiquement
                    $this->triggerChargingAfterPayment($transactionId, $reservation);
                }
            }
        }
    }

    /**
     * Déclencher la recharge après un paiement réussi
     */
    protected function triggerChargingAfterPayment(string $transactionId, ?Reservation $reservation = null): void
    {
        try {
            // Si aucune réservation n'est fournie, la récupérer via la transaction
            if (!$reservation) {
                $order = Order::where('payment_reference', $transactionId)->first();
                if ($order) {
                    $reservation = Reservation::find($order->details['reservation_id'] ?? null);
                }
            }

            if (!$reservation) {
                Log::warning('SteVeChargingTriggerService: Aucune réservation trouvée pour déclencher la recharge', [
                    'transaction_id' => $transactionId
                ]);
                return;
            }

            // Préparer les données de paiement
            $paymentData = [
                'transaction_id' => $transactionId,
                'payment_method' => $this->detectPaymentMethod($transactionId),
                'connector_id' => 1, // Par défaut
                'id_tag' => 'admin', // Par défaut
                'meter_start' => 0
            ];

            // Déclencher la recharge via SETEVE
            $result = $this->chargingTriggerService->triggerChargingAfterPayment($reservation, $paymentData);

            if ($result['success']) {
                Log::info('SteVeChargingTriggerService: Recharge déclenchée avec succès', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $transactionId,
                    'method' => $result['method'] ?? 'unknown',
                    'charging_transaction_id' => $result['transaction_id'] ?? null
                ]);

                // Optionnel: envoyer une notification au client
                $this->sendChargingStartedNotification($reservation, $result);
            } else {
                Log::error('SteVeChargingTriggerService: Échec du déclenchement de la recharge', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $transactionId,
                    'error' => $result['error'] ?? 'Erreur inconnue',
                    'code' => $result['code'] ?? 'unknown'
                ]);

                // Optionnel: envoyer une notification d'erreur
                $this->sendChargingErrorNotification($reservation, $result);
            }

        } catch (\Exception $e) {
            Log::error('SteVeChargingTriggerService: Erreur lors du déclenchement de la recharge', [
                'transaction_id' => $transactionId,
                'reservation_id' => $reservation?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Détecter la méthode de paiement utilisée
     */
    protected function detectPaymentMethod(string $transactionId): string
    {
        if (str_starts_with($transactionId, 'CMI_') || str_contains($transactionId, 'cmi')) {
            return 'cmi';
        } elseif (str_starts_with($transactionId, 'STRIPE_') || str_contains($transactionId, 'cs_')) {
            return 'stripe';
        }
        
        return 'unknown';
    }

    /**
     * Envoyer une notification de démarrage de recharge
     */
    protected function sendChargingStartedNotification(Reservation $reservation, array $result): void
    {
        try {
            $customerEmail = $reservation->guest_email ?? $reservation->user?->email;
            $chargingPoint = $reservation->chargingPoint;
            
            Log::info('SteVeChargingTriggerService: Notification de démarrage de recharge', [
                'reservation_id' => $reservation->id,
                'customer_email' => $customerEmail,
                'charging_point' => $chargingPoint?->name ?? 'Inconnu',
                'method' => $result['method'] ?? 'unknown',
                'charging_transaction_id' => $result['transaction_id'] ?? null
            ]);

            // Ici vous pouvez ajouter l'envoi d'email, SMS, etc.
            // Par exemple:
            // if ($customerEmail) {
            //     Mail::to($customerEmail)->send(new ChargingStartedMail($reservation, $result));
            // }

        } catch (\Exception $e) {
            Log::warning('SteVeChargingTriggerService: Erreur lors de l\'envoi de notification de démarrage', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer une notification d'erreur de recharge
     */
    protected function sendChargingErrorNotification(Reservation $reservation, array $result): void
    {
        try {
            $customerEmail = $reservation->guest_email ?? $reservation->user?->email;
            $chargingPoint = $reservation->chargingPoint;
            
            Log::info('SteVeChargingTriggerService: Notification d\'erreur de recharge', [
                'reservation_id' => $reservation->id,
                'customer_email' => $customerEmail,
                'charging_point' => $chargingPoint?->name ?? 'Inconnu',
                'error' => $result['error'] ?? 'Erreur inconnue',
                'code' => $result['code'] ?? 'unknown'
            ]);

            // Ici vous pouvez ajouter l'envoi d'email, SMS, etc.
            // Par exemple:
            // if ($customerEmail) {
            //     Mail::to($customerEmail)->send(new ChargingErrorMail($reservation, $result));
            // }

        } catch (\Exception $e) {
            Log::warning('SteVeChargingTriggerService: Erreur lors de l\'envoi de notification d\'erreur', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
