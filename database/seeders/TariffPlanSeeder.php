<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TariffPlan;

class TariffPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TariffPlan::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Default Plan',
                'price_per_minute' => 0.50,
                'max_minutes_per_reservation' => 120,
            ]
        );
    }
}