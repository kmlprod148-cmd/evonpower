<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\RemoteAction;
use App\Services\SteveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChargePointRemoteActionController extends Controller
{
    protected $steve;

    public function __construct(SteveService $steve)
    {
        $this->steve = $steve;
    }

    /**
     * Start a charging session remotely
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function start(Request $request, ChargingPoint $chargingPoint)
    {
        return $this->executeAction($request, $chargingPoint, 'start', [
            'connectorId' => $request->input('connectorId', 1),
            'idTag' => $request->input('idTag', 'default')
        ]);
    }

    /**
     * Stop a charging session remotely
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function stop(Request $request, ChargingPoint $chargingPoint)
    {
        return $this->executeAction($request, $chargingPoint, 'stop', [
            'connectorId' => $request->input('connectorId', 1),
            'transactionId' => $request->input('transactionId')
        ]);
    }

    /**
     * Unlock a connector remotely
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function unlock(Request $request, ChargingPoint $chargingPoint)
    {
        return $this->executeAction($request, $chargingPoint, 'unlock', [
            'connectorId' => $request->input('connectorId', 1)
        ]);
    }

    /**
     * Reset a charging point remotely
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request, ChargingPoint $chargingPoint)
    {
        return $this->executeAction($request, $chargingPoint, 'reset', [
            'type' => $request->input('type', 'Soft') // 'Soft' or 'Hard'
        ]);
    }

    /**
     * Execute a remote action and log it
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @param string $action
     * @param array $payload
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function executeAction(Request $request, ChargingPoint $chargingPoint, string $action, array $payload = [])
    {
        try {
            // Vérifier les permissions
            $user = auth()->user();
            if (!$user->can('update', $chargingPoint) && !$user->hasRole(['admin', 'super_admin'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.'
                    ], 403);
                }
                return redirect()->back()
                    ->with('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
            }

            // Vérifier que le point de charge a un ID Steve
            if (empty($chargingPoint->steve_charging_point_id)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce point de charge n\'a pas d\'identifiant Steve configuré.'
                    ], 400);
                }
                return redirect()->back()
                    ->with('error', 'Ce point de charge n\'a pas d\'identifiant Steve configuré.');
            }

            // Envoyer la commande à Steve
            $result = $this->steve->sendCommand(
                $chargingPoint->steve_charging_point_id,
                $action,
                $payload
            );

            // Déterminer le statut de l'action
            $status = 'Failed';
            if ($result) {
                // Vérifier le statut dans la réponse
                $steveStatus = $result['status'] ?? $result['steve_status'] ?? null;
                if (in_array(strtolower($steveStatus ?? ''), ['accepted', 'ok', 'success'])) {
                    $status = 'Accepted';
                } elseif (in_array(strtolower($steveStatus ?? ''), ['rejected', 'error', 'failed'])) {
                    $status = 'Rejected';
                } else {
                    $status = $result ? 'Accepted' : 'Failed';
                }
            }

            // Logger l'action dans la base de données
            try {
                RemoteAction::create([
                    'charging_point_id' => $chargingPoint->id,
                    'action' => $action,
                    'status' => $status,
                    'response_json' => $result,
                    'created_by' => $user->id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to log remote action', [
                    'error' => $e->getMessage(),
                    'charging_point_id' => $chargingPoint->id,
                    'action' => $action
                ]);
                // Ne pas échouer si le logging échoue
            }

            // Préparer les messages
            $actionNames = [
                'start' => 'Démarrer la recharge',
                'stop' => 'Arrêter la recharge',
                'unlock' => 'Débloquer le connecteur',
                'reset' => 'Réinitialiser la borne'
            ];

            $actionName = $actionNames[$action] ?? ucfirst($action);

            if ($result && $status === 'Accepted') {
                $message = "✅ Commande '{$actionName}' envoyée avec succès.";
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'data' => [
                            'action' => $action,
                            'status' => $status,
                            'response' => $result
                        ]
                    ]);
                }

                return redirect()->back()->with('success', $message);
            } else {
                $message = "❌ Échec de la commande '{$actionName}'.";
                $errorMessage = $result['error'] ?? $result['message'] ?? 'Erreur inconnue';
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'error' => $errorMessage,
                        'data' => [
                            'action' => $action,
                            'status' => $status,
                            'response' => $result
                        ]
                    ], 500);
                }

                return redirect()->back()
                    ->with('error', $message . ' ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::error("Error executing remote action {$action}", [
                'charging_point_id' => $chargingPoint->id,
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'exécution de l\'action: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de l\'exécution de l\'action: ' . $e->getMessage());
        }
    }
}
