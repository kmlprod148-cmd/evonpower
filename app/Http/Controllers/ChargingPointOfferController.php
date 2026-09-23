<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\Station;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Services\QRCodeService;
use App\Services\ChargingPointQRCodeService;
use App\Services\PricingService;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ChargingPoint\SelectPricingPlanRequest;
use App\Http\Requests\ChargingPoint\StartChargeRequest;
use App\Http\Requests\PublicChargingOffer\StoreReservationRequest;
use App\Repositories\Interfaces\ChargingPointRepositoryInterface;
use App\Repositories\StationRepository;
use App\Services\PricingPlanService;
use App\DTO\ChargingSessionDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\ChargingPointNotFoundException;
use App\Exceptions\StationUnavailableException;
use App\Exceptions\ChargingSessionStartException;
use App\Exceptions\ChargingSessionStopException;

/**
 * Contrôleur pour gérer les pages d'offre de borne de recharge
 */
class ChargingPointOfferController extends Controller
{
    protected $qrCodeService;
    protected $pricingService;
    protected $chargingPointRepository;
    protected $stationRepository;
    protected PricingPlanService $pricingPlanService;

    /**
     * Constructeur avec injection des dépendances
     */
    public function __construct(
        QRCodeService $qrCodeService,
        PricingService $pricingService,
        ChargingPointRepositoryInterface $chargingPointRepository,
        StationRepository $stationRepository,
        PricingPlanService $pricingPlanService
    ) {
        $this->qrCodeService = $qrCodeService;
        $this->pricingService = $pricingService;
        $this->chargingPointRepository = $chargingPointRepository; // Assign injected repository
        $this->stationRepository = $stationRepository;         // Assign injected repository
        $this->pricingPlanService = $pricingPlanService;          // Assign injected repository

        $this->middleware('web');
    }

    /**
     * Affiche la page d'offre pour une borne spécifique
     *
     * @param string $id Identifiant de la borne
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        try {
            // Récupérer la borne avec toutes ses relations nécessaires
            $chargingPoint = \App\Models\ChargingPoint::with([
                'connectors', 
                'pricingPlan', 
                'partner', 
                'integrator',
                'group'
            ])->findOrFail($id);

            // Récupérer le plan tarifaire actif
            $pricingPlanService = app(\App\Services\PricingPlanService::class);
            
            // 1. D'abord vérifier si la borne a un plan assigné
            if ($chargingPoint->pricingPlan && $chargingPoint->pricingPlan->is_active) {
                $pricingPlan = $chargingPoint->pricingPlan;
            } else {
                // 2. Sinon, utiliser le premier plan actif disponible
                $pricingPlan = \App\Models\PricingPlan::where('is_active', true)->first();
            }

            // 3. Si toujours pas de plan, créer un plan par défaut temporaire
            if (!$pricingPlan) {
                $pricingPlan = new \App\Models\PricingPlan([
                    'name' => 'Plan Standard',
                    'rate_type' => 'mixed',
                    'price_per_kwh' => 0.30,
                    'price_per_minute' => 0.10,
                    'activation_fee' => 1.00,
                    'currency' => 'EUR',
                    'is_active' => true
                ]);
            }

            // Déterminer le type de carousel selon le rate_type du plan
            $carouselType = 'minute'; // Par défaut
            if ($pricingPlan->rate_type === 'kwh' || $pricingPlan->rate_type === 'energy') {
                $carouselType = 'kwh';
            } elseif ($pricingPlan->rate_type === 'minute' || $pricingPlan->rate_type === 'time') {
                $carouselType = 'minute';
            } elseif ($pricingPlan->rate_type === 'mixed') {
                // Pour le type mixte, on peut laisser l'utilisateur choisir
                $carouselType = 'both';
            }
            
            // Préparer les options pour le carousel
            $carouselOptions = [];
            if ($carouselType === 'minute' || $carouselType === 'both') {
                $carouselOptions['minute'] = [15, 30, 45, 60, 90, 120]; // minutes
            }
            if ($carouselType === 'kwh' || $carouselType === 'both') {
                $carouselOptions['kwh'] = [5, 10, 15, 20, 25, 30]; // kWh
            }
            
            // Calculer les prix pour chaque option
            $pricingOptions = [];
            
            if (isset($carouselOptions['minute'])) {
                foreach ($carouselOptions['minute'] as $value) {
                    $price = $this->calculatePrice($pricingPlan, $value, 'minute');
                    $pricingOptions['minute'][] = [
                        'value' => $value,
                        'unit' => 'min',
                        'price' => $price,
                        'formatted_price' => number_format($price, 2) . ' ' . ($pricingPlan->currency ?? 'EUR'),
                        'includes_vat' => true
                    ];
                }
            }
            
            if (isset($carouselOptions['kwh'])) {
                foreach ($carouselOptions['kwh'] as $value) {
                    $price = $this->calculatePrice($pricingPlan, $value, 'kwh');
                    $pricingOptions['kwh'][] = [
                        'value' => $value,
                        'unit' => 'kWh',
                        'price' => $price,
                        'formatted_price' => number_format($price, 2) . ' ' . ($pricingPlan->currency ?? 'EUR'),
                        'includes_vat' => true
                    ];
                }
            }
            
            // Générer le QR code pour cette offre (pointe vers la page d'offre de réservation)
            $qrCodeService = app(\App\Services\ChargingPointQRCodeService::class);
            try {
                $qrCodeData = $qrCodeService->get($chargingPoint->id);
                $qrCode = $qrCodeData['qr_code_url'];
            } catch (\Exception $e) {
                // Si pas de QR code, en générer un qui pointe vers la page d'offre de réservation
                $qrCode = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . 
                         urlencode(route('public.charging-point.offer.reservation', $id));
            }

            // Préparer les options de réservation basées sur le plan tarifaire actif
            $reservationOptions = [];

            // Déterminer les options disponibles en fonction du rate_type du plan
            if ($pricingPlan->rate_type === 'kwh' || $pricingPlan->rate_type === 'energy' || $pricingPlan->rate_type === 'mixed') {
                $kwhOptions = [5, 10, 15, 20, 25, 30]; // Default kWh options
                foreach ($kwhOptions as $value) {
                    $price = $this->calculatePrice($pricingPlan, $value, 'kwh');
                    $reservationOptions['kwh'][] = [
                        'value' => $value,
                        'unit' => 'kWh',
                        'price' => $price,
                        'formatted_price' => number_format($price, 2) . ' ' . ($pricingPlan->currency ?? 'EUR')
                    ];
                }
            }

            if ($pricingPlan->rate_type === 'minute' || $pricingPlan->rate_type === 'time' || $pricingPlan->rate_type === 'mixed') {
                $minuteOptions = [15, 30, 45, 60, 90, 120]; // Default minute options
                // Filter minute options by max_duration if set
                if ($pricingPlan->max_duration) {
                    $minuteOptions = array_filter($minuteOptions, function($value) use ($pricingPlan) {
                        return $value <= $pricingPlan->max_duration;
                    });
                }
                foreach ($minuteOptions as $value) {
                    $price = $this->calculatePrice($pricingPlan, $value, 'minute');
                    $reservationOptions['minute'][] = [
                        'value' => $value,
                        'unit' => 'min',
                        'price' => $price,
                        'formatted_price' => number_format($price, 2) . ' ' . ($pricingPlan->currency ?? 'EUR')
                    ];
                }
            }

            // Pas de créneaux horaires - recharge immédiate après paiement
            $timeSlots = [];

            // Préparer les données pour la vue
            $viewData = [
                'chargingPoint' => $chargingPoint,
                'pricingPlan' => $pricingPlan,
                'carouselType' => $carouselType,
                'pricingOptions' => $pricingOptions,
                'qrCode' => $qrCode,
                'station' => $chargingPoint->station,
                'currency' => 'EUR', // Force EUR instead of pricingPlan currency
                'reservationOptions' => $reservationOptions, // Add reservation options
                'timeSlots' => $timeSlots, // Pas de créneaux - recharge immédiate
                'min_duration' => $pricingPlan->min_charge_duration ?? 1,
                'min_kwh' => $pricingPlan->min_energy ?? 1,
            ];

            return view('public.charging-offer', ['data' => $viewData]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage offre: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return redirect()->route('charging-points.index')
                ->with('error', 'Une erreur est survenue lors de l\'affichage de l\'offre.');
        }
    }

    private function calculatePrice($pricingPlan, $value, $type)
    {
        if (!$pricingPlan) return 0;

        $basePrice = 0;

        // Frais d'activation
        $basePrice += $pricingPlan->activation_fee ?? 0;

        // Calcul selon le type
        if ($type === 'minute') {
            $pricePerMinute = $pricingPlan->price_per_minute ?? 0;
            // Pour les anciens modèles qui utilisent base_rate_per_minute
            if ($pricePerMinute == 0 && isset($pricingPlan->base_rate_per_minute)) {
                $pricePerMinute = $pricingPlan->base_rate_per_minute;
            }
            if ($pricePerMinute > 0) {
                $basePrice += $pricePerMinute * $value;
            }
        } else { // kwh
            $pricePerKwh = $pricingPlan->price_per_kwh ?? 0;
            // Pour les anciens modèles qui utilisent base_rate_per_kwh
            if ($pricePerKwh == 0 && isset($pricingPlan->base_rate_per_kwh)) {
                $pricePerKwh = $pricingPlan->base_rate_per_kwh;
            }
            if ($pricePerKwh > 0) {
                $basePrice += $pricePerKwh * $value;
            }
        }

        // Ajouter la TVA si applicable
        if ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) {
            $basePrice *= (1 + ($pricingPlan->vatRate->rate / 100));
        } elseif (isset($pricingPlan->vat_rate) && $pricingPlan->vat_rate > 0) {
            // Pour la compatibilité avec l'ancien modèle
            $basePrice *= (1 + ($pricingPlan->vat_rate / 100));
        }

        return round($basePrice, 2);
    }

    public function createOrder(Request $request, $id)
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,id',
            'pricing_option' => 'required|numeric|min:0',
            'pricing_type' => 'required|in:minute,kwh',
            'estimated_price' => 'required|numeric|min:0',
            'pricing_plan_id' => 'required|exists:pricing_plans,id'
        ]);

        try {
            \DB::beginTransaction();

            // Vérifier que la borne existe
            $chargingPoint = \App\Models\ChargingPoint::findOrFail($id);

            // Vérifier que la station est disponible
            $station = \App\Models\Station::where('id', $validated['station_id'])
                ->where('charging_point_id', $id)
                ->where('status', 'available')
                ->firstOrFail();

            // Récupérer le plan tarifaire
            $pricingPlan = \App\Models\PricingPlan::findOrFail($validated['pricing_plan_id']);

            // Générer un ID de transaction unique
            $transactionId = 'TXN-' . strtoupper(uniqid());

            // Créer la transaction (pas encore une recharge, juste une commande)
            $transaction = new \App\Models\Transaction();
            $transaction->transaction_id = $transactionId;
            $transaction->charging_point_id = $id;
            $transaction->station_id = $station->id;
            $transaction->user_id = auth()->check() ? auth()->id() : null;
            $transaction->start_timestamp = now();
            $transaction->status = 'pending'; // Status en attente
            $transaction->auth_method = 'app';
            $transaction->pricing_plan_id = $pricingPlan->id;

            // Stocker les détails de la commande
            $transaction->meter_start = 0;
            $transaction->amount = $validated['estimated_price']; // Ajouter le champ amount manquant
            $transaction->price_total = $validated['estimated_price'];
            $transaction->currency = $pricingPlan->currency ?? 'EUR';

            // Populate price_details for commission calculation
            $transaction->price_details = json_encode([
                'pricing_plan_id' => $pricingPlan->id,
                'pricing_plan_type' => $pricingPlan->rate_type, // Assuming rate_type is the pricing_plan_type
                'price_per_kwh' => $pricingPlan->price_per_kwh,
                'price_per_minute' => $pricingPlan->price_per_minute,
                'activation_fee' => $pricingPlan->activation_fee,
                'vat_rate' => $pricingPlan->vatRate ? $pricingPlan->vatRate->rate : ($pricingPlan->vat_rate ?? 0),
            ]);

            // Calculer les commissions selon le plan
            $this->calculateCommissions($transaction, $chargingPoint, $pricingPlan);

            // Métadonnées de la commande
            $transaction->metadata = json_encode([
                'order_type' => $validated['pricing_type'],
                'order_value' => $validated['pricing_option'],
                'estimated_duration' => $validated['pricing_type'] === 'minute' ? $validated['pricing_option'] : null,
                'estimated_energy' => $validated['pricing_type'] === 'kwh' ? $validated['pricing_option'] : null,
            ]);

            $transaction->save();

            // Marquer la station comme réservée
            $station->status = 'reserved';
            $station->save();

            // Créer une session temporaire pour l'utilisateur
            session(['active_transaction_id' => $transaction->id]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->transaction_id,
                'order_id' => $transaction->id,
                'message' => 'Commande créée avec succès',
                'redirect_url' => route('charging-points.confirm-order', [
                    'id' => $id,
                    'transaction_id' => $transaction->transaction_id
                ])
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Erreur création commande: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateCommissions($transaction, $chargingPoint, $pricingPlan)
    {
        // Récupérer le plan de commission applicable
        $commissionService = app(\App\Services\CommissionService::class);
        $commissionPlan = $commissionService->determineCommissionPlan($transaction);

        if ($commissionPlan) {
            $transaction->commission_plan_id = $commissionPlan->id;

            // Calculer les commissions selon les pourcentages définis
            $totalAmount = $transaction->price_total;

            // Initialize commissions to 0
            $adminCommission = 0;
            $integratorCommission = 0;
            $partnerCommission = 0;

            // Commission admin
            $adminCommission = round(
                ($totalAmount * $commissionPlan->admin_percentage) / 100,
                2
            );
            
            // Commission intégrateur (si applicable)
            if ($chargingPoint->integrator_id) {
                $integratorCommission = round(
                    ($totalAmount * $commissionPlan->integrator_percentage) / 100,
                    2
                );
            }

            // Commission partenaire (si applicable)
            if ($chargingPoint->partner_id) {
                $partnerCommission = round(
                    ($totalAmount * $commissionPlan->partner_percentage) / 100,
                    2
                );
            }

            $transaction->admin_commission = $adminCommission;
            $transaction->integrator_commission = $integratorCommission;
            $transaction->partner_commission = $partnerCommission;

            // Stocker la répartition détaillée
            $transaction->repartition_breakdown = json_encode([
                'admin' => [
                    'percentage' => $commissionPlan->admin_percentage,
                    'amount' => $adminCommission
                ],
                'integrator' => [
                    'id' => $chargingPoint->integrator_id,
                    'percentage' => $commissionPlan->integrator_percentage,
                    'amount' => $integratorCommission
                ],
                'partner' => [
                    'id' => $chargingPoint->partner_id,
                    'percentage' => $commissionPlan->partner_percentage,
                    'amount' => $partnerCommission
                ]
            ]);
        }
    }
    /**
     * Sélectionne un plan tarifaire pour une session de recharge
     *
     * @param SelectPricingPlanRequest $request
     * @param string $id Identifiant de la borne
     * @return \Illuminate\Http\RedirectResponse
     */
    public function selectPricingPlan(SelectPricingPlanRequest $request, $id)
    {
        try {
            $validatedData = $request->validated();

            // Récupérer la borne
            $chargingPoint = $this->chargingPointRepository->find($id);
            if (!$chargingPoint) {
                 return back()->with('error', 'Borne de recharge introuvable.');
            }
            
            // Vérifier que la station existe et est disponible
            $station = $this->stationRepository->findByChargingPointAndId(
                $chargingPoint->id,
                $validatedData['station_id']
            );

            if (!$station || $station->status !== 'available') {
                return back()->with('error', 'Cette station n\'est pas disponible.');
            }

            // Vérifier que le plan tarifaire est valide pour cette borne
            $pricingPlan = $this->pricingPlanService->findValidPlanForChargingPoint(
                $validatedData['pricing_plan_id'],
                $chargingPoint
            );

            if (!$pricingPlan) {
                return back()->with('error', 'Plan tarifaire invalide.');
            }

            // Rediriger vers la page de confirmation
            return redirect()->route('charging-points.offer.confirm', [
                'id' => $chargingPoint->id,
                'station_id' => $station->id,
                'pricing_plan_id' => $pricingPlan->id
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la sélection du plan tarifaire', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id,
                'request_data' => $request->validated()
            ]);

            return back()->with('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }

    /**
     * Affiche la page de confirmation de recharge
     *
     * @param string $id Identifiant de la borne
     * @return \Illuminate\View\View
     */
    public function confirmCharge($id)
    {
        try {
            $chargingPoint = $this->chargingPointRepository->findWithRelations($id, ['connectors', 'pricingPlan']);
            if (!$chargingPoint) {
                return redirect()->route('charging-points.index')
                    ->with('error', 'Borne de recharge introuvable.');
            }

            $pricingPlan = $this->pricingPlanService->find($pricingPlanId);

            if (!$connector || !$pricingPlan) {
                return redirect()->route('charging-points.offer.show', $id)
                    ->with('error', 'Information de recharge invalide.');
            }

            return view('charging-points.confirm', compact(
                'chargingPoint',
                'connector',
                'pricingPlan'
            ));
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la confirmation de recharge', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id
            ]);

            return redirect()->route('charging-points.offer.show', $id)
                ->with('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }

    /**
     * Démarre une session de recharge
     *
     * @param StartChargeRequest $request
     * @param string $id Identifiant de la borne
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startCharge(StartChargeRequest $request, $id)
    {
        try {
            $validatedData = $request->validated();

            // Créer un DTO pour la session de recharge
            $sessionDTO = new ChargingSessionDTO(
                $id,
                $validatedData['connector_id'],
                $validatedData['pricing_plan_id'],
                auth()->id() // Assurez-vous que l'utilisateur est authentifié
            );

            // Démarrer la session de recharge via le service
            $session = $this->chargingPointService->startChargingSession($sessionDTO);

            // Stocker l'ID de session dans la session utilisateur pour référence future
            session(['active_charging_session_id' => $session->id]);

            return redirect()->route('charging-points.charging', $id)
                ->with('success', 'Recharge démarrée avec succès !');
        } catch (ChargingPointNotFoundException $e) {
            Log::error('Erreur lors du démarrage de la session de recharge: Borne non trouvée', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id,
                'request_data' => $request->validated()
            ]);
            return back()->with('error', $e->getMessage());
        } catch (ConnectorUnavailableException $e) {
            Log::error('Erreur lors du démarrage de la session de recharge: Connecteur non disponible', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id,
                'request_data' => $request->validated()
            ]);
            return back()->with('error', $e->getMessage());
        } catch (ChargingSessionStartException $e) {
            Log::error('Erreur lors du démarrage de la session de recharge: Impossible de démarrer', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id,
                'request_data' => $request->validated()
            ]);
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erreur inattendue lors du démarrage de la session de recharge', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id,
                'request_data' => $request->validated()
            ]);
            return back()->with('error', 'Une erreur inattendue est survenue lors du démarrage de la recharge.');
        }
    }

    /**
     * Affiche la page de recharge en cours
     *
     * @param string $id Identifiant de la borne
     * @return \Illuminate\View\View
     */
    public function charging($id)
    {
        try {
            $chargingPoint = $this->chargingPointRepository->findWithRelations($id, ['connectors', 'pricingPlan']);
            if (!$chargingPoint) {
                return redirect()->route('charging-points.index')
                    ->with('error', 'Borne de recharge introuvable.');
            }

            // Récupérer l'ID de session active depuis la session utilisateur
            $sessionId = session('active_charging_session_id');

            // Récupérer les détails de la session de recharge
            $activeSession = $sessionId
                ? $this->chargingPointService->getChargingSession($sessionId)
                : null;

            if (!$activeSession || $activeSession->charging_point_id != $id) { // Ensure session belongs to this point
                return redirect()->route('charging-points.offer.show', $id)
                    ->with('error', 'Aucune session de recharge active trouvée pour cette borne.');
            }

            // Récupérer la station utilisée
            $station = $this->stationRepository->findByChargingPointAndId(
                $chargingPoint->id,
                $activeSession->station_id
            );

            // Récupérer le plan tarifaire utilisé
            $pricingPlan = $this->pricingPlanService->find($activeSession->pricing_plan_id);

            return view('charging-points.charging', compact(
                'chargingPoint',
                'station',
                'pricingPlan',
                'sessionId',
                'activeSession'
            ));
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la page de recharge', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id
            ]);

            return redirect()->route('charging-points.offer.show', $id)
                ->with('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }

    /**
     * Arrête une session de recharge
     *
     * @param string $id Identifiant de la borne
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stopChargingSession($id)
    {
        try {
            // Récupérer l'ID de session active depuis la session utilisateur
            $sessionId = session('active_charging_session_id');

            if (!$sessionId) {
                return redirect()->route('charging-points.offer.show', $id)
                    ->with('error', 'Aucune session de recharge active trouvée.');
            }

            // Vérifier que la session appartient bien à cette borne (sécurité)
            $session = $this->chargingPointService->getChargingSession($sessionId);
            if (!$session || $session->charging_point_id != $id) {
                 return redirect()->route('charging-points.offer.show', $id)
                    ->with('error', 'Session de recharge invalide pour cette borne.');
            }

            // Arrêter la session de recharge
            $this->chargingPointService->stopChargingSession($sessionId);

            // Supprimer l'ID de session de la session utilisateur
            session()->forget('active_charging_session_id');

            return redirect()->route('charging-points.offer.show', $id)
                ->with('success', 'Recharge arrêtée avec succès !');
        } catch (ChargingSessionStopException $e) {
            Log::error('Erreur lors de l\'arrêt de la session de recharge', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id,
                'session_id' => session('active_charging_session_id')
            ]);
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erreur inattendue lors de l\'arrêt de la session de recharge', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id,
                'session_id' => session('active_charging_session_id')
            ]);

            return back()->with('error', 'Une erreur inattendue est survenue lors de l\'arrêt de la recharge.');
        }
    }

    /**
     * Affiche le formulaire de réservation publique pour une borne spécifique
     *
     * @param string $id Identifiant de la borne
     * @return \Illuminate\View\View
     */
    public function showReservationOffer($id)
    {
        try {
            // Récupérer la borne avec toutes ses relations nécessaires
            $chargingPoint = \App\Models\ChargingPoint::with([
                'connectors', 
                'pricingPlan', 
                'partner', 
                'integrator',
                'group'
            ])->findOrFail($id);

            // Récupérer le plan tarifaire actif
            $pricingPlanService = app(\App\Services\PricingPlanService::class);
            
            // 1. D'abord vérifier si la borne a un plan assigné
            if ($chargingPoint->pricingPlan && $chargingPoint->pricingPlan->is_active) {
                $pricingPlan = $chargingPoint->pricingPlan;
            } else {
                // 2. Sinon, utiliser le premier plan actif disponible
                $pricingPlan = \App\Models\PricingPlan::where('is_active', true)->first();
            }

            // 3. Si toujours pas de plan, utiliser le premier plan actif disponible
            if (!$pricingPlan) {
                $pricingPlan = \App\Models\PricingPlan::where('is_active', true)->first();
                
                // Si aucun plan actif, créer un plan par défaut temporaire
                if (!$pricingPlan) {
                    $pricingPlan = new \App\Models\PricingPlan([
                        'name' => 'Plan Standard',
                        'rate_type' => 'mixed',
                        'price_per_kwh' => 0.30,
                        'price_per_minute' => 0.10,
                        'activation_fee' => 1.00,
                        'currency' => 'EUR',
                        'is_active' => true
                    ]);
                }
            }

            // Préparer les options de réservation
            $reservationOptions = [];
            
            // Options kWh
            $kwhOptions = [5, 10, 15, 20, 25, 30];
            foreach ($kwhOptions as $value) {
                $price = $this->calculateReservationPrice($pricingPlan, $value, 'kwh');
                $reservationOptions[] = [
                    'type' => 'kwh',
                    'value' => $value,
                    'unit' => 'kWh',
                    'price' => $price,
                    'formatted_price' => number_format($price, 2) . ' ' . ($pricingPlan->currency ?? 'EUR')
                ];
            }
            
            // Options minutes
            $minuteOptions = [15, 30, 45, 60, 90, 120];
            foreach ($minuteOptions as $value) {
                $price = $this->calculateReservationPrice($pricingPlan, $value, 'minute');
                $reservationOptions[] = [
                    'type' => 'minute',
                    'value' => $value,
                    'unit' => 'min',
                    'price' => $price,
                    'formatted_price' => number_format($price, 2) . ' ' . ($pricingPlan->currency ?? 'EUR')
                ];
            }

            // Pas de créneaux horaires - recharge immédiate après paiement
            $timeSlots = [];

            // Generate QR Code for the charging point
            $qrCodeService = app(\App\Services\QRCodeServiceFixed::class);
            $qrCode = $qrCodeService->generateAsDataUrl($chargingPoint->id);

            // Préparer les données pour le module crédit (uniquement pour admin/intégrateur)
            $creditData = null;
            if (auth()->check() && auth()->user()->hasRole(['admin', 'super_admin', 'integrator'])) {
                $creditService = app(\App\Services\CreditService::class);
                $creditHistory = $creditService->getCreditHistory(null, ['per_page' => 10]);
                $creditStatistics = $creditService->getCreditStatistics();
                $users = \App\Models\User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
                })->orderBy('name')->get();
                
                $creditData = [
                    'history' => $creditHistory,
                    'statistics' => $creditStatistics,
                    'users' => $users,
                ];
            }

            // Déterminer le type de réservation basé sur le plan tarifaire
            $reservationType = $this->getReservationTypeFromPricingPlan($pricingPlan);
            $showReservationType = $reservationType !== 'mixed';

            // Détecter si c'est un appareil mobile
            $isMobile = $this->isMobileDevice();
            
            // Utiliser la vue mobile si c'est un appareil mobile
            if ($isMobile) {
                return view('charging-points.offer-reservation-mobile', [
                    'chargingPoint' => $chargingPoint,
                    'pricingPlan' => $pricingPlan,
                    'timeSlots' => $timeSlots,
                    'currency' => 'EUR', // Force EUR instead of pricingPlan currency
                    'qrCode' => $qrCode,
                    'creditData' => $creditData,
                    'reservationType' => $reservationType,
                    'showReservationType' => $showReservationType,
                ]);
            }
            
            return view('charging-points.offer-reservation', [
                'chargingPoint' => $chargingPoint,
                'pricingPlan' => $pricingPlan,
                'timeSlots' => $timeSlots,
                'currency' => 'EUR', // Force EUR instead of pricingPlan currency
                'qrCode' => $qrCode,
                'creditData' => $creditData,
                'reservationType' => $reservationType,
                'showReservationType' => $showReservationType,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage du formulaire de réservation', [
                'error' => $e->getMessage(),
                'charging_point_id' => $id
            ]);

            return redirect()->route('public.charging-point.offer', $id)
                ->with('error', 'Une erreur est survenue lors du chargement du formulaire de réservation.');
        }
    }

    /**
     * Affiche le formulaire de réservation mobile (force mobile view)
     */
    public function showReservationOfferMobile($id)
    {
        try {
            // Récupérer la borne avec toutes ses relations nécessaires
            $chargingPoint = \App\Models\ChargingPoint::with([
                'connectors', 
                'pricingPlan', 
                'partner', 
                'integrator',
                'group'
            ])->findOrFail($id);

            // Récupérer le plan tarifaire actif
            $pricingPlanService = app(\App\Services\PricingPlanService::class);
            
            // 1. D'abord vérifier si la borne a un plan assigné
            if ($chargingPoint->pricingPlan && $chargingPoint->pricingPlan->is_active) {
                $pricingPlan = $chargingPoint->pricingPlan;
            } else {
                // 2. Sinon, utiliser le premier plan actif disponible
                $pricingPlan = \App\Models\PricingPlan::where('is_active', true)->first();
            }

            // 3. Si toujours pas de plan, créer un plan par défaut temporaire
            if (!$pricingPlan) {
                $pricingPlan = new \App\Models\PricingPlan([
                    'name' => 'Plan Standard',
                    'rate_type' => 'mixed',
                    'price_per_kwh' => 0.30,
                    'price_per_minute' => 0.10,
                    'activation_fee' => 1.00,
                    'currency' => 'EUR',
                    'is_active' => true
                ]);
            }

            // Pas de créneaux horaires - recharge immédiate après paiement
            $timeSlots = [];

            // Generate QR Code for the charging point
            $qrCodeService = app(\App\Services\QRCodeServiceFixed::class);
            $qrCode = $qrCodeService->generateAsDataUrl($chargingPoint->id);

            // Préparer les données pour le module crédit (uniquement pour admin/intégrateur)
            $creditData = null;
            if (auth()->check() && auth()->user()->hasRole(['admin', 'super_admin', 'integrator'])) {
                $creditService = app(\App\Services\CreditService::class);
                $creditHistory = $creditService->getCreditHistory(null, ['per_page' => 10]);
                $creditStatistics = $creditService->getCreditStatistics();
                $users = \App\Models\User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
                })->orderBy('name')->get();
                
                $creditData = [
                    'history' => $creditHistory,
                    'statistics' => $creditStatistics,
                    'users' => $users,
                ];
            }

            // Déterminer le type de réservation basé sur le plan tarifaire
            $reservationType = $this->getReservationTypeFromPricingPlan($pricingPlan);
            $showReservationType = $reservationType !== 'mixed';

            // Force mobile view
            return view('charging-points.offer-reservation-mobile', [
                'chargingPoint' => $chargingPoint,
                'pricingPlan' => $pricingPlan,
                'timeSlots' => $timeSlots,
                'currency' => 'EUR', // Force EUR instead of pricingPlan currency
                'qrCode' => $qrCode,
                'creditData' => $creditData,
                'reservationType' => $reservationType,
                'showReservationType' => $showReservationType,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Charging point not found for mobile offer view: ' . $id);
            return redirect()->route('charging-points.index')
                ->with('error', 'Borne de recharge non trouvée.');
        } catch (\Exception $e) {
            Log::error('Error showing mobile charging offer for charging point ' . $id . ': ' . $e->getMessage());
            return redirect()->route('charging-points.index')
                ->with('error', 'Une erreur est survenue lors de l\'affichage de l\'offre mobile.');
        }
    }

    /**
     * Détecte si l'appareil est mobile
     */
    private function isMobileDevice()
    {
        $userAgent = request()->header('User-Agent');
        
        // Patterns pour détecter les appareils mobiles
        $mobilePatterns = [
            'Mobile',
            'Android',
            'iPhone',
            'iPad',
            'iPod',
            'BlackBerry',
            'Windows Phone',
            'Opera Mini',
            'IEMobile',
            'Mobile Safari'
        ];
        
        foreach ($mobilePatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        
        // Vérifier la largeur de l'écran via JavaScript (si disponible)
        $screenWidth = request()->header('X-Screen-Width');
        if ($screenWidth && $screenWidth <= 768) {
            return true;
        }
        
        return false;
    }

    /**
     * Détermine le type de réservation basé sur le plan tarifaire
     */
    private function getReservationTypeFromPricingPlan($pricingPlan)
    {
        if (!$pricingPlan) {
            return 'mixed'; // Par défaut, afficher les deux options
        }

        $rateType = $pricingPlan->rate_type;

        switch ($rateType) {
            case 'energy':
            case 'kwh':
                return 'kwh';
            case 'time':
            case 'minute':
                return 'minute';
            case 'mixed':
            case 'both':
                return 'mixed';
            default:
                // Si le type n'est pas reconnu, vérifier les prix disponibles
                if ($pricingPlan->price_per_kwh && !$pricingPlan->price_per_minute) {
                    return 'kwh';
                } elseif ($pricingPlan->price_per_minute && !$pricingPlan->price_per_kwh) {
                    return 'minute';
                } else {
                    return 'mixed';
                }
        }
    }

    /**
     * Calcule le prix d'une réservation
     */
    private function calculateReservationPrice($pricingPlan, $value, $type)
    {
        $price = 0;
        
        if ($type === 'kwh') {
            $price = ($pricingPlan->price_per_kwh ?? 0) * $value;
        } elseif ($type === 'minute') {
            $price = ($pricingPlan->price_per_minute ?? 0) * $value;
        }
        
        // Ajouter les frais d'activation si applicable
        if ($pricingPlan->activation_fee) {
            $price += $pricingPlan->activation_fee;
        }
        
        return $price;
    }

}
