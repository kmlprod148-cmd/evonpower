<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ChargingPointRepositoryInterface;
use App\Services\ChargingService;
use App\DTO\ChargingSession\ChargingSessionDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChargingSessionController extends Controller
{
    protected $chargingPointRepository;
    protected $chargingService;

    public function __construct(
        ChargingPointRepositoryInterface $chargingPointRepository,
        ChargingService $chargingService
    ) {
        $this->chargingPointRepository = $chargingPointRepository;
        $this->chargingService = $chargingService;
    }

    /**
     * Afficher la page de sélection de plan et connecteur
     */
    public function showStartForm($chargingPointId)
    {
        try {
            $chargingPoint = $this->chargingPointRepository->findWithRelations($chargingPointId, ['connectors', 'pricingPlan']);
            
            if (!$chargingPoint) {
                abort(404, 'Borne de recharge non trouvée');
            }

            $data = [
                'charging_point' => $chargingPoint,
                'pricing_plan' => $chargingPoint->pricingPlan,
                'connectors' => $chargingPoint->connectors
            ];

            return view('charging.start', compact('data'));
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage du formulaire de recharge: ' . $e->getMessage());
            abort(500, 'Erreur lors du chargement');
        }
    }

    /**
     * Traiter le démarrage d'une session de recharge
     */
    public function startSession(Request $request, $chargingPointId)
    {
        try {
            $validated = $request->validate([
                'connector_id' => 'required|integer|exists:connectors,id',
                'email' => 'nullable|email',
                'vehicle_plate' => 'nullable|string|max:20',
            ]);

            // Vérifier que la borne existe
            $chargingPoint = $this->chargingPointRepository->findWithRelations($chargingPointId, ['connectors', 'pricingPlan']);
            
            if (!$chargingPoint) {
                return back()->withErrors(['error' => 'Borne de recharge non trouvée']);
            }

            // Vérifier que le connecteur appartient à cette borne
            $connector = $chargingPoint->connectors->where('id', $validated['connector_id'])->first();
            if (!$connector) {
                return back()->withErrors(['error' => 'Connecteur invalide']);
            }

            // Vérifier que le connecteur est disponible
            if ($connector->status !== 'available') {
                return back()->withErrors(['error' => 'Ce connecteur n\'est pas disponible']);
            }

            // Vérifier qu'un plan tarifaire est assigné
            if (!$chargingPoint->pricingPlan) {
                return back()->withErrors(['error' => 'Aucun plan tarifaire assigné à cette borne']);
            }

            // Créer la session de recharge
            $sessionDTO = new ChargingSessionDTO(
                $chargingPointId,
                $validated['connector_id'],
                $chargingPoint->pricingPlan->id,
                auth()->id() ?? null
            );

            // Ajouter les métadonnées
            $sessionDTO->metaData = [
                'email' => $validated['email'] ?? null,
                'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                'started_at' => now(),
            ];

            // Démarrer la session
            $session = $this->chargingService->startSession($sessionDTO);

            // Rediriger vers la page de suivi
            return redirect()->route('charging.monitor', [
                'sessionId' => $session->id,
                'token' => $session->monitoring_token
            ])->with('success', 'Session de recharge démarrée avec succès !');

        } catch (\Exception $e) {
            Log::error('Erreur lors du démarrage de la session: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors du démarrage de la session']);
        }
    }

    /**
     * Afficher la page de suivi de la session
     */
    public function monitor($sessionId, $token)
    {
        try {
            $session = $this->chargingService->getSessionByToken($token, $sessionId);
            
            if (!$session) {
                abort(404, 'Session non trouvée');
            }

            $metrics = $this->chargingService->getSessionMetrics($session->id);
            
            $data = [
                'session' => $session,
                'metrics' => $metrics
            ];

            return view('charging.monitor', compact('data'));
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du suivi de session: ' . $e->getMessage());
            abort(500, 'Erreur lors du chargement');
        }
    }

    /**
     * Arrêter une session de recharge
     */
    public function stopSession(Request $request, $sessionId)
    {
        try {
            $session = $this->chargingService->getSessionById($sessionId);
            
            if (!$session) {
                return back()->withErrors(['error' => 'Session non trouvée']);
            }

            // Arrêter la session
            $result = $this->chargingService->stopSession($sessionId);
            
            if ($result) {
                return redirect()->route('charging.receipt', $sessionId)
                    ->with('success', 'Session arrêtée avec succès');
            } else {
                return back()->withErrors(['error' => 'Erreur lors de l\'arrêt de la session']);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'arrêt de session: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur lors de l\'arrêt']);
        }
    }

    /**
     * Afficher le reçu de la session
     */
    public function receipt($sessionId)
    {
        try {
            $session = $this->chargingService->getSessionById($sessionId);
            
            if (!$session) {
                abort(404, 'Session non trouvée');
            }

            $metrics = $this->chargingService->getSessionMetrics($session->id);
            
            $data = [
                'session' => $session,
                'metrics' => $metrics
            ];

            return view('charging.receipt', compact('data'));
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage du reçu: ' . $e->getMessage());
            abort(500, 'Erreur lors du chargement');
        }
    }
} 