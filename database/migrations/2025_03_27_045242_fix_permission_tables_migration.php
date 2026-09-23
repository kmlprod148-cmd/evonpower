<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('migrations')->insert([
            'migration' => '2025_03_27_044051_create_permission_tables',
            'batch' => 1
        ]);
    }

    public function down()
    {
        DB::table('migrations')
            ->where('migration', '2025_03_27_044051_create_permission_tables')
            ->delete();
    }
};
