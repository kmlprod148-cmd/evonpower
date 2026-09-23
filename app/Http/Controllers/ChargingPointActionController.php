<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChargingPoint;
use App\Models\RemoteAction;
use App\Models\Transaction;
use App\Services\SteveService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChargingPointActionController extends Controller
{
    protected SteveService $steveService;

    public function __construct(SteveService $steveService)
    {
        $this->middleware('auth');
        $this->steveService = $steveService;
    }

    /**
     * Déclencher une recharge à distance
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'connector_id' => 'required|integer|min:1',
            'id_tag' => 'required|string|max:20',
        ]);

        try {
            $connectorId = (int) $request->input('connector_id');
            $idTag = $request->input('id_tag');

            Log::info('ChargingPointActionController: Starting charging session', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'connector_id' => $connectorId,
                'id_tag' => $idTag
            ]);

            // Appel à l'API Steve
            $result = $this->steveService->startCharging($chargingPoint, $connectorId, $idTag);

            // Déterminer le statut pour l'enregistrement
            $status = 'Failed';
            if ($result['ok'] === true) {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Accepted');
            } else {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Rejected');
            }

            // Enregistrer l'action distante
            $remoteAction = RemoteAction::create([
                'charging_point_id' => $chargingPoint->id,
                'action' => 'start_charging',
                'status' => $status,
                'response_json' => $result['body'] ?? $result,
                'created_by' => Auth::id(),
            ]);

            Log::info('ChargingPointActionController: Remote action saved', [
                'remote_action_id' => $remoteAction->id,
                'status' => $status
            ]);

            // Préparer la réponse
            if ($result['ok'] === true && ($status === 'Accepted' || $status === 'accepted')) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Recharge déclenchée',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'connector_id' => $connectorId,
                        'id_tag' => $idTag,
                        'status' => $status,
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Échec du déclenchement de la recharge',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => $status,
                        'error' => $result['error'] ?? 'Action rejetée',
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointActionController: Failed to start charging', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer l'erreur comme action distante
            try {
                RemoteAction::create([
                    'charging_point_id' => $chargingPoint->id,
                    'action' => 'start_charging',
                    'status' => 'Failed',
                    'response_json' => [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ],
                    'created_by' => Auth::id(),
                ]);
            } catch (\Exception $dbException) {
                Log::error('ChargingPointActionController: Failed to save remote action', [
                    'error' => $dbException->getMessage()
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors du déclenchement de la recharge: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Arrêter une recharge à distance
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse
     */
    public function stop(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        try {
            $transactionId = $request->input('transaction_id');

            Log::info('ChargingPointActionController: Stopping charging session', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'transaction_id' => $transactionId
            ]);

            // Find the transaction by transaction_id or session_id
            $transaction = Transaction::where('charging_point_id', $chargingPoint->id)
                ->where(function($query) use ($transactionId) {
                    $query->where('transaction_id', $transactionId)
                          ->orWhere('session_id', $transactionId)
                          ->orWhere('id', $transactionId);
                })
                ->first();

            if (!$transaction) {
                Log::warning('ChargingPointActionController: Transaction not found', [
                    'charging_point_id' => $chargingPoint->id,
                    'transaction_id' => $transactionId
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Transaction introuvable',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'transaction_id' => $transactionId,
                    ]
                ], 404);
            }

            // Appel à l'API Steve
            $result = $this->steveService->stopCharging($transaction);

            // Déterminer le statut pour l'enregistrement
            $status = 'Failed';
            if ($result['ok'] === true) {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Accepted');
            } else {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Rejected');
            }

            // Enregistrer l'action distante
            $remoteAction = RemoteAction::create([
                'charging_point_id' => $chargingPoint->id,
                'action' => 'stop_charging',
                'status' => $status,
                'response_json' => $result['body'] ?? $result,
                'created_by' => Auth::id(),
            ]);

            Log::info('ChargingPointActionController: Remote stop action saved', [
                'remote_action_id' => $remoteAction->id,
                'status' => $status
            ]);

            // Préparer la réponse
            if ($result['ok'] === true && ($status === 'Accepted' || $status === 'accepted')) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Recharge arrêtée',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'transaction_id' => $transactionId,
                        'status' => $status,
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ]);
            } else {
                // Si rejeté, retourner HTTP 422 comme demandé
                $httpStatus = ($status === 'Rejected' || $status === 'rejected') ? 422 : 400;
                
                return response()->json([
                    'ok' => false,
                    'message' => 'Échec de l\'arrêt de la recharge',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => $status,
                        'error' => $result['error'] ?? 'Action rejetée',
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ], $httpStatus);
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointActionController: Failed to stop charging', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer l'erreur comme action distante
            try {
                RemoteAction::create([
                    'charging_point_id' => $chargingPoint->id,
                    'action' => 'stop_charging',
                    'status' => 'Failed',
                    'response_json' => [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ],
                    'created_by' => Auth::id(),
                ]);
            } catch (\Exception $dbException) {
                Log::error('ChargingPointActionController: Failed to save remote action', [
                    'error' => $dbException->getMessage()
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors de l\'arrêt de la recharge: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Réinitialiser un point de charge à distance
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'type' => 'nullable|string|in:Hard,Soft',
        ]);

        try {
            $type = $request->input('type', 'Hard');

            Log::info('ChargingPointActionController: Resetting charging point', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'type' => $type
            ]);

            // Appel à l'API Steve
            $result = $this->steveService->resetChargingPoint($chargingPoint, $type);

            // Déterminer le statut pour l'enregistrement
            $status = 'Failed';
            if ($result['ok'] === true) {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Accepted');
            } else {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Rejected');
            }

            // Enregistrer l'action distante
            $remoteAction = RemoteAction::create([
                'charging_point_id' => $chargingPoint->id,
                'action' => 'reset',
                'status' => $status,
                'response_json' => $result['body'] ?? $result,
                'created_by' => Auth::id(),
            ]);

            Log::info('ChargingPointActionController: Remote reset action saved', [
                'remote_action_id' => $remoteAction->id,
                'status' => $status
            ]);

            // Préparer la réponse
            if ($result['ok'] === true && ($status === 'Accepted' || $status === 'accepted')) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Point de charge réinitialisé',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'type' => $type,
                        'status' => $status,
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Échec de la réinitialisation',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => $status,
                        'error' => $result['error'] ?? 'Action rejetée',
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointActionController: Failed to reset charging point', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer l'erreur comme action distante
            try {
                RemoteAction::create([
                    'charging_point_id' => $chargingPoint->id,
                    'action' => 'reset',
                    'status' => 'Failed',
                    'response_json' => [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ],
                    'created_by' => Auth::id(),
                ]);
            } catch (\Exception $dbException) {
                Log::error('ChargingPointActionController: Failed to save remote action', [
                    'error' => $dbException->getMessage()
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors de la réinitialisation: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Mettre à jour la configuration d'un point de charge
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateConfig(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string|max:255',
        ]);

        try {
            $key = $request->input('key');
            $value = $request->input('value');

            Log::info('ChargingPointActionController: Updating charging point configuration', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'key' => $key,
                'value' => $value
            ]);

            // Appel à l'API Steve
            $result = $this->steveService->updateChargingPointConfig($chargingPoint, $key, $value);

            // Déterminer le statut pour l'enregistrement
            $status = 'Failed';
            if ($result['ok'] === true) {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Accepted');
            } else {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Rejected');
            }

            // Enregistrer l'action distante
            $remoteAction = RemoteAction::create([
                'charging_point_id' => $chargingPoint->id,
                'action' => 'update_config',
                'status' => $status,
                'response_json' => array_merge($result['body'] ?? $result, [
                    'config_key' => $key,
                    'config_value' => $value,
                ]),
                'created_by' => Auth::id(),
            ]);

            Log::info('ChargingPointActionController: Remote config update action saved', [
                'remote_action_id' => $remoteAction->id,
                'status' => $status
            ]);

            // Préparer la réponse
            if ($result['ok'] === true && ($status === 'Accepted' || $status === 'accepted')) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Configuration mise à jour',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'key' => $key,
                        'value' => $value,
                        'status' => $status,
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Échec de la mise à jour de configuration',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => $status,
                        'error' => $result['error'] ?? 'Action rejetée',
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointActionController: Failed to update config', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer l'erreur comme action distante
            try {
                RemoteAction::create([
                    'charging_point_id' => $chargingPoint->id,
                    'action' => 'update_config',
                    'status' => 'Failed',
                    'response_json' => [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ],
                    'created_by' => Auth::id(),
                ]);
            } catch (\Exception $dbException) {
                Log::error('ChargingPointActionController: Failed to save remote action', [
                    'error' => $dbException->getMessage()
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors de la mise à jour de configuration: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Récupérer les diagnostics d'un point de charge
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse
     */
    public function diagnostic(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            $location = $request->input('location', config('app.url') . '/api/diagnostics/upload');

            Log::info('ChargingPointActionController: Requesting diagnostics', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'location' => $location
            ]);

            // Appel à l'API Steve
            $result = $this->steveService->getDiagnostics($chargingPoint, $location);

            // Déterminer le statut pour l'enregistrement
            $status = 'Failed';
            if ($result['ok'] === true) {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Accepted');
            } else {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Rejected');
            }

            // Enregistrer l'action distante
            $remoteAction = RemoteAction::create([
                'charging_point_id' => $chargingPoint->id,
                'action' => 'get_diagnostics',
                'status' => $status,
                'response_json' => $result['body'] ?? $result,
                'created_by' => Auth::id(),
            ]);

            Log::info('ChargingPointActionController: Remote diagnostic action saved', [
                'remote_action_id' => $remoteAction->id,
                'status' => $status
            ]);

            // Préparer la réponse
            if ($result['ok'] === true && ($status === 'Accepted' || $status === 'accepted')) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Demande de diagnostics envoyée',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'status' => $status,
                        'remote_action_id' => $remoteAction->id,
                        'upload_location' => $location,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Échec de la demande de diagnostics',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => $status,
                        'error' => $result['error'] ?? 'Action rejetée',
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointActionController: Failed to get diagnostics', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer l'erreur comme action distante
            try {
                RemoteAction::create([
                    'charging_point_id' => $chargingPoint->id,
                    'action' => 'get_diagnostics',
                    'status' => 'Failed',
                    'response_json' => [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ],
                    'created_by' => Auth::id(),
                ]);
            } catch (\Exception $dbException) {
                Log::error('ChargingPointActionController: Failed to save remote action', [
                    'error' => $dbException->getMessage()
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors de la demande de diagnostics: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Débloquer un connecteur d'un point de charge
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlock(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'connector_id' => 'required|integer|min:1',
        ]);

        try {
            $connectorId = (int) $request->input('connector_id');

            Log::info('ChargingPointActionController: Unlocking connector', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'connector_id' => $connectorId
            ]);

            // Appel à l'API Steve
            $result = $this->steveService->unlockConnector($chargingPoint, $connectorId);

            // Déterminer le statut pour l'enregistrement
            $status = 'Failed';
            if ($result['ok'] === true) {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Unlocked');
            } else {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'NotSupported');
            }

            // Enregistrer l'action distante
            $remoteAction = RemoteAction::create([
                'charging_point_id' => $chargingPoint->id,
                'action' => 'unlock_connector',
                'status' => $status,
                'response_json' => $result['body'] ?? $result,
                'created_by' => Auth::id(),
            ]);

            Log::info('ChargingPointActionController: Remote unlock action saved', [
                'remote_action_id' => $remoteAction->id,
                'status' => $status
            ]);

            // Préparer la réponse
            if ($result['ok'] === true && (in_array($status, ['Unlocked', 'unlocked', 'Accepted', 'accepted']))) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Connecteur débloqué',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'connector_id' => $connectorId,
                        'status' => $status,
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Échec du déblocage du connecteur',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => $status,
                        'error' => $result['error'] ?? 'Action rejetée ou non supportée',
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointActionController: Failed to unlock connector', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer l'erreur comme action distante
            try {
                RemoteAction::create([
                    'charging_point_id' => $chargingPoint->id,
                    'action' => 'unlock_connector',
                    'status' => 'Failed',
                    'response_json' => [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ],
                    'created_by' => Auth::id(),
                ]);
            } catch (\Exception $dbException) {
                Log::error('ChargingPointActionController: Failed to save remote action', [
                    'error' => $dbException->getMessage()
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors du déblocage du connecteur: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Récupérer les logs d'un point de charge
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse
     */
    public function logs(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'log_type' => 'nullable|string|max:100',
        ]);

        try {
            $logType = $request->input('log_type', 'DiagnosticsLog');

            Log::info('ChargingPointActionController: Requesting logs', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'log_type' => $logType
            ]);

            // Appel à l'API Steve
            $result = $this->steveService->getLogs($chargingPoint, $logType);

            // Déterminer le statut pour l'enregistrement
            $status = 'Failed';
            if ($result['ok'] === true) {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Accepted');
            } else {
                $status = $result['steve_status'] ?? ($result['body']['status'] ?? 'Rejected');
            }

            // Enregistrer l'action distante
            $remoteAction = RemoteAction::create([
                'charging_point_id' => $chargingPoint->id,
                'action' => 'get_logs',
                'status' => $status,
                'response_json' => array_merge($result['body'] ?? $result, [
                    'log_type' => $logType,
                ]),
                'created_by' => Auth::id(),
            ]);

            Log::info('ChargingPointActionController: Remote logs action saved', [
                'remote_action_id' => $remoteAction->id,
                'status' => $status
            ]);

            // Extraire le lien de téléchargement si disponible
            $downloadLink = null;
            if (isset($result['body']['file_name']) || isset($result['body']['file'])) {
                $downloadLink = $result['body']['file'] ?? $result['body']['file_name'] ?? null;
            }

            // Préparer la réponse
            if ($result['ok'] === true && ($status === 'Accepted' || $status === 'accepted')) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Demande de logs envoyée',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'charging_point_name' => $chargingPoint->name,
                        'log_type' => $logType,
                        'status' => $status,
                        'remote_action_id' => $remoteAction->id,
                        'download_link' => $downloadLink,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ]);
            } else {
                return response()->json([
                    'ok' => false,
                    'message' => 'Échec de la demande de logs',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => $status,
                        'error' => $result['error'] ?? 'Action rejetée',
                        'remote_action_id' => $remoteAction->id,
                        'steve_response' => $result['body'] ?? null,
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointActionController: Failed to get logs', [
                'user_id' => Auth::id(),
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer l'erreur comme action distante
            try {
                RemoteAction::create([
                    'charging_point_id' => $chargingPoint->id,
                    'action' => 'get_logs',
                    'status' => 'Failed',
                    'response_json' => [
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ],
                    'created_by' => Auth::id(),
                ]);
            } catch (\Exception $dbException) {
                Log::error('ChargingPointActionController: Failed to save remote action', [
                    'error' => $dbException->getMessage()
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Erreur lors de la demande de logs: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
