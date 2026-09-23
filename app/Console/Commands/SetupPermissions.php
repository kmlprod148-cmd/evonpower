<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:setup {--refresh : Refresh all permissions by dropping and re-seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets up initial roles and permissions for the application.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Setting up roles and permissions...');

        if ($this->option('refresh')) {
            $this->warn('Refreshing permissions...');
            // Drop existing permissions and roles
            Permission::query()->delete();
            Role::query()->delete();
            $this->info('Existing permissions and roles dropped.');
        }

        // Run the seeders
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder'], $this->output);
        Artisan::call('db:seed', ['--class' => 'BusinessProfilePermissionsSeeder'], $this->output);

        $this->info('Roles and permissions setup complete.');

        return 0;
    }
}