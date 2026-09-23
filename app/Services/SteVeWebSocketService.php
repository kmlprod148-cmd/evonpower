<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;
use React\Stream\WritableResourceStream;

class SteVeWebSocketService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config("steve.api_url", "http://158.69.27.239:8180");
        $this->timeout = config("steve.timeout", 30);
    }

    /**
     * Obtenir l'URL WebSocket pour une borne de charge
     */
    public function getWebSocketUrl($chargeBoxId)
    {
        $wsUrl = str_replace("http://", "ws://", $this->baseUrl);
        return $wsUrl . "/steve/websocket/CentralSystemService/" . $chargeBoxId;
    }

    /**
     * Tester la connectivité WebSocket
     */
    public function testWebSocketConnection($chargeBoxId = "test-charge-box")
    {
        $wsUrl = $this->getWebSocketUrl($chargeBoxId);
        
        Log::info("SteVeWebSocketService: Testing WebSocket connection", [
            "url" => $wsUrl,
            "charge_box_id" => $chargeBoxId
        ]);

        try {
            // Note: WebSocket nécessite une connexion active
            // Ceci est un test de format d'URL
            if (filter_var($wsUrl, FILTER_VALIDATE_URL) || strpos($wsUrl, "ws://") === 0) {
                return [
                    "status" => "success",
                    "message" => "URL WebSocket valide",
                    "url" => $wsUrl,
                    "note" => "WebSocket nécessite une connexion active pour être testé"
                ];
            } else {
                return [
                    "status" => "error",
                    "message" => "URL WebSocket invalide",
                    "url" => $wsUrl
                ];
            }
        } catch (\Exception $e) {
            return [
                "status" => "error",
                "message" => "Erreur test WebSocket: " . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir la configuration WebSocket complète
     */
    public function getWebSocketConfig()
    {
        return [
            "base_url" => $this->baseUrl,
            "websocket_endpoint" => "/steve/websocket/CentralSystemService/(chargeBoxId)",
            "example_url" => $this->getWebSocketUrl("example-charge-box"),
            "timeout" => $this->timeout,
            "protocol" => "OCPP 1.6",
            "note" => "Remplacer (chargeBoxId) par l'ID réel de la borne"
        ];
    }
}