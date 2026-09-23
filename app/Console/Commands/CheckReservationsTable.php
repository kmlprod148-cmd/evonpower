<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckReservationsTable extends Command
{
    protected $signature = 'check:reservations-table';
    protected $description = 'Vérifier la structure de la table reservations';

    public function handle()
    {
        $this->info('🔍 Vérification de la table reservations');
        $this->info('=====================================');

        try {
            // Vérifier la structure de la table
            $this->info('1. ✅ Structure de la table reservations...');
            
            $schema = DB::select('PRAGMA table_info(reservations)');
            $this->info("   📋 Colonnes trouvées: " . count($schema));
            
            foreach ($schema as $column) {
                $nullable = $column->notnull ? 'NOT NULL' : 'NULL';
                $this->info("   - {$column->name}: {$column->type} ({$nullable})");
            }
            
            // Vérifier les contraintes de clé étrangère
            $this->info("\n2. ✅ Contraintes de clé étrangère...");
            
            $foreignKeys = DB::select('PRAGMA foreign_key_list(reservations)');
            if (count($foreignKeys) > 0) {
                foreach ($foreignKeys as $fk) {
                    $this->info("   - {$fk->from} -> {$fk->table}.{$fk->to}");
                }
            } else {
                $this->info("   ✅ Aucune contrainte de clé étrangère trouvée");
            }
            
            // Vérifier les index
            $this->info("\n3. ✅ Index de la table...");
            
            $indexes = DB::select('PRAGMA index_list(reservations)');
            foreach ($indexes as $index) {
                $this->info("   - {$index->name} (unique: " . ($index->unique ? 'oui' : 'non') . ")");
            }
            
            // Vérifier les données existantes
            $this->info("\n4. ✅ Données existantes...");
            
            $count = DB::table('reservations')->count();
            $this->info("   📊 Nombre de réservations: {$count}");
            
            if ($count > 0) {
                $latest = DB::table('reservations')->latest()->first();
                $this->info("   📅 Dernière réservation: ID {$latest->id}");
                $this->info("   🔗 Order ID: " . ($latest->order_id ?? 'NULL'));
            }
            
            // Vérifier la table orders/transactions
            $this->info("\n5. ✅ Vérification des tables liées...");
            
            $tables = ['orders', 'transactions'];
            foreach ($tables as $table) {
                try {
                    $count = DB::table($table)->count();
                    $this->info("   ✅ Table '{$table}': {$count} enregistrements");
                } catch (\Exception $e) {
                    $this->error("   ❌ Table '{$table}' n'existe pas: " . $e->getMessage());
                }
            }
            
            // Test de création d'une réservation sans order_id
            $this->info("\n6. ✅ Test de création sans order_id...");
            
            try {
                $testReservation = new \App\Models\Reservation();
                $testReservation->charging_point_id = 1;
                $testReservation->pricing_plan_id = 1;
                $testReservation->reservation_type = 'minute';
                $testReservation->reservation_value = 30;
                $testReservation->start_time = now();
                $testReservation->estimated_cost = 2.5;
                $testReservation->status = 'pending';
                $testReservation->guest_email = 'test@example.com';
                $testReservation->guest_phone = '0123456789';
                // Ne pas définir order_id
                
                $testReservation->save();
                $this->info("   ✅ Réservation créée sans order_id - ID: {$testReservation->id}");
                
                // Nettoyer
                $testReservation->delete();
                $this->info("   🧹 Réservation de test supprimée");
                
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
