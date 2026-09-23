<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestReservationLive extends Command
{
    protected $signature = 'test:reservation-live';
    protected $description = 'Test de réservation en direct avec simulation de requête';

    public function handle()
    {
        $this->info('🔍 Test de Réservation en Direct');
        $this->info('================================');

        try {
            // Récupérer une borne de recharge
            $chargingPoint = ChargingPoint::with(['pricingPlan'])->first();
            if (!$chargingPoint) {
                $this->error("❌ Aucune borne de recharge trouvée");
                return;
            }

            $this->info("✅ Borne trouvée: {$chargingPoint->name} (ID: {$chargingPoint->id})");

            // Récupérer le plan tarifaire
            $pricingPlan = $chargingPoint->pricingPlan ?? PricingPlan::where('is_active', true)->first();
            if (!$pricingPlan) {
                $this->error("❌ Aucun plan tarifaire trouvé");
                return;
            }

            $this->info("✅ Plan tarifaire: {$pricingPlan->name}");

            // Simuler une requête de réservation
            $this->info("\n1. ✅ Simulation de requête de réservation...");
            
            $requestData = [
                'type' => 'duration',
                'value' => 30,
                'time' => 'immediate',
                'customer_name' => 'Test User',
                'customer_email' => 'test@example.com',
                'customer_phone' => '0123456789',
                '_token' => csrf_token()
            ];

            $this->info("   📋 Données de requête: " . json_encode($requestData));

            // Test de validation
            $this->info("\n2. ✅ Test de validation...");
            
            $validator = \Validator::make($requestData, [
                'type' => 'required|in:energy,duration',
                'value' => 'required|numeric|min:1',
                'time' => 'required|string',
                'customer_name' => 'nullable|string|max:255',
                'customer_email' => 'nullable|email|max:255',
                'customer_phone' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                $this->error("❌ Validation échouée: " . json_encode($validator->errors()));
                return;
            }
            
            $this->info("   ✅ Validation réussie");

            // Test de création via le contrôleur
            $this->info("\n3. ✅ Test via PublicChargingOfferWebController...");
            
            try {
                // Simuler l'appel au contrôleur
                $controller = new \App\Http\Controllers\PublicChargingOfferWebController(
                    app(\App\Services\PricingPlanService::class),
                    app(\App\Services\TransactionCalculator::class)
                );

                // Créer une requête simulée
                $request = new \Illuminate\Http\Request();
                $request->merge($requestData);
                $request->setMethod('POST');
                $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
                $request->headers->set('Accept', 'application/json');

                $this->info("   📝 Appel du contrôleur...");
                
                // Appeler la méthode storeReservation
                $response = $controller->storeReservation($request, $chargingPoint->id);
                
                $this->info("   ✅ Réponse du contrôleur: " . $response->getContent());
                
                if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                    $this->info("   ✅ Réservation créée avec succès via le contrôleur");
                } else {
                    $this->error("   ❌ Erreur du contrôleur: " . $response->getStatusCode());
                }

            } catch (\Exception $e) {
                $this->error("❌ Erreur lors de l'appel du contrôleur: " . $e->getMessage());
                $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
                $this->error("Stack trace: " . $e->getTraceAsString());
            }

            // Test des routes
            $this->info("\n4. ✅ Test des routes...");
            
            $routes = [
                'reservations.store' => "POST /reservations/store/{$chargingPoint->id}",
                'offer.reserve' => "POST /offer/{$chargingPoint->id}/reserve",
                'public.charging-point.offer.store-reservation.web' => "POST /offer/{$chargingPoint->id}/reservation",
                'public.charging-point.offer.store-reservation' => "POST /public/charging-point/{$chargingPoint->id}/offer/store-reservation"
            ];

            foreach ($routes as $name => $route) {
                try {
                    $routeUrl = route($name, $chargingPoint->id);
                    $this->info("   ✅ Route '$name': $routeUrl");
                } catch (\Exception $e) {
                    $this->error("   ❌ Route '$name' invalide: " . $e->getMessage());
                }
            }

            $this->info("\n🎯 TEST TERMINÉ");
            $this->info("===============");
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR GLOBALE: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
