<?php

namespace Database\Seeders;

use App\Models\Group; // Use the correct Group model
use Illuminate\Database\Seeder;

class StationGroupSeeder extends Seeder
{
    public function run()
    {
        $groups = [
            [
                'name' => 'Morocco Mall',
                'description' => 'Charging stations at Morocco Mall',
                'type' => 'public',
                'city' => 'Casablanca',
                'user_id' => 1,
            ],
        ];
        
        foreach ($groups as $group) {
            Group::create($group); // Use the correct Group model
        }
    }
}
