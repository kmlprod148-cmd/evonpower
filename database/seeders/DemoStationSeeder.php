<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Station;
use App\Models\Group;
use App\Models\BusinessProfile;

class DemoStationSeeder extends Seeder
{
    public function run()
    {
        // Get demo groups and business profiles
        $group1 = Group::where('name', 'Downtown Charging Group')->first();
        $group2 = Group::where('name', 'Mall Charging Group')->first();
        $businessProfile = BusinessProfile::first();

        // Create demo stations
        $stations = [
            [
                'name' => 'Central Plaza Station',
                'address' => '123 Main St, City Center',
                'group_id' => $group1 ? $group1->id : null,
                'latitude' => 34.0522, // Example latitude
                'longitude' => -118.2437, // Example longitude
                'city' => 'Los Angeles', // Added city
                'postal_code' => '90001', // Added postal_code
                'country' => 'USA', // Added country
            ],
            [
                'name' => 'Mall Parking Station',
                'address' => '456 Mall Ave, Shopping District',
                'group_id' => $group2 ? $group2->id : null,
                'latitude' => 34.0736, // Example latitude
                'longitude' => -118.4004, // Example longitude
                'city' => 'Los Angeles', // Added city
                'postal_code' => '90010', // Added postal_code
                'country' => 'USA', // Added country
            ],
        ];

        foreach ($stations as $station) {
            Station::updateOrCreate(
                ['name' => $station['name']],
                [
                    'address' => $station['address'],
                    'group_id' => $station['group_id'],
                    'latitude' => $station['latitude'], // Added latitude
                    'longitude' => $station['longitude'], // Added longitude
                    'city' => $station['city'], // Added city
                    'postal_code' => $station['postal_code'], // Added postal_code
                    'country' => $station['country'], // Added country
                ]
            );
        }

        // Optionally, generate more demo stations
        Station::factory()->count(3)->create();
    }
}