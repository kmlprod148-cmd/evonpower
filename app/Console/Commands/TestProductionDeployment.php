<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

class TestProductionDeployment extends Command
{
    protected $signature = 'test:production-deployment';
    protected $description = 'Test du déploiement en production';

    public function handle()
    {
        $this->info('🔍 Test du Déploiement en Production');
        $this->info('====================================');

        try {
            $this->info('1. ✅ Vérification des caches...');
            
            $this->info("   📊 Cache config: " . (Cache::has('config') ? 'Oui' : 'Non'));
            $this->info("   📊 Cache routes: " . (Cache::has('routes') ? 'Oui' : 'Non'));
            $this->info("   📊 Cache views: " . (Cache::has('views') ? 'Oui' : 'Non'));

            $this->info('\n2. ✅ Vérification des routes de réservation...');
            
            $reservationRoutes = [
                'reservations.store' => 'POST /reservations/store/{chargingPoint}',
                'offer.reserve' => 'POST /offer/{id}/reserve',
                'public.charging-point.offer.store-reservation.web' => 'POST /offer/{id}/reservation',
                'public.charging-point.offer.store-reservation' => 'POST /public/charging-point/{id}/offer/store-reservation'
            ];

            foreach ($reservationRoutes as $name => $description) {
                try {
                    $route = Route::getRoutes()->getByName($name);
                    if ($route) {
                        $action = $route->getActionName();
                        if (strpos($action, 'PublicChargingOfferWebController') !== false) {
                            $this->info("   ✅ Route '$name': $action");
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

            $this->info('\n3. ✅ Vérification des routes problématiques...');
            
            $problematicRoutes = [
                'reservations.show' => 'GET /reservations/{reservation}',
                'reservations.edit' => 'GET /reservations/{reservation}/edit',
                'reservations.cancel' => 'POST /reservations/{reservation}/cancel',
                'reservations.confirm' => 'POST /reservations/{reservation}/confirm'
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

            $this->info('\n4. ✅ Vérification de la configuration...');
            
            $this->info("   📊 App Name: " . Config::get('app.name'));
            $this->info("   📊 App Env: " . Config::get('app.env'));
            $this->info("   📊 App Debug: " . (Config::get('app.debug') ? 'Oui' : 'Non'));

            $this->info('\n5. ✅ Vérification des services...');
            
            try {
                $pricingPlanService = app(\App\Services\PricingPlanService::class);
                $this->info("   ✅ PricingPlanService disponible");
            } catch (\Exception $e) {
                $this->error("   ❌ PricingPlanService: " . $e->getMessage());
            }

            try {
                $transactionCalculator = app(\App\Services\TransactionCalculator::class);
                $this->info("   ✅ TransactionCalculator disponible");
            } catch (\Exception $e) {
                $this->error("   ❌ TransactionCalculator: " . $e->getMessage());
            }

            $this->info('\n🎯 TEST TERMINÉ');
            $this->info('===============');
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
