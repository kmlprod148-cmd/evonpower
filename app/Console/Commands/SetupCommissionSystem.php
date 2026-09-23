<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupCommissionSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:commission-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the commission system by running migrations and seeders';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Setting up the commission system...');

        // Check if commission_plan_id column already exists in transactions table
        $columnExists = Schema::hasColumn('transactions', 'commission_plan_id');
        
        // Run the migrations
        $this->info('Running migrations...');
        
        // Create commission_plans table
        Artisan::call('migrate', ['--path' => 'database/migrations/2025_03_27_000000_create_commission_plans_table.php']);
        $this->info(Artisan::output());
        
        // Add commission fields to transactions table if they don't exist
        if (!$columnExists) {
            $this->info('Adding commission fields to transactions table...');
            Artisan::call('migrate', ['--path' => 'database/migrations/2025_03_27_000001_add_commission_fields_to_transactions_table.php']);
            $this->info(Artisan::output());
        } else {
            $this->info('Commission fields already exist in transactions table. Skipping migration.');
        }

        // Create permissions if they don't exist
        $this->info('Creating permissions...');
        Permission::firstOrCreate(['name' => 'view_commission_settings']);
        Permission::firstOrCreate(['name' => 'manage_commissions']);

        // Assign permissions to admin role
        $this->info('Assigning permissions to admin role...');
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo('view_commission_settings');
        $adminRole->givePermissionTo('manage_commissions');

        // Run the commission plan seeder
        $this->info('Running commission plan seeder...');
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CommissionPlanSeeder']);
        $this->info(Artisan::output());

        $this->info('Commission system setup completed successfully!');

        return 0;
    }
}