<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Support\Facades\Gate;

class TestAdminAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:test-access {user_id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test admin access to reservations and other resources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("Testing admin access for user ID={$userId}...");

        // Find the user
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID={$userId} not found!");
            return 1;
        }

        $this->info("Found user: {$user->name} (ID: {$user->id})");

        // Check roles
        $roles = $user->getRoleNames();
        $this->info("User roles: " . $roles->implode(', '));

        // Test specific permissions
        $testPermissions = [
            'view_reservations',
            'manage_reservations',
            'confirm_reservations',
            'cancel_reservations',
            'view_stations',
            'create_stations',
            'edit_stations',
            'delete_stations',
        ];

        $this->info("\nTesting permissions:");
        foreach ($testPermissions as $permission) {
            $hasPermission = $user->can($permission);
            $status = $hasPermission ? "✅ YES" : "❌ NO";
            $this->line("  - {$permission}: {$status}");
        }

        // Test Gate policies
        $this->info("\nTesting Gate policies:");
        
        // Test viewAny for reservations
        try {
            $canViewAnyReservations = Gate::forUser($user)->allows('viewAny', Reservation::class);
            $status = $canViewAnyReservations ? "✅ YES" : "❌ NO";
            $this->line("  - viewAny reservations: {$status}");
        } catch (\Exception $e) {
            $this->line("  - viewAny reservations: ❌ ERROR - " . $e->getMessage());
        }

        // Test specific reservation access
        $reservation = Reservation::first();
        if ($reservation) {
            try {
                $canViewReservation = Gate::forUser($user)->allows('view', $reservation);
                $status = $canViewReservation ? "✅ YES" : "❌ NO";
                $this->line("  - view reservation ID {$reservation->id}: {$status}");
            } catch (\Exception $e) {
                $this->line("  - view reservation: ❌ ERROR - " . $e->getMessage());
            }
        }

        // Test admin role specifically
        $isAdmin = $user->hasRole('admin');
        $isSuperAdmin = $user->hasRole('super_admin');
        $this->info("\nRole checks:");
        $this->line("  - is admin: " . ($isAdmin ? "✅ YES" : "❌ NO"));
        $this->line("  - is super-admin: " . ($isSuperAdmin ? "✅ YES" : "❌ NO"));

        // Test middleware permissions
        $this->info("\nTesting middleware permissions:");
        $middlewarePermissions = [
            'view_reservations',
            'manage_reservations',
            'confirm_reservations',
        ];

        foreach ($middlewarePermissions as $permission) {
            $hasPermission = $user->can($permission);
            $status = $hasPermission ? "✅ YES" : "❌ NO";
            $this->line("  - {$permission}: {$status}");
        }

        $this->info("\nTest completed!");
        return 0;
    }
}