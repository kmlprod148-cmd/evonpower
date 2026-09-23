<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Partner;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixAdminPartnerAccess extends Command
{
    protected $signature = 'fix:admin-partner-access';
    protected $description = 'Configure l\'accès admin aux partenaires et teste les permissions';

    public function handle()
    {
        $this->info('🔧 Configuration de l\'accès admin aux partenaires...');
        $this->newLine();

        try {
            // 1. Créer les permissions si elles n'existent pas
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

            $this->info('📝 Création des permissions...');
            foreach ($partnerPermissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
                $this->info("  ✅ {$permission}");
            }

            // 2. Trouver ou créer le rôle admin
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $this->info("✅ Rôle admin: {$adminRole->name}");

            // 3. Assigner les permissions au rôle admin
            $adminRole->givePermissionTo($partnerPermissions);
            $this->info('✅ Permissions assignées au rôle admin');

            // 4. Trouver tous les utilisateurs admin
            $adminUsers = User::role('admin')->get();
            $this->info("👥 Trouvé {$adminUsers->count()} utilisateur(s) admin");

            // 5. Assigner les permissions à chaque admin
            foreach ($adminUsers as $adminUser) {
                $adminUser->assignRole('admin');
                $adminUser->givePermissionTo($partnerPermissions);
                $this->info("  ✅ {$adminUser->email}");
            }

            // 6. Vérifier l'utilisateur admin principal
            $mainAdmin = User::where('email', 'admin@example.com')->orWhere('id', 1)->first();
            if ($mainAdmin) {
                $mainAdmin->assignRole('admin');
                $mainAdmin->givePermissionTo($partnerPermissions);
                $this->info("✅ Admin principal configuré: {$mainAdmin->email}");
            }

            $this->newLine();

            // 7. Tester les permissions
            $this->info('🧪 Test des permissions...');
            $testAdmin = User::role('admin')->first();
            
            if ($testAdmin) {
                $this->info("👤 Test avec: {$testAdmin->email}");
                
                foreach ($partnerPermissions as $permission) {
                    $hasPermission = $testAdmin->can($permission);
                    $status = $hasPermission ? '✅' : '❌';
                    $this->info("  {$status} {$permission}");
                }

                // Tester l'accès à un partenaire
                $partner = Partner::first();
                if ($partner) {
                    $this->newLine();
                    $this->info("🔍 Test d'accès au partenaire: {$partner->name}");
                    
                    $canView = $testAdmin->can('view', $partner);
                    $canEdit = $testAdmin->can('update', $partner);
                    $canDelete = $testAdmin->can('delete', $partner);
                    
                    $this->info("  " . ($canView ? '✅' : '❌') . " Peut voir");
                    $this->info("  " . ($canEdit ? '✅' : '❌') . " Peut éditer");
                    $this->info("  " . ($canDelete ? '✅' : '❌') . " Peut supprimer");
                }
            }

            $this->newLine();
            $this->info('🎉 Configuration terminée !');
            $this->info('');
            $this->info('📋 L\'admin peut maintenant:');
            $this->info('  - Voir la liste des partenaires');
            $this->info('  - Voir les détails d\'un partenaire');
            $this->info('  - Créer de nouveaux partenaires');
            $this->info('  - Éditer les partenaires existants');
            $this->info('  - Supprimer des partenaires');
            $this->info('  - Activer/désactiver des partenaires');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
