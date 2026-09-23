<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Reservation;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixAdminReservationAccess extends Command
{
    protected $signature = 'fix:admin-reservation-access';
    protected $description = 'Configure l\'accès admin aux réservations et teste les permissions';

    public function handle()
    {
        $this->info('🔧 Configuration de l\'accès admin aux réservations...');
        $this->newLine();

        try {
            // 1. Créer les permissions si elles n'existent pas
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

            $this->info('📝 Création des permissions...');
            foreach ($reservationPermissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
                $this->info("  ✅ {$permission}");
            }

            // 2. Trouver ou créer le rôle admin
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $this->info("✅ Rôle admin: {$adminRole->name}");

            // 3. Assigner les permissions au rôle admin
            $adminRole->givePermissionTo($reservationPermissions);
            $this->info('✅ Permissions assignées au rôle admin');

            // 4. Trouver tous les utilisateurs admin
            $adminUsers = User::role('admin')->get();
            $this->info("👥 Trouvé {$adminUsers->count()} utilisateur(s) admin");

            // 5. Assigner les permissions à chaque admin
            foreach ($adminUsers as $adminUser) {
                $adminUser->assignRole('admin');
                $adminUser->givePermissionTo($reservationPermissions);
                $this->info("  ✅ {$adminUser->email}");
            }

            // 6. Vérifier l'utilisateur admin principal
            $mainAdmin = User::where('email', 'admin@example.com')->orWhere('id', 1)->first();
            if ($mainAdmin) {
                $mainAdmin->assignRole('admin');
                $mainAdmin->givePermissionTo($reservationPermissions);
                $this->info("✅ Admin principal configuré: {$mainAdmin->email}");
            }

            $this->newLine();

            // 7. Tester les permissions
            $this->info('🧪 Test des permissions...');
            $testAdmin = User::role('admin')->first();
            
            if ($testAdmin) {
                $this->info("👤 Test avec: {$testAdmin->email}");
                
                foreach ($reservationPermissions as $permission) {
                    $hasPermission = $testAdmin->can($permission);
                    $status = $hasPermission ? '✅' : '❌';
                    $this->info("  {$status} {$permission}");
                }

                // Tester l'accès à une réservation
                $reservation = Reservation::first();
                if ($reservation) {
                    $this->newLine();
                    $this->info("🔍 Test d'accès à la réservation: ID {$reservation->id}");
                    
                    $canView = $testAdmin->can('view', $reservation);
                    $canEdit = $testAdmin->can('update', $reservation);
                    $canDelete = $testAdmin->can('delete', $reservation);
                    $canConfirm = $testAdmin->can('confirm', $reservation);
                    $canReject = $testAdmin->can('reject', $reservation);
                    
                    $this->info("  " . ($canView ? '✅' : '❌') . " Peut voir");
                    $this->info("  " . ($canEdit ? '✅' : '❌') . " Peut éditer");
                    $this->info("  " . ($canDelete ? '✅' : '❌') . " Peut supprimer");
                    $this->info("  " . ($canConfirm ? '✅' : '❌') . " Peut confirmer");
                    $this->info("  " . ($canReject ? '✅' : '❌') . " Peut rejeter");
                }
            }

            $this->newLine();
            $this->info('🎉 Configuration terminée !');
            $this->info('');
            $this->info('📋 L\'admin peut maintenant:');
            $this->info('  - Voir toutes les réservations');
            $this->info('  - Voir les détails de chaque réservation');
            $this->info('  - Créer de nouvelles réservations');
            $this->info('  - Éditer les réservations existantes');
            $this->info('  - Supprimer des réservations');
            $this->info('  - Confirmer/rejeter des réservations');
            $this->info('  - Gérer toutes les réservations');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
