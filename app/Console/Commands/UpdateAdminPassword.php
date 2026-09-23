<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateAdminPassword extends Command
{
    protected $signature = 'admin:update-password';
    protected $description = 'Update admin user password securely';

    public function handle()
    {
        DB::table('users')
            ->where('email', 'admin@evonpower.com')
            ->update(['password' => Hash::make('EvonPower')]);

        $this->info('Admin password updated successfully');
        $this->line('Email: admin@evonpower.com');
        $this->line('New Password: EvonPower');
    }
}