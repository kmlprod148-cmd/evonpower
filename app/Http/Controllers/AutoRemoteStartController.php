<?php

namespace App\Http\Controllers;

use App\Services\AutoRemoteStartService;
use App\Models\Reservation;
use App\Models\AutoRemoteStartLog;
use App\Jobs\AutoStartTransactionJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Contrôleur API pour le système de démarrage automatique OCPP
 * 
 * Fournit des endpoints pour:
 * - Déclencher manuellement des démarrages automatiques
 * - Consulter les logs et statistiques
 * - Gérer la configuration
 * - Monitoring et health checks
 */
class AutoRemoteStartController extends Controller
{
    protected AutoRemoteStartService $autoStartService;

    public function __construct(AutoRemoteStartService $autoStartService)
    {
        $this->middleware('auth:sanctum');
        $this->autoStartService = $autoStartService;
    }

    /**
     * Traiter toutes les réservations éligibles
     * 
     * GET|POST /api/auto-remote-start/process
     */
    public function processAll(Request $request): JsonResponse
    {
        try {
            // Vérifier les permissions (admin ou operator)
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'super_admin', 'operator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée'
                ], 403);
            }

            Log::info('AutoRemoteStart: Traitement manuel déclenché', [
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);

            $result = $this->autoStartService->processAllEligibleReservations();

            return response()->json($result);

        } catch (Exception $e) {
            Log::error('AutoRemoteStart: Erreur traitement manuel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traiter une réservation spécifique
     * 
     * POST /api/auto-remote-start/reservation/{id}
     */
    public function processReservation(Request $request, int $reservationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Charger la réservation
            $reservation = Reservation::with(['chargingPoint', 'connector', 'user', 'ocppTag'])
                ->findOrFail($reservationId);

            // Vérifier les permissions
            if (!$user->hasRole(['admin', 'super_admin'])) {
                // Operators peuvent uniquement traiter leurs propres réservations
                if (!$user->hasRole('operator') || $reservation->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permission refusée'
                    ], 403);
                }
            }

            $force = $request->boolean('force', false);

            Log::info('AutoRemoteStart: Traitement manuel réservation', [
                'user_id' => $user->id,
                'reservation_id' => $reservationId,
                'force' => $force
            ]);

            if ($force) {
                $result = $this->autoStartService->forceStartReservation($reservationId);
            } else {
                $result = $this->autoStartService->processReservation($reservation);
            }

            return response()->json($result);

        } catch (Exception $e) {
            Log::error('AutoRemoteStart: Erreur traitement réservation', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre une réservation en queue pour traitement
     * 
     * POST /api/auto-remote-start/queue/{id}
     */
    public function queueReservation(Request $request, int $reservationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Charger la réservation
            $reservation = Reservation::findOrFail($reservationId);

            // Vérifier les permissions
            if (!$user->hasRole(['admin', 'super_admin', 'operator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée'
                ], 403);
            }

            $force = $request->boolean('force', false);

            // Dispatcher le job
            AutoStartTransactionJob::dispatch($reservationId, $force);

            Log::info('AutoRemoteStart: Réservation mise en queue', [
                'user_id' => $user->id,
                'reservation_id' => $reservationId,
                'force' => $force
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réservation mise en queue pour traitement',
                'reservation_id' => $reservationId,
                'force' => $force
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise en queue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier l'éligibilité d'une réservation
     * 
     * GET /api/auto-remote-start/check-eligibility/{id}
     */
    public function checkEligibility(int $reservationId): JsonResponse
    {
        try {
            $reservation = Reservation::with(['chargingPoint', 'connector', 'user', 'ocppTag'])
                ->findOrFail($reservationId);

            $eligibility = $this->autoStartService->checkReservationEligibility($reservation);

            return response()->json([
                'success' => true,
                'reservation_id' => $reservationId,
                'eligibility' => $eligibility
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques
     * 
     * GET /api/auto-remote-start/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'user_id', 'charging_point_id']);

            // Par défaut: stats des dernières 24h
            if (!isset($filters['date_from'])) {
                $filters['date_from'] = now()->subDay();
            }

            $stats = $this->autoStartService->getAutoStartStats($filters);

            // Stats additionnelles des derniers 7 jours et du mois
            $stats['last_7_days'] = $this->autoStartService->getAutoStartStats([
                'date_from' => now()->subDays(7)
            ]);

            $stats['this_month'] = $this->autoStartService->getAutoStartStats([
                'date_from' => now()->startOfMonth()
            ]);

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les logs récents
     * 
     * GET /api/auto-remote-start/logs
     */
    public function logs(Request $request): JsonResponse
    {
        try {
            $query = AutoRemoteStartLog::with(['reservation', 'chargingPoint', 'user'])
                ->orderBy('processed_at', 'desc');

            // Filtres
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('reservation_id')) {
                $query->where('reservation_id', $request->input('reservation_id'));
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            if ($request->has('charging_point_id')) {
                $query->where('charging_point_id', $request->input('charging_point_id'));
            }

            if ($request->has('date_from')) {
                $query->where('processed_at', '>=', $request->input('date_from'));
            }

            if ($request->has('date_to')) {
                $query->where('processed_at', '<=', $request->input('date_to'));
            }

            // Pagination
            $perPage = $request->input('per_page', 20);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'logs' => $logs
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir un log spécifique
     * 
     * GET /api/auto-remote-start/logs/{id}
     */
    public function showLog(int $logId): JsonResponse
    {
        try {
            $log = AutoRemoteStartLog::with(['reservation', 'chargingPoint', 'user', 'connector'])
                ->findOrFail($logId);

            return response()->json([
                'success' => true,
                'log' => $log
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Log introuvable',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Health check du système
     * 
     * GET /api/auto-remote-start/health
     */
    public function health(): JsonResponse
    {
        try {
            $health = $this->autoStartService->healthCheck();

            $statusCode = $health['healthy'] ? 200 : 503;

            return response()->json($health, $statusCode);

        } catch (Exception $e) {
            return response()->json([
                'healthy' => false,
                'status' => 'ERROR',
                'issues' => ['Exception lors du health check: ' . $e->getMessage()],
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Obtenir la configuration actuelle
     * 
     * GET /api/auto-remote-start/config
     */
    public function config(): JsonResponse
    {
        try {
            // Seuls les admins peuvent voir la configuration
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée'
                ], 403);
            }

            $config = config('auto-remote-start');

            return response()->json([
                'success' => true,
                'config' => $config
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les réservations éligibles actuelles
     * 
     * GET /api/auto-remote-start/eligible-reservations
     */
    public function eligibleReservations(Request $request): JsonResponse
    {
        try {
            // Récupérer les réservations via reflection (méthode protégée)
            $reflection = new \ReflectionClass($this->autoStartService);
            $method = $reflection->getMethod('getEligibleReservations');
            $method->setAccessible(true);
            
            $reservations = $method->invoke($this->autoStartService);

            // Ajouter les informations d'éligibilité
            $reservationsWithEligibility = $reservations->map(function ($reservation) {
                $eligibility = $this->autoStartService->checkReservationEligibility($reservation);
                
                return [
                    'id' => $reservation->id,
                    'user' => [
                        'id' => $reservation->user_id,
                        'name' => $reservation->user->name ?? 'N/A'
                    ],
                    'charging_point' => [
                        'id' => $reservation->charging_point_id,
                        'name' => $reservation->chargingPoint->name ?? 'N/A'
                    ],
                    'start_time' => $reservation->start_time->toISOString(),
                    'status' => $reservation->status,
                    'payment_status' => $reservation->payment_status,
                    'eligibility' => $eligibility
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $reservationsWithEligibility->count(),
                'reservations' => $reservationsWithEligibility
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des réservations éligibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard avec vue d'ensemble
     * 
     * GET /api/auto-remote-start/dashboard
     */
    public function dashboard(): JsonResponse
    {
        try {
            // Stats générales
            $stats24h = $this->autoStartService->getAutoStartStats([
                'date_from' => now()->subDay()
            ]);

            $stats7d = $this->autoStartService->getAutoStartStats([
                'date_from' => now()->subDays(7)
            ]);

            // Health check
            $health = $this->autoStartService->healthCheck();

            // Logs récents nécessitant une action
            $requiresAction = AutoRemoteStartLog::requiresManualAction()
                ->limit(10)
                ->get();

            // Réservations éligibles actuelles
            $reflection = new \ReflectionClass($this->autoStartService);
            $method = $reflection->getMethod('getEligibleReservations');
            $method->setAccessible(true);
            $eligibleCount = $method->invoke($this->autoStartService)->count();

            return response()->json([
                'success' => true,
                'dashboard' => [
                    'stats_24h' => $stats24h,
                    'stats_7d' => $stats7d,
                    'health' => $health,
                    'requires_action_count' => $requiresAction->count(),
                    'requires_action' => $requiresAction,
                    'eligible_reservations_count' => $eligibleCount,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

