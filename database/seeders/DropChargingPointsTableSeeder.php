<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DropChargingPointsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sql = File::get(database_path('sql/drop_charging_points_table.sql'));
        DB::unprepared($sql);

        $this->command->info('Dropped charging_points table if it existed.');
    }
}