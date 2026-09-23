<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\BusinessProfile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixIntegratorBusinessProfile extends Command
{
    protected $signature = 'fix:integrator-business-profile';
    protected $description = 'Corrige les permissions business profile pour les intégrateurs';

    public function handle()
    {
        $this->info('🔧 Correction des permissions business profile pour les intégrateurs...');

        try {
            // 1. Créer les permissions business profile
            $businessProfilePermissions = [
                'view_business_profiles',
                'create_business_profiles',
                'edit_business_profiles',
                'delete_business_profiles',
                'assign_business_profiles',
                'view_all_business_profiles',
                'view_integrator_business_profiles',
                'view_own_business_profiles',
            ];

            $this->info('📝 Création des permissions business profile...');
            foreach ($businessProfilePermissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
                $this->info("  ✅ {$permission}");
            }

            // 2. Créer le rôle intégrateur
            $integratorRole = Role::firstOrCreate(['name' => 'integrator']);
            $this->info("✅ Rôle intégrateur créé/trouvé");

            // 3. Assigner les permissions au rôle intégrateur
            $integratorRole->givePermissionTo($businessProfilePermissions);
            $this->info("✅ Permissions assignées au rôle intégrateur");

            // 4. Trouver tous les utilisateurs intégrateurs
            $integratorUsers = User::role('integrator')->get();
            $this->info("👥 Trouvé {$integratorUsers->count()} utilisateur(s) intégrateur");

            // 5. Assigner les permissions à chaque intégrateur
            foreach ($integratorUsers as $integrator) {
                $integrator->assignRole('integrator');
                $integrator->givePermissionTo($businessProfilePermissions);
                $this->info("  ✅ {$integrator->email}");
            }

            // 6. Rendre les business profiles publics accessibles aux intégrateurs
            $this->newLine();
            $this->info('🔧 Configuration des business profiles publics...');
            $publicBusinessProfiles = BusinessProfile::where('is_public', true)->get();
            $this->info("📋 Trouvé {$publicBusinessProfiles->count()} business profile(s) public(s)");

            foreach ($publicBusinessProfiles as $bp) {
                $this->info("  ✅ {$bp->name} (ID: {$bp->id}) - Public");
            }

            // 7. Test final
            $testIntegrator = User::role('integrator')->first();
            if ($testIntegrator) {
                $this->newLine();
                $this->info("🧪 Test avec intégrateur: {$testIntegrator->email}");
                $this->info("   - Rôles: " . implode(', ', $testIntegrator->roles->pluck('name')->toArray()));
                $this->info("   - Peut voir business profiles: " . ($testIntegrator->can('view_business_profiles') ? '✅' : '❌'));
                $this->info("   - Peut créer business profiles: " . ($testIntegrator->can('create_business_profiles') ? '✅' : '❌'));
                $this->info("   - Peut assigner business profiles: " . ($testIntegrator->can('assign_business_profiles') ? '✅' : '❌'));
            }

            $this->newLine();
            $this->info('🎉 Configuration terminée !');
            $this->info('Les intégrateurs peuvent maintenant assigner des business profiles.');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
