<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class GrantAdminPanelAccess extends Command
{
    protected $signature = 'admin:grant
                            {email : User email to grant or revoke access for}
                            {--revoke : Revoke the permission instead of granting it}';

    protected $description = 'Grant (or revoke) the admin-panel access permission for a specific non-client user.';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $this->error("No user found with email {$email}.");
            return self::FAILURE;
        }

        $name = config('admin.permission', 'access-admin-panel');
        $guard = config('auth.defaults.guard', 'web');

        $permission = Permission::firstOrCreate(
            ['name' => $name, 'guard_name' => $guard],
        );

        if ($this->option('revoke')) {
            $user->revokePermissionTo($permission);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info("Revoked '{$name}' from {$user->email} (id={$user->getKey()}).");
            return self::SUCCESS;
        }

        $user->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info("Granted '{$name}' to {$user->email} (id={$user->getKey()}).");
        return self::SUCCESS;
    }
}
