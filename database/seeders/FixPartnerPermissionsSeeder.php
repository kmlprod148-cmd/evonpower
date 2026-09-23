<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class FixPartnerPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 Configuration des permissions partenaires pour l\'admin...');

        // Définir toutes les permissions partenaires
        $partnerPermissions = [
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',
            'bulk_update_partners',
            'export_partners',
        ];

        // Créer les permissions si elles n'existent pas
        foreach ($partnerPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $this->command->info("✅ Permission créée/vérifiée: {$permission}");
        }

        // Trouver ou créer le rôle admin
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->command->info("✅ Rôle admin trouvé/créé");

        // Assigner toutes les permissions partenaires au rôle admin
        $adminRole->givePermissionTo($partnerPermissions);
        $this->command->info("✅ Permissions partenaires assignées au rôle admin");

        // Trouver tous les utilisateurs admin
        $adminUsers = User::role('admin')->get();
        $this->command->info("👥 Trouvé {$adminUsers->count()} utilisateur(s) admin");

        // Assigner le rôle admin et les permissions à chaque utilisateur admin
        foreach ($adminUsers as $adminUser) {
            $adminUser->assignRole('admin');
            $adminUser->givePermissionTo($partnerPermissions);
            $this->command->info("✅ Permissions assignées à l'utilisateur: {$adminUser->email}");
        }

        // Vérifier les permissions de l'utilisateur admin principal
        $mainAdmin = User::where('email', 'admin@example.com')->first();
        if ($mainAdmin) {
            $mainAdmin->assignRole('admin');
            $mainAdmin->givePermissionTo($partnerPermissions);
            $this->command->info("✅ Permissions assignées à l'admin principal: {$mainAdmin->email}");
        }

        // Vérifier les permissions de l'utilisateur ID 1
        $user1 = User::find(1);
        if ($user1) {
            $user1->assignRole('admin');
            $user1->givePermissionTo($partnerPermissions);
            $this->command->info("✅ Permissions assignées à l'utilisateur ID 1: {$user1->email}");
        }

        $this->command->info('🎉 Configuration des permissions partenaires terminée !');
        $this->command->info('');
        $this->command->info('📋 Permissions configurées pour l\'admin:');
        foreach ($partnerPermissions as $permission) {
            $this->command->info("  - {$permission}");
        }
    }
}
