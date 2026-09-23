<?php

namespace App\Console\Commands;

use App\Services\TransactionRepartitionService;
use Illuminate\Console\Command;

class CreateMissingTransactionRepartitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:create-repartitions 
                            {--validate : Valider les répartitions existantes}
                            {--force : Forcer la recréation de toutes les répartitions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée les répartitions manquantes pour les transactions existantes';

    protected $repartitionService;

    public function __construct(TransactionRepartitionService $repartitionService)
    {
        parent::__construct();
        $this->repartitionService = $repartitionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Début de la création des répartitions de transactions...');

        if ($this->option('validate')) {
            $this->validateRepartitions();
            return;
        }

        if ($this->option('force')) {
            $this->info('⚠️  Mode FORCE activé - Recréation de toutes les répartitions...');
            $this->recreateAllRepartitions();
        } else {
            $this->createMissingRepartitions();
        }

        $this->info('✅ Opération terminée avec succès !');
    }

    /**
     * Crée les répartitions manquantes
     */
    private function createMissingRepartitions()
    {
        $this->info('📊 Recherche des transactions sans répartition...');
        
        $createdCount = $this->repartitionService->createMissingRepartitions();
        
        if ($createdCount > 0) {
            $this->info("✅ {$createdCount} répartitions créées avec succès");
        } else {
            $this->info('ℹ️  Toutes les transactions ont déjà une répartition');
        }
    }

    /**
     * Recrée toutes les répartitions
     */
    private function recreateAllRepartitions()
    {
        $this->info('🔄 Suppression de toutes les répartitions existantes...');
        
        // Supprimer toutes les répartitions existantes
        \App\Models\TransactionRepartition::truncate();
        
        $this->info('📊 Recréation de toutes les répartitions...');
        
        $createdCount = $this->repartitionService->createMissingRepartitions();
        
        $this->info("✅ {$createdCount} répartitions recréées avec succès");
    }

    /**
     * Valide toutes les répartitions existantes
     */
    private function validateRepartitions()
    {
        $this->info('🔍 Validation des répartitions existantes...');
        
        $results = $this->repartitionService->validateAllRepartitions();
        
        $this->info("📊 Résultats de validation :");
        $this->info("   Total des répartitions : {$results['total']}");
        $this->info("   Répartitions cohérentes : {$results['consistent']}");
        $this->info("   Répartitions incohérentes : {$results['inconsistent']}");
        
        if ($results['inconsistent'] > 0) {
            $this->warn("⚠️  {$results['inconsistent']} répartitions incohérentes détectées :");
            
            foreach ($results['inconsistent_details'] as $detail) {
                $this->warn("   Transaction #{$detail['transaction_id']} : 
                    Montant transaction = {$detail['transaction_amount']} EUR, 
                    Total répartition = {$detail['repartition_total']} EUR, 
                    Différence = {$detail['difference']} EUR");
            }
            
            if ($this->confirm('Voulez-vous corriger les répartitions incohérentes ?')) {
                $this->info('🔧 Correction des répartitions incohérentes...');
                $this->repartitionService->createMissingRepartitions();
                $this->info('✅ Correction terminée');
            }
        } else {
            $this->info('✅ Toutes les répartitions sont cohérentes !');
        }
    }
}