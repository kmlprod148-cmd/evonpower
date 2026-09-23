<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Partner;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestPartnerPermissions extends Command
{
    protected $signature = 'test:partner-permissions';
    protected $description = 'Teste les permissions partenaires pour l\'admin';

    public function handle()
    {
        $this->info('🧪 Test des permissions partenaires pour l\'admin...');
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

            // Vérifier les permissions partenaires
            $partnerPermissions = [
                'view_partners',
                'create_partners',
                'edit_partners',
                'delete_partners',
                'show_partners',
                'activate_partners',
                'deactivate_partners',
            ];

            $this->info('🔍 Vérification des permissions:');
            foreach ($partnerPermissions as $permission) {
                $hasPermission = $admin->can($permission);
                $status = $hasPermission ? '✅' : '❌';
                $this->info("  {$status} {$permission}");
            }

            $this->newLine();

            // Tester l'accès à un partenaire
            $partner = Partner::first();
            if ($partner) {
                $this->info("🔍 Test d'accès au partenaire: {$partner->name} (ID: {$partner->id})");
                
                $canView = $admin->can('view', $partner);
                $canEdit = $admin->can('update', $partner);
                $canDelete = $admin->can('delete', $partner);
                
                $this->info("  " . ($canView ? '✅' : '❌') . " Peut voir le partenaire");
                $this->info("  " . ($canEdit ? '✅' : '❌') . " Peut éditer le partenaire");
                $this->info("  " . ($canDelete ? '✅' : '❌') . " Peut supprimer le partenaire");
            } else {
                $this->warn('⚠️  Aucun partenaire trouvé pour le test');
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
            $partnerRelatedPermissions = $allPermissions->filter(function ($permission) {
                return strpos($permission->name, 'partner') !== false;
            });

            if ($partnerRelatedPermissions->count() > 0) {
                foreach ($partnerRelatedPermissions as $permission) {
                    $this->info("  - {$permission->name}");
                }
            } else {
                $this->warn('⚠️  Aucune permission partenaire trouvée');
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
