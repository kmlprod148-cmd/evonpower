<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignQrCodePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:assign-qr-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign QR code permissions to admin and super-admin roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Assigning QR code permissions...');

        // Create QR code permissions if they don't exist
        $qrPermissions = ['view_qr_codes', 'manage_qr_codes'];
        
        foreach ($qrPermissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $this->info("✅ Permission '{$permissionName}' created/verified");
        }

        // Assign permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($qrPermissions);
            $this->info('✅ QR code permissions assigned to admin role');
        } else {
            $this->error('❌ Admin role not found');
        }

        // Assign permissions to super-admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($qrPermissions);
            $this->info('✅ QR code permissions assigned to super-admin role');
        } else {
            $this->warn('⚠️  Super-admin role not found');
        }

        // Assign permissions to integrator role
        $integratorRole = Role::where('name', 'integrator')->first();
        if ($integratorRole) {
            $integratorRole->givePermissionTo($qrPermissions);
            $this->info('✅ QR code permissions assigned to integrator role');
        } else {
            $this->warn('⚠️  Integrator role not found');
        }

        $this->info('QR code permissions assignment completed!');
        
        return Command::SUCCESS;
    }
}
