<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CheckUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the authenticated user\'s permissions and roles.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->info('User is authenticated.');
            $this->info('User ID: ' . $user->id);
            $this->info('Roles: ' . $user->getRoleNames()->implode(', '));
            $this->info('Permissions: ' . $user->getAllPermissions()->pluck('name')->implode(', '));
        } else {
            $this->error('User is not authenticated.');
        }
    }
}