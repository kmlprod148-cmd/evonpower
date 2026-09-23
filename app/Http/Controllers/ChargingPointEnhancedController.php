<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use Illuminate\Http\Request;

class ChargingPointEnhancedController extends Controller
{
    /**
     * Afficher la vue améliorée des détails de borne
     */
    public function showEnhanced(ChargingPoint $chargingPoint)
    {
        // Charger les relations nécessaires
        $chargingPoint->load(['pricingPlan', 'businessProfile', 'transactions']);
        
        // Calculer les statistiques
        $stats = [
            'energy_delivered' => $chargingPoint->transactions()->sum('energy_delivered') ?? 0,
            'transactions_count' => $chargingPoint->transactions()->count(),
            'success_rate' => $this->calculateSuccessRate($chargingPoint),
        ];
        
        // Ajouter les statistiques à l'objet chargingPoint
        $chargingPoint->energy_delivered = $stats['energy_delivered'];
        $chargingPoint->transactions_count = $stats['transactions_count'];
        $chargingPoint->success_rate = $stats['success_rate'];
        
        $steveChargeBoxId = $this->resolveChargeBoxId($chargingPoint);

        return view('charging-points.show-complete', compact('chargingPoint', 'steveChargeBoxId'));
    }
    
    /**
     * Calculer le taux de réussite des sessions
     */
    private function calculateSuccessRate(ChargingPoint $chargingPoint): int
    {
        $totalSessions = $chargingPoint->transactions()->count();
        if ($totalSessions === 0) {
            return 100; // Par défaut si aucune session
        }
        
        $successfulSessions = $chargingPoint->transactions()
            ->whereNotNull('stop_timestamp')
            ->where('reason', '!=', 'DeAuthorized')
            ->count();
            
        return round(($successfulSessions / $totalSessions) * 100);
    }
    
    /**
     * Générer un QR Code pour la borne
     */
    public function generateQRCode(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            // Vérifier que le répertoire qrcodes existe
            if (!\Storage::disk('public')->exists('qrcodes')) {
                \Storage::disk('public')->makeDirectory('qrcodes');
                \Log::info('Répertoire qrcodes créé');
            }
            
            // Utiliser le service QR Code existant
            $qrCodeService = app(\App\Services\QRCodeService::class);
            $qrCodeUrl = $qrCodeService->generateAndSaveQRCode($chargingPoint->id);
            
            \Log::info("QR Code URL généré: {$qrCodeUrl}");
            
            // Le service QR Code fait déjà les vérifications nécessaires
            // On fait juste une vérification basique de l'URL
            if (empty($qrCodeUrl) || !filter_var($qrCodeUrl, FILTER_VALIDATE_URL)) {
                throw new \Exception('URL du QR Code invalide');
            }
            
            return response()->json([
                'success' => true,
                'qr_code_url' => $qrCodeUrl,
                'message' => 'QR Code généré avec succès',
                'download_url' => $qrCodeUrl
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur génération QR Code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du QR Code: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculer l'estimation de coût
     */
    public function calculateEstimation(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'type' => 'required|in:duration,energy,target',
            'value' => 'required|numeric|min:0',
            'initial_charge' => 'nullable|numeric|min:0|max:100',
            'battery_capacity' => 'nullable|numeric|min:10|max:200'
        ]);
        
        $type = $request->input('type');
        $value = $request->input('value');
        $initialCharge = $request->input('initial_charge', 20);
        $batteryCapacity = $request->input('battery_capacity', 50);
        $powerOutput = $chargingPoint->power_output ?? 22;
        $pricingPlan = $chargingPoint->pricingPlan;
        
        // Calculs selon le type
        $energyToCharge = 0;
        $duration = 0;
        
        if ($type === 'duration') {
            $duration = $value;
            $energyToCharge = ($powerOutput * $duration) / 60;
        } elseif ($type === 'energy') {
            $energyToCharge = $value;
            $duration = ($energyToCharge / $powerOutput) * 60;
        } elseif ($type === 'target') {
            $targetCharge = $value;
            $chargeNeeded = (($targetCharge - $initialCharge) / 100) * $batteryCapacity;
            $energyToCharge = min($chargeNeeded, $powerOutput * 8); // Limite à 8h max
            $duration = ($energyToCharge / $powerOutput) * 60;
        }
        
        // Calcul des coûts
        $costs = $this->calculateCosts($pricingPlan, $energyToCharge, $duration);
        
        return response()->json([
            'success' => true,
            'data' => [
                'energy_to_charge' => round($energyToCharge, 1),
                'duration' => round($duration),
                'costs' => $costs
            ]
        ]);
    }
    
    /**
     * Calculer les coûts détaillés
     */
    private function calculateCosts($pricingPlan, $energyToCharge, $duration)
    {
        if (!$pricingPlan) {
            // Tarifs par défaut
            $activationFee = 0.50;
            $pricePerKwh = 0.25;
            $pricePerMinute = 0.10;
            $vatRate = 20;
        } else {
            $activationFee = $pricingPlan->activation_fee ?? 0.50;
            $pricePerKwh = $pricingPlan->price_per_kwh ?? 0.25;
            $pricePerMinute = $pricingPlan->price_per_minute ?? 0.10;
            $vatRate = $pricingPlan->vatRate->rate ?? 20;
        }
        
        $energyCost = $energyToCharge * $pricePerKwh;
        $timeCost = ($duration / 60) * $pricePerMinute;
        $subtotal = $activationFee + $energyCost + $timeCost;
        $vatCost = $subtotal * ($vatRate / 100);
        $totalCost = $subtotal + $vatCost;
        
        return [
            'activation_fee' => round($activationFee, 2),
            'energy_cost' => round($energyCost, 2),
            'time_cost' => round($timeCost, 2),
            'vat_cost' => round($vatCost, 2),
            'total_cost' => round($totalCost, 2)
        ];
    }
    
    /**
     * Se connecter à SteVe
     */
    public function connectToSteVe(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'chargebox_id' => 'required|string'
        ]);

