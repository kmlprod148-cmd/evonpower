<?php

namespace App\Http\Controllers;

use App\Http\Requests\SteveChargingPointStoreRequest;
use App\Http\Requests\SteveChargingPointUpdateRequest;
use App\Services\SteveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SteveChargingPointController extends Controller
{
    protected $steve;

    public function __construct(SteveService $steve)
    {
        $this->steve = $steve;
    }

    /**
     * 🔹 Liste des bornes depuis l'API Steve
     */
    public function index()
    {
        try {
            Log::info('SteveChargingPointController: Fetching charge points from Steve API');
            
            $chargePoints = $this->steve->getChargePoints();
            
            if ($chargePoints === null) {
                Log::warning('SteveChargingPointController: Failed to retrieve charge points from Steve API');
                return view('steve-charging-points.index', [
                    'chargePoints' => [],
                    'error' => 'Impossible de récupérer les bornes depuis l\'API Steve. Veuillez vérifier la configuration de l\'API.'
                ]);
            }

            // S'assurer que c'est un tableau
            if (!is_array($chargePoints)) {
                Log::warning('SteveChargingPointController: Invalid response format from Steve API', [
                    'type' => gettype($chargePoints),
                    'value' => $chargePoints
                ]);
                $chargePoints = [];
            }

            Log::info('SteveChargingPointController: Successfully retrieved charge points', [
                'count' => count($chargePoints)
            ]);

            return view('steve-charging-points.index', compact('chargePoints'));
        } catch (\Exception $e) {
            Log::error('SteveChargingPointController: Exception during charge points retrieval', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('steve-charging-points.index', [
                'chargePoints' => [],
                'error' => 'Erreur lors de la récupération des bornes: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 🔹 Détails d'une borne depuis l'API Steve
     */
    public function show($id)
    {
        try {
            $chargePoint = $this->steve->getChargePoint($id);

            if (!$chargePoint) {
                return redirect()->route('steve-charging-points.index')
                    ->with('error', 'Borne introuvable sur l\'API Steve.');
            }

            return view('steve-charging-points.show', compact('chargePoint'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la borne Steve: ' . $e->getMessage());
            return redirect()->route('steve-charging-points.index')
                ->with('error', 'Erreur lors de la récupération de la borne: ' . $e->getMessage());
        }
    }

    /**
     * 🔹 Formulaire de création
     */
    public function create()
    {
        return view('steve-charging-points.create');
    }

    /**
     * 🔹 Enregistrement d'une nouvelle borne sur l'API Steve
     */
    public function store(SteveChargingPointStoreRequest $request)
    {
        try {
            $data = $request->only('chargeBoxId', 'description', 'note', 'address', 'adminAddress', 
                                   'locationLatitude', 'locationLongitude', 'registrationStatus');
            
            Log::info('SteveChargingPointController: Creating charge point on Steve API', [
                'chargeBoxId' => $data['chargeBoxId'] ?? 'N/A',
                'description' => $data['description'] ?? 'N/A'
            ]);
            
            $result = $this->steve->createChargePoint($data);

            // Le service retourne maintenant toujours un tableau avec 'success'
            if ($result['success'] === true) {
                Log::info('SteveChargingPointController: Charge point created successfully', [
                    'chargeBoxId' => $data['chargeBoxId'] ?? 'N/A',
                    'chargeBoxPk' => $result['data']['chargeBoxPk'] ?? 'N/A'
                ]);
                
                return redirect()->route('steve-charging-points.index')
                    ->with('success', 'Borne créée avec succès sur l\'API Steve.');
            }

            // Gérer les erreurs
            $errorMessage = $result['error'] ?? 'Erreur inconnue lors de la création';
            
            Log::warning('SteveChargingPointController: Failed to create charge point', [
                'chargeBoxId' => $data['chargeBoxId'] ?? 'N/A',
                'error' => $errorMessage,
                'status' => $result['status'] ?? 'N/A'
            ]);
            
            return back()->withInput()
                ->with('error', 'Échec de la création de la borne: ' . $errorMessage);
        } catch (\Exception $e) {
            Log::error('SteveChargingPointController: Exception during charge point creation', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withInput()
                ->with('error', 'Erreur lors de la création: ' . $e->getMessage());
        }
    }

    /**
     * 🔹 Formulaire d'édition
     */
    public function edit($id)
    {
        try {
            $chargePoint = $this->steve->getChargePoint($id);

            if (!$chargePoint) {
                return redirect()->route('steve-charging-points.index')
                    ->with('error', 'Borne introuvable sur l\'API Steve.');
            }

            return view('steve-charging-points.edit', compact('chargePoint'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la borne pour édition: ' . $e->getMessage());
            return redirect()->route('steve-charging-points.index')
                ->with('error', 'Erreur lors du chargement: ' . $e->getMessage());
        }
    }

    /**
     * 🔹 Mise à jour d'une borne sur l'API Steve
     */
    public function update(SteveChargingPointUpdateRequest $request, $id)
    {
        try {
            // Vérifier d'abord si la borne existe
            $chargePoint = $this->steve->getChargePoint($id);
            if (!$chargePoint) {
                return redirect()->route('steve-charging-points.index')
                    ->with('error', 'Borne introuvable sur l\'API Steve.');
            }
            
            $data = $request->only('description', 'note', 'address', 'adminAddress', 
                                   'locationLatitude', 'locationLongitude', 'registrationStatus');
            
            Log::info('SteveChargingPointController: Updating charge point on Steve API', [
                'chargePointPk' => $id,
                'description' => $data['description'] ?? 'N/A'
            ]);
            
            $result = $this->steve->updateChargePoint($id, $data);

            // Le service retourne maintenant toujours un tableau avec 'success'
            if ($result['success'] === true) {
                Log::info('SteveChargingPointController: Charge point updated successfully', [
                    'chargePointPk' => $id
                ]);
                
                return redirect()->route('steve-charging-points.index')
                    ->with('success', 'Borne mise à jour avec succès sur l\'API Steve.');
            }

            // Gérer les erreurs
            $errorMessage = $result['error'] ?? 'Erreur inconnue lors de la mise à jour';
            
            Log::warning('SteveChargingPointController: Failed to update charge point', [
                'chargePointPk' => $id,
                'error' => $errorMessage,
                'status' => $result['status'] ?? 'N/A'
            ]);
            
            return back()->withInput()
                ->with('error', 'Échec de la mise à jour de la borne: ' . $errorMessage);
        } catch (\Exception $e) {
            Log::error('SteveChargingPointController: Exception during charge point update', [
                'chargePointPk' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withInput()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * 🔹 Suppression d'une borne de l'API Steve
     * ATTENTION: Cette opération est destructive et supprime toutes les données associées
     */
    public function destroy($id)
    {
        try {
            // Vérifier d'abord si la borne existe
            $chargePoint = $this->steve->getChargePoint($id);
            if (!$chargePoint) {
                return redirect()->route('steve-charging-points.index')
                    ->with('error', 'Borne introuvable sur l\'API Steve.');
            }
            
            Log::info('SteveChargingPointController: Deleting charge point from Steve API', [
                'chargePointPk' => $id
            ]);
            
            $result = $this->steve->deleteChargePoint($id);

            // Le service retourne maintenant toujours un tableau avec 'success'
            if ($result['success'] === true) {
                Log::info('SteveChargingPointController: Charge point deleted successfully', [
                    'chargePointPk' => $id
                ]);
                
                return redirect()->route('steve-charging-points.index')
                    ->with('success', 'Borne supprimée avec succès de l\'API Steve (toutes les données associées ont été supprimées).');
            }

            // Gérer les erreurs
            $errorMessage = $result['error'] ?? 'Erreur inconnue lors de la suppression';
            
            Log::warning('SteveChargingPointController: Failed to delete charge point', [
                'chargePointPk' => $id,
                'error' => $errorMessage,
                'status' => $result['status'] ?? 'N/A'
            ]);
            
            return back()->with('error', 'Échec de la suppression de la borne: ' . $errorMessage);
        } catch (\Exception $e) {
            Log::error('SteveChargingPointController: Exception during charge point deletion', [
                'chargePointPk' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * 🔹 Remote Start - Démarrer une session de charge à distance
     */
    public function remoteStart(Request $request, $chargeBoxId)
    {
        try {
            // Validation des paramètres
            // connectorId: 0 signifie que la borne choisit automatiquement le connecteur
            $validated = $request->validate([
                'connectorId' => 'required|integer|min:0',
                'ocppTag' => 'required|string|max:20'
            ]);

            Log::info('SteveChargingPointController: Remote start requested', [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $validated['connectorId'],
                'ocppTag' => $validated['ocppTag']
            ]);

            // Appeler le service
            $result = $this->steve->remoteStart(
                $chargeBoxId,
                $validated['connectorId'],
                $validated['ocppTag']
            );

            // Log du résultat complet pour debug
            Log::info('SteveChargingPointController: Remote start result', [
                'chargeBoxId' => $chargeBoxId,
                'result' => $result
            ]);

            if ($result['success']) {
                $message = $result['message'] ?? 'Commande Remote Start envoyée avec succès. Statut: ' . ($result['data']['status'] ?? 'ACCEPTED');
                return back()->with('success', $message);
            }

            // Utiliser le message détaillé si disponible, sinon l'erreur courte
            $errorMessage = $result['message'] ?? $result['error'] ?? 'Erreur inconnue';
            
            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            Log::error('SteveChargingPointController: Exception during remote start', [
                'chargeBoxId' => $chargeBoxId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur lors du Remote Start: ' . $e->getMessage());
        }
    }

    /**
     * 🔹 Remote Stop - Arrêter une session de charge à distance
     */
    public function remoteStop($chargeBoxId)
    {
        try {
            Log::info('SteveChargingPointController: Remote stop requested', [
                'chargeBoxId' => $chargeBoxId
            ]);

            // Appeler le service
            $result = $this->steve->remoteStop($chargeBoxId);

            if ($result['success']) {
                return back()->with('success', 'Commande Remote Stop envoyée avec succès. Statut: ' . ($result['data']['status'] ?? 'ACCEPTED'));
            }

            return back()->with('error', 'Échec Remote Stop: ' . ($result['error'] ?? 'Erreur inconnue'));
        } catch (\Exception $e) {
            Log::error('SteveChargingPointController: Exception during remote stop', [
                'chargeBoxId' => $chargeBoxId,
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Erreur lors du Remote Stop: ' . $e->getMessage());
        }
    }
}

