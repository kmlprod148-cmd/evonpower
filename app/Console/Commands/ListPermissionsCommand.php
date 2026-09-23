<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class ListPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available permissions in the application.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $permissions = Permission::all('name')->pluck('name');

        if ($permissions->isEmpty()) {
            $this->info('No permissions found.');
            return Command::SUCCESS;
        }

        $this->info('Available Permissions:');
        foreach ($permissions as $permission) {
            $this->line("- {$permission}");
        }

        return Command::SUCCESS;
    }
}