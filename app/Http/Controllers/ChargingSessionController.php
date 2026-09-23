<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\ChargingPoint;
use App\Services\ChargingSessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChargingSessionController extends Controller
{
    protected ChargingSessionManager $sessionManager;

    public function __construct(ChargingSessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
        $this->middleware('auth');
    }

    /**
     * Afficher toutes les sessions de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();
        
        $sessions = ChargingSession::forUser($user->id)
            ->with(['reservation', 'chargingPoint'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('charging-sessions.index', compact('sessions'));
    }

    /**
     * Afficher les détails d'une session
     */
    public function show(ChargingSession $session)
    {
        $this->authorize('view', $session);

        $session->load(['reservation', 'chargingPoint', 'user']);

        return view('charging-sessions.show', compact('session'));
    }

    /**
     * Démarrer manuellement une session de recharge pour une réservation
     */
    public function start(Request $request, Reservation $reservation)
    {
        try {
            // Vérifier les autorisations
            if ($reservation->user_id !== Auth::id() && !Auth::user()->can('manage_charging_sessions')) {
                return response()->json([
                    'success' => false,
                    'error' => 'unauthorized',
                    'message' => 'Vous n\'avez pas l\'autorisation de démarrer cette session'
                ], 403);
            }

            Log::info('ChargingSessionController: Manual start requested', [
                'reservation_id' => $reservation->id,
                'user_id' => Auth::id(),
                'charging_point_id' => $reservation->charging_point_id
            ]);

            // Démarrer la session via le manager
            $result = $this->sessionManager->startChargingSession($reservation);

            if ($result['success']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Session de recharge démarrée avec succès',
                        'session_id' => $result['session']->id,
                        'session' => $result['session']
                    ]);
                }

                return redirect()
                    ->back()
                    ->with('success', 'Session de recharge démarrée avec succès');
            } else {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'] ?? 'unknown',
                        'message' => $result['message'] ?? 'Erreur lors du démarrage de la session'
                    ], 400);
                }

                return redirect()
                    ->back()
                    ->with('error', $result['message'] ?? 'Impossible de démarrer la session');
            }

        } catch (\Exception $e) {
            Log::error('ChargingSessionController: Error starting session', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'exception',
                    'message' => 'Erreur: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Arrêter manuellement une session de recharge
     */
    public function stop(Request $request, ChargingSession $session)
    {
        try {
            // Vérifier les autorisations
            if ($session->user_id !== Auth::id() && !Auth::user()->can('manage_charging_sessions')) {
                return response()->json([
                    'success' => false,
                    'error' => 'unauthorized',
                    'message' => 'Vous n\'avez pas l\'autorisation d\'arrêter cette session'
                ], 403);
            }

            // Vérifier que la session est active
            if (!$session->isActive()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'session_not_active',
                        'message' => 'La session n\'est pas active'
                    ], 400);
                }

                return redirect()
                    ->back()
                    ->with('error', 'La session n\'est pas active');
            }

            Log::info('ChargingSessionController: Manual stop requested', [
                'session_id' => $session->id,
                'reservation_id' => $session->reservation_id,
                'user_id' => Auth::id()
            ]);

            // Arrêter la session via le manager
            $result = $this->sessionManager->stopChargingSession($session, 'manual');

            if ($result['success']) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Session de recharge arrêtée avec succès',
                        'session' => $result['session']
                    ]);
                }

                return redirect()
                    ->back()
                    ->with('success', 'Session de recharge arrêtée avec succès');
            } else {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'] ?? 'unknown',
                        'message' => $result['message'] ?? 'Erreur lors de l\'arrêt de la session'
                    ], 400);
                }

                return redirect()
                    ->back()
                    ->with('error', $result['message'] ?? 'Impossible d\'arrêter la session');
            }

        } catch (\Exception $e) {
            Log::error('ChargingSessionController: Error stopping session', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'exception',
                    'message' => 'Erreur: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * API: Obtenir les sessions actives de l'utilisateur
     */
    public function activeSessions(Request $request)
    {
        $user = Auth::user();
        
        $sessions = ChargingSession::forUser($user->id)
            ->active()
            ->with(['reservation', 'chargingPoint'])
            ->get();

        return response()->json([
            'success' => true,
            'sessions' => $sessions
        ]);
    }

    /**
     * API: Obtenir le statut d'une session
     */
    public function status(ChargingSession $session)
    {
        $this->authorize('view', $session);

        $session->load(['reservation', 'chargingPoint']);

        // Calculer la durée actuelle
        $duration = $session->getDuration();

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'started_at' => $session->started_at,
                'stopped_at' => $session->stopped_at,
                'duration_minutes' => $duration,
                'payment_mode' => $session->payment_mode,
                'estimated_cost' => $session->estimated_cost,
                'actual_cost' => $session->actual_cost,
                'actual_energy' => $session->actual_energy,
                'charging_point' => [
                    'id' => $session->chargingPoint->id,
                    'name' => $session->chargingPoint->name,
                    'address' => $session->chargingPoint->address
                ],
                'reservation_id' => $session->reservation_id
            ]
        ]);
    }

    /**
     * Admin: Voir toutes les sessions actives
     */
    public function adminActiveSessions()
    {
        $this->authorize('manage_charging_sessions');

        $sessions = ChargingSession::active()
            ->with(['reservation', 'chargingPoint', 'user'])
            ->orderBy('started_at', 'desc')
            ->paginate(50);

        return view('admin.charging-sessions.active', compact('sessions'));
    }

    /**
     * Admin: Arrêter une session (force)
     */
    public function adminForceStop(Request $request, ChargingSession $session)
    {
        $this->authorize('manage_charging_sessions');

        $reason = $request->input('reason', 'admin_intervention');

        Log::warning('ChargingSessionController: Admin force stop', [
            'session_id' => $session->id,
            'admin_id' => Auth::id(),
            'reason' => $reason
        ]);

        $result = $this->sessionManager->stopChargingSession($session, $reason);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()
                ->back()
                ->with('success', 'Session arrêtée par l\'administrateur');
        } else {
            return redirect()
                ->back()
                ->with('error', 'Impossible d\'arrêter la session: ' . ($result['message'] ?? 'Erreur inconnue'));
        }
    }
}
