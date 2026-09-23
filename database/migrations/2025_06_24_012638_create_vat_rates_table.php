<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only create if table doesn't exist
        if (!Schema::hasTable('vat_rates')) {
            Schema::create('vat_rates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->decimal('rate', 5, 2)->default(0); // Supports rates up to 999.99%
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
                
                // Indexes for better performance
                $table->index(['is_active']);
                $table->index(['is_default', 'is_active']);
                $table->index(['rate']);
                
                // Ensure only one default rate at a time
                $table->unique(['is_default'], 'unique_default_when_true')
                      ->where('is_default', true);
            });
            
            // Insert default VAT rates for Morocco
            $this->insertDefaultVatRates();
            
            echo "VAT rates table created with default rates.\n";
        } else {
            echo "VAT rates table already exists, skipping creation.\n";
            
            // Check if we need to add missing default rates
            $this->ensureDefaultRatesExist();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('vat_rates');
        Schema::enableForeignKeyConstraints();
    }
    
    /**
     * Insert default VAT rates
     */
    private function insertDefaultVatRates(): void
    {
        $ratesToInsert = [
            [
                'name' => 'Sans TVA',
                'rate' => 0.00,
                'is_default' => false,
                'is_active' => true,
                'description' => 'Aucune taxe sur la valeur ajoutée',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'TVA 7%',
                'rate' => 7.00,
                'is_default' => false,
                'is_active' => true,
                'description' => 'Taux réduit de TVA (7%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'TVA 10%',
                'rate' => 10.00,
                'is_default' => false,
                'is_active' => true,
                'description' => 'Taux intermédiaire de TVA (10%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'TVA 14%',
                'rate' => 14.00,
                'is_default' => false,
                'is_active' => true,
                'description' => 'Taux intermédiaire de TVA (14%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert non-default rates, checking if they exist
        foreach ($ratesToInsert as $rate) {
             $exists = DB::table('vat_rates')
                ->where('rate', $rate['rate'])
                ->where('name', $rate['name'])
                ->exists();

            if (!$exists) {
                DB::table('vat_rates')->insert($rate);
                echo "Inserted VAT rate: {$rate['name']}\n";
            }
        }

        // Handle the default rate separately
        $defaultRate = [
            'name' => 'TVA 20%',
            'rate' => 20.00,
            'is_default' => true,
            'is_active' => true,
            'description' => 'Taux normal de TVA (20%) - Maroc',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Check if a default rate already exists
        $defaultExists = DB::table('vat_rates')
            ->where('is_default', true)
            ->exists();

        // If no default rate exists, insert the 20% rate as default
        if (!$defaultExists) {
             $rateExists = DB::table('vat_rates')
                ->where('rate', $defaultRate['rate'])
                ->where('name', $defaultRate['name'])
                ->exists();

            if (!$rateExists) {
                DB::table('vat_rates')->insert($defaultRate);
                echo "Inserted default VAT rate: {$defaultRate['name']}\n";
            } else {
                // If the 20% rate exists but is not default, set it as default
                 DB::table('vat_rates')
                    ->where('rate', $defaultRate['rate'])
                    ->where('name', $defaultRate['name'])
                    ->update(['is_default' => true]);
                 echo "Set existing 20% VAT rate as default.\n";
            }
        }
    }
    
    /**
     * Ensure default rates exist (for existing tables)
     */
    private function ensureDefaultRatesExist(): void
    {
        $count = DB::table('vat_rates')->count();
        
        if ($count == 0) {
            echo "VAT rates table is empty, inserting default rates.\n";
            $this->insertDefaultVatRates();
        } else {
            // Check if we have a default rate
            $hasDefault = DB::table('vat_rates')
                ->where('is_default', true)
                ->where('is_active', true)
                ->exists();
                
            if (!$hasDefault) {
                echo "No default VAT rate found, setting 20% as default.\n";
                
                // Try to set an existing 20% rate as default
                $updated = DB::table('vat_rates')
                    ->where('rate', 20.00)
                    ->where('is_active', true)
                    ->update(['is_default' => true]);
                    
                // If no 20% rate exists, set the first active rate as default
                if (!$updated) {
                    DB::table('vat_rates')
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->limit(1)
                        ->update(['is_default' => true]);
                }
            }
            
            echo "VAT rates table validated.\n";
        }
    }
};
