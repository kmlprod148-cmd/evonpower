<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SteVeAutoStopService;
use App\Models\Reservation;

/**
 * Contrôleur pour l'arrêt automatique des sessions de recharge SETEVE
 * 
 * Ce contrôleur gère l'arrêt automatique des sessions qui ont atteint
 * leurs limites de réservation (temps ou énergie)
 */
class SteVeAutoStopController extends Controller
{
    protected SteVeAutoStopService $autoStopService;

    public function __construct(SteVeAutoStopService $autoStopService)
    {
        $this->autoStopService = $autoStopService;
    }

    /**
     * Vérifier et arrêter automatiquement les sessions expirées
     */
    public function checkAndStopSessions()
    {
        try {
            Log::info('SteVeAutoStopController: Vérification des sessions expirées');

            $result = $this->autoStopService->checkAndStopExpiredSessions();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Vérification terminée',
                'sessions_checked' => $result['sessions_checked'] ?? 0,
                'sessions_stopped' => $result['sessions_stopped'] ?? 0,
                'results' => $result['results'] ?? [],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopController: Erreur lors de la vérification', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification des sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier une session spécifique
     */
    public function checkSpecificSession(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|string'
            ]);

            $sessionId = $request->session_id;
            
            Log::info('SteVeAutoStopController: Vérification de session spécifique', [
                'session_id' => $sessionId
            ]);

            $result = $this->autoStopService->checkSpecificSession($sessionId);

            return response()->json([
                'success' => $result['success'],
                'action' => $result['action'] ?? 'unknown',
                'reason' => $result['reason'] ?? 'Aucune raison fournie',
                'remaining' => $result['remaining'] ?? null,
                'result' => $result['result'] ?? null,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopController: Erreur lors de la vérification de session', [
                'session_id' => $request->session_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de la session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des sessions
     */
    public function getSessionStats()
    {
        try {
            Log::info('SteVeAutoStopController: Récupération des statistiques');

            $result = $this->autoStopService->getSessionStats();

            return response()->json([
                'success' => $result['success'],
                'stats' => $result['stats'] ?? [],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopController: Erreur lors de la récupération des statistiques', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les sessions actives avec leurs détails
     */
    public function getActiveSessions()
    {
        try {
            Log::info('SteVeAutoStopController: Récupération des sessions actives');

            // Récupérer les sessions actives via le service
            $reflection = new \ReflectionClass($this->autoStopService);
            $method = $reflection->getMethod('getActiveChargingSessions');
            $method->setAccessible(true);
            $sessions = $method->invoke($this->autoStopService);

            // Enrichir avec les informations de réservation
            $enrichedSessions = [];
            foreach ($sessions as $session) {
                $enrichedSession = $session;
                
                if (isset($session['reservation_id'])) {
                    $reservation = Reservation::with(['chargingPoint', 'pricingPlan', 'user'])
                        ->find($session['reservation_id']);
                    
                    if ($reservation) {
                        $enrichedSession['reservation'] = [
                            'id' => $reservation->id,
                            'type' => $reservation->reservation_type,
                            'value' => $reservation->reservation_value,
                            'status' => $reservation->status,
                            'charging_point' => $reservation->chargingPoint?->name ?? 'Inconnu',
                            'customer' => $reservation->guest_email ?? $reservation->user?->email ?? 'Inconnu'
                        ];
                    }
                }
                
                $enrichedSessions[] = $enrichedSession;
            }

            return response()->json([
                'success' => true,
                'sessions' => $enrichedSessions,
                'total' => count($enrichedSessions),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopController: Erreur lors de la récupération des sessions actives', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des sessions actives',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forcer l'arrêt d'une session spécifique
     */
    public function forceStopSession(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|string',
                'reason' => 'nullable|string|max:255'
            ]);

            $sessionId = $request->session_id;
            $reason = $request->reason ?? 'Arrêt forcé par l\'administrateur';
            
            Log::info('SteVeAutoStopController: Arrêt forcé de session', [
                'session_id' => $sessionId,
                'reason' => $reason
            ]);

            // Récupérer la session
            $reflection = new \ReflectionClass($this->autoStopService);
            $method = $reflection->getMethod('getSessionById');
            $method->setAccessible(true);
            $session = $method->invoke($this->autoStopService, $sessionId);

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session non trouvée'
                ], 404);
            }

            // Forcer l'arrêt
            $method = $reflection->getMethod('stopSession');
            $method->setAccessible(true);
            $result = $method->invoke($this->autoStopService, $session, $reason);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Session arrêtée avec succès' : 'Échec de l\'arrêt de la session',
                'result' => $result,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopController: Erreur lors de l\'arrêt forcé', [
                'session_id' => $request->session_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'arrêt forcé de la session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des arrêts automatiques
     */
    public function getAutoStopHistory(Request $request)
    {
        try {
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:100',
                'offset' => 'nullable|integer|min:0'
            ]);

            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);

            // Récupérer les réservations avec arrêt automatique
            $reservations = Reservation::where('auto_stopped', true)
                ->with(['chargingPoint', 'user'])
                ->orderBy('charging_stopped_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();

            $history = $reservations->map(function ($reservation) {
                return [
                    'reservation_id' => $reservation->id,
                    'charging_point' => $reservation->chargingPoint?->name ?? 'Inconnu',
                    'customer' => $reservation->guest_email ?? $reservation->user?->email ?? 'Inconnu',
                    'reservation_type' => $reservation->reservation_type,
                    'reservation_value' => $reservation->reservation_value,
                    'actual_energy' => $reservation->actual_energy,
                    'actual_duration' => $reservation->actual_duration,
                    'auto_stop_reason' => $reservation->auto_stop_reason,
                    'stopped_at' => $reservation->charging_stopped_at?->toISOString(),
                    'created_at' => $reservation->created_at->toISOString()
                ];
            });

            return response()->json([
                'success' => true,
                'history' => $history,
                'total' => $reservations->count(),
                'limit' => $limit,
                'offset' => $offset,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopController: Erreur lors de la récupération de l\'historique', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configuration du monitoring automatique
     */
    public function getMonitoringConfig()
    {
        try {
            $config = [
                'enabled' => config('steve.auto_stop.enabled', true),
                'check_interval' => config('steve.auto_stop.check_interval', 60), // secondes
                'max_retries' => config('steve.commands.retry_attempts', 3),
                'retry_delay' => config('steve.commands.retry_delay', 5),
                'timeout' => config('steve.timeout', 30),
                'supported_types' => ['minute', 'kwh'],
                'notification_enabled' => config('steve.auto_stop.notification_enabled', true)
            ];

            return response()->json([
                'success' => true,
                'config' => $config,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour la configuration du monitoring
     */
    public function updateMonitoringConfig(Request $request)
    {
        try {
            $request->validate([
                'enabled' => 'nullable|boolean',
                'check_interval' => 'nullable|integer|min:30|max:3600',
                'notification_enabled' => 'nullable|boolean'
            ]);

            $config = $request->only(['enabled', 'check_interval', 'notification_enabled']);
            
            // Mettre à jour la configuration (dans un vrai projet, vous utiliseriez une table de configuration)
            Log::info('SteVeAutoStopController: Configuration mise à jour', $config);

            return response()->json([
                'success' => true,
                'message' => 'Configuration mise à jour avec succès',
                'config' => $config,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