        $requestedChargeBoxId = $request->input('chargebox_id');
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint, $requestedChargeBoxId);

        try {
            $steVeService = app(\App\Services\SteVeHttpClientService::class);
            $result = $steVeService->getChargePoint($chargeBoxId);

            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connexion établie avec SteVe',
                    'data' => $result['data'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Connexion à SteVe impossible',
            ], 502);
        } catch (\Throwable $e) {
            \Log::error('Erreur connexion SteVe: ' . $e->getMessage(), [
                'charging_point_id' => $chargingPoint->id,
                'chargebox_id' => $chargeBoxId,
                'requested_chargebox_id' => $requestedChargeBoxId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur de connexion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exécuter une action SteVe
     */
    public function executeSteVeAction(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'action'         => 'required|in:start,stop,status,transactions',
            'chargebox_id'   => 'required|string',
            'connector_id'   => 'nullable|integer|min:1',
            'ocpp_tag'       => 'nullable|string',
            'transaction_id' => 'nullable|integer|min:1',
        ]);

        return $this->executeSteVeActionInternal($request, $chargingPoint);
    }

    /**
      * Récupérer la transaction active d'une borne
      */
    public function getActiveTransaction(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'chargebox_id' => 'required|string',
        ]);

        $requestedChargeBoxId = $request->input('chargebox_id');
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint, $requestedChargeBoxId);

        try {
            $steVeService = app(\App\Services\SteVeHttpClientService::class);
            $result = $steVeService->getTransactions([
                'chargeBoxId' => $chargeBoxId,
                'type'        => 'ACTIVE',
            ]);

            if ($result['success'] ?? false) {
                $transactions = $result['data'] ?? [];
                if (is_array($transactions) && count($transactions) > 0) {
                    // Retourner le premier transactionId trouvé
                    $firstTransaction = is_array($transactions[0]) ? $transactions[0] : [];
                    $transactionId = $firstTransaction['transactionId'] 
                                  ?? $firstTransaction['id'] 
                                  ?? $firstTransaction['transactionPk'] 
                                  ?? null;
                    
                    return response()->json([
                        'success' => true,
                        'transaction_id' => $transactionId,
                        'transactions' => $transactions,
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune transaction active trouvée',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de la récupération des transactions',
            ], 502);
        } catch (\Throwable $e) {
            \Log::error('Erreur getActiveTransaction: ' . $e->getMessage(), [
                'charging_point_id' => $chargingPoint->id,
                'chargebox_id' => $chargeBoxId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
      * Exécuter une action SteVe (logique interne réutilisable)
      */
    private function executeSteVeActionInternal(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'action'         => 'required|in:start,stop,status,transactions',
            'chargebox_id'   => 'required|string',
            'connector_id'   => 'nullable|integer|min:1',
            'ocpp_tag'       => 'nullable|string',
            'transaction_id' => 'nullable',
        ]);

        $action      = $request->input('action');
        $requestedChargeBoxId = $request->input('chargebox_id');
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint, $requestedChargeBoxId);

        try {
            $steVeService = app(\App\Services\SteVeHttpClientService::class);

            switch ($action) {
                case 'start':
                    $result = $steVeService->remoteStartTransaction($chargeBoxId, [
                        'connector_id' => $request->input('connector_id'),
                        'ocpp_tag'     => $request->input('ocpp_tag'),
                    ]);
                    break;
                case 'stop':
                    $result = $steVeService->remoteStopTransaction(
                        $chargeBoxId,
                        $request->input('transaction_id')
                    );
                    break;
                case 'status':
                    $result = $steVeService->getChargePoint($chargeBoxId);
                    break;
                case 'transactions':
                    $result = $steVeService->getTransactions([
                        'chargeBoxId' => $chargeBoxId,
                        'periodType'  => 'LAST_30',
                        'type'        => 'ALL',
                    ]);
                    break;
                default:
                    throw new \InvalidArgumentException('Action non supportée');
            }

            $errorCode = $result['error_code'] ?? null;
            $statusCode = match (true) {
                (bool) ($result['success'] ?? false) => 200,
                $errorCode === 'chargebox_id_required' => 422,
                in_array($errorCode, ['charger_not_connected', 'remote_stop_rejected'], true) => 409,
                default => 502,
            };

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'action'  => $action,
                'data'    => $result['data'] ?? null,
                'error_code' => $result['error_code'] ?? null,
                'ocpp_status' => $result['ocpp_status'] ?? null,
                'transaction_id' => $result['transaction_id'] ?? null,
                'message' => $result['message'] ?? null,
            ], $statusCode);
        } catch (\Throwable $e) {
            \Log::error('Erreur action SteVe enhanced: ' . $e->getMessage(), [
                'charging_point_id' => $chargingPoint->id,
                'chargebox_id' => $chargeBoxId,
                'requested_chargebox_id' => $requestedChargeBoxId,
                'action' => $action,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de l\'action: ' . $e->getMessage()
            ], 500);
        }
    }

    private function resolveChargeBoxId(ChargingPoint $chargingPoint, ?string $requestedChargeBoxId = null): string
    {
        foreach ([
            $chargingPoint->charge_box_id ?? null,
            $chargingPoint->steve_charging_point_id ?? null,
            $requestedChargeBoxId,
            $chargingPoint->serial_number ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }
}
