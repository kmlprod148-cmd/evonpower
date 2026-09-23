<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PublicChargingOfferWebController;
use App\Services\PricingPlanService;
use App\Services\TransactionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class TestProductionIssueFinal extends Command
{
    protected $signature = 'test:production-issue-final';
    protected $description = 'Test final pour identifier le problème de production';

    public function handle()
    {
        $this->info('🔍 Test Final du Problème de Production');
        $this->info('======================================');

        try {
            $this->info('1. ✅ Test de la réservation avec toutes les routes...');
            
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
                            
                            // Tester cette route
                            try {
                                $pricingPlanService = App::make(PricingPlanService::class);
                                $transactionCalculator = App::make(TransactionCalculator::class);
                                $controller = new PublicChargingOfferWebController($pricingPlanService, $transactionCalculator);
                                
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
                                
                                $response = $controller->storeReservation($request, 1);
                                
                                $this->info("      📊 Code: " . $response->getStatusCode());
                                if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                                    $this->info("      ✅ Réservation créée avec succès");
                                } else {
                                    $this->error("      ❌ Erreur: " . $response->getStatusCode());
                                }
                                
                            } catch (\Exception $e) {
                                $this->error("      ❌ Erreur lors du test: " . $e->getMessage());
                            }
                            
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

            $this->info('\n2. ✅ Vérification des routes problématiques...');
            
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

            $this->info('\n3. ✅ Vérification des logs...');
            
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $this->info("   📊 Fichier de log: $logFile");
                $this->info("   📊 Taille: " . filesize($logFile) . " bytes");
                
                // Lire les dernières lignes du log
                $lines = file($logFile);
                $lastLines = array_slice($lines, -20);
                
                $this->info("   📊 Dernières lignes du log:");
                foreach ($lastLines as $line) {
                    if (trim($line) !== '') {
                        $this->info("      " . trim($line));
                    }
                }
            } else {
                $this->warn("   ⚠️  Fichier de log introuvable");
            }

            $this->info('\n4. ✅ Vérification de la base de données...');
            
            try {
                $reservations = \App\Models\Reservation::latest()->take(3)->get();
                $this->info("   📊 Dernières réservations:");
                foreach ($reservations as $reservation) {
                    $this->info("      - ID: {$reservation->id}, Type: {$reservation->type}, Value: {$reservation->value}");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur base de données: " . $e->getMessage());
            }

            $this->info('\n5. ✅ Vérification des routes de réservation publiques...');
            
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
            Log::error('Erreur lors du test final du problème de production', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
