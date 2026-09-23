<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Station;
use Illuminate\Support\Facades\Log;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if stations already exist
        if (Station::count() > 0) {
            Log::info('Stations already exist. Skipping seeding.');
            return;
        }

        $stations = [
            [
                'label' => 'Central Charging Station',
                'code' => 'CS001',
                'address' => '123 Main Street',
                'city' => 'Paris',
                'country' => 'France',
                'type' => 'public',
                'status' => 'active',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'description' => 'Main public charging station in central Paris',
                'amenities' => json_encode(['parking', 'wifi']),
                'postal_code' => '75001',
                'integrator_id' => null,
            ],
        ];

        // Use insert to bypass validation and mass assignment
        foreach ($stations as $stationData) {
            try {
                Station::create($stationData);
            } catch (\Exception $e) {
                Log::error('Error creating station: ' . $e->getMessage());
                Log::error('Station data: ' . json_encode($stationData));
            }
        }

        Log::info('Stations seeded successfully');
    }
}