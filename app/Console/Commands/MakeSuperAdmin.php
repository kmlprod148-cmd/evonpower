<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class MakeSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-super-admin {user_id : L\'ID de l\'utilisateur}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigner le rôle super admin à un utilisateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        // Vérifier si l'utilisateur existe
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ L'utilisateur avec l'ID {$userId} n'existe pas.");
            return 1;
        }
        
        // Vérifier si le rôle super_admin existe, sinon le créer
        $role = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );
        
        // Vérifier si l'utilisateur a déjà le rôle
        if ($user->hasRole('super_admin')) {
            $this->info("ℹ️  L'utilisateur {$user->name} ({$user->email}) est déjà super admin.");
            return 0;
        }
        
        // Retirer tous les autres rôles et assigner super_admin
        $user->syncRoles(['super_admin']);
        
        $this->info("✅ L'utilisateur {$user->name} ({$user->email}) est maintenant super admin !");
        $this->newLine();
        $this->table(
            ['ID', 'Nom', 'Email', 'Rôles'],
            [[$user->id, $user->name, $user->email, $user->getRoleNames()->implode(', ')]]
        );
        
        return 0;
    }
}
