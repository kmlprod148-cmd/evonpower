<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PublicChargingOfferWebController;
use App\Services\PricingPlanService;
use App\Services\TransactionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class TestFinalReservation extends Command
{
    protected $signature = 'test:final-reservation';
    protected $description = 'Test final de la réservation pour identifier le problème';

    public function handle()
    {
        $this->info('🔍 Test Final de la Réservation');
        $this->info('==============================');

        try {
            $this->info('1. ✅ Test de la réservation avec logging détaillé...');
            
            // Activer le logging détaillé
            Log::info('Test de réservation démarré');
            
            $pricingPlanService = App::make(PricingPlanService::class);
            $transactionCalculator = App::make(TransactionCalculator::class);
            $controller = new PublicChargingOfferWebController($pricingPlanService, $transactionCalculator);
            
            Log::info('Contrôleur créé avec succès');
            
            $request = new Request();
            $request->merge([
                'type' => 'duration',
                'value' => 30,
                'time' => 'immediate',
                'customer_name' => 'Test User Final',
                'customer_email' => 'testfinal@example.com',
                'customer_phone' => '0123456789'
            ]);
            $request->setMethod('POST');
            $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
            $request->headers->set('Accept', 'application/json');
            
            Log::info('Requête créée', $request->all());
            
            $this->info("   📋 Données de requête: " . json_encode($request->all()));
            
            $response = $controller->storeReservation($request, 1);
            
            Log::info('Réponse reçue', [
                'status' => $response->getStatusCode(),
                'content' => $response->getContent()
            ]);
            
            $this->info("   📊 Code de réponse: " . $response->getStatusCode());
            $this->info("   📊 Contenu: " . $response->getContent());
            
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $this->info("   ✅ Réservation créée avec succès");
                Log::info('Réservation créée avec succès');
            } else {
                $this->error("   ❌ Erreur du contrôleur: " . $response->getStatusCode());
                Log::error('Erreur du contrôleur', [
                    'status' => $response->getStatusCode(),
                    'content' => $response->getContent()
                ]);
            }

            $this->info('\n2. ✅ Vérification des logs...');
            
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $this->info("   📊 Fichier de log: $logFile");
                $this->info("   📊 Taille: " . filesize($logFile) . " bytes");
                
                // Lire les dernières lignes du log
                $lines = file($logFile);
                $lastLines = array_slice($lines, -10);
                
                $this->info("   📊 Dernières lignes du log:");
                foreach ($lastLines as $line) {
                    $this->info("      " . trim($line));
                }
            } else {
                $this->warn("   ⚠️  Fichier de log introuvable");
            }

            $this->info('\n3. ✅ Vérification de la base de données...');
            
            try {
                $reservations = \App\Models\Reservation::latest()->take(5)->get();
                $this->info("   📊 Dernières réservations:");
                foreach ($reservations as $reservation) {
                    $this->info("      - ID: {$reservation->id}, Type: {$reservation->type}, Value: {$reservation->value}");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur base de données: " . $e->getMessage());
            }

            $this->info('\n🎯 TEST TERMINÉ');
            $this->info('===============');
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
            Log::error('Erreur lors du test final', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
