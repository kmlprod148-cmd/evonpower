<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Services\QRCodeServiceFixed;
use App\Services\PricingPlanService;
use App\Repositories\Interfaces\ChargingPointRepositoryInterface;
use App\Repositories\StationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur public pour les offres de borne de recharge (sans authentification)
 */
class PublicChargingPointOfferController extends Controller
{
    protected $qrCodeService;
    protected $chargingPointRepository;
    protected $stationRepository;
    protected PricingPlanService $pricingPlanService;

    /**
     * Constructeur avec injection des dépendances
     */
    public function __construct(
        QRCodeServiceFixed $qrCodeService,
        ChargingPointRepositoryInterface $chargingPointRepository,
        StationRepository $stationRepository,
        PricingPlanService $pricingPlanService
    ) {
        $this->qrCodeService = $qrCodeService;
        $this->chargingPointRepository = $chargingPointRepository;
        $this->stationRepository = $stationRepository;
        $this->pricingPlanService = $pricingPlanService;

        // Pas de middleware d'authentification pour les routes publiques
    }

    /**
     * Affiche le formulaire de réservation publique pour une borne spécifique
     * Accessible sans authentification
     *
     * @param string $id Identifiant de la borne
     * @return \Illuminate\View\View
     */
    public function showReservationOffer($id)
    {
        // Désactiver la compression de sortie pour éviter l'erreur ob_end_flush
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Désactiver la compression gzip
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        
        // Désactiver la compression via headers
        if (!headers_sent()) {
            header('Content-Encoding: identity');
            header('Vary: Accept-Encoding');
        }
        
        try {
            // Récupérer la borne avec toutes ses relations nécessaires
            $chargingPoint = ChargingPoint::with([
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
                $pricingPlan = PricingPlan::where('is_active', true)->first();
                
                // 3. Si aucun plan actif, créer un plan par défaut temporaire
                if (!$pricingPlan) {
                    $pricingPlan = new PricingPlan([
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

            // Pas de créneaux horaires - recharge immédiate après paiement
            $timeSlots = [];

            // Generate QR Code for the charging point
            $qrCodeService = app(\App\Services\QRCodeServiceFixed::class);
            $qrCode = $qrCodeService->generateAsDataUrl($chargingPoint->id);

            // Créer l'estimateur d'offre pour récupérer les informations de limite
            $offerEstimator = new \App\Services\OfferEstimator($chargingPoint);
            $limitInfo = $offerEstimator->getReservationLimitInfo();

            // Déterminer le type de réservation basé sur le plan tarifaire
            $reservationType = $this->getReservationTypeFromPricingPlan($pricingPlan);
            $showReservationType = $reservationType !== 'mixed';
            
            // Détecter si c'est un appareil mobile
            $isMobile = $this->isMobileDevice();
            
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
            
            // Utiliser la vue mobile si c'est un appareil mobile
            if ($isMobile) {
                return view('charging-points.offer-reservation-mobile', [
                    'chargingPoint' => $chargingPoint,
                    'pricingPlan' => $pricingPlan,
                    'timeSlots' => $timeSlots,
                    'currency' => 'EUR', // Force EUR instead of pricingPlan currency
                    'qrCode' => $qrCode,
                    'limitInfo' => $limitInfo,
                    'reservationType' => $reservationType,
                    'showReservationType' => $showReservationType,
                    'creditData' => $creditData,
                ]);
            }
            
            $response = response()->view('charging-points.offer-reservation', [
                'chargingPoint' => $chargingPoint,
                'pricingPlan' => $pricingPlan,
                'timeSlots' => $timeSlots,
                'currency' => 'EUR', // Force EUR instead of pricingPlan currency
                'qrCode' => $qrCode,
                'limitInfo' => $limitInfo,
                'reservationType' => $reservationType,
                'showReservationType' => $showReservationType,
                'creditData' => $creditData,
            ]);
            
            // Désactiver la compression sur la réponse
            $response->headers->set('Content-Encoding', 'identity');
            $response->headers->set('Vary', 'Accept-Encoding');
            
            return $response;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Charging point not found for public offer view: ' . $id);
            return redirect()->route('charging-points.index')
                ->with('error', 'Borne de recharge non trouvée.');
        } catch (\Exception $e) {
            Log::error('Error showing public charging offer for charging point ' . $id . ': ' . $e->getMessage());
            
            // Nettoyer les buffers de sortie en cas d'erreur
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            return redirect()->route('charging-points.index')
                ->with('error', 'Une erreur est survenue lors de l\'affichage de l\'offre.');
        }
    }

    /**
     * Affiche le formulaire de réservation mobile (force mobile view)
     * Accessible sans authentification
     */
    public function showReservationOfferMobile($id)
    {
        try {
            // Récupérer la borne avec toutes ses relations nécessaires
            $chargingPoint = ChargingPoint::with([
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
                $pricingPlan = PricingPlan::where('is_active', true)->first();
                
                // 3. Si aucun plan actif, créer un plan par défaut temporaire
                if (!$pricingPlan) {
                    $pricingPlan = new PricingPlan([
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

            // Pas de créneaux horaires - recharge immédiate après paiement
            $timeSlots = [];

            // Generate QR Code for the charging point
            $qrCodeService = app(\App\Services\QRCodeServiceFixed::class);
            $qrCode = $qrCodeService->generateAsDataUrl($chargingPoint->id);

            // Créer l'estimateur d'offre pour récupérer les informations de limite
            $offerEstimator = new \App\Services\OfferEstimator($chargingPoint);
            $limitInfo = $offerEstimator->getReservationLimitInfo();

            // Force mobile view
            return view('charging-points.offer-reservation-public', [
                'chargingPoint' => $chargingPoint,
                'pricingPlan' => $pricingPlan,
                'timeSlots' => $timeSlots,
                'currency' => 'EUR', // Force EUR instead of pricingPlan currency
                'qrCode' => $qrCode,
                'limitInfo' => $limitInfo,
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
}
