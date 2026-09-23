<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantAllPermissionsToAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:grant-all-permissions {email=admin@evonpower.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant all permissions to an admin user (default: admin@evonpower.com)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("🔍 Recherche de l'utilisateur: {$email}...");
        
        // Trouver l'utilisateur
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("❌ Utilisateur non trouvé: {$email}");
            $this->info("💡 Voulez-vous créer cet utilisateur ? (o/n)");
            
            if ($this->confirm('Créer un nouvel administrateur ?', true)) {
                $user = $this->createAdminUser($email);
            } else {
                return Command::FAILURE;
            }
        }
        
        $this->info("✅ Utilisateur trouvé: {$user->name} ({$user->email})");
        
        // S'assurer que le rôle admin existe
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->info("✅ Rôle admin vérifié");
        
        // Assigner le rôle admin à l'utilisateur
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
            $this->info("✅ Rôle admin assigné à l'utilisateur");
        } else {
            $this->info("ℹ️  L'utilisateur a déjà le rôle admin");
        }
        
        // Récupérer toutes les permissions
        $allPermissions = Permission::all();
        $permissionCount = $allPermissions->count();
        
        $this->info("📋 {$permissionCount} permissions trouvées dans le système");
        
        // Afficher une barre de progression
        $bar = $this->output->createProgressBar($permissionCount);
        $bar->start();
        
        // Assigner toutes les permissions à l'utilisateur
        $user->syncPermissions($allPermissions);
        
        // Assigner toutes les permissions au rôle admin aussi
        $adminRole->syncPermissions($allPermissions);
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Toutes les permissions ont été assignées à {$user->name}");
        
        // Afficher un résumé
        $this->displaySummary($user, $allPermissions);
        
        return Command::SUCCESS;
    }
    
    /**
     * Créer un nouvel utilisateur administrateur
     */
    private function createAdminUser(string $email): User
    {
        $name = $this->ask('Nom de l\'administrateur', 'Admin EVON');
        $password = $this->secret('Mot de passe (laisser vide pour "admin123")') ?: 'admin123';
        
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'email_verified_at' => now(),
            'is_active' => true,
            'balance' => 0.00,
            'currency' => 'EUR'
        ]);
        
        $this->info("✅ Nouvel utilisateur créé");
        $this->info("   📧 Email: {$email}");
        $this->info("   🔑 Mot de passe: {$password}");
        
        return $user;
    }
    
    /**
     * Afficher un résumé des permissions accordées
     */
    private function displaySummary(User $user, $permissions): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('🎉 RÉSUMÉ DES PERMISSIONS');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();
        
        $this->table(
            ['Information', 'Valeur'],
            [
                ['👤 Utilisateur', $user->name],
                ['📧 Email', $user->email],
                ['👑 Rôles', $user->getRoleNames()->implode(', ')],
                ['🔐 Permissions directes', $user->getDirectPermissions()->count()],
                ['🔓 Permissions via rôles', $user->getPermissionsViaRoles()->count()],
                ['📊 Total permissions', $user->getAllPermissions()->count()],
            ]
        );
        
        $this->newLine();
        $this->info('📋 TOUTES LES PERMISSIONS ACCORDÉES:');
        $this->info('───────────────────────────────────────────────────────────');
        
        // Grouper les permissions par catégorie
        $grouped = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);
            return $parts[0] ?? 'other';
        });
        
        foreach ($grouped as $category => $perms) {
            $this->info("  📁 " . ucfirst($category) . " (" . $perms->count() . ")");
            foreach ($perms->take(5) as $perm) {
                $this->line("     • {$perm->name}");
            }
            if ($perms->count() > 5) {
                $this->line("     ... et " . ($perms->count() - 5) . " autres");
            }
        }
        
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("✅ L'utilisateur {$user->name} a maintenant TOUS les droits !");
        $this->info('═══════════════════════════════════════════════════════════');
    }
}
