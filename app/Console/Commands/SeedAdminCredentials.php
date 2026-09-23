<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\AdminCredentialsSeeder;

class SeedAdminCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:seed-credentials {--force : Force the operation even if users exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed admin credentials and demo users for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Seeding admin credentials...');
        
        if (!$this->option('force')) {
            $existingUsers = \App\Models\User::whereIn('email', [
                'admin@evonpower.com',
                'demo@evonpower.com',
                'test@evonpower.com'
            ])->count();
            
            if ($existingUsers > 0) {
                if (!$this->confirm('Some admin users already exist. Do you want to continue?')) {
                    $this->info('Operation cancelled.');
                    return;
                }
            }
        }

        $seeder = new AdminCredentialsSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('');
        $this->info('🎉 Admin credentials seeded successfully!');
        $this->info('');
        $this->info('You can now login with:');
        $this->info('👑 Admin: admin@evonpower.com / admin123');
        $this->info('👤 Demo: demo@evonpower.com / demo123');
        $this->info('👤 Test: test@evonpower.com / test123');
    }
}
