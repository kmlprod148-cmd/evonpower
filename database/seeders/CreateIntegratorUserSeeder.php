<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateIntegratorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create integrator user
        $integratorUser = User::firstOrCreate(
            ['email' => 'integrator@test.com'],
            [
                'name' => 'Test Integrator',
                'password' => Hash::make('password'),
                'balance' => 0.00,
                'currency' => 'EUR',
            ]
        );

        // Assign integrator role
        $integratorRole = Role::where('name', 'integrator')->first();
        if ($integratorRole && !$integratorUser->hasRole('integrator')) {
            $integratorUser->assignRole('integrator');
            $this->command->info("✅ Integrator role assigned to {$integratorUser->name}");
        }

        $this->command->info("✅ Integrator user created: {$integratorUser->name} ({$integratorUser->email})");
        $this->command->info("   Password: password");
    }
}
