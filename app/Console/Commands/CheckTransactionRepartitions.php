<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\TransactionRepartition;

class CheckTransactionRepartitions extends Command
{
    protected $signature = 'check:transaction-repartitions';
    protected $description = 'Vérifier la table transaction_repartitions';

    public function handle()
    {
        $this->info('🔍 Vérification de la table transaction_repartitions');
        $this->info('================================================');

        try {
            // Test 1: Vérifier si la table existe
            $this->info('1. ✅ Test de l\'existence de la table...');
            
            try {
                $count = DB::table('transaction_repartitions')->count();
                $this->info("   ✅ Table existe, nombre d'enregistrements: {$count}");
            } catch (\Exception $e) {
                $this->error("   ❌ Table n'existe pas: " . $e->getMessage());
                $this->info("   🔧 Création de la table...");
                
                // Créer la table si elle n'existe pas
                DB::statement('
                    CREATE TABLE IF NOT EXISTS transaction_repartitions (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        transaction_id INTEGER NOT NULL,
                        admin_percentage DECIMAL(5,2) DEFAULT 0,
                        integrator_percentage DECIMAL(5,2) DEFAULT 0,
                        operator_percentage DECIMAL(5,2) DEFAULT 0,
                        admin_amount DECIMAL(10,2) DEFAULT 0,
                        integrator_amount DECIMAL(10,2) DEFAULT 0,
                        operator_amount DECIMAL(10,2) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ');
                
                $this->info("   ✅ Table créée avec succès");
            }
            
            // Test 2: Vérifier le modèle
            $this->info("\n2. ✅ Test du modèle TransactionRepartition...");
            
            try {
                $repartition = new TransactionRepartition();
                $this->info("   ✅ Modèle TransactionRepartition peut être instancié");
                
                // Vérifier la méthode createFromCalculation
                if (method_exists(TransactionRepartition::class, 'createFromCalculation')) {
                    $this->info("   ✅ Méthode createFromCalculation disponible");
                } else {
                    $this->error("   ❌ Méthode createFromCalculation manquante");
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur avec le modèle: " . $e->getMessage());
            }
            
            // Test 3: Test de création d'un enregistrement
            $this->info("\n3. ✅ Test de création d'un enregistrement...");
            
            try {
                $testData = [
                    'admin_amount' => 10.0,
                    'integrator_amount' => 30.0,
                    'operator_amount' => 60.0,
                ];
                
                // Créer un enregistrement de test
                $repartition = new TransactionRepartition();
                $repartition->transaction_id = 999999; // ID de test
                $repartition->admin_percentage = 10.0;
                $repartition->integrator_percentage = 30.0;
                $repartition->operator_percentage = 60.0;
                $repartition->admin_amount = $testData['admin_amount'];
                $repartition->integrator_amount = $testData['integrator_amount'];
                $repartition->operator_amount = $testData['operator_amount'];
                
                $this->info("   ✅ Enregistrement de test créé (non sauvegardé)");
                $this->info("   ✅ Admin: {$repartition->admin_amount}");
                $this->info("   ✅ Integrator: {$repartition->integrator_amount}");
                $this->info("   ✅ Operator: {$repartition->operator_amount}");
                
            } catch (\Exception $e) {
                $this->error("   ❌ Erreur lors de la création: " . $e->getMessage());
            }
            
            $this->info("\n🎯 VÉRIFICATION TERMINÉE");
            $this->info("========================");
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
