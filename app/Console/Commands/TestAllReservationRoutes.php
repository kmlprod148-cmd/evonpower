<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PublicChargingOfferWebController;
use App\Services\PricingPlanService;
use App\Services\TransactionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class TestAllReservationRoutes extends Command
{
    protected $signature = 'test:all-reservation-routes';
    protected $description = 'Test de toutes les routes de réservation possibles';

    public function handle()
    {
        $this->info('🔍 Test de Toutes les Routes de Réservation');
        $this->info('==========================================');

        try {
            $this->info('1. ✅ Test des routes publiques...');
            
            $publicRoutes = [
                'reservations.store' => 'POST /reservations/store/{chargingPoint}',
                'offer.reserve' => 'POST /offer/{id}/reserve',
                'public.charging-point.offer.store-reservation.web' => 'POST /offer/{id}/reservation',
                'public.charging-point.offer.store-reservation' => 'POST /public/charging-point/{id}/offer/store-reservation'
            ];

            foreach ($publicRoutes as $name => $route) {
                try {
                    $routeUrl = route($name, 1);
                    $this->info("   ✅ Route '$name': $routeUrl");
                } catch (\Exception $e) {
                    $this->error("   ❌ Route '$name' invalide: " . $e->getMessage());
                }
            }

            $this->info('\n2. ✅ Test du contrôleur PublicChargingOfferWebController...');
            
            try {
                $pricingPlanService = App::make(PricingPlanService::class);
                $transactionCalculator = App::make(TransactionCalculator::class);
                $controller = new PublicChargingOfferWebController($pricingPlanService, $transactionCalculator);
                $this->info("   ✅ Contrôleur PublicChargingOfferWebController créé");
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur lors de la création du contrôleur: " . $e->getMessage());
            }

            $this->info('\n3. ✅ Test de la méthode storeReservation...');
            
            try {
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

                $response = $controller->storeReservation($request, 1);
                
                $this->info("   📊 Code de réponse: " . $response->getStatusCode());
                $this->info("   📊 Contenu: " . $response->getContent());
                
                if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                    $this->info("   ✅ Réservation créée avec succès");
                } else {
                    $this->error("   ❌ Erreur du contrôleur: " . $response->getStatusCode());
                }

            } catch (\Exception $e) {
                $this->error("❌ Erreur lors du test: " . $e->getMessage());
                $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
            }

            $this->info('\n4. ✅ Vérification des routes problématiques...');
            
            $problematicRoutes = [
                'reservations.show' => 'GET /reservations/{reservation}',
                'reservations.edit' => 'GET /reservations/{reservation}/edit',
                'reservations.cancel' => 'POST /reservations/{reservation}/cancel',
                'reservations.confirm' => 'POST /reservations/{reservation}/confirm',
                'reservations.start-charge' => 'POST /reservations/{reservation}/start-charge',
                'reservations.start-charging' => 'POST /reservations/{reservation}/start-charging',
                'reservations.stop-charge' => 'POST /reservations/{reservation}/stop-charge',
                'reservations.end-charging' => 'POST /reservations/{reservation}/end-charging'
            ];

            foreach ($problematicRoutes as $name => $route) {
                try {
                    $routeUrl = route($name, 1);
                    $this->warn("   ⚠️  Route '$name': $routeUrl (utilise ReservationController)");
                } catch (\Exception $e) {
                    $this->error("   ❌ Route '$name' invalide: " . $e->getMessage());
                }
            }

            $this->info('\n🎯 TEST TERMINÉ');
            $this->info('===============');
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR GLOBALE: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
