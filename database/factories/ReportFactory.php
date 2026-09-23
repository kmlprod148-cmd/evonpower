<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $businessProfile = BusinessProfile::first() ?? BusinessProfile::factory()->create();
        $user = User::first() ?? User::factory()->create();

        return [
            'title' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['usage', 'revenue', 'commission', 'maintenance', 'user']),
            'description' => $this->faker->paragraph,
            'start_date' => Carbon::now()->subMonth()->startOfMonth(),
            'end_date' => Carbon::now()->subMonth()->endOfMonth(),
            'business_profile_id' => $businessProfile->id,
            'generated_by' => $user->id,
            'status' => $this->faker->randomElement(['draft', 'generated', 'sent']),
            'recipients' => json_encode([$this->faker->email]),
            'include_charts' => $this->faker->boolean,
            'include_summary' => $this->faker->boolean,
            'auto_send' => $this->faker->boolean,
            'data' => json_encode(['key' => 'value']), // Placeholder data
        ];
    }
}