<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PublicChargingOfferWebController;
use App\Services\PricingPlanService;
use App\Services\TransactionCalculator;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class DiagnosticReservation extends Command
{
    protected $signature = 'diagnostic:reservation';
    protected $description = 'Diagnostic complet de l\'erreur de réservation';

    public function handle()
    {
        $this->info('🔍 DIAGNOSTIC COMPLET - Erreur de Réservation');
        $this->info('===============================================');

        try {
            // 1. Vérification de la base de données
            $this->checkDatabase();
            
            // 2. Vérification des routes
            $this->checkRoutes();
            
            // 3. Test des contrôleurs
            $this->testControllers();
            
            // 4. Test de création de réservation
            $this->testReservationCreation();
            
            // 5. Vérification des logs
            $this->checkLogs();
            
            // 6. Test des services
            $this->testServices();
            
            // 7. Vérification des contraintes
            $this->checkConstraints();
            
            $this->info('\n🎯 DIAGNOSTIC TERMINÉ');
            $this->info('======================');
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR LORS DU DIAGNOSTIC: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
            Log::error('Erreur lors du diagnostic de réservation', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function checkDatabase()
    {
        $this->info('\n1. 🔍 Vérification de la base de données...');
        
        try {
            // Test de connexion
            DB::connection()->getPdo();
            $this->info('   ✅ Connexion à la base de données OK');
            
            // Vérification des tables
            $tables = ['charging_points', 'pricing_plans', 'reservations', 'transactions', 'transaction_repartitions'];
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $this->info("   ✅ Table '$table' existe");
                } else {
                    $this->error("   ❌ Table '$table' manquante");
                }
            }
            
            // Vérification de la colonne metadata
            if (Schema::hasColumn('transactions', 'metadata')) {
                $this->info('   ✅ Colonne metadata existe dans transactions');
            } else {
                $this->error('   ❌ Colonne metadata manquante dans transactions');
            }
            
            // Vérification des données
            $chargingPoints = ChargingPoint::count();
            $pricingPlans = PricingPlan::count();
            $reservations = Reservation::count();
            $transactions = Transaction::count();
            
            $this->info("   📊 ChargingPoints: $chargingPoints");
            $this->info("   📊 PricingPlans: $pricingPlans");
            $this->info("   📊 Reservations: $reservations");
            $this->info("   📊 Transactions: $transactions");
            
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur base de données: " . $e->getMessage());
        }
    }

    private function checkRoutes()
    {
        $this->info('\n2. 🔍 Vérification des routes...');
        
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
                    $this->info("   ✅ Route '$name': $action");
                } else {
                    $this->error("   ❌ Route '$name' introuvable");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur route '$name': " . $e->getMessage());
            }
        }
    }

    private function testControllers()
    {
        $this->info('\n3. 🔍 Test des contrôleurs...');
        
        try {
            // Test du PublicChargingOfferWebController
            $pricingPlanService = App::make(PricingPlanService::class);
            $transactionCalculator = App::make(TransactionCalculator::class);
            $controller = new PublicChargingOfferWebController($pricingPlanService, $transactionCalculator);
            
            $this->info('   ✅ PublicChargingOfferWebController créé');
            $this->info('   ✅ PricingPlanService injecté');
            $this->info('   ✅ TransactionCalculator injecté');
            
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur contrôleur: " . $e->getMessage());
        }
    }

    private function testReservationCreation()
    {
        $this->info('\n4. 🔍 Test de création de réservation...');
        
        try {
            // Récupérer une borne de test
            $chargingPoint = ChargingPoint::first();
            if (!$chargingPoint) {
                $this->error('   ❌ Aucune borne de recharge trouvée');
                return;
            }
            
            $this->info("   📊 Borne de test: ID {$chargingPoint->id}");
            
            // Récupérer un plan tarifaire
            $pricingPlan = PricingPlan::first();
            if (!$pricingPlan) {
                $this->error('   ❌ Aucun plan tarifaire trouvé');
                return;
            }
            
            $this->info("   📊 Plan tarifaire: ID {$pricingPlan->id}");
            
            // Test de création de réservation
            $pricingPlanService = App::make(PricingPlanService::class);
            $transactionCalculator = App::make(TransactionCalculator::class);
            $controller = new PublicChargingOfferWebController($pricingPlanService, $transactionCalculator);
            
            $request = new Request();
            $request->merge([
                'type' => 'duration',
                'value' => 30,
                'time' => 'immediate',
                'customer_name' => 'Test Diagnostic',
                'customer_email' => 'test@diagnostic.com',
                'customer_phone' => '0123456789'
            ]);
            $request->setMethod('POST');
            $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
            $request->headers->set('Accept', 'application/json');
            
            $response = $controller->storeReservation($request, $chargingPoint->id);
            
            $this->info("   📊 Code de réponse: " . $response->getStatusCode());
            
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                $this->info("   ✅ Réservation créée avec succès");
                
                $content = $response->getContent();
                $data = json_decode($content, true);
                
                if (isset($data['reservation_id'])) {
                    $this->info("   📊 ID Réservation: " . $data['reservation_id']);
                }
                if (isset($data['transaction_id'])) {
                    $this->info("   📊 ID Transaction: " . $data['transaction_id']);
                }
                if (isset($data['cost'])) {
                    $this->info("   📊 Coût: " . $data['cost']);
                }
            } else {
                $this->error("   ❌ Erreur: " . $response->getStatusCode());
                $this->error("   📊 Contenu: " . $response->getContent());
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur lors du test: " . $e->getMessage());
            $this->error("   📊 Fichier: " . $e->getFile() . ":" . $e->getLine());
        }
    }

    private function checkLogs()
    {
        $this->info('\n5. 🔍 Vérification des logs...');
        
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $this->info("   📊 Fichier de log: $logFile");
            $this->info("   📊 Taille: " . filesize($logFile) . " bytes");
            
            if (filesize($logFile) > 0) {
                $lines = file($logFile);
                $lastLines = array_slice($lines, -10);
                
                $this->info("   📊 Dernières lignes du log:");
                foreach ($lastLines as $line) {
                    if (trim($line) !== '') {
                        $this->info("      " . trim($line));
                    }
                }
            } else {
                $this->info("   📊 Fichier de log vide");
            }
        } else {
            $this->warn("   ⚠️  Fichier de log introuvable");
        }
    }

    private function testServices()
    {
        $this->info('\n6. 🔍 Test des services...');
        
        try {
            // Test PricingPlanService
            $pricingPlanService = App::make(PricingPlanService::class);
            $this->info('   ✅ PricingPlanService disponible');
            
            // Test TransactionCalculator
            $transactionCalculator = App::make(TransactionCalculator::class);
            $this->info('   ✅ TransactionCalculator disponible');
            
            // Test FinancialService
            $financialService = App::make(\App\Services\FinancialService::class);
            $this->info('   ✅ FinancialService disponible');
            
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur service: " . $e->getMessage());
        }
    }

    private function checkConstraints()
    {
        $this->info('\n7. 🔍 Vérification des contraintes...');
        
        try {
            // Vérifier les contraintes de clé étrangère
            $reservations = Reservation::with(['chargingPoint', 'pricingPlan'])->latest()->take(3)->get();
            
            $this->info("   📊 Dernières réservations:");
            foreach ($reservations as $reservation) {
                $this->info("      - ID: {$reservation->id}");
                $this->info("        Type: {$reservation->reservation_type}");
                $this->info("        Value: {$reservation->reservation_value}");
                $this->info("        Status: " . $reservation->status->value);
                $this->info("        ChargingPoint: " . ($reservation->chargingPoint ? $reservation->chargingPoint->id : 'NULL'));
                $this->info("        PricingPlan: " . ($reservation->pricingPlan ? $reservation->pricingPlan->id : 'NULL'));
            }
            
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur contraintes: " . $e->getMessage());
        }
    }
}
