<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SteVeAutoStopService;
use App\Models\Reservation;
use App\Models\ChargingPoint;

/**
 * Contrôleur de test pour l'arrêt automatique SETEVE
 * 
 * Ce contrôleur permet de tester le système d'arrêt automatique
 * des sessions de recharge selon les limites de réservation
 */
class SteVeAutoStopTestController extends Controller
{
    protected SteVeAutoStopService $autoStopService;

    public function __construct(SteVeAutoStopService $autoStopService)
    {
        $this->autoStopService = $autoStopService;
    }

    /**
     * Test de vérification des sessions expirées
     */
    public function testCheckAndStopSessions()
    {
        try {
            Log::info('SteVeAutoStopTestController: Test de vérification des sessions expirées');

            $result = $this->autoStopService->checkAndStopExpiredSessions();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Test terminé',
                'sessions_checked' => $result['sessions_checked'] ?? 0,
                'sessions_stopped' => $result['sessions_stopped'] ?? 0,
                'results' => $result['results'] ?? [],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopTestController: Erreur lors du test', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de vérification d'une session spécifique
     */
    public function testCheckSpecificSession(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|string'
            ]);

            $sessionId = $request->session_id;
            
            Log::info('SteVeAutoStopTestController: Test de vérification de session', [
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
            Log::error('SteVeAutoStopTestController: Erreur lors du test de session', [
                'session_id' => $request->session_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test des statistiques des sessions
     */
    public function testSessionStats()
    {
        try {
            Log::info('SteVeAutoStopTestController: Test des statistiques');

            $result = $this->autoStopService->getSessionStats();

            return response()->json([
                'success' => $result['success'],
                'stats' => $result['stats'] ?? [],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopTestController: Erreur lors du test des statistiques', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test complet du système d'arrêt automatique
     */
    public function testFullAutoStopSystem()
    {
        try {
            Log::info('SteVeAutoStopTestController: Test complet du système d\'arrêt automatique');

            $results = [];

            // 1. Test des statistiques
            $statsResult = $this->autoStopService->getSessionStats();
            $results['stats'] = $statsResult;

            // 2. Test de vérification des sessions
            $checkResult = $this->autoStopService->checkAndStopExpiredSessions();
            $results['check_sessions'] = $checkResult;

            // 3. Test de création d'une réservation de test (si possible)
            $testReservation = $this->createTestReservation();
            if ($testReservation) {
                $results['test_reservation'] = [
                    'created' => true,
                    'reservation_id' => $testReservation->id,
                    'type' => $testReservation->reservation_type,
                    'value' => $testReservation->reservation_value
                ];
            } else {
                $results['test_reservation'] = [
                    'created' => false,
                    'reason' => 'Impossible de créer une réservation de test'
                ];
            }

            $overallSuccess = $statsResult['success'] && $checkResult['success'];

            return response()->json([
                'success' => $overallSuccess,
                'message' => $overallSuccess ? 'Test complet réussi' : 'Test complet échoué',
                'results' => $results,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopTestController: Erreur lors du test complet', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test complet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de simulation d'arrêt automatique
     */
    public function testSimulateAutoStop(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservations,id',
                'simulate_time_limit' => 'nullable|boolean',
                'simulate_energy_limit' => 'nullable|boolean'
            ]);

            $reservation = Reservation::with(['chargingPoint', 'pricingPlan'])->findOrFail($request->reservation_id);
            
            Log::info('SteVeAutoStopTestController: Test de simulation d\'arrêt automatique', [
                'reservation_id' => $reservation->id,
                'type' => $reservation->reservation_type,
                'value' => $reservation->reservation_value
            ]);

            $results = [];

            // Simuler une session de recharge
            $mockSession = [
                'id' => 'TEST_SESSION_' . time(),
                'reservation_id' => $reservation->id,
                'status' => 'active',
                'start_time' => now()->subMinutes(30)->toISOString(),
                'created_at' => now()->subMinutes(30)->toISOString()
            ];

            // Test de limite de temps
            if ($request->get('simulate_time_limit', false) && $reservation->reservation_type === 'minute') {
                $results['time_limit_test'] = $this->testTimeLimit($mockSession, $reservation);
            }

            // Test de limite d'énergie
            if ($request->get('simulate_energy_limit', false) && $reservation->reservation_type === 'kwh') {
                $results['energy_limit_test'] = $this->testEnergyLimit($mockSession, $reservation);
            }

            return response()->json([
                'success' => true,
                'message' => 'Simulation d\'arrêt automatique terminée',
                'reservation' => [
                    'id' => $reservation->id,
                    'type' => $reservation->reservation_type,
                    'value' => $reservation->reservation_value,
                    'charging_point' => $reservation->chargingPoint?->name ?? 'Inconnu'
                ],
                'simulation_results' => $results,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopTestController: Erreur lors de la simulation', [
                'reservation_id' => $request->reservation_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la simulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une réservation de test
     */
    protected function createTestReservation(): ?Reservation
    {
        try {
            // Trouver un point de charge disponible
            $chargingPoint = ChargingPoint::with('pricingPlan')->first();
            if (!$chargingPoint || !$chargingPoint->pricingPlan) {
                return null;
            }

            // Créer une réservation de test
            $reservation = Reservation::create([
                'user_id' => null, // Réservation invité
                'charging_point_id' => $chargingPoint->id,
                'pricing_plan_id' => $chargingPoint->pricingPlan->id,
                'reservation_type' => 'minute',
                'reservation_value' => 30, // 30 minutes
                'status' => 'confirmed',
                'payment_confirmed' => true,
                'estimated_cost' => 50.00,
                'guest_email' => 'test@example.com',
                'guest_phone' => '+212600000000'
            ]);

            return $reservation;

        } catch (\Exception $e) {
            Log::warning('SteVeAutoStopTestController: Impossible de créer une réservation de test', [
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Test de limite de temps
     */
    protected function testTimeLimit(array $session, Reservation $reservation): array
    {
        try {
            $maxDuration = $reservation->reservation_value; // en minutes
            $startTime = \Carbon\Carbon::parse($session['start_time']);
            $currentTime = \Carbon\Carbon::now();
            $elapsedMinutes = $currentTime->diffInMinutes($startTime);

            $shouldStop = $elapsedMinutes >= $maxDuration;

            return [
                'test_type' => 'time_limit',
                'max_duration' => $maxDuration,
                'elapsed_minutes' => $elapsedMinutes,
                'should_stop' => $shouldStop,
                'reason' => $shouldStop ? 
                    "Limite de temps atteinte ({$elapsedMinutes}min >= {$maxDuration}min)" : 
                    "Limite de temps non atteinte",
                'remaining_minutes' => $maxDuration - $elapsedMinutes
            ];

        } catch (\Exception $e) {
            return [
                'test_type' => 'time_limit',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test de limite d'énergie
     */
    protected function testEnergyLimit(array $session, Reservation $reservation): array
    {
        try {
            $maxEnergy = $reservation->reservation_value; // en kWh
            
            // Simuler des valeurs de compteur
            $currentEnergy = $maxEnergy * 0.8; // 80% de la limite
            $shouldStop = $currentEnergy >= $maxEnergy;

            return [
                'test_type' => 'energy_limit',
                'max_energy' => $maxEnergy,
                'current_energy' => $currentEnergy,
                'should_stop' => $shouldStop,
                'reason' => $shouldStop ? 
                    "Limite d'énergie atteinte ({$currentEnergy}kWh >= {$maxEnergy}kWh)" : 
                    "Limite d'énergie non atteinte",
                'remaining_energy' => $maxEnergy - $currentEnergy
            ];

        } catch (\Exception $e) {
            return [
                'test_type' => 'energy_limit',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test de la commande Artisan
     */
    public function testArtisanCommand()
    {
        try {
            Log::info('SteVeAutoStopTestController: Test de la commande Artisan');

            // Exécuter la commande Artisan
            $exitCode = \Artisan::call('steve:auto-stop', [
                '--verbose' => true
            ]);

            $output = \Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $output,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeAutoStopTestController: Erreur lors du test de la commande Artisan', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de la commande Artisan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
