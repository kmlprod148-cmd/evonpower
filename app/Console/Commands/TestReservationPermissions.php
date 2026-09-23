<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Reservation;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestReservationPermissions extends Command
{
    protected $signature = 'test:reservation-permissions';
    protected $description = 'Teste les permissions réservations pour l\'admin';

    public function handle()
    {
        $this->info('🧪 Test des permissions réservations pour l\'admin...');
        $this->newLine();

        try {
            // Trouver l'utilisateur admin
            $admin = User::role('admin')->first();
            
            if (!$admin) {
                $this->error('❌ Aucun utilisateur admin trouvé');
                return 1;
            }

            $this->info("👤 Admin trouvé: {$admin->email} (ID: {$admin->id})");
            $this->newLine();

            // Vérifier les permissions réservations
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

            $this->info('🔍 Vérification des permissions:');
            foreach ($reservationPermissions as $permission) {
                $hasPermission = $admin->can($permission);
                $status = $hasPermission ? '✅' : '❌';
                $this->info("  {$status} {$permission}");
            }

            $this->newLine();

            // Tester l'accès à une réservation
            $reservation = Reservation::first();
            if ($reservation) {
                $this->info("🔍 Test d'accès à la réservation: ID {$reservation->id}");
                
                $canView = $admin->can('view', $reservation);
                $canEdit = $admin->can('update', $reservation);
                $canDelete = $admin->can('delete', $reservation);
                $canConfirm = $admin->can('confirm', $reservation);
                $canReject = $admin->can('reject', $reservation);
                
                $this->info("  " . ($canView ? '✅' : '❌') . " Peut voir la réservation");
                $this->info("  " . ($canEdit ? '✅' : '❌') . " Peut éditer la réservation");
                $this->info("  " . ($canDelete ? '✅' : '❌') . " Peut supprimer la réservation");
                $this->info("  " . ($canConfirm ? '✅' : '❌') . " Peut confirmer la réservation");
                $this->info("  " . ($canReject ? '✅' : '❌') . " Peut rejeter la réservation");
            } else {
                $this->warn('⚠️  Aucune réservation trouvée pour le test');
            }

            $this->newLine();

            // Vérifier les rôles
            $this->info('🎭 Rôles de l\'admin:');
            foreach ($admin->roles as $role) {
                $this->info("  - {$role->name}");
            }

            $this->newLine();

            // Vérifier toutes les permissions
            $this->info('📋 Toutes les permissions de l\'admin:');
            $allPermissions = $admin->getAllPermissions();
            $reservationRelatedPermissions = $allPermissions->filter(function ($permission) {
                return strpos($permission->name, 'reservation') !== false;
            });

            if ($reservationRelatedPermissions->count() > 0) {
                foreach ($reservationRelatedPermissions as $permission) {
                    $this->info("  - {$permission->name}");
                }
            } else {
                $this->warn('⚠️  Aucune permission réservation trouvée');
            }

            $this->newLine();
            $this->info('✅ Test terminé !');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
