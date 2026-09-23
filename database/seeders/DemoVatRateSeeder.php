<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoVatRateSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks to allow truncation (database-agnostic)
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // Clear existing VAT rates (database-agnostic)
        if ($driver === 'sqlite') {
            DB::table('vat_rates')->delete();
        } else {
            DB::table('vat_rates')->truncate();
        }

        // Un seul taux de TVA de démo
        $vatRates = [
            ['name' => 'Standard VAT 20%', 'rate' => 20.0, 'is_default' => true],
        ];

        // Insert VAT rates one by one
        foreach ($vatRates as $vat) {
            DB::table('vat_rates')->insert($vat);
        }

        // Re-enable foreign key checks (database-agnostic)
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }
}