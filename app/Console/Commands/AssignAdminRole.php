<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {user? : User ID or email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign admin role to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userInput = $this->argument('user');

        // Si aucun argument, afficher la liste des utilisateurs
        if (!$userInput) {
            $this->info('📋 Liste des utilisateurs:');
            $this->line('');
            
            $users = User::all();
            
            if ($users->isEmpty()) {
                $this->error('❌ Aucun utilisateur trouvé dans la base de données.');
                return 1;
            }

            $headers = ['ID', 'Nom', 'Email', 'Rôles'];
            $data = [];

            foreach ($users as $user) {
                $roles = $user->roles->pluck('name')->implode(', ') ?: 'Aucun';
                $data[] = [
                    $user->id,
                    $user->name,
                    $user->email,
                    $roles
                ];
            }

            $this->table($headers, $data);
            $this->line('');
            
            $userId = $this->ask('Entrez l\'ID de l\'utilisateur à promouvoir admin');
            
            if (!$userId) {
                $this->error('❌ Opération annulée.');
                return 1;
            }
            
            $user = User::find($userId);
        } else {
            // Chercher par ID ou email
            if (is_numeric($userInput)) {
                $user = User::find($userInput);
            } else {
                $user = User::where('email', $userInput)->first();
            }
        }

        if (!$user) {
            $this->error('❌ Utilisateur non trouvé.');
            return 1;
        }

        // Vérifier si le rôle admin existe
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->warn('⚠️  Le rôle "admin" n\'existe pas. Création en cours...');
            $adminRole = Role::create(['name' => 'admin']);
            $this->info('✅ Rôle "admin" créé avec succès.');
        }

        // Vérifier si l'utilisateur a déjà le rôle
        if ($user->hasRole('admin')) {
            $this->warn("⚠️  {$user->name} ({$user->email}) a déjà le rôle admin.");
            return 0;
        }

        // Assigner le rôle
        $user->assignRole('admin');

        $this->line('');
        $this->info('✅ Rôle admin assigné avec succès!');
        $this->line('');
        $this->line("👤 Utilisateur: {$user->name}");
        $this->line("📧 Email: {$user->email}");
        $this->line("🎭 Rôles: " . $user->roles->pluck('name')->implode(', '));
        $this->line('');
        $this->info('🎉 L\'utilisateur peut maintenant accéder à la section "Gestion Crédits" dans la sidebar!');
        $this->line('');

        return 0;
    }
}
