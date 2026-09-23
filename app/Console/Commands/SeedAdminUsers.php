<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\CompleteAdminSuperAdminSeeder;

class SeedAdminUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:admin-users 
                            {--fresh : Supprimer tous les utilisateurs existants avant de créer les nouveaux}
                            {--force : Forcer l\'exécution même en production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer les utilisateurs admin et super admin avec toutes les permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Vérifier l'environnement
        if (app()->environment('production') && !$this->option('force')) {
            if (!$this->confirm('⚠️  Vous êtes en PRODUCTION. Voulez-vous vraiment continuer ?')) {
                $this->error('❌ Opération annulée.');
                return 1;
            }
        }

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║     🚀 CRÉATION DES UTILISATEURS ADMIN ET SUPER ADMIN       ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Option fresh : supprimer les utilisateurs existants
        if ($this->option('fresh')) {
            if ($this->confirm('⚠️  Cette action va SUPPRIMER tous les utilisateurs existants. Continuer ?')) {
                $this->freshDatabase();
            } else {
                $this->warn('❌ Mode fresh annulé. Création sans suppression...');
            }
        }

        // Exécuter le seeder
        try {
            $this->call('db:seed', [
                'class' => CompleteAdminSuperAdminSeeder::class
            ]);

            $this->newLine(2);
            $this->info('╔══════════════════════════════════════════════════════════════╗');
            $this->info('║                ✅ SUCCÈS !                                   ║');
            $this->info('╚══════════════════════════════════════════════════════════════╝');
            $this->newLine();

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('╔══════════════════════════════════════════════════════════════╗');
            $this->error('║                ❌ ERREUR !                                   ║');
            $this->error('╚══════════════════════════════════════════════════════════════╝');
            $this->error('Message: ' . $e->getMessage());
            $this->error('Fichier: ' . $e->getFile() . ':' . $e->getLine());
            $this->newLine();

            return 1;
        }
    }

    /**
     * Supprimer toutes les données existantes
     */
    private function freshDatabase(): void
    {
        $this->warn('🗑️  Suppression des données existantes...');

        try {
            // Supprimer les relations model_has_roles et model_has_permissions
            \DB::table('model_has_roles')->truncate();
            \DB::table('model_has_permissions')->truncate();
            \DB::table('role_has_permissions')->truncate();

            // Supprimer les utilisateurs
            \App\Models\User::query()->delete();

            // Supprimer les rôles et permissions
            \Spatie\Permission\Models\Role::query()->delete();
            \Spatie\Permission\Models\Permission::query()->delete();

            $this->info('   ✅ Données existantes supprimées');

        } catch (\Exception $e) {
            $this->error('   ❌ Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}

