<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Integrator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminCredentialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Création des identifiants admin...');

        // 1. Créer les rôles s'ils n'existent pas
        $this->createRoles();

        // 2. Créer l'admin principal
        $this->createMainAdmin();

        // 3. Créer les utilisateurs de démonstration
        $this->createDemoUsers();

        // 4. Créer les intégrateurs
        $this->createIntegrators();

        $this->command->info('✅ Identifiants admin créés avec succès !');
        $this->displayCredentials();
    }

    /**
     * Créer les rôles nécessaires
     */
    private function createRoles(): void
    {
        $roles = ['admin', 'integrator', 'operator', 'partner', 'client', 'user'];
        
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
        
        $this->command->info('   ✅ Rôles créés');
    }

    /**
     * Créer l'admin principal
     */
    private function createMainAdmin(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@evonpower.com'],
            [
                'name' => 'Admin EVON',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'balance' => 0.00,
                'currency' => 'EUR'
            ]
        );

        $admin->assignRole('admin');
        $this->command->info('   👑 Admin principal créé: admin@evonpower.com');
    }

    /**
     * Créer les utilisateurs de démonstration
     */
    private function createDemoUsers(): void
    {
        $users = [
            [
                'email' => 'demo@evonpower.com',
                'name' => 'Demo Admin',
                'password' => 'demo123',
                'role' => 'admin'
            ],
            [
                'email' => 'test@evonpower.com',
                'name' => 'Test Admin',
                'password' => 'test123',
                'role' => 'admin'
            ],
            [
                'email' => 'integrator@evonpower.com',
                'name' => 'Intégrateur EVON',
                'password' => 'integrator123',
                'role' => 'integrator'
            ],
            [
                'email' => 'operator@evonpower.com',
                'name' => 'Opérateur EVON',
                'password' => 'operator123',
                'role' => 'operator'
            ],
            [
                'email' => 'client@evonpower.com',
                'name' => 'Client EVON',
                'password' => 'client123',
                'role' => 'client'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'balance' => 0.00,
                    'currency' => 'EUR'
                ]
            );

            $user->assignRole($userData['role']);
            $this->command->info("   👤 {$userData['role']}: {$userData['email']}");
        }
    }

    /**
     * Créer les intégrateurs
     */
    private function createIntegrators(): void
    {
        // Get the main admin user to be the creator (required by model validation)
        $adminUser = User::where('email', 'admin@evonpower.com')->first();
        
        if (!$adminUser) {
            // If admin doesn't exist, try to find any admin user
            $adminUser = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super-admin']);
            })->first();
        }
        
        if (!$adminUser) {
            $this->command->warn('   ⚠️  No admin user found. Skipping integrator creation.');
            return;
        }

        $integrators = [
            [
                'email' => 'integrator@evonpower.com',
                'name' => 'Intégrateur EVON',
                'phone' => '+212600000000',
                'city' => 'Casablanca',
                'country' => 'Morocco',
                'postal_code' => '20000'
            ],
            [
                'email' => 'integrator2@evonpower.com',
                'name' => 'Intégrateur 2 EVON',
                'phone' => '+212600000001',
                'city' => 'Rabat',
                'country' => 'Morocco',
                'postal_code' => '10000'
            ]
        ];

        foreach ($integrators as $integratorData) {
            // Check if integrator already exists
            $existingIntegrator = Integrator::where('email', $integratorData['email'])->first();
            
            if ($existingIntegrator) {
                // Update existing integrator if it doesn't have a creator
                if (empty($existingIntegrator->created_by)) {
                    $existingIntegrator->update(['created_by' => $adminUser->id]);
                }
                $this->command->info("   🔗 Intégrateur (mis à jour): {$integratorData['email']}");
            } else {
                // Create new integrator with admin as creator
                $integrator = Integrator::create([
                    'name' => $integratorData['name'],
                    'email' => $integratorData['email'],
                    'phone' => $integratorData['phone'],
                    'city' => $integratorData['city'],
                    'country' => $integratorData['country'],
                    'postal_code' => $integratorData['postal_code'],
                    'is_active' => true,
                    'status' => 'active',
                    'created_by' => $adminUser->id, // Required: must be created by an admin
                ]);
                $this->command->info("   🔗 Intégrateur (créé): {$integratorData['email']}");
            }
        }
    }

    /**
     * Afficher les identifiants créés
     */
    private function displayCredentials(): void
    {
        $this->command->info('');
        $this->command->info('🔐 IDENTIFIANTS ADMIN CRÉÉS:');
        $this->command->info('================================');
        $this->command->info('👑 Admin Principal:');
        $this->command->info('   Email: admin@evonpower.com');
        $this->command->info('   Password: admin123');
        $this->command->info('');
        $this->command->info('👤 Utilisateurs de démonstration:');
        $this->command->info('   Demo Admin: demo@evonpower.com / demo123');
        $this->command->info('   Test Admin: test@evonpower.com / test123');
        $this->command->info('   Intégrateur: integrator@evonpower.com / integrator123');
        $this->command->info('   Opérateur: operator@evonpower.com / operator123');
        $this->command->info('   Client: client@evonpower.com / client123');
        $this->command->info('');
        $this->command->info('🔗 Intégrateurs:');
        $this->command->info('   Intégrateur 1: integrator@evonpower.com');
        $this->command->info('   Intégrateur 2: integrator2@evonpower.com');
        $this->command->info('');
        $this->command->info('✅ Tous les identifiants sont prêts pour la démonstration !');
    }
}
