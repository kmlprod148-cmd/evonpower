<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IntegratorPermissionService;
use App\Models\User;

class FixIntegratorPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrators:fix-permissions {--user-id= : Fix specific user ID} {--all : Fix all integrators}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix permissions for integrator users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing integrator permissions...');

        // Ensure integrator role exists with all permissions
        $this->info('Creating/updating integrator role...');
        IntegratorPermissionService::createIntegratorRole();

        if ($this->option('user-id')) {
            // Fix specific user
            $userId = $this->option('user-id');
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }

            if (!$user->hasRole('integrator')) {
                $this->error("User {$userId} is not an integrator.");
                return 1;
            }

            $this->info("Fixing permissions for user: {$user->name} (ID: {$user->id})");
            $success = IntegratorPermissionService::assignIntegratorPermissions($user);
            
            if ($success) {
                $this->info("✅ Permissions fixed successfully for user {$user->id}");
            } else {
                $this->error("❌ Failed to fix permissions for user {$user->id}");
                return 1;
            }

        } elseif ($this->option('all')) {
            // Fix all integrators
            $this->info('Fixing all integrators...');
            $results = IntegratorPermissionService::fixAllIntegrators();
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($results as $userId => $success) {
                if ($success) {
                    $successCount++;
                    $this->info("✅ User {$userId}: Permissions fixed");
                } else {
                    $failCount++;
                    $this->error("❌ User {$userId}: Failed to fix permissions");
                }
            }
            
            $this->info("\nSummary:");
            $this->info("✅ Successfully fixed: {$successCount}");
            $this->info("❌ Failed: {$failCount}");
            
        } else {
            $this->error('Please specify --user-id=ID or --all');
            return 1;
        }

        $this->info('Integrator permissions fix completed.');
        return 0;
    }
}
