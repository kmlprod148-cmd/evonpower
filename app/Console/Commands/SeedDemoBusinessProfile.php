<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\DemoBusinessProfileSeeder;

class SeedDemoBusinessProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:business-profile {--fresh : Supprimer les données existantes avant de créer les nouvelles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer des données de démonstration pour les transactions Business Profile';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏢 Création des données de démonstration Business Profile...');
        
        if ($this->option('fresh')) {
            $this->warn('⚠️  Suppression des données existantes...');
            
            // Supprimer les transactions de démonstration existantes
            \App\Models\Transaction::where('description', 'like', '%Démo Seeder%')
                ->orWhere('description', 'like', '%Commission Admin -%')
                ->orWhere('description', 'like', '%Commission Intégrateur -%')
                ->orWhere('description', 'like', '%Part Opérateur -%')
                ->delete();
                
            // Supprimer les points de charge de démonstration
            \App\Models\ChargingPoint::where('name', 'like', '%Démo Seeder%')->delete();
            
            // Supprimer les business profiles de démonstration
            \App\Models\BusinessProfile::where('name', 'like', '%Démo Seeder%')->delete();
            
            $this->info('✅ Données existantes supprimées');
        }
        
        // Exécuter le seeder
        $this->call('db:seed', ['--class' => DemoBusinessProfileSeeder::class]);
        
        $this->info('🎉 Données de démonstration créées avec succès !');
        $this->info('');
        $this->info('📊 Résumé:');
        $this->info('   - Business Profile créé avec configuration des frais');
        $this->info('   - Point de charge configuré');
        $this->info('   - Transactions de démonstration créées');
        $this->info('   - Flux Admin → Intégrateur → Opérateur configuré');
        $this->info('');
        $this->info('🌐 Pour voir les données:');
        $this->info('   - Connectez-vous en tant qu\'admin (ID: 1)');
        $this->info('   - Allez dans la section Transactions');
        $this->info('   - Filtrez par "Démo Seeder"');
        $this->info('');
        $this->info('👥 Utilisateurs de test:');
        $this->info('   - Admin: ID 1');
        $this->info('   - Intégrateur: ID 12');
        $this->info('   - Opérateur: ID 12');
        $this->info('   - Client: client.demo@test.com');
    }
}
