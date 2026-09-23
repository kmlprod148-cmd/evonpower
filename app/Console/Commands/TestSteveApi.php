<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestSteveApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-steve-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the SteVe API for OCPP tag creation.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $baseUrl = 'http://158.69.27.239:8080';
        $endpoint = '/steve/api/v1/ocppTags';
        $url = $baseUrl . $endpoint;

        $payload = [
            'idTag' => 'TEST_TAG_' . uniqid(),
            'note' => 'Created by TestSteveApi command',
            'maxActiveTransactionCount' => 1,
            'expiryDate' => now()->addYears(1)->format('Y-m-d\TH:i:s'),
        ];

        Log::info('Attempting to create OCPP tag.', [
            'url' => $url,
            'payload' => $payload,
        ]);

        try {
            $response = Http::post($url, $payload);

            Log::info('SteVe API Response:', [
                'status' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers(),
            ]);

            if ($response->successful()) {
                $this->info('OCPP Tag created successfully. Check logs for details.');
            } else {
                $this->error('Failed to create OCPP Tag. Check logs for details.');
            }
        } catch (\Exception $e) {
            Log::error('Error connecting to SteVe API:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('An error occurred while connecting to the SteVe API. Check logs for details.');
        }
    }
}
