<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Services\SteVeChargingTriggerService;
use App\Services\SteVeRetryService;
use App\Services\OcppService;

/**
 * Contrôleur de test pour l'intégration SETEVE
 * 
 * Ce contrôleur permet de tester l'intégration complète
 * du déclenchement automatique de recharge après paiement
 */
class SteVeTestController extends Controller
{
    protected SteVeChargingTriggerService $chargingTriggerService;
    protected SteVeRetryService $retryService;
    protected OcppService $ocppService;

    public function __construct(
        SteVeChargingTriggerService $chargingTriggerService,
        SteVeRetryService $retryService,
        OcppService $ocppService
    ) {
        $this->chargingTriggerService = $chargingTriggerService;
        $this->retryService = $retryService;
        $this->ocppService = $ocppService;
    }

    /**
     * Test de connectivité SETEVE
     */
    public function testConnectivity()
    {
        try {
            Log::info('SteVeTestController: Test de connectivité SETEVE');

            $result = $this->retryService->checkConnectivityWithRetry();

            return response()->json([
                'success' => true,
                'message' => 'Test de connectivité terminé',
                'result' => $result,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeTestController: Erreur lors du test de connectivité', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connectivité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de déclenchement de recharge
     */
    public function testChargingTrigger(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservations,id'
            ]);

            $reservation = Reservation::with(['chargingPoint', 'pricingPlan'])->findOrFail($request->reservation_id);
            
            Log::info('SteVeTestController: Test de déclenchement de recharge', [
                'reservation_id' => $reservation->id,
                'charging_point_id' => $reservation->charging_point_id
            ]);

            // Simuler des données de paiement
            $paymentData = [
                'transaction_id' => 'TEST_' . time(),
                'payment_method' => 'test',
                'connector_id' => 1,
                'id_tag' => 'admin',
                'meter_start' => 0
            ];

            $result = $this->chargingTriggerService->triggerChargingAfterPayment($reservation, $paymentData);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Test de déclenchement réussi' : 'Test de déclenchement échoué',
                'result' => $result,
                'reservation' => $reservation,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeTestController: Erreur lors du test de déclenchement', [
                'error' => $e->getMessage(),
                'reservation_id' => $request->reservation_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de déclenchement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de vérification du statut de recharge
     */
    public function testChargingStatus(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservations,id'
            ]);

            $reservation = Reservation::findOrFail($request->reservation_id);
            
            Log::info('SteVeTestController: Test de vérification du statut', [
                'reservation_id' => $reservation->id
            ]);

            $result = $this->chargingTriggerService->checkChargingStatus($reservation);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Vérification du statut réussie' : 'Vérification du statut échouée',
                'result' => $result,
                'reservation' => $reservation,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeTestController: Erreur lors de la vérification du statut', [
                'error' => $e->getMessage(),
                'reservation_id' => $request->reservation_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test d'arrêt de recharge
     */
    public function testStopCharging(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservations,id'
            ]);

            $reservation = Reservation::findOrFail($request->reservation_id);
            
            Log::info('SteVeTestController: Test d\'arrêt de recharge', [
                'reservation_id' => $reservation->id
            ]);

            $result = $this->chargingTriggerService->stopCharging($reservation);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Arrêt de recharge réussi' : 'Arrêt de recharge échoué',
                'result' => $result,
                'reservation' => $reservation,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeTestController: Erreur lors de l\'arrêt de recharge', [
                'error' => $e->getMessage(),
                'reservation_id' => $request->reservation_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'arrêt de recharge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test complet du flux de paiement et recharge
     */
    public function testFullPaymentFlow(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|exists:reservations,id',
                'payment_method' => 'required|in:cmi,stripe,test'
            ]);

            $reservation = Reservation::with(['chargingPoint', 'pricingPlan'])->findOrFail($request->reservation_id);
            
            Log::info('SteVeTestController: Test complet du flux de paiement', [
                'reservation_id' => $reservation->id,
                'payment_method' => $request->payment_method
            ]);

            $results = [];

            // 1. Test de connectivité
            $connectivityResult = $this->retryService->checkConnectivityWithRetry();
            $results['connectivity'] = $connectivityResult;

            if (!$connectivityResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test de connectivité échoué',
                    'results' => $results
                ]);
            }

            // 2. Simulation du paiement réussi
            $reservation->update([
                'status' => 'confirmed',
                'payment_confirmed' => true
            ]);

            // 3. Test de déclenchement de recharge
            $paymentData = [
                'transaction_id' => 'TEST_' . $request->payment_method . '_' . time(),
                'payment_method' => $request->payment_method,
                'connector_id' => 1,
                'id_tag' => 'admin',
                'meter_start' => 0
            ];

            $chargingResult = $this->chargingTriggerService->triggerChargingAfterPayment($reservation, $paymentData);
            $results['charging_trigger'] = $chargingResult;

            // 4. Test de vérification du statut
            if ($chargingResult['success']) {
                sleep(2); // Attendre un peu
                $statusResult = $this->chargingTriggerService->checkChargingStatus($reservation);
                $results['status_check'] = $statusResult;
            }

            // 5. Test d'arrêt (optionnel)
            if ($request->get('test_stop', false)) {
                sleep(2); // Attendre un peu
                $stopResult = $this->chargingTriggerService->stopCharging($reservation);
                $results['stop_charging'] = $stopResult;
            }

            $overallSuccess = $connectivityResult['success'] && $chargingResult['success'];

            return response()->json([
                'success' => $overallSuccess,
                'message' => $overallSuccess ? 'Test complet réussi' : 'Test complet échoué',
                'results' => $results,
                'reservation' => $reservation,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeTestController: Erreur lors du test complet', [
                'error' => $e->getMessage(),
                'reservation_id' => $request->reservation_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test complet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques de retry
     */
    public function getRetryStats()
    {
        try {
            $stats = $this->retryService->getRetryStats();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques de retry récupérées',
                'stats' => $stats,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeTestController: Erreur lors de la récupération des statistiques', [
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
     * Mettre à jour la configuration de retry
     */
    public function updateRetryConfig(Request $request)
    {
        try {
            $request->validate([
                'max_retries' => 'nullable|integer|min:1|max:10',
                'retry_delay' => 'nullable|integer|min:1|max:60',
                'timeout' => 'nullable|integer|min:5|max:300'
            ]);

            $config = $request->only(['max_retries', 'retry_delay', 'timeout']);
            $this->retryService->updateRetryConfig($config);

            return response()->json([
                'success' => true,
                'message' => 'Configuration de retry mise à jour',
                'config' => $config,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('SteVeTestController: Erreur lors de la mise à jour de la configuration', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
