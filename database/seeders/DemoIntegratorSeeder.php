<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Integrator;
use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Hash;

class DemoIntegratorSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();
        
        // Get an admin user to be the creator (required by model validation)
        $adminUser = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })->first();
        
        // If no admin exists, try to get the demo admin
        if (!$adminUser) {
            $adminUser = User::where('email', 'admin@demo.com')->first();
        }
        
        // If still no admin, create one
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => 'Admin',
                'email' => 'admin@evon.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            // Assigner le rôle admin
            $adminUser->assignRole('super_admin');
            // Recharger pour s'assurer que les rôles sont disponibles
            $adminUser->refresh();
        }
        
        // Vérifier qu'on a bien un admin
        if (!$adminUser || !$adminUser->hasRole(['admin', 'super_admin'])) {
            $this->command->error('Impossible de trouver ou créer un utilisateur admin. Seeder annulé.');
            return;
        }
        
        // Get the demo integrator user and a business profile
        $user = User::where('email', 'integrator@evoncharge.com')->first();
        $businessProfile = BusinessProfile::where('name', 'EVON Power Operator')->first();

        // Create a demo integrator with admin as creator
        $integrator = Integrator::create([
            'name' => $faker->company,
            'email' => $faker->unique()->safeEmail,
            'phone' => $faker->phoneNumber,
            'city' => $faker->city,
            'address' => $faker->address,
            'postal_code' => $faker->postcode,
            'country' => $faker->country,
            'created_by' => $adminUser->id, // Required: must be created by an admin
            // 'business_profile_id' => $businessProfile ? $businessProfile->id : null, // supprimé car plus utilisé
        ]);

    }
}