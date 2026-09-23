<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SteveService;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;

class TestSteveConnection extends Command
{
    protected $signature = 'steve:test-connection {charging_point_id?}';
    protected $description = 'Test the connection to Steve API and attempt to create a charging point';

    public function handle()
    {
        $this->info('🔍 Testing Steve API Connection...');
        $this->newLine();

        // Check configuration
        $baseUrl = config('services.steve.url');
        $user = config('services.steve.user');
        $pass = config('services.steve.pass');

        $this->info('Configuration:');
        $this->line('  URL: ' . ($baseUrl ?: '❌ NOT CONFIGURED'));
        $this->line('  User: ' . ($user ?: '❌ NOT CONFIGURED'));
        $this->line('  Pass: ' . ($pass ? '***' : '❌ NOT CONFIGURED'));
        $this->newLine();

        if (empty($baseUrl)) {
            $this->error('❌ Steve API URL is not configured!');
            $this->line('Please set SERVICES_STEVE_URL in your .env file');
            return Command::FAILURE;
        }

        // Test with a charging point
        $chargingPointId = $this->argument('charging_point_id');
        
        if ($chargingPointId) {
            $chargingPoint = ChargingPoint::find($chargingPointId);
            if (!$chargingPoint) {
                $this->error("Charging point with ID {$chargingPointId} not found");
                return Command::FAILURE;
            }
        } else {
            // Get the most recent charging point
            $chargingPoint = ChargingPoint::latest()->first();
            if (!$chargingPoint) {
                $this->error('No charging points found in database');
                return Command::FAILURE;
            }
        }

        $this->info("Testing with Charging Point ID: {$chargingPoint->id}");
        $this->line("  Name: {$chargingPoint->name}");
        $this->line("  Serial Number: {$chargingPoint->serial_number}");
        $this->line("  Steve ID: " . ($chargingPoint->steve_charging_point_id ?: 'NOT SET'));
        $this->line("  Latitude: " . ($chargingPoint->latitude ?: 'NOT SET'));
        $this->line("  Longitude: " . ($chargingPoint->longitude ?: 'NOT SET'));
        $this->newLine();

        // Check required fields
        $hasRequiredData = (!empty($chargingPoint->steve_charging_point_id) || !empty($chargingPoint->serial_number))
                        && !empty($chargingPoint->name)
                        && !empty($chargingPoint->latitude) 
                        && !empty($chargingPoint->longitude);

        if (!$hasRequiredData) {
            $this->error('❌ Charging point is missing required data for Steve API:');
            if (empty($chargingPoint->steve_charging_point_id) && empty($chargingPoint->serial_number)) {
                $this->line('  - Missing: steve_charging_point_id or serial_number');
            }
            if (empty($chargingPoint->name)) {
                $this->line('  - Missing: name');
            }
            if (empty($chargingPoint->latitude)) {
                $this->line('  - Missing: latitude');
            }
            if (empty($chargingPoint->longitude)) {
                $this->line('  - Missing: longitude');
            }
            return Command::FAILURE;
        }

        $this->info('✅ All required fields are present');
        $this->newLine();

        // Attempt to create on Steve
        $this->info('🚀 Attempting to create charging point on Steve API...');
        
        try {
            $steveService = app(SteveService::class);
            $result = $steveService->createCompleteChargingPointFromModel($chargingPoint);

            if ($result['ok']) {
                $this->info('✅ Successfully created on Steve API!');
                $this->line('  Status: ' . ($result['status'] ?? 'N/A'));
                if (!empty($result['steve_id'])) {
                    $this->line('  Steve ID returned: ' . $result['steve_id']);
                }
                if (!empty($result['body'])) {
                    $this->line('  Response: ' . json_encode($result['body'], JSON_PRETTY_PRINT));
                }
                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to create on Steve API');
                $this->line('  Error: ' . ($result['error'] ?? $result['message'] ?? 'Unknown error'));
                $this->line('  Status: ' . ($result['status'] ?? 'N/A'));
                if (!empty($result['details'])) {
                    $this->line('  Details: ' . json_encode($result['details'], JSON_PRETTY_PRINT));
                }
                if (!empty($result['body'])) {
                    $this->line('  Response Body: ' . json_encode($result['body'], JSON_PRETTY_PRINT));
                }
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred:');
            $this->line('  ' . $e->getMessage());
            $this->line('  File: ' . $e->getFile() . ':' . $e->getLine());
            return Command::FAILURE;
        }
    }
}
