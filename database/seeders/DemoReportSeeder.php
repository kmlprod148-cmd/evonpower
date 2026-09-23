<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;
use App\Models\BusinessProfile;
use App\Models\User; // Import User model
use Illuminate\Support\Carbon;

class DemoReportSeeder extends Seeder
{
    public function run()
    {
        // Get a business profile and a user
        $businessProfile = BusinessProfile::first();
        $user = User::first(); // Get the first user

        // Un seul rapport de démo
        $reports = [
            [
                'type' => 'usage',
                'title' => 'Monthly Usage Report',
                'start_date' => Carbon::now()->subMonth()->startOfMonth(),
                'end_date' => Carbon::now()->subMonth()->endOfMonth(),
                'business_profile_id' => $businessProfile ? $businessProfile->id : null,
                'generated_by' => $user ? $user->id : null,
                'data' => json_encode([
                    'total_sessions' => 120,
                    'total_energy_kwh' => 2450.5,
                    'total_revenue' => 735.20,
                ]),
            ],
        ];

        foreach ($reports as $report) {
            Report::updateOrCreate(
                [
                    'type' => $report['type'],
                    'title' => $report['title'],
                    'start_date' => $report['start_date'],
                ],
                [
                    'end_date' => $report['end_date'],
                    'business_profile_id' => $report['business_profile_id'],
                    'generated_by' => $report['generated_by'],
                    'data' => $report['data'],
                ]
            );
        }
    }
}