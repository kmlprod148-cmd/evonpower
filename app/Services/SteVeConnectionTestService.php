<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated Use SteVeHealthService instead. This class will be removed in a future version.
 */
class SteVeConnectionTestService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config("steve.api_url", "http://158.69.27.239:8180");
        $this->username = config("steve.username", "admin");
        $this->password = config("steve.password", "1234");
        $this->timeout = config("steve.timeout", 30);
    }

    /**
     * Test complet de la connexion SteVe
     */
    public function testFullConnection()
    {
        $results = [
            "connectivity" => $this->testConnectivity(),
            "web_interface" => $this->testWebInterface(),
            "authentication" => $this->testAuthentication(),
            "ocpp_soap" => $this->testOcppSoap(),
            "websocket" => $this->testWebSocket()
        ];

        return $results;
    }

    /**
     * Test de connectivité de base
     */
    public function testConnectivity()
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        $port = parse_url($this->baseUrl, PHP_URL_PORT) ?: 80;
        
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($connection) {
            fclose($connection);
            return ["status" => "success", "message" => "Port $port accessible"];
        } else {
            return ["status" => "error", "message" => "Port $port non accessible: $errstr ($errno)"];
        }
    }

    /**
     * Test de l'interface web
     */
    public function testWebInterface()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . "/steve/manager");
            
            if ($response->successful()) {
                return ["status" => "success", "message" => "Interface web accessible", "http_code" => $response->status()];
            } else {
                return ["status" => "warning", "message" => "Interface web répond avec HTTP " . $response->status()];
            }
        } catch (\Exception $e) {
            return ["status" => "error", "message" => "Erreur interface web: " . $e->getMessage()];
        }
    }

    /**
     * Test d'authentification
     */
    public function testAuthentication()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . "/steve/manager");
            
            if ($response->successful()) {
                return ["status" => "success", "message" => "Authentification fonctionnelle", "http_code" => $response->status()];
            } else {
                return ["status" => "error", "message" => "Authentification échouée (HTTP " . $response->status() . ")"];
            }
        } catch (\Exception $e) {
            return ["status" => "error", "message" => "Erreur authentification: " . $e->getMessage()];
        }
    }

    /**
     * Test de l'endpoint OCPP SOAP
     */
    public function testOcppSoap()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . "/steve/services/CentralSystemService");
            
            if ($response->successful()) {
                return ["status" => "success", "message" => "Endpoint OCPP SOAP accessible", "http_code" => $response->status()];
            } else {
                return ["status" => "warning", "message" => "Endpoint OCPP SOAP répond avec HTTP " . $response->status()];
            }
        } catch (\Exception $e) {
            return ["status" => "error", "message" => "Erreur endpoint OCPP SOAP: " . $e->getMessage()];
        }
    }

    /**
     * Test de l'endpoint WebSocket
     */
    public function testWebSocket()
    {
        $wsUrl = str_replace("http://", "ws://", $this->baseUrl) . "/steve/websocket/CentralSystemService";
        return ["status" => "info", "message" => "WebSocket URL: $wsUrl", "note" => "WebSocket nécessite une connexion active pour être testé"];
    }

    /**
     * Obtenir un rapport détaillé
     */
    public function getDetailedReport()
    {
        $results = $this->testFullConnection();
        
        $report = "=== RAPPORT DE CONNEXION STEVE ===\n";
        $report .= "URL: " . $this->baseUrl . "\n";
        $report .= "Utilisateur: " . $this->username . "\n";
        $report .= "Timeout: " . $this->timeout . "s\n\n";
        
        foreach ($results as $test => $result) {
            $status = $result["status"];
            $message = $result["message"];
            
            $icon = match($status) {
                "success" => "✅",
                "warning" => "⚠️",
                "error" => "❌",
                "info" => "ℹ️",
                default => "❓"
            };
            
            $report .= "$icon $test: $message\n";
            if (isset($result["http_code"])) {
                $report .= "   Code HTTP: " . $result["http_code"] . "\n";
            }
            if (isset($result["note"])) {
                $report .= "   Note: " . $result["note"] . "\n";
            }
        }
        
        return $report;
    }
}