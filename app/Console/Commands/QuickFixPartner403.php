<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class QuickFixPartner403 extends Command
{
    protected $signature = 'fix:partner-403';
    protected $description = 'Corrige rapidement l\'erreur 403 pour les partenaires';

    public function handle()
    {
        $this->info('🔧 Correction rapide de l\'erreur 403 pour les partenaires...');

        try {
            // 1. Créer les permissions
            $permissions = [
                'view_partners',
                'create_partners',
                'edit_partners',
                'delete_partners',
                'show_partners',
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
                $this->info("✅ Permission: {$permission}");
            }

            // 2. Créer le rôle admin
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $this->info("✅ Rôle admin créé/trouvé");

            // 3. Assigner les permissions au rôle
            $adminRole->givePermissionTo($permissions);
            $this->info("✅ Permissions assignées au rôle admin");

            // 4. Trouver tous les utilisateurs admin
            $adminUsers = User::where('email', 'admin@example.com')
                ->orWhere('id', 1)
                ->get();

            $this->info("👥 Trouvé {$adminUsers->count()} utilisateur(s) admin");

            // 5. Assigner le rôle et les permissions à chaque admin
            foreach ($adminUsers as $user) {
                $user->assignRole('admin');
                $user->givePermissionTo($permissions);
                $this->info("✅ {$user->email} configuré");
            }

            // 6. Test final
            $testUser = User::where('email', 'admin@example.com')->orWhere('id', 1)->first();
            if ($testUser) {
                $this->newLine();
                $this->info("🧪 Test avec: {$testUser->email}");
                $this->info("   - Rôles: " . implode(', ', $testUser->roles->pluck('name')->toArray()));
                $this->info("   - Peut voir partenaires: " . ($testUser->can('view_partners') ? '✅' : '❌'));
                $this->info("   - Peut éditer partenaires: " . ($testUser->can('edit_partners') ? '✅' : '❌'));
            }

            $this->newLine();
            $this->info('🎉 Correction terminée !');
            $this->info('L\'admin devrait maintenant pouvoir voir et éditer les partenaires.');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
