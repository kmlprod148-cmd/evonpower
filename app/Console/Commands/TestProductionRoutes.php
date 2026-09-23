<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class TestProductionRoutes extends Command
{
    protected $signature = 'test:production-routes';
    protected $description = 'Test des routes de production pour identifier le problème';

    public function handle()
    {
        $this->info('🔍 Test des Routes de Production');
        $this->info('===============================');

        try {
            $this->info('1. ✅ Vérification de toutes les routes POST...');
            
            $postRoutes = [];
            foreach (Route::getRoutes() as $route) {
                if (in_array('POST', $route->methods())) {
                    $postRoutes[] = [
                        'name' => $route->getName(),
                        'uri' => $route->uri(),
                        'action' => $route->getActionName()
                    ];
                }
            }

            $this->info("   📊 Nombre de routes POST: " . count($postRoutes));

            $this->info('\n2. ✅ Routes POST contenant "reservation"...');
            
            $reservationRoutes = [];
            foreach ($postRoutes as $route) {
                if (strpos($route['uri'], 'reservation') !== false || 
                    strpos($route['name'], 'reservation') !== false ||
                    strpos($route['action'], 'Reservation') !== false) {
                    $reservationRoutes[] = $route;
                }
            }

            foreach ($reservationRoutes as $route) {
                $this->info("   📋 Route: {$route['name']}");
                $this->info("      URI: {$route['uri']}");
                $this->info("      Action: {$route['action']}");
                $this->info("");
            }

            $this->info('\n3. ✅ Routes POST contenant "offer"...');
            
            $offerRoutes = [];
            foreach ($postRoutes as $route) {
                if (strpos($route['uri'], 'offer') !== false || 
                    strpos($route['name'], 'offer') !== false) {
                    $offerRoutes[] = $route;
                }
            }

            foreach ($offerRoutes as $route) {
                $this->info("   📋 Route: {$route['name']}");
                $this->info("      URI: {$route['uri']}");
                $this->info("      Action: {$route['action']}");
                $this->info("");
            }

            $this->info('\n4. ✅ Vérification des contrôleurs utilisés...');
            
            $controllers = [];
            foreach ($postRoutes as $route) {
                if (strpos($route['action'], 'ReservationController') !== false) {
                    $controllers[] = $route;
                }
            }

            if (count($controllers) > 0) {
                $this->warn("   ⚠️  Routes utilisant ReservationController:");
                foreach ($controllers as $route) {
                    $this->warn("      - {$route['name']}: {$route['uri']}");
                }
            } else {
                $this->info("   ✅ Aucune route POST n'utilise ReservationController");
            }

            $this->info('\n🎯 TEST TERMINÉ');
            $this->info('===============');
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
