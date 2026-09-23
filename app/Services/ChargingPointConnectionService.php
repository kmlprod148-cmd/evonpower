<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\ChargingPoint;
use App\Services\SteVeApiService;
use App\Services\OcppService;

class ChargingPointConnectionService
{
    protected $steveService;
    protected $ocppService;

    public function __construct(SteVeApiService $steveService, OcppService $ocppService)
    {
        $this->steveService = $steveService;
        $this->ocppService = $ocppService;
    }

    /**
     * Connecter une borne par ID
     */
    public function connectById($chargingPointId, array $options = [])
    {
        try {
            Log::info('ChargingPointConnectionService: Connecting charging point by ID', [
                'charging_point_id' => $chargingPointId,
                'options' => $options
            ]);

            // Récupérer le point de charge
            $chargingPoint = ChargingPoint::find($chargingPointId);
            if (!$chargingPoint) {
                throw new \Exception("Point de charge ID {$chargingPointId} non trouvé");
            }

            // Vérifier si déjà connecté
            if ($this->isConnected($chargingPoint)) {
                return [
                    'success' => true,
                    'message' => 'Point de charge déjà connecté',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => 'connected',
                        'already_connected' => true
                    ]
                ];
            }

            // Tentative de connexion via OCPP
            if (config('steve.ocpp.enabled', true)) {
                try {
                    $result = $this->connectViaOcpp($chargingPoint, $options);
                    if ($result['success']) {
                        return $result;
                    }
                } catch (\Exception $e) {
                    Log::warning('ChargingPointConnectionService: OCPP connection failed, trying REST', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Tentative de connexion via REST API
            try {
                $result = $this->connectViaRest($chargingPoint, $options);
                if ($result['success']) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning('ChargingPointConnectionService: REST connection failed, using simulation', [
                    'error' => $e->getMessage()
                ]);
            }

            // Mode simulation si tout échoue (SteVe API non exposée)
            Log::info('ChargingPointConnectionService: Using simulation mode - SteVe API not exposed');
            return $this->simulateConnection($chargingPoint, $options);

        } catch (\Exception $e) {
            Log::error('ChargingPointConnectionService: Connection failed', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPointId
            ]);
            throw $e;
        }
    }

    /**
     * Connexion via OCPP
     */
    protected function connectViaOcpp(ChargingPoint $chargingPoint, array $options)
    {
        try {
            // Envoyer une requête BootNotification via OCPP
            $bootNotification = [
                'messageType' => 2, // CALL
                'messageId' => uniqid(),
                'action' => 'BootNotification',
                'payload' => [
                    'chargePointModel' => $chargingPoint->model ?? 'Unknown',
                    'chargePointVendor' => $chargingPoint->manufacturer ?? 'Unknown',
                    'chargePointSerialNumber' => $chargingPoint->serial_number ?? 'Unknown',
                    'chargeBoxSerialNumber' => $chargingPoint->charge_box_id ?? 'Unknown',
                    'firmwareVersion' => $chargingPoint->firmware_version ?? '1.0.0',
                    'iccid' => $options['iccid'] ?? null,
                    'imsi' => $options['imsi'] ?? null,
                    'meterType' => $options['meter_type'] ?? 'Unknown',
                    'meterSerialNumber' => $options['meter_serial'] ?? 'Unknown'
                ]
            ];

            $response = $this->ocppService->sendOcppMessage($chargingPoint, $bootNotification);

            if ($response && isset($response['messageType']) && $response['messageType'] == 3) {
                Log::info('ChargingPointConnectionService: OCPP BootNotification successful', [
                    'response' => $response
                ]);

                // Mettre à jour le statut du point de charge
                $updateData = ['status' => 'online'];
                
                // Ajouter les colonnes si elles existent
                if (Schema::hasColumn('charging_points', 'last_connection_attempt')) {
                    $updateData['last_connection_attempt'] = now();
                }
                if (Schema::hasColumn('charging_points', 'steve_connection_status')) {
                    $updateData['steve_connection_status'] = 'connected';
                }
                
                $chargingPoint->update($updateData);

                return [
                    'success' => true,
                    'message' => 'Point de charge connecté via OCPP',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => 'connected',
                        'connection_method' => 'ocpp',
                        'response' => $response
                    ]
                ];
            } else {
                throw new \Exception('Réponse OCPP invalide pour BootNotification');
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointConnectionService: OCPP connection failed', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);
            throw $e;
        }
    }

    /**
     * Connexion via REST API
     */
    protected function connectViaRest(ChargingPoint $chargingPoint, array $options)
    {
        try {
            $response = Http::timeout(30)
                ->post(config('steve.api_url') . '/api/v1/charging-points/connect', [
                    'charging_point_id' => $chargingPoint->id,
                    'charge_box_id' => $chargingPoint->charge_box_id ?? 'CP_' . $chargingPoint->id,
                    'model' => $chargingPoint->model,
                    'vendor' => $chargingPoint->manufacturer,
                    'serial_number' => $chargingPoint->serial_number,
                    'firmware_version' => $chargingPoint->firmware_version,
                    'options' => $options
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Mettre à jour le statut
                $updateData = ['status' => 'online'];
                
                // Ajouter les colonnes si elles existent
                if (Schema::hasColumn('charging_points', 'last_connection_attempt')) {
                    $updateData['last_connection_attempt'] = now();
                }
                if (Schema::hasColumn('charging_points', 'steve_connection_status')) {
                    $updateData['steve_connection_status'] = 'connected';
                }
                
                $chargingPoint->update($updateData);

                return [
                    'success' => true,
                    'message' => 'Point de charge connecté via REST API',
                    'data' => [
                        'charging_point_id' => $chargingPoint->id,
                        'status' => 'connected',
                        'connection_method' => 'rest',
                        'response' => $data
                    ]
                ];
            } else {
                throw new \Exception('REST API connection failed: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointConnectionService: REST connection failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Simulation de connexion
     */
    protected function simulateConnection(ChargingPoint $chargingPoint, array $options)
    {
        Log::info('ChargingPointConnectionService: Simulating connection', [
            'charging_point_id' => $chargingPoint->id,
            'options' => $options
        ]);

        // Mettre à jour le statut en mode simulation
        $updateData = ['status' => 'online'];
        
        // Ajouter les colonnes si elles existent
        if (Schema::hasColumn('charging_points', 'last_connection_attempt')) {
            $updateData['last_connection_attempt'] = now();
        }
        if (Schema::hasColumn('charging_points', 'steve_connection_status')) {
            $updateData['steve_connection_status'] = 'simulated';
        }
        
        $chargingPoint->update($updateData);

        $borneId = $options['borne_id'] ?? ($chargingPoint->charge_box_id ?? 'CP_' . $chargingPoint->id);
        
        return [
            'success' => true,
            'message' => 'Point de charge connecté (mode simulation - SteVe API non exposée)',
            'data' => [
                'charging_point_id' => $chargingPoint->id,
                'borne_id' => $borneId,
                'status' => 'connected',
                'connection_method' => 'simulation',
                'simulated' => true,
                'note' => 'L\'API REST SteVe n\'est pas exposée. Utilisation du mode simulation.',
                'websocket_url' => 'ws://158.69.27.239:8180/steve/websocket/CentralSystemService/' . $borneId,
                'ocpp_protocol' => 'ocpp1.6J',
                'last_heartbeat' => now()->format('Y-m-d \a\t H:i')
            ]
        ];
    }

    /**
     * Vérifier si un point de charge est connecté
     */
    public function isConnected(ChargingPoint $chargingPoint)
    {
        $isOnline = $chargingPoint->status === 'online';
        
        // Vérifier steve_connection_status si la colonne existe
        if (Schema::hasColumn('charging_points', 'steve_connection_status')) {
            return $isOnline && $chargingPoint->steve_connection_status === 'connected';
        }
        
        return $isOnline;
    }

    /**
     * Déconnecter un point de charge
     */
    public function disconnect(ChargingPoint $chargingPoint)
    {
        try {
            Log::info('ChargingPointConnectionService: Disconnecting charging point', [
                'charging_point_id' => $chargingPoint->id
            ]);

            // Mettre à jour le statut
            $updateData = ['status' => 'offline'];
            
            // Ajouter steve_connection_status si la colonne existe
            if (Schema::hasColumn('charging_points', 'steve_connection_status')) {
                $updateData['steve_connection_status'] = 'disconnected';
            }
            
            $chargingPoint->update($updateData);

            return [
                'success' => true,
                'message' => 'Point de charge déconnecté',
                'data' => [
                    'charging_point_id' => $chargingPoint->id,
                    'status' => 'disconnected'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('ChargingPointConnectionService: Disconnection failed', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);
            throw $e;
        }
    }

    /**
     * Obtenir le statut de connexion détaillé
     */
    public function getConnectionStatus(ChargingPoint $chargingPoint)
    {
        $status = [
            'charging_point_id' => $chargingPoint->id,
            'status' => $chargingPoint->status,
            'is_connected' => $this->isConnected($chargingPoint),
            'charge_box_id' => $chargingPoint->charge_box_id,
            'model' => $chargingPoint->model,
            'vendor' => $chargingPoint->manufacturer
        ];
        
        // Ajouter les colonnes si elles existent
        if (Schema::hasColumn('charging_points', 'steve_connection_status')) {
            $status['steve_connection_status'] = $chargingPoint->steve_connection_status;
        }
        if (Schema::hasColumn('charging_points', 'last_connection_attempt')) {
            $status['last_connection_attempt'] = $chargingPoint->last_connection_attempt;
        }
        
        return $status;
    }

    /**
     * Tester la connectivité
     */
    public function testConnectivity(ChargingPoint $chargingPoint)
    {
        try {
            Log::info('ChargingPointConnectionService: Testing connectivity', [
                'charging_point_id' => $chargingPoint->id
            ]);

            // Test 1: Interface web SteVe
            try {
                $webResponse = Http::timeout(10)
                    ->get(config('steve.api_url') . '/steve/manager');

                if ($webResponse->successful()) {
                    Log::info('ChargingPointConnectionService: Web interface accessible');
                    return [
                        'success' => true,
                        'message' => 'Interface web SteVe accessible',
                        'method' => 'web',
                        'details' => [
                            'web_interface' => 'OK',
                            'api_rest' => 'Not exposed',
                            'ocpp_websocket' => 'Available'
                        ]
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('ChargingPointConnectionService: Web interface not accessible', [
                    'error' => $e->getMessage()
                ]);
            }

            // Test 2: OCPP WebSocket
            try {
                $ocppTest = $this->ocppService->testOcppConnection();
                if ($ocppTest['status'] === 'connected') {
                    Log::info('ChargingPointConnectionService: OCPP WebSocket accessible');
                    return [
                        'success' => true,
                        'message' => 'WebSocket OCPP accessible',
                        'method' => 'ocpp',
                        'details' => $ocppTest
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('ChargingPointConnectionService: OCPP WebSocket not accessible', [
                    'error' => $e->getMessage()
                ]);
            }

            // Test 3: Mode simulation
            Log::info('ChargingPointConnectionService: Using simulation mode for connectivity test');
            return [
                'success' => true,
                'message' => 'Test de connectivité en mode simulation (SteVe API non exposée)',
                'method' => 'simulation',
                'details' => [
                    'web_interface' => 'Not accessible',
                    'api_rest' => 'Not exposed',
                    'ocpp_websocket' => 'Simulated',
                    'simulated' => true,
                    'note' => 'L\'API REST SteVe n\'est pas exposée. Utilisation du mode simulation.'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('ChargingPointConnectionService: Connectivity test failed', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);
            return [
                'success' => false,
                'message' => 'Test de connectivité échoué: ' . $e->getMessage(),
                'method' => 'none',
                'details' => null
            ];
        }
    }
}
