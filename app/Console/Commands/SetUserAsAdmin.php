<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetUserAsAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns the "admin" role and necessary permissions to a user by email.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Ensure the 'admin' role exists
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);

        // Ensure the 'edit_business_profiles' permission exists
        $editBusinessProfilesPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'edit_business_profiles']);

        // Assign the 'admin' role to the user
        $user->assignRole($adminRole);

        // Assign the 'edit_business_profiles' permission to the 'admin' role
        $adminRole->givePermissionTo($editBusinessProfilesPermission);

        $this->info("User {$email} has been assigned the 'admin' role and 'edit_business_profiles' permission successfully.");
        return 0;
    }
}
