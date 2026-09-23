<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SeedAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:admin {email} {password} {--name=Admin User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds a new admin user with the specified email and password.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->option('name');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");
            return 1;
        }

        // Create the new user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(), // Mark email as verified
        ]);

        // Ensure the 'admin' role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Assign the 'admin' role to the user
        $user->assignRole($adminRole);

        $this->info("Admin user with email {$email} created and assigned the 'admin' role successfully.");
        return 0;
    }
}