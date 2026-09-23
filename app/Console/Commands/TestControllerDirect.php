<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PublicChargingOfferWebController;
use App\Services\PricingPlanService;
use App\Services\TransactionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class TestControllerDirect extends Command
{
    protected $signature = 'test:controller-direct';
    protected $description = 'Test direct du contrôleur avec requête simulée';

    public function handle()
    {
        $this->info('🔍 Test Direct du Contrôleur');
        $this->info('============================');

        try {
            $this->info('1. ✅ Test des services...');
            
            // Vérifier que les services existent
            $pricingPlanService = App::make(PricingPlanService::class);
            $this->info("   ✅ PricingPlanService disponible");
            
            $transactionCalculator = App::make(TransactionCalculator::class);
            $this->info("   ✅ TransactionCalculator disponible");

            $this->info('\n2. ✅ Test du contrôleur...');
            
            // Créer le contrôleur
            $controller = new PublicChargingOfferWebController($pricingPlanService, $transactionCalculator);
            $this->info("   ✅ Contrôleur créé");

            $this->info('\n3. ✅ Test de la requête simulée...');
            
            // Créer une requête simulée
            $request = new Request();
            $request->merge([
                'type' => 'duration',
                'value' => 30,
                'time' => 'immediate',
                'customer_name' => 'Test User',
                'customer_email' => 'test@example.com',
                'customer_phone' => '0123456789'
            ]);
            $request->setMethod('POST');
            $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
            $request->headers->set('Accept', 'application/json');

            $this->info("   📋 Données de requête: " . json_encode($request->all()));

            $this->info('\n4. ✅ Test de la méthode storeReservation...');
            
            try {
                // Appeler la méthode storeReservation
                $response = $controller->storeReservation($request, 1);
                
                $this->info("   📊 Code de réponse: " . $response->getStatusCode());
                $this->info("   📊 Contenu de la réponse: " . $response->getContent());
                
                if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                    $this->info("   ✅ Réservation créée avec succès");
                } else {
                    $this->error("   ❌ Erreur du contrôleur: " . $response->getStatusCode());
                }

            } catch (\Exception $e) {
                $this->error("❌ Erreur lors de l'appel du contrôleur: " . $e->getMessage());
                $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
                $this->error("Stack trace: " . $e->getTraceAsString());
            }

            $this->info('\n🎯 TEST TERMINÉ');
            $this->info('===============');
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR GLOBALE: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
