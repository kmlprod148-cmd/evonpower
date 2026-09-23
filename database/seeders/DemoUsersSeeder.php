<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👥 Création des utilisateurs de démonstration...');

        // Créer les rôles s'ils n'existent pas
        $this->createRoles();

        // Créer les utilisateurs de démonstration
        $this->createDemoUsers();

        $this->command->info('✅ Utilisateurs de démonstration créés avec succès !');
    }

    private function createRoles(): void
    {
        $roles = [
            'admin' => 'Administrateur système',
            'integrator' => 'Intégrateur',
            'operator' => 'Opérateur',
            'business_owner' => 'Propriétaire Business',
            'customer' => 'Client',
            'demo_user' => 'Utilisateur Démo'
        ];

        foreach ($roles as $roleName => $displayName) {
            Role::firstOrCreate(['name' => $roleName], [
                'display_name' => $displayName,
                'description' => "Rôle {$displayName} pour la démonstration"
            ]);
        }
    }

    private function createDemoUsers(): void
    {
        $users = [
            [
                'name' => 'Admin Démo',
                'email' => 'admin@demo.evonpower.com',
                'password' => 'password',
                'role' => 'admin',
                'balance' => 5000.00,
                'description' => 'Administrateur système de démonstration'
            ],
            [
                'name' => 'Intégrateur Démo',
                'email' => 'integrator@demo.evonpower.com',
                'password' => 'password',
                'role' => 'integrator',
                'balance' => 2500.00,
                'description' => 'Intégrateur de démonstration - TechIntegrateur Pro'
            ],
            [
                'name' => 'Opérateur Démo',
                'email' => 'operator@demo.evonpower.com',
                'password' => 'password',
                'role' => 'operator',
                'balance' => 3000.00,
                'description' => 'Opérateur de démonstration - PowerGrid France'
            ],
            [
                'name' => 'Business Owner Démo',
                'email' => 'business@demo.evonpower.com',
                'password' => 'password',
                'role' => 'business_owner',
                'balance' => 1500.00,
                'description' => 'Propriétaire business de démonstration'
            ],
            [
                'name' => 'Client Premium',
                'email' => 'premium@demo.evonpower.com',
                'password' => 'password',
                'role' => 'customer',
                'balance' => 800.00,
                'description' => 'Client premium de démonstration'
            ],
            [
                'name' => 'Client Standard',
                'email' => 'standard@demo.evonpower.com',
                'password' => 'password',
                'role' => 'customer',
                'balance' => 500.00,
                'description' => 'Client standard de démonstration'
            ],
            [
                'name' => 'Client Économique',
                'email' => 'economy@demo.evonpower.com',
                'password' => 'password',
                'role' => 'customer',
                'balance' => 300.00,
                'description' => 'Client économique de démonstration'
            ],
            [
                'name' => 'Client Entreprise',
                'email' => 'enterprise@demo.evonpower.com',
                'password' => 'password',
                'role' => 'customer',
                'balance' => 2000.00,
                'description' => 'Client entreprise de démonstration'
            ]
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assigner le rôle
            $user->assignRole($userData['role']);

            // Créer le compte
            Account::create([
                'user_id' => $user->id,
                'balance' => $userData['balance'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("✅ Utilisateur créé: {$userData['name']} ({$userData['email']}) - Rôle: {$userData['role']}");
        }
    }
}
