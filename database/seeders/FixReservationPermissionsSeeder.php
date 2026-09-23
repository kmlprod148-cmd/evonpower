<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class FixReservationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 Configuration des permissions réservations pour l\'admin...');

        // Définir toutes les permissions réservations
        $reservationPermissions = [
            'view_reservations',
            'view_admin_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'manage_reservations',
            'confirm_reservations',
            'reject_reservations',
            'approve_reservations',
            'cancel_reservations',
        ];

        // Créer les permissions si elles n'existent pas
        foreach ($reservationPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $this->command->info("✅ Permission créée/vérifiée: {$permission}");
        }

        // Trouver ou créer le rôle admin
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->command->info("✅ Rôle admin trouvé/créé");

        // Assigner toutes les permissions réservations au rôle admin
        $adminRole->givePermissionTo($reservationPermissions);
        $this->command->info("✅ Permissions réservations assignées au rôle admin");

        // Trouver tous les utilisateurs admin
        $adminUsers = User::role('admin')->get();
        $this->command->info("👥 Trouvé {$adminUsers->count()} utilisateur(s) admin");

        // Assigner le rôle admin et les permissions à chaque utilisateur admin
        foreach ($adminUsers as $adminUser) {
            $adminUser->assignRole('admin');
            $adminUser->givePermissionTo($reservationPermissions);
            $this->command->info("✅ Permissions assignées à l'utilisateur: {$adminUser->email}");
        }

        // Vérifier les permissions de l'utilisateur admin principal
        $mainAdmin = User::where('email', 'admin@example.com')->first();
        if ($mainAdmin) {
            $mainAdmin->assignRole('admin');
            $mainAdmin->givePermissionTo($reservationPermissions);
            $this->command->info("✅ Permissions assignées à l'admin principal: {$mainAdmin->email}");
        }

        // Vérifier les permissions de l'utilisateur ID 1
        $user1 = User::find(1);
        if ($user1) {
            $user1->assignRole('admin');
            $user1->givePermissionTo($reservationPermissions);
            $this->command->info("✅ Permissions assignées à l'utilisateur ID 1: {$user1->email}");
        }

        $this->command->info('🎉 Configuration des permissions réservations terminée !');
        $this->command->info('');
        $this->command->info('📋 Permissions configurées pour l\'admin:');
        foreach ($reservationPermissions as $permission) {
            $this->command->info("  - {$permission}");
        }
    }
}
