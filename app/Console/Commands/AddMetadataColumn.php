<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddMetadataColumn extends Command
{
    protected $signature = 'add:metadata-column';
    protected $description = 'Ajouter la colonne metadata à la table transactions';

    public function handle()
    {
        $this->info('🔧 Ajout de la colonne metadata à la table transactions');
        $this->info('====================================================');

        try {
            // Vérifier si la colonne existe déjà
            $this->info('1. ✅ Vérification de l\'existence de la colonne...');
            
            $schema = DB::select('PRAGMA table_info(transactions)');
            $hasMetadata = false;
            
            foreach ($schema as $column) {
                if ($column->name === 'metadata') {
                    $hasMetadata = true;
                    break;
                }
            }
            
            if ($hasMetadata) {
                $this->info('   ✅ Colonne metadata existe déjà');
                return;
            }
            
            $this->info('   ❌ Colonne metadata manquante');

            // Ajouter la colonne
            $this->info('\n2. ✅ Ajout de la colonne metadata...');
            
            try {
                DB::statement('ALTER TABLE transactions ADD COLUMN metadata TEXT NULL');
                $this->info('   ✅ Colonne metadata ajoutée avec succès');
            } catch (\Exception $e) {
                $this->error('   ❌ Erreur lors de l\'ajout de la colonne: ' . $e->getMessage());
                return;
            }

            // Vérifier que la colonne a été ajoutée
            $this->info('\n3. ✅ Vérification de l\'ajout...');
            
            $schema = DB::select('PRAGMA table_info(transactions)');
            $hasMetadata = false;
            
            foreach ($schema as $column) {
                if ($column->name === 'metadata') {
                    $hasMetadata = true;
                    $this->info("   ✅ Colonne metadata trouvée: {$column->name} - {$column->type}");
                    break;
                }
            }
            
            if ($hasMetadata) {
                $this->info('   ✅ Colonne metadata ajoutée avec succès');
            } else {
                $this->error('   ❌ Colonne metadata non trouvée après l\'ajout');
            }

            $this->info('\n🎯 AJOUT TERMINÉ');
            $this->info('===============');
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
