<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SimpleFixPartner403 extends Command
{
    protected $signature = 'fix:simple-partner-403';
    protected $description = 'Corrige simplement l\'erreur 403 pour les partenaires';

    public function handle()
    {
        $this->info('🔧 Correction simple de l\'erreur 403 pour les partenaires...');

        try {
            // 1. Créer les permissions
            $permissions = [
                'view_partners',
                'create_partners',
                'edit_partners',
                'delete_partners',
                'show_partners',
            ];

            $this->info('📝 Création des permissions...');
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
                $this->info("  ✅ {$permission}");
            }

            // 2. Créer le rôle admin
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $this->info("✅ Rôle admin créé/trouvé");

            // 3. Assigner les permissions au rôle
            $adminRole->givePermissionTo($permissions);
            $this->info("✅ Permissions assignées au rôle admin");

            // 4. Trouver l'utilisateur admin (par email ou ID)
            $adminUser = User::where('email', 'admin@example.com')->first();
            if (!$adminUser) {
                $adminUser = User::find(1);
            }
            
            if (!$adminUser) {
                $this->error('❌ Aucun utilisateur admin trouvé');
                return 1;
            }

            $this->info("👤 Admin trouvé: {$adminUser->email} (ID: {$adminUser->id})");

            // 5. Assigner le rôle et les permissions
            $adminUser->assignRole('admin');
            $adminUser->givePermissionTo($permissions);
            $this->info("✅ Rôle et permissions assignés à {$adminUser->email}");

            // 6. Test final
            $adminUser->refresh();
            $this->newLine();
            $this->info('🧪 Test des permissions:');
            $this->info("   - Rôles: " . implode(', ', $adminUser->roles->pluck('name')->toArray()));
            $this->info("   - Peut voir partenaires: " . ($adminUser->can('view_partners') ? '✅' : '❌'));
            $this->info("   - Peut éditer partenaires: " . ($adminUser->can('edit_partners') ? '✅' : '❌'));

            $this->newLine();
            $this->info('🎉 Correction terminée !');
            $this->info("L'admin {$adminUser->email} devrait maintenant pouvoir voir et éditer les partenaires.");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
