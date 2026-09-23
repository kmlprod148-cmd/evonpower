<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class TestProductionRoutesFinal extends Command
{
    protected $signature = 'test:production-routes-final';
    protected $description = 'Test final des routes de production pour identifier le problème';

    public function handle()
    {
        $this->info('🔍 Test Final des Routes de Production');
        $this->info('=====================================');

        try {
            $this->info('1. ✅ Vérification de toutes les routes POST...');
            
            $allRoutes = Route::getRoutes();
            $postRoutes = [];
            
            foreach ($allRoutes as $route) {
                if (in_array('POST', $route->methods())) {
                    $postRoutes[] = [
                        'name' => $route->getName(),
                        'uri' => $route->uri(),
                        'action' => $route->getActionName(),
                        'middleware' => $route->gatherMiddleware()
                    ];
                }
            }

            $this->info("   📊 Nombre total de routes POST: " . count($postRoutes));

            $this->info('\n2. ✅ Routes POST contenant "reservation" ou "offer"...');
            
            $relevantRoutes = [];
            foreach ($postRoutes as $route) {
                if (strpos($route['uri'], 'reservation') !== false || 
                    strpos($route['uri'], 'offer') !== false ||
                    strpos($route['name'], 'reservation') !== false ||
                    strpos($route['name'], 'offer') !== false) {
                    $relevantRoutes[] = $route;
                }
            }

            foreach ($relevantRoutes as $route) {
                $this->info("   📋 Route: {$route['name']}");
                $this->info("      URI: {$route['uri']}");
                $this->info("      Action: {$route['action']}");
                $this->info("      Middleware: " . implode(', ', $route['middleware']));
                $this->info("");
            }

            $this->info('\n3. ✅ Routes utilisant ReservationController...');
            
            $reservationControllerRoutes = [];
            foreach ($postRoutes as $route) {
                if (strpos($route['action'], 'ReservationController') !== false) {
                    $reservationControllerRoutes[] = $route;
                }
            }

            if (count($reservationControllerRoutes) > 0) {
                $this->warn("   ⚠️  Routes utilisant ReservationController:");
                foreach ($reservationControllerRoutes as $route) {
                    $this->warn("      - {$route['name']}: {$route['uri']}");
                    $this->warn("        Action: {$route['action']}");
                    $this->warn("        Middleware: " . implode(', ', $route['middleware']));
                }
            } else {
                $this->info("   ✅ Aucune route POST n'utilise ReservationController");
            }

            $this->info('\n4. ✅ Routes utilisant PublicChargingOfferWebController...');
            
            $publicControllerRoutes = [];
            foreach ($postRoutes as $route) {
                if (strpos($route['action'], 'PublicChargingOfferWebController') !== false) {
                    $publicControllerRoutes[] = $route;
                }
            }

            if (count($publicControllerRoutes) > 0) {
                $this->info("   ✅ Routes utilisant PublicChargingOfferWebController:");
                foreach ($publicControllerRoutes as $route) {
                    $this->info("      - {$route['name']}: {$route['uri']}");
                    $this->info("        Action: {$route['action']}");
                    $this->info("        Middleware: " . implode(', ', $route['middleware']));
                }
            } else {
                $this->error("   ❌ Aucune route POST n'utilise PublicChargingOfferWebController");
            }

            $this->info('\n5. ✅ Vérification des routes problématiques...');
            
            $problematicRoutes = [
                'reservations.calculate-cost' => 'POST /reservations/calculate-cost/{chargingPoint}',
                'reservations.enhanced.store' => 'POST /reservations/enhanced',
                'reservations.cancel' => 'POST /reservations/{reservation}/cancel',
                'reservations.confirm' => 'POST /reservations/{reservation}/confirm',
                'reservations.start-charge' => 'POST /reservations/{reservation}/start-charge',
                'reservations.start-charging' => 'POST /reservations/{reservation}/start-charging',
                'reservations.stop-charge' => 'POST /reservations/{reservation}/stop-charge',
                'reservations.end-charging' => 'POST /reservations/{reservation}/end-charging'
            ];

            foreach ($problematicRoutes as $name => $description) {
                try {
                    $route = Route::getRoutes()->getByName($name);
                    if ($route) {
                        $action = $route->getActionName();
                        if (strpos($action, 'ReservationController') !== false) {
                            $this->warn("   ⚠️  Route '$name': $action (utilise ReservationController)");
                        } else {
                            $this->info("   ✅ Route '$name': $action (bon contrôleur)");
                        }
                    } else {
                        $this->info("   ✅ Route '$name' introuvable (bon)");
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ Erreur route '$name': " . $e->getMessage());
                }
            }

            $this->info('\n6. ✅ Vérification des routes de réservation publiques...');
            
            $publicReservationRoutes = [
                'reservations.store' => 'POST /reservations/store/{chargingPoint}',
                'offer.reserve' => 'POST /offer/{id}/reserve',
                'public.charging-point.offer.store-reservation.web' => 'POST /offer/{id}/reservation',
                'public.charging-point.offer.store-reservation' => 'POST /public/charging-point/{id}/offer/store-reservation'
            ];

            foreach ($publicReservationRoutes as $name => $description) {
                try {
                    $route = Route::getRoutes()->getByName($name);
                    if ($route) {
                        $action = $route->getActionName();
                        if (strpos($action, 'PublicChargingOfferWebController') !== false) {
                            $this->info("   ✅ Route '$name': $action (bon contrôleur)");
                        } else {
                            $this->error("   ❌ Route '$name': $action (MAUVAIS CONTRÔLEUR)");
                        }
                    } else {
                        $this->error("   ❌ Route '$name' introuvable");
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ Erreur route '$name': " . $e->getMessage());
                }
            }

            $this->info('\n🎯 TEST TERMINÉ');
            $this->info('===============');
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
            Log::error('Erreur lors du test final des routes de production', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
