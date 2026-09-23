<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;

class TestTransactionModel extends Command
{
    protected $signature = 'test:transaction-model';
    protected $description = 'Test du modèle Transaction';

    public function handle()
    {
        $this->info('🔍 Test du Modèle Transaction');
        $this->info('============================');

        try {
            // Test 1: Vérifier si le modèle existe
            $this->info('1. ✅ Test d\'existence du modèle...');
            
            if (class_exists(Transaction::class)) {
                $this->info("   ✅ Modèle Transaction existe");
            } else {
                $this->error("   ❌ Modèle Transaction n'existe pas");
                return;
            }

            // Test 2: Créer une instance
            $this->info('\n2. ✅ Test de création d\'instance...');
            
            try {
                $transaction = new Transaction();
                $this->info("   ✅ Instance Transaction créée");
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur lors de la création: " . $e->getMessage());
                return;
            }

            // Test 3: Récupérer des données de base
            $this->info('\n3. ✅ Test des données de base...');
            
            $chargingPoint = ChargingPoint::first();
            $pricingPlan = PricingPlan::first();
            
            if ($chargingPoint) {
                $this->info("   ✅ ChargingPoint trouvé: ID {$chargingPoint->id}");
            } else {
                $this->error("   ❌ Aucun ChargingPoint trouvé");
                return;
            }
            
            if ($pricingPlan) {
                $this->info("   ✅ PricingPlan trouvé: ID {$pricingPlan->id}");
            } else {
                $this->error("   ❌ Aucun PricingPlan trouvé");
                return;
            }

            // Test 4: Créer une transaction de test
            $this->info('\n4. ✅ Test de création de transaction...');
            
            try {
                $transaction = new Transaction();
                $transaction->transaction_id = 'TEST-' . strtoupper(uniqid());
                $transaction->charging_point_id = $chargingPoint->id;
                $transaction->user_id = null;
                $transaction->start_timestamp = now();
                $transaction->status = 'pending';
                $transaction->auth_method = 'public_reservation';
                $transaction->pricing_plan_id = $pricingPlan->id;
                $transaction->meter_start = 0;
                $transaction->price_total = 10.0;
                $transaction->currency = 'EUR';
                
                $this->info("   ✅ Transaction de test créée (non sauvegardée)");
                $this->info("   ✅ Transaction ID: {$transaction->transaction_id}");
                $this->info("   ✅ Charging Point ID: {$transaction->charging_point_id}");
                $this->info("   ✅ Pricing Plan ID: {$transaction->pricing_plan_id}");
                $this->info("   ✅ Price Total: {$transaction->price_total}");
                
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur lors de la création de transaction: " . $e->getMessage());
                $this->error("   Fichier: " . $e->getFile() . ":" . $e->getLine());
            }

            // Test 5: Vérifier les colonnes de la table
            $this->info('\n5. ✅ Test des colonnes de la table...');
            
            try {
                $schema = \DB::select('PRAGMA table_info(transactions)');
                $this->info("   📋 Colonnes trouvées: " . count($schema));
                
                foreach ($schema as $column) {
                    $this->info("   - {$column->name}: {$column->type}");
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur lors de la vérification de la table: " . $e->getMessage());
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
