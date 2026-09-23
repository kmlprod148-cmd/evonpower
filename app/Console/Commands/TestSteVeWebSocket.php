<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SteVeWebSocketService;

class TestSteVeWebSocket extends Command
{
    protected $signature = "steve:test-websocket {chargeBoxId?}";
    protected $description = "Tester la connexion WebSocket SteVe";

    public function handle()
    {
        $chargeBoxId = $this->argument("chargeBoxId") ?? "test-charge-box";
        
        $this->info("Test de la connexion WebSocket SteVe...");
        $this->info("Charge Box ID: $chargeBoxId");
        
        $websocketService = new SteVeWebSocketService();
        
        // Test de l'URL WebSocket
        $result = $websocketService->testWebSocketConnection($chargeBoxId);
        
        $this->line("Status: " . $result["status"]);
        $this->line("Message: " . $result["message"]);
        $this->line("URL: " . $result["url"]);
        
        if (isset($result["note"])) {
            $this->line("Note: " . $result["note"]);
        }
        
        // Afficher la configuration complète
        $this->line("\n=== CONFIGURATION WEBSOCKET ===");
        $config = $websocketService->getWebSocketConfig();
        
        foreach ($config as $key => $value) {
            $this->line("$key: $value");
        }
        
        return 0;
    }
}