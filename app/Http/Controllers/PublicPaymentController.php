<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\PricingPlan;
use App\Services\PrepaidPaymentService;
use App\Services\PricingService;
use App\Services\ChargingPointStatusService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur public pour les flux de paiement QR Code
 * Gère les pages de paiement prépayé et postpayé
 */
class PublicPaymentController extends Controller
{
    protected PrepaidPaymentService $prepaidPaymentService;
    protected PricingService $pricingService;
    protected ChargingPointStatusService $statusService;
    protected WalletService $walletService;

    public function __construct(
        PrepaidPaymentService $prepaidPaymentService,
        PricingService $pricingService,
        ChargingPointStatusService $statusService,
        WalletService $walletService
    ) {
        $this->prepaidPaymentService = $prepaidPaymentService;
        $this->pricingService = $pricingService;
        $this->statusService = $statusService;
        $this->walletService = $walletService;
    }

    /**
     * Étape 1: Afficher les informations de la borne et le formulaire de sélection
     * Accessible via QR code
     */
    public function showStationInfo(Request $request, int $chargingPointId)
    {
        try {
            // Récupérer la borne avec ses relations
            $chargingPoint = ChargingPoint::with([
                'pricingPlan',
                'group',
                'station',
                'connectors'
            ])->findOrFail($chargingPointId);

            // Récupérer le plan tarifaire
            $pricingPlan = $chargingPoint->pricingPlan;
            if (!$pricingPlan) {
                $pricingPlan = PricingPlan::where('is_active', true)->first();
            }

            if (!$pricingPlan) {
                abort(400, 'Plan tarifaire non disponible');
            }

            // Vérifier la disponibilité en temps réel
            $availability = $this->statusService->getChargingPointAvailability($chargingPointId);

            // Déterminer le mode de facturation
            $consumptionMode = $chargingPoint->group?->consumption_mode ?? 'prepaid';

            // Préparer les données pour l'affichage
            $stationInfo = [
                'id' => $chargingPoint->id,
                'name' => $chargingPoint->name,
                'address' => $this->formatAddress($chargingPoint),
                'latitude' => $chargingPoint->latitude,
                'longitude' => $chargingPoint->longitude,
                'max_power' => $chargingPoint->max_power,
                'connectors' => $chargingPoint->connectors->map(function ($connector) {
                    return [
                        'id' => $connector->id,
                        'type' => $connector->connector_type ?? 'Type 2',
                        'status' => $connector->status ?? 'available',
                        'power' => $connector->max_power ?? 22,
                    ];
                }),
                'pricing' => [
                    'currency' => $pricingPlan->currency ?? 'EUR',
                    'rate_type' => $pricingPlan->rate_type,
                    'price_per_kwh' => $pricingPlan->price_per_kwh,
                    'price_per_minute' => $pricingPlan->price_per_minute,
                    'activation_fee' => $pricingPlan->activation_fee,
                    'vat_rate' => $pricingPlan->vatRate?->rate ?? 20,
                ],
                'availability' => $availability,
                'consumption_mode' => $consumptionMode,
            ];

            // URL du QR code
            $qrCodeUrl = route('api.charging-points.qrcode.get', $chargingPointId);

            return view('payments.public.station-info', [
                'station' => $stationInfo,
                'pricingPlan' => $pricingPlan,
                'consumptionMode' => $consumptionMode,
                'qrCodeUrl' => $qrCodeUrl,
                'chargingPointId' => $chargingPointId,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage des informations de borne', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Erreur lors du chargement des informations');
        }
    }

    /**
     * Étape 1 (POST): Calculer l'estimation du prix
     */
    public function calculateEstimate(Request $request, int $chargingPointId)
    {
        $validator = Validator::make($request->all(), [
            'reservation_type' => 'required|in:energy,duration',
            'reservation_value' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $chargingPoint = ChargingPoint::with('pricingPlan')->findOrFail($chargingPointId);
            $pricingPlan = $chargingPoint->pricingPlan;

            if (!$pricingPlan) {
                return response()->json([
                    'success' => false,
                    'error' => 'Plan tarifaire non disponible',
                ], 400);
            }

            // Calculer l'estimation
            $estimate = $this->pricingService->calculateEstimate(
                $request->reservation_type,
                $request->reservation_value,
                $pricingPlan
            );

            // Vérifier la disponibilité
            $availability = $this->statusService->getChargingPointAvailability($chargingPointId);

            return response()->json([
                'success' => true,
                'estimate' => $estimate,
                'availability' => $availability,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul d\'estimation', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du calcul',
            ], 500);
        }
    }

    /**
     * Étape 2: Formulaire client et collecte d'informations
     */
    public function showCustomerForm(Request $request, int $chargingPointId)
    {
        try {
            // Récupérer les données de session
            $estimateData = $request->session()->get('prepayment_estimate');
            
            if (!$estimateData) {
                return redirect()->route('public.payment.station', $chargingPointId)
                    ->with('error', 'Veuillez d\'abord sélectionner vos options de recharge');
            }

            $chargingPoint = ChargingPoint::with('pricingPlan')->findOrFail($chargingPointId);
            $consumptionMode = $chargingPoint->group?->consumption_mode ?? 'prepaid';

            return view('payments.public.customer-form', [
                'chargingPointId' => $chargingPointId,
                'estimate' => $estimateData,
                'consumptionMode' => $consumptionMode,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage du formulaire client', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);
            abort(500);
        }
    }

    /**
     * Étape 2 (POST): Traiter les informations client
     */
    public function processCustomerForm(Request $request, int $chargingPointId)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|min:8|max:20',
            'customer_address' => 'nullable|string|max:500',
            'register_account' => 'nullable|boolean',
            'reservation_type' => 'required|in:energy,duration',
            'reservation_value' => 'required|numeric|min:1',
            'estimated_cost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Stocker les données en session pour l'étape suivante
            $request->session()->put('prepayment_customer', [
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'register_account' => $request->boolean('register_account'),
            ]);

            $request->session()->put('prepayment_estimate', [
                'reservation_type' => $request->reservation_type,
                'reservation_value' => $request->reservation_value,
                'estimated_cost' => $request->estimated_cost,
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => route('public.payment.select-method', $chargingPointId),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du formulaire client', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du traitement',
            ], 500);
        }
    }

    /**
     * Étape 3: Sélection du mode de paiement
     */
    public function showPaymentMethod(Request $request, int $chargingPointId)
    {
        try {
            $customerData = $request->session()->get('prepayment_customer');
            $estimateData = $request->session()->get('prepayment_estimate');

            if (!$customerData || !$estimateData) {
                return redirect()->route('public.payment.station', $chargingPointId)
                    ->with('error', 'Session expirée. Veuillez recommencer.');
            }

            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            return view('payments.public.payment-method', [
                'chargingPointId' => $chargingPointId,
                'customer' => $customerData,
                'estimate' => $estimateData,
                'chargingPoint' => $chargingPoint,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage du mode de paiement', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);
            abort(500);
        }
    }

    /**
     * Étape 3 (POST): Initier le paiement
     */
    public function initiatePayment(Request $request, int $chargingPointId)
    {
        $validator = Validator::make($request->all(), [
            'payment_gateway' => 'required|in:cmi,stripe',
            'save_card' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Récupérer les données de session
            $customerData = $request->session()->get('prepayment_customer');
            $estimateData = $request->session()->get('prepayment_estimate');

            if (!$customerData || !$estimateData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session expirée',
                ], 400);
            }

            // Préparer les données de réservation
            $reservationData = array_merge(
                $customerData,
                $estimateData,
                [
                    'charging_point_id' => $chargingPointId,
                    'payment_gateway' => $request->payment_gateway,
                ]
            );

            // Créer la réservation prépayée
            $result = $this->prepaidPaymentService->createPrepaidReservation(
                $reservationData,
                $request->payment_gateway
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur lors de la création de la réservation',
                ], 400);
            }

            // Stocker l'ID de réservation en session
            $request->session()->put('prepayment_reservation_id', $result['reservation_id']);

            return response()->json([
                'success' => true,
                'reservation_id' => $result['reservation_id'],
                'payment_url' => $result['payment_url'],
                'redirect_url' => $result['payment_url'],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'initiation du paiement', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du paiement',
            ], 500);
        }
    }

    /**
     * Page de confirmation après paiement réussi
     */
    public function paymentSuccess(Request $request, int $reservationId)
    {
        try {
            $reservation = Reservation::with(['chargingPoint', 'pricingPlan'])
                ->findOrFail($reservationId);

            // Générer le QR code d'activation
            $qrCodeUrl = null;
            if ($reservation->activation_token) {
                $qrCodeUrl = route('api.qrcode.validate', $reservation->activation_token);
            }

            // Nettoyer la session
            $request->session()->forget(['prepayment_estimate', 'prepayment_customer', 'prepayment_reservation_id']);

            return view('payments.public.success', [
                'reservation' => $reservation,
                'qrCodeUrl' => $qrCodeUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la confirmation', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
            ]);
            abort(500);
        }
    }

    /**
     * Page d'annulation de paiement
     */
    public function paymentCancel(Request $request, int $reservationId = null)
    {
        try {
            // Nettoyer la session
            $request->session()->forget(['prepayment_estimate', 'prepayment_customer', 'prepayment_reservation_id']);

            return view('payments.public.cancel', [
                'reservationId' => $reservationId,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de l\'annulation', [
                'error' => $e->getMessage(),
            ]);
            abort(500);
        }
    }

    /**
     * Vérifier le statut du paiement (pour polling)
     */
    public function checkPaymentStatus(Request $request, int $reservationId)
    {
        try {
            $reservation = Reservation::findOrFail($reservationId);

            $status = [
                'reservation_id' => $reservation->id,
                'payment_status' => $reservation->payment_status,
                'status' => $reservation->status,
                'is_paid' => $reservation->payment_status === 'PAID',
                'is_completed' => in_array($reservation->status, ['completed', 'confirmed']),
            ];

            return response()->json([
                'success' => true,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Réservation non trouvée',
            ], 404);
        }
    }

    /**
     * Formater l'adresse de la borne
     */
    protected function formatAddress(ChargingPoint $chargingPoint): string
    {
        $parts = array_filter([
            $chargingPoint->address ?? $chargingPoint->station?->address,
            $chargingPoint->city ?? $chargingPoint->station?->city,
            $chargingPoint->country,
        ]);

        return implode(', ', $parts) ?: 'Adresse non disponible';
    }

    // ==================== FLUX POSTPAYÉ ====================

    /**
     * Démarrer une session postpayée via QR code
     */
    public function startPostpaidSession(Request $request, int $chargingPointId)
    {
        try {
            $chargingPoint = ChargingPoint::with(['pricingPlan', 'group'])->findOrFail($chargingPointId);
            
            // Vérifier que le mode postpayé est activé
            $consumptionMode = $chargingPoint->group?->consumption_mode ?? 'prepaid';
            if ($consumptionMode !== 'postpaid') {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette borne ne supporte pas le mode postpayé',
                ], 400);
            }

            // Vérifier la disponibilité
            $availability = $this->statusService->getChargingPointAvailability($chargingPointId);
            if (!$availability['available']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Aucune borne disponible',
                ], 400);
            }

            // Si utilisateur connecté, vérifier le wallet
            $user = auth()->user();
            if ($user) {
                $walletValidation = $this->walletService->validateForPostpaid($user);
                
                if (!$walletValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'error' => $walletValidation['message'],
                        'requires_deposit' => true,
                        'required_amount' => $walletValidation['required_amount'] ?? null,
                    ], 402);
                }
            }

            // Créer une session invité ou lié à l'utilisateur
            // TODO: Intégrer avec PostpaidChargingService
            
            return response()->json([
                'success' => true,
                'message' => 'Session postpayée initialisée',
                'availability' => $availability,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du démarrage de session postpayée', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du démarrage',
            ], 500);
        }
    }

    /**
     * Page de sélection prépayé/postpayé
     */
    public function showPaymentModeSelection(Request $request, int $chargingPointId)
    {
        try {
            $chargingPoint = ChargingPoint::with(['pricingPlan', 'group'])->findOrFail($chargingPointId);
            $consumptionMode = $chargingPoint->group?->consumption_mode ?? 'prepaid';

            // Récupérer l'estimation de la session si exists
            $estimateData = $request->session()->get('prepayment_estimate');

            return view('payments.public.mode-selection', [
                'chargingPointId' => $chargingPointId,
                'chargingPoint' => $chargingPoint,
                'consumptionMode' => $consumptionMode,
                'estimate' => $estimateData,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de sélection du mode', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);
            abort(500);
        }
    }
}
