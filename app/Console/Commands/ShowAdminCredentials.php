<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Integrator;

class ShowAdminCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:show-credentials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all admin credentials and demo users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 IDENTIFIANTS ADMIN DISPONIBLES:');
        $this->info('==================================');
        
        // Afficher les admins
        $admins = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();
        
        if ($admins->count() > 0) {
            $this->info('👑 Administrateurs:');
            foreach ($admins as $admin) {
                $this->info("   {$admin->name} ({$admin->email})");
            }
            $this->info('');
        }
        
        // Afficher les intégrateurs
        $integrators = User::whereHas('roles', function($query) {
            $query->where('name', 'integrator');
        })->get();
        
        if ($integrators->count() > 0) {
            $this->info('🔗 Intégrateurs:');
            foreach ($integrators as $integrator) {
                $this->info("   {$integrator->name} ({$integrator->email})");
            }
            $this->info('');
        }
        
        // Afficher les opérateurs
        $operators = User::whereHas('roles', function($query) {
            $query->where('name', 'operator');
        })->get();
        
        if ($operators->count() > 0) {
            $this->info('⚡ Opérateurs:');
            foreach ($operators as $operator) {
                $this->info("   {$operator->name} ({$operator->email})");
            }
            $this->info('');
        }
        
        // Afficher les clients
        $clients = User::whereHas('roles', function($query) {
            $query->where('name', 'client');
        })->get();
        
        if ($clients->count() > 0) {
            $this->info('👤 Clients:');
            foreach ($clients as $client) {
                $this->info("   {$client->name} ({$client->email})");
            }
            $this->info('');
        }
        
        // Afficher les intégrateurs enregistrés
        $integratorRecords = Integrator::all();
        if ($integratorRecords->count() > 0) {
            $this->info('🏢 Intégrateurs enregistrés:');
            foreach ($integratorRecords as $integrator) {
                $this->info("   {$integrator->name} ({$integrator->email}) - {$integrator->city}, {$integrator->country}");
            }
            $this->info('');
        }
        
        $this->info('✅ Utilisez ces identifiants pour vous connecter à l\'application !');
        $this->info('');
        $this->info('💡 Commandes utiles:');
        $this->info('   php artisan admin:seed-credentials --force  # Créer les identifiants');
        $this->info('   php artisan admin:show-credentials        # Afficher les identifiants');
        $this->info('   php artisan create_simple_demo_repartition.php  # Créer des données de démo');
    }
}
