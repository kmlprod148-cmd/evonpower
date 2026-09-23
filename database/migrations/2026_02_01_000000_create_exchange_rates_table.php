<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3)->index();
            $table->string('to_currency', 3)->index();
            $table->decimal('rate', 15, 6);
            $table->string('source')->default('manual'); // 'api', 'manual'
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency']);
        });

        // Create currency settings in admin_settings if they don't exist
        DB::table('admin_settings')->insertOrIgnore([
            [
                'category' => 'currency',
                'key' => 'app_default_currency',
                'value' => 'MAD',
                'type' => 'select',
                'description' => 'Default application currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'currency',
                'key' => 'enabled_currencies',
                'value' => json_encode(['MAD', 'EUR', 'USD']),
                'type' => 'json',
                'description' => 'Enabled currencies for the application',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'currency',
                'key' => 'auto_convert_display',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Automatically convert and display prices in user currency',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'currency',
                'key' => 'exchange_rate_api_provider',
                'value' => 'exchangerate',
                'type' => 'select',
                'description' => 'Exchange rate API provider',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'currency',
                'key' => 'exchange_rate_api_key',
                'value' => '',
                'type' => 'password',
                'description' => 'API key for exchange rate provider',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Insert default exchange rates
        $defaultRates = [
            ['from_currency' => 'EUR', 'to_currency' => 'MAD', 'rate' => 10.80],
            ['from_currency' => 'EUR', 'to_currency' => 'USD', 'rate' => 1.08],
            ['from_currency' => 'EUR', 'to_currency' => 'GBP', 'rate' => 0.86],
            ['from_currency' => 'MAD', 'to_currency' => 'EUR', 'rate' => 0.093],
            ['from_currency' => 'MAD', 'to_currency' => 'USD', 'rate' => 0.10],
            ['from_currency' => 'USD', 'to_currency' => 'EUR', 'rate' => 0.93],
            ['from_currency' => 'USD', 'to_currency' => 'MAD', 'rate' => 10.00],
        ];

        foreach ($defaultRates as $rate) {
            DB::table('exchange_rates')->insertOrIgnore([
                'from_currency' => $rate['from_currency'],
                'to_currency' => $rate['to_currency'],
                'rate' => $rate['rate'],
                'source' => 'manual',
                'fetched_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
        
        DB::table('admin_settings')
            ->where('category', 'currency')
            ->delete();
    }
};
