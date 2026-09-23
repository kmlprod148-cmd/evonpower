<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\PricingPlan;
use App\Models\TransactionRepartition;
use App\Services\PricingPlanService;
use App\Services\TransactionCalculator;
use App\Services\CreditPaymentService;
use App\Services\CMIReservationPaymentService;
use App\Services\ChargingPointService;
use App\Services\ReservationCostCalculationService;
use App\Services\UnifiedPaymentService;
use App\Services\ReservationOtpService;
use App\Exceptions\ReservationOtpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

    /**
     * Contrôleur public pour les offres de recharge (sans authentification)
     */
    class PublicChargingOfferWebController extends Controller
    {
        protected $pricingPlanService;
        protected $transactionCalculator;
        protected $creditPaymentService;
        protected $cmiPaymentService;
        protected $chargingPointService;
        protected $reservationCostCalculationService;

    public function __construct(
        PricingPlanService $pricingPlanService, 
        TransactionCalculator $transactionCalculator,
        CreditPaymentService $creditPaymentService,
        CMIReservationPaymentService $cmiPaymentService,
        ChargingPointService $chargingPointService,
        UnifiedPaymentService $unifiedPaymentService,
        ReservationCostCalculationService $reservationCostCalculationService
    ) {
        $this->pricingPlanService = $pricingPlanService;
        $this->transactionCalculator = $transactionCalculator;
        $this->creditPaymentService = $creditPaymentService;
        $this->cmiPaymentService = $cmiPaymentService;
        $this->chargingPointService = $chargingPointService;
        $this->unifiedPaymentService = $unifiedPaymentService;
        $this->reservationCostCalculationService = $reservationCostCalculationService;
    }

        /**
         * Affiche l'offre de réservation publique pour une borne de recharge
         */
        public function showOffer($id)
        {
            // Unauthenticated visitors must register before accessing any charging offer.
            // The id is preserved in the session so RegisteredUserController can resume
            // the flow after registration.
            if (!auth()->check()) {
                session(['pending_charging_point_id' => $id]);
                return redirect()->route('register')
                    ->with('info', 'Veuillez créer votre compte pour accéder à l\'offre de recharge.');
            }

            return redirect()->route('client.charging.offer', ['id' => $id]);
        }

    /**
     * Traite la soumission du formulaire de réservation publique
     */
    public function storeReservation(Request $request, $id)
    {
        try {
            try {
                app(ReservationOtpService::class)->assertCanCreateReservation($request->user());
            } catch (ReservationOtpException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => $e->errorCode,
                ], $e->statusCode);
            }

            // Log the incoming request
            Log::info('PublicChargingOfferWebController@storeReservation called', [
                'charging_point_id' => $id,
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Délégation au ReservationController si format "create" (pricing_plan_id, reservation_type, reservation_value, payment_method)
            if ($request->has('pricing_plan_id') && $request->has('reservation_type') && $request->has('reservation_value')) {
                $request->merge(['charging_point_id' => $id]);
                return app(\App\Http\Controllers\ReservationController::class)->store($request, $id);
            }
            
            // Test simple pour vérifier que les données arrivent
            if ($request->has('debug_test')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Debug test successful',
                    'received_data' => $request->all(),
                    'charging_point_id' => $id
                ]);
            }
            
            // Valider les données (format offer: type, value, time)
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:energy,duration',
                'value' => 'required|numeric|min:1',
                'time' => 'required|string',
                'customer_name' => 'nullable|string|max:255',
                'customer_email' => 'nullable|email|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'customer_address' => 'nullable|string|max:500',
                'register_account' => 'nullable|boolean',
            ]);
            
            // Log temporaire pour debugging
            \Log::info('Validation attempt', [
                'request_data' => $request->all(),
                'validation_rules' => [
                    'type' => 'required|in:energy,duration',
                    'value' => 'required|numeric|min:1',
                    'time' => 'required|string',
                    'customer_name' => 'nullable|string|max:255',
                    'customer_email' => 'nullable|email|max:255',
                    'customer_phone' => 'nullable|string|max:20',
                    'customer_address' => 'nullable|string|max:500',
                    'register_account' => 'nullable|boolean',
                ]
            ]);

            if ($validator->fails()) {
                Log::error('Validation failed in PublicChargingOfferWebController', [
                    'request_data' => $request->all(),
                    'validation_errors' => $validator->errors()->toArray(),
                    'charging_point_id' => $id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors(),
                    'debug_data' => $request->all()
                ], 422);
            }

            // Récupérer la borne (avec groupe pour consumption_mode prépayé/postpayé)
            $chargingPoint = ChargingPoint::with(['pricingPlan', 'group'])->findOrFail($id);
            
            if (!$chargingPoint) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borne de recharge non trouvée'
                ], 404);
            }

            // Récupérer le plan tarifaire
                $pricingPlan = $chargingPoint->pricingPlan;
            if (!$pricingPlan) {
                $pricingPlan = PricingPlan::where('is_active', true)->first();
            }

            if (!$pricingPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan tarifaire non disponible'
                ], 400);
            }

            // Calculer le coût
            $cost = $this->calculateCost($request->type, $request->value, $pricingPlan);

            // Mode consommation du groupe (prépayé = débit immédiat, postpayé = débit à la fin de session)
            $consumptionMode = $chargingPoint->group?->consumption_mode ?? 'prepaid';

            // Vérifier le paiement par crédit si demandé (credit, prepaid_credit, postpaid_credit)
            $paymentMethod = $request->payment_method ?? 'offline';
            $isCreditPayment = in_array($paymentMethod, ['credit', 'prepaid_credit', 'postpaid_credit']);
            if ($isCreditPayment) {
                // Vérifier que l'utilisateur est authentifié
                if (!auth()->check()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous devez être connecté pour payer par crédit',
                        'requires_auth' => true
                    ], 401);
                }

                $user = auth()->user();
                $creditMode = ($consumptionMode === 'postpaid') ? 'postpaid' : 'prepaid';

                // Vérifier le solde : prépayé = coût total, postpayé = seuil minimum
                $canPay = $this->creditPaymentService->canMakeCreditPayment($user, $cost, $creditMode);

                if (!$canPay['can_pay']) {
                    $required = $creditMode === 'prepaid' ? $cost : ($canPay['required_balance'] ?? config('charging.postpaid_min_threshold', 10));
                    return response()->json([
                        'success' => false,
                        'message' => 'Solde insuffisant. Requis: ' . number_format($required, 2) . ' EUR, Disponible: ' . number_format($canPay['current_balance'], 2) . ' EUR',
                        'current_balance' => $canPay['current_balance'],
                        'required_balance' => $required,
                        'shortfall' => $canPay['shortfall']
                    ], 400);
                }
            }

            // Démarrer une transaction de base de données
            DB::beginTransaction();
            
            try {
                // Créer la réservation
                $reservation = new Reservation();
                $reservation->user_id = auth()->check() ? auth()->id() : null;
                $reservation->charging_point_id = $chargingPoint->id;
                $reservation->pricing_plan_id = $pricingPlan->id;
                $reservation->reservation_type = $request->type === 'energy' ? 'kwh' : 'minute';
                $reservation->reservation_value = $request->value;
                // Handle 'immediate' start time or parse the provided time
                if ($request->time === 'immediate' || empty($request->time)) {
                    $reservation->start_time = now();
                } else {
                    try {
                        // Vérifier si c'est un format de date valide
                        if (strpos($request->time, ':') !== false) {
                            // Format heure (HH:MM)
                            $reservation->start_time = \Carbon\Carbon::createFromFormat('H:i', $request->time);
                        } else {
                            // Format date complète
                            $reservation->start_time = \Carbon\Carbon::parse($request->time);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Invalid start_time format in PublicChargingOfferWebController, using current time', [
                            'start_time' => $request->time,
                            'error' => $e->getMessage()
                        ]);
                        $reservation->start_time = now();
                    }
                }
                $reservation->estimated_cost = $cost;
                $reservation->amount = $cost; // Utiliser 'amount' qui existe dans le fillable
                // Note: total_cost pourrait exister mais n'est pas dans fillable, donc on utilise amount
                $reservation->status = 'pending';
                $reservation->payment_method = $paymentMethod;
                $reservation->payment_mode = ($paymentMethod === 'postpaid_credit') ? 'postpaid' : ($isCreditPayment ? 'prepaid' : null);
                $reservation->payment_status = $isCreditPayment ? 'PENDING' : null;
                
                // Mapper payment_method vers payment_type (ENUM: 'cmi' ou 'offline')
                // payment_type est un ENUM avec seulement ['cmi', 'offline']
                // IMPORTANT: Ne jamais mettre 'credit' dans payment_type, toujours mapper vers 'cmi' ou 'offline'
                $paymentType = 'cmi'; // Valeur par défaut
                if ($isCreditPayment) {
                    // Le paiement par crédit est considéré comme un paiement en ligne (cmi)
                    $paymentType = 'cmi';
                } elseif (in_array($paymentMethod, ['cmi', 'offline'])) {
                    $paymentType = $paymentMethod;
                }
                
                // Forcer la valeur avec setAttribute pour éviter tout problème
                $reservation->setAttribute('payment_type', $paymentType);
                
                // Log pour déboguer
                \Log::debug('Setting payment_type for reservation', [
                    'payment_method' => $paymentMethod,
                    'payment_type' => $paymentType,
                    'reservation_id' => $reservation->id ?? 'new'
                ]);
                
                // Pour les clients connectés : garantir guest_email/guest_phone pour la visibilité des réservations
                $reservation->guest_email = $request->customer_email ?: (auth()->check() ? auth()->user()->email : null);
                $reservation->guest_phone = $request->customer_phone ?: (auth()->check() ? (auth()->user()->phone ?? null) : null);
                
                // Vérifier que payment_type est bien défini avant le save
                if ($reservation->getAttribute('payment_type') === 'credit') {
                    \Log::error('payment_type is still "credit" before save, forcing to "cmi"', [
                        'payment_method' => $paymentMethod,
                        'payment_type_before' => $reservation->getAttribute('payment_type')
                    ]);
                    $reservation->setAttribute('payment_type', 'cmi');
                }
                
                if (!$reservation->save()) {
                    throw new \Exception('Impossible de sauvegarder la réservation');
                }

                // Générer un ID de transaction unique
                $transactionId = 'PUB-' . strtoupper(uniqid());

                // Créer la transaction associée
                $transaction = new \App\Models\Transaction();
                $transaction->transaction_id = $transactionId;
                $transaction->charging_point_id = $chargingPoint->id;
                $transaction->user_id = auth()->check() ? auth()->id() : null;
                $transaction->start_timestamp = $reservation->start_time;
                $transaction->status = 'pending';
                $transaction->auth_method = 'public_reservation';
                $transaction->pricing_plan_id = $pricingPlan->id;
                $transaction->meter_start = 0;
                $transaction->amount = $cost; // Ajouter le champ amount manquant
                $transaction->price_total = $cost;
                $transaction->currency = $pricingPlan->currency ?? 'EUR';

                // Détails de prix pour le calcul des commissions
                $transaction->price_details = json_encode([
                    'pricing_plan_id' => $pricingPlan->id,
                    'pricing_plan_type' => $pricingPlan->rate_type,
                    'price_per_kwh' => $pricingPlan->price_per_kwh,
                    'price_per_minute' => $pricingPlan->price_per_minute,
                    'activation_fee' => $pricingPlan->activation_fee,
                    'vat_rate' => $pricingPlan->vatRate ? $pricingPlan->vatRate->rate : ($pricingPlan->vat_rate ?? 0),
                ]);

                // Lier la transaction à la réservation
                $transaction->reservation_id = $reservation->id;

                // Métadonnées de la réservation publique
                $transaction->metadata = json_encode([
                    'reservation_id' => $reservation->id,
                    'reservation_type' => $request->type,
                    'reservation_value' => $request->value,
                    'customer_name' => $request->customer_name,
                    'customer_email' => $request->customer_email,
                    'customer_phone' => $request->customer_phone,
                    'customer_address' => $request->customer_address,
                    'is_public' => true,
                ]);

                // Opt-in pour l'inscription
                if ($request->boolean('register_account') && $request->customer_email) {
                    // Vérifier si l'utilisateur n'existe pas déjà
                    $existingUser = \App\Models\User::where('email', $request->customer_email)->first();
                    if (!$existingUser) {
                        try {
                            $newUser = \App\Models\User::create([
                                'name' => $request->customer_name ?? 'Nouveau Client',
                                'email' => $request->customer_email,
                                'phone' => $request->customer_phone,
                                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
                            ]);
                            // Assigner le rôle client si utilisé
                            // $newUser->assignRole('client');
                            
                            $reservation->user_id = $newUser->id;
                            $transaction->user_id = $newUser->id;
                            $reservation->save();
                            $transaction->save();
                        } catch (\Exception $e) {
                            Log::warning('Erreur lors de la création automatique du compte client: ' . $e->getMessage());
                        }
                    }
                }

                if (!$transaction->save()) {
                    throw new \Exception('Impossible de sauvegarder la transaction');
                }

                // Recharger la transaction avec les relations nécessaires
                $transaction->load('chargingPoint');

                // Calculer la répartition avec le nouveau service
                try {
                    // Vérifier si le business profile existe
                    if (!$chargingPoint->business_profile_id) {
                        Log::warning('Aucun business profile trouvé', [
                            'transaction_id' => $transaction->id,
                            'charging_point_id' => $chargingPoint->id
                        ]);
                        
                        // Créer une répartition par défaut
                        $repartition = [
                            'admin_amount' => round($cost * 0.1, 2), // 10% pour l'admin
                            'integrator_amount' => round($cost * 0.3, 2), // 30% pour l'intégrateur
                            'operator_amount' => round($cost * 0.6, 2), // 60% pour l'opérateur
                        ];
                    } else {
                        try {
                            $repartition = $this->transactionCalculator->calculateForPublicTransaction($transaction);
                        } catch (\Exception $calcError) {
                            Log::warning('Erreur lors du calcul de la répartition, utilisation de la répartition par défaut', [
                                'transaction_id' => $transaction->id,
                                'error' => $calcError->getMessage()
                            ]);
                            // Fallback sur répartition par défaut
                            $repartition = [
                                'admin_amount' => round($cost * 0.1, 2),
                                'integrator_amount' => round($cost * 0.3, 2),
                                'operator_amount' => round($cost * 0.6, 2),
                            ];
                        }
                    }
                    
                    // Créer l'enregistrement de répartition
                    try {
                        $repartitionModel = TransactionRepartition::createFromCalculation($transaction, $repartition);
                        if (!$repartitionModel || !$repartitionModel->id) {
                            Log::warning('createFromCalculation n\'a pas retourné d\'ID', [
                                'transaction_id' => $transaction->id
                            ]);
                        }
                    } catch (\Exception $repartitionError) {
                        Log::warning('Erreur répartition (non bloquante) - réservation sauvegardée', [
                            'transaction_id' => $transaction->id,
                            'error' => $repartitionError->getMessage()
                        ]);
                        // Ne pas bloquer : la réservation et la transaction sont prioritaires
                    }
                    
                    Log::info('Répartition calculée pour réservation publique', [
                        'transaction_id' => $transaction->id,
                        'repartition' => $repartition
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Impossible de calculer la répartition pour la réservation publique: ' . $e->getMessage(), [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Créer une répartition d'urgence en cas d'erreur
                    try {
                        $emergencyRepartition = [
                            'admin_amount' => round($cost * 0.1, 2),
                            'integrator_amount' => round($cost * 0.3, 2),
                            'operator_amount' => round($cost * 0.6, 2),
                        ];
                        TransactionRepartition::createFromCalculation($transaction, $emergencyRepartition);
                        Log::info('Répartition d\'urgence créée', [
                            'transaction_id' => $transaction->id,
                            'repartition' => $emergencyRepartition
                        ]);
                    } catch (\Exception $emergencyError) {
                        Log::error('Impossible de créer une répartition d\'urgence: ' . $emergencyError->getMessage(), [
                            'transaction_id' => $transaction->id,
                            'error' => $emergencyError->getMessage()
                        ]);
                        // Ne pas faire échouer la création de réservation si la répartition échoue
                        // La répartition peut être créée plus tard
                    }
                }

                // Pour les réservations publiques, on ne lie pas à order_id
                // car cela nécessite une table orders qui n'est pas utilisée pour les réservations publiques
                // La transaction a déjà reservation_id, pas besoin de transaction_id dans la réservation
                // La relation est inversée : transactions.reservation_id -> reservations

                // COMMIT AVANT le paiement crédit : la réservation doit être visible pour la page thank-you
                DB::commit();

                // Traiter le paiement par crédit APRÈS commit (prepaid_credit = débit immédiat)
                $creditPaymentResult = null;
                if ($isCreditPayment && auth()->check()) {
                    try {
                        if ($paymentMethod === 'postpaid_credit') {
                            $paymentResult = $this->creditPaymentService->processPostpaidPayment($reservation);
                        } else {
                            $paymentResult = $this->creditPaymentService->processPrepaidPayment($reservation);
                        }
                        $creditPaymentResult = $paymentResult;

                        if (!$paymentResult['success']) {
                            Log::warning('Paiement crédit échoué après création réservation', [
                                'reservation_id' => $reservation->id,
                                'error' => $paymentResult['error'] ?? ''
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => $paymentResult['error'] ?? 'Erreur lors du paiement par crédit',
                                'reservation_id' => $reservation->id,
                                'redirect_url' => route('reservations.thank-you', $reservation->id)
                            ], 400);
                        }

                        // Mettre à jour la transaction : status completed seulement si ce n'est pas postpayé
                        if ($paymentMethod !== 'postpaid_credit') {
                            $transaction->status = 'completed';
                            $transaction->save();
                        }

                        $reservation->refresh();

                        Log::info('Paiement par crédit traité avec succès', [
                            'reservation_id' => $reservation->id,
                            'user_id' => auth()->id(),
                            'amount' => $cost,
                            'remaining_balance' => $paymentResult['remaining_balance'] ?? $paymentResult['current_balance'] ?? 0
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Erreur paiement par crédit', [
                            'reservation_id' => $reservation->id,
                            'user_id' => auth()->id(),
                            'error' => $e->getMessage()
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Erreur lors du paiement par crédit: ' . $e->getMessage(),
                            'reservation_id' => $reservation->id,
                            'redirect_url' => route('reservations.thank-you', $reservation->id)
                        ], 500);
                    }
                }

                Log::info('Réservation publique et transaction créées', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $transaction->id,
                    'charging_point_id' => $chargingPoint->id,
                    'type' => $request->type,
                    'value' => $request->value,
                    'cost' => $cost
                ]);

                // Préparer la réponse selon la méthode de paiement
                $redirectUrl = route('reservations.thank-you', $reservation->id);
                
                // Si paiement CMI, rediriger vers le formulaire de paiement
                if ($paymentMethod === 'cmi') {
                    $redirectUrl = route('payment.cmi.reservation.send', $reservation->id);
                }
                
                $response = [
                    'success' => true,
                    'message' => $isCreditPayment ? 'Réservation créée et payée avec succès' : 'Réservation créée avec succès',
                    'reservation' => $reservation,
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $transaction->id,
                    'cost' => $cost,
                    'currency' => 'EUR', // Force EUR instead of pricingPlan currency
                    'payment_method' => $paymentMethod,
                    'redirect_url' => $redirectUrl
                ];

                // Ajouter les informations de balance si paiement par crédit (solde client mis à jour)
                if ($isCreditPayment && auth()->check()) {
                    $response['payment_processed'] = true;
                    $response['remaining_balance'] = (float) ($creditPaymentResult['remaining_balance'] ?? auth()->user()->getOrCreateWallet()->fresh()->balance ?? 0);
                    $response['amount_paid'] = $cost;
                }

                return response()->json($response);

            } catch (\Exception $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                Log::error('Erreur dans la transaction de base de données lors de la création de réservation', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'request_data' => $request->all(),
                    'charging_point_id' => $id
                ]);
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Borne non trouvée pour réservation publique: ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Borne de recharge non trouvée'
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Erreur de base de données lors de la création de réservation publique', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
                'charging_point_id' => $id
            ]);
            
            // Message d'erreur plus spécifique selon le type d'erreur SQL
            $errorMessage = 'Une erreur est survenue lors de la création de la réservation';
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                $errorMessage = 'Erreur de référence : une des données liées n\'existe pas';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessage = 'Cette réservation existe déjà';
            } elseif (str_contains($e->getMessage(), 'cannot be null')) {
                $errorMessage = 'Des champs requis sont manquants';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_details' => config('app.debug') ? $e->getMessage() : null,
                'error_code' => 'database_error'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de réservation publique: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'charging_point_id' => $id,
                'exception_class' => get_class($e)
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de la réservation',
                'error_details' => config('app.debug') ? $e->getMessage() : null,
                'error_code' => 'general_error',
                'debug_info' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'class' => get_class($e)
                ] : null
            ], 500);
        }
    }

    /**
     * Calcule le coût de la réservation (HT puis TTC avec TVA)
     * Cette méthode doit correspondre exactement au calcul JavaScript dans offer-reservation.blade.php
     */
    private function calculateCost($type, $value, $pricingPlan, ?ChargingPoint $chargingPoint = null): array
    {
        return $this->reservationCostCalculationService->calculateEstimate([
            'reservation_type' => $type === 'energy' ? 'kwh' : 'minute',
            'reservation_value' => (float) $value,
            'start_time' => now(),
        ], $pricingPlan, $chargingPoint);
    }

    /**
     * Calcule le coût en temps réel (AJAX)
     */
    public function calculateCostAjax(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:energy,duration',
                'value' => 'required|numeric|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $chargingPoint = ChargingPoint::with(['pricingPlan'])->findOrFail($id);
            $pricingPlan = $chargingPoint->pricingPlan;
            
            if (!$pricingPlan) {
                $pricingPlan = PricingPlan::where('is_active', true)->first();
            }

            if (!$pricingPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan tarifaire non disponible'
                ], 400);
            }

            $estimate = $this->calculateCost($request->type, $request->value, $pricingPlan, $chargingPoint);

            if (isset($estimate['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $estimate['error']
                ], 422);
            }

            return response()->json([
                'success' => true,
                'cost' => $estimate['estimated_cost'],
                'total_ttc' => $estimate['estimated_cost'],
                'currency' => $estimate['currency'],
                'estimated_duration' => $estimate['estimated_duration'],
                'estimated_energy' => $estimate['estimated_energy'],
                'breakdown' => $estimate['breakdown'],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur calcul coût: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul du coût'
            ], 500);
        }
    }

}
