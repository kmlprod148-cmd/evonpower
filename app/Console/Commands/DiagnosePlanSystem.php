<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\VatRate;
use App\Models\Plan;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Log; // Added this line

class DiagnosePlanSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'plans:diagnose {--fix : Automatically fix detected issues}';

    /**
     * The console command description.
     */
    protected $description = 'Diagnose and optionally fix issues with the pricing plan system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Diagnosing Pricing Plan System...');
        $this->newLine();

        Log::info('Starting DiagnosePlanSystem command.'); // Added this line

        $issues = [];
        $fixes = [];

        // Check database tables
        Log::info('Checking database tables...'); // Added this line
        $issues = array_merge($issues, $this->checkTables());
        Log::info('Finished checking database tables. Found ' . count($issues) . ' issues so far.'); // Added this line

        // Check model files
        Log::info('Checking model files...'); // Added this line
        $issues = array_merge($issues, $this->checkModels());
        Log::info('Finished checking model files. Found ' . count($issues) . ' issues so far.'); // Added this line

        // Check data integrity
        Log::info('Checking data integrity...'); // Added this line
        $issues = array_merge($issues, $this->checkData());
        Log::info('Finished checking data integrity. Found ' . count($issues) . ' issues so far.'); // Added this line

        // Check controller files
        Log::info('Checking controller files...'); // Added this line
        $issues = array_merge($issues, $this->checkControllers());
        Log::info('Finished checking controller files. Found ' . count($issues) . ' issues so far.'); // Added this line


        // Display results
        if (empty($issues)) {
            $this->info('✅ No issues found! The pricing plan system appears to be working correctly.');
            Log::info('No issues found.'); // Added this line
            return 0;
        }

        $this->error("❌ Found " . count($issues) . " issue(s):");
        Log::error("Found " . count($issues) . " issue(s)."); // Added this line
        foreach ($issues as $issue) {
            $this->line("  • " . $issue['message']);
            Log::error("Issue: " . $issue['message']); // Added this line
            if (isset($issue['fix'])) {
                $fixes[] = $issue['fix'];
            }
        }

        if ($this->option('fix') && !empty($fixes)) {
            $this->newLine();
            $this->info('🔧 Attempting to fix issues...');
            Log::info('Attempting to fix issues...'); // Added this line

            foreach ($fixes as $fix) {
                try {
                    $fix();
                    $this->info('✅ Fixed an issue');
                    Log::info('Successfully fixed an issue.'); // Added this line
                } catch (\Exception $e) {
                    $this->error('❌ Failed to fix: ' . $e->getMessage());
                    Log::error('Failed to fix issue: ' . $e->getMessage()); // Added this line
                }
            }
        } elseif (!empty($fixes)) {
            $this->newLine();
            $this->comment('💡 Run with --fix flag to automatically fix some issues');
            Log::info('Fixes available, but --fix flag not used.'); // Added this line
        }

        Log::info('DiagnosePlanSystem command finished.'); // Added this line
        return empty($issues) ? 0 : 1;
    }

    /**
     * Check if required tables exist
     */
    private function checkTables()
    {
        Log::info('Executing checkTables method.'); // Added this line
        $issues = [];
        $requiredTables = ['plans', 'vat_rates', 'plan_rates'];

        foreach ($requiredTables as $table) {
            Log::info("Checking for table: {$table}"); // Added this line
            if (!Schema::hasTable($table)) {
                Log::warning("Table '{$table}' does not exist."); // Added this line
                $issues[] = [
                    'message' => "Table '{$table}' does not exist",
                    'fix' => function() use ($table) {
                        if ($table === 'vat_rates') {
                            $this->createVatRatesTable();
                        } elseif ($table === 'plan_rates') {
                            $this->createPlanRatesTable();
                        }
                    }
                ];
            } else {
                Log::info("Table '{$table}' exists."); // Added this line
            }
        }

        // Check required columns in plans table
        if (Schema::hasTable('plans')) {
            Log::info('Checking required columns in plans table.'); // Added this line
            $requiredColumns = [
                'rate_type', 'price_per_minute', 'price_per_kwh',
                'base_rate', 'tva_rate', 'priority', 'is_active'
            ];

            foreach ($requiredColumns as $column) {
                Log::info("Checking for column 'plans.{$column}'."); // Added this line
                if (!Schema::hasColumn('plans', $column)) {
                    Log::warning("Column 'plans.{$column}' does not exist."); // Added this line
                    $issues[] = [
                        'message' => "Column 'plans.{$column}' does not exist",
                        'fix' => function() { $this->updatePlansTable(); }
                    ];
                    break; // Only show one missing column issue
                } else {
                    Log::info("Column 'plans.{$column}' exists."); // Added this line
                }
            }
        } else {
             Log::warning('Plans table does not exist, skipping column check.'); // Added this line
        }

        Log::info('Finished checkTables method.'); // Added this line
        return $issues;
    }

    /**
     * Check if required model files exist
     */
    private function checkModels()
    {
        Log::info('Executing checkModels method.'); // Added this line
        $issues = [];
        $requiredModels = [
            'App\Models\Plan' => app_path('Models/Plan.php'),
            'App\Models\VatRate' => app_path('Models/VatRate.php'),
            'App\Models\PlanRate' => app_path('Models/PlanRate.php'),
        ];

        foreach ($requiredModels as $class => $file) {
            Log::info("Checking for model file: {$file}"); // Added this line
            if (!file_exists($file)) {
                Log::warning("Model file does not exist: {$file}"); // Added this line
                $issues[] = [
                    'message' => "Model file does not exist: {$file}",
                ];
            } elseif (!class_exists($class)) {
                Log::warning("Model class not found: {$class}"); // Added this line
                $issues[] = [
                    'message' => "Model class not found: {$class}",
                    'fix' => function() {
                        $this->call('optimize:clear');
                    }
                ];
            } else {
                Log::info("Model file exists and class found: {$class}"); // Added this line
            }
        }

        Log::info('Finished checkModels method.'); // Added this line
        return $issues;
    }

    /**
     * Check data integrity
     */
    private function checkData()
    {
        Log::info('Executing checkData method.'); // Added this line
        $issues = [];

        if (Schema::hasTable('vat_rates')) {
            try {
                Log::info('Checking VAT rates data.'); // Added this line
                $vatRatesCount = VatRate::count();
                Log::info("Found {$vatRatesCount} VAT rates."); // Added this line
                if ($vatRatesCount === 0) {
                    Log::warning('No VAT rates found in database.'); // Added this line
                    $issues[] = [
                        'message' => 'No VAT rates found in database',
                        'fix' => function() { $this->seedVatRates(); }
                    ];
                }

                $defaultVatRate = VatRate::where('is_default', true)->first();
                if (!$defaultVatRate && $vatRatesCount > 0) {
                    Log::warning('No default VAT rate set.'); // Added this line
                    $issues[] = [
                        'message' => 'No default VAT rate set',
                        'fix' => function() { $this->setDefaultVatRate(); }
                    ];
                } else if ($defaultVatRate) {
                    Log::info('Default VAT rate found.'); // Added this line
                }
            } catch (\Exception $e) {
                Log::error('Error accessing VAT rates: ' . $e->getMessage()); // Added this line
                $issues[] = [
                    'message' => 'Error accessing VAT rates: ' . $e->getMessage()
                ];
            }
        } else {
            Log::warning('VAT rates table does not exist, skipping data check.'); // Added this line
        }

        if (Schema::hasTable('plans')) {
            try {
                Log::info('Checking plans data integrity.'); // Added this line
                // Check for plans with missing base_rate
                $plansWithoutBaseRate = DB::table('plans')
                    ->whereNull('base_rate')
                    ->where(function($query) {
                        $query->whereNotNull('price_per_minute')
                              ->orWhereNotNull('price_per_kwh');
                    })
                    ->count();

                if ($plansWithoutBaseRate > 0) {
                    Log::warning("{$plansWithoutBaseRate} plans have missing base_rate."); // Added this line
                    $issues[] = [
                        'message' => "{$plansWithoutBaseRate} plans have missing base_rate",
                        'fix' => function() { $this->fixMissingBaseRates(); }
                    ];
                } else {
                    Log::info('No plans found with missing base_rate.'); // Added this line
                }
            } catch (\Exception $e) {
                Log::error('Error accessing plans: ' . $e->getMessage()); // Added this line
                $issues[] = [
                    'message' => 'Error accessing plans: ' . $e->getMessage()
                ];
            }
        } else {
            Log::warning('Plans table does not exist, skipping data integrity check.'); // Added this line
        }

        Log::info('Finished checkData method.'); // Added this line
        return $issues;
    }

    /**
     * Check controller files
     */
    private function checkControllers()
    {
        Log::info('Executing checkControllers method.'); // Added this line
        $issues = [];
        $controllers = [
            'PlanController' => app_path('Http/Controllers/PlanController.php'),
            'PricingPlanController' => app_path('Http/Controllers/PricingPlanController.php'),
        ];

        foreach ($controllers as $name => $file) {
            Log::info("Checking for controller file: {$file}"); // Added this line
            if (!file_exists($file)) {
                Log::warning("Controller file does not exist: {$file}"); // Added this line
                $issues[] = [
                    'message' => "Controller file does not exist: {$file}",
                ];
            } else {
                Log::info("Controller file exists: {$file}"); // Added this line
                // Check for common issues in controller content
                $content = file_get_contents($file);

                if (!str_contains($content, 'base_rate_per_minute') && !str_contains($content, 'rate_type')) {
                    Log::warning("{$name} may not be updated to handle new rate types."); // Added this line
                    $issues[] = [
                        'message' => "{$name} may not be updated to handle new rate types",
                    ];
                } else {
                    Log::info("{$name} appears to handle new rate types."); // Added this line
                }
            }
        }

        Log::info('Finished checkControllers method.'); // Added this line
        return $issues;
    }

    /**
     * Create VAT rates table
     */
    private function createVatRatesTable()
    {
        Log::info('Executing createVatRatesTable method.'); // Added this line
        Schema::create('vat_rates', function ($table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Log::info('Finished createVatRatesTable method.'); // Added this line
    }

    /**
     * Create plan rates table
     */
    private function createPlanRatesTable()
    {
        Log::info('Executing createPlanRatesTable method.'); // Added this line
        Schema::create('plan_rates', function ($table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('name');
            $table->enum('rate_type', ['minute', 'kwh'])->default('minute');
            $table->decimal('price_per_minute', 8, 2)->nullable();
            $table->decimal('price_per_kwh', 8, 2)->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('vat', 8, 2)->nullable();
            $table->foreignId('vat_rate_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('condition_type', ['all', 'time', 'day', 'power', 'duration', 'custom'])->default('all');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->json('days')->nullable();
            $table->decimal('min_power', 8, 2)->nullable();
            $table->decimal('max_power', 8, 2)->nullable();
            $table->integer('min_duration')->nullable();
            $table->integer('max_duration')->nullable();
            $table->text('custom_condition')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Log::info('Finished createPlanRatesTable method.'); // Added this line
    }

    /**
     * Update plans table with missing columns
     */
    private function updatePlansTable()
    {
        Log::info('Executing updatePlansTable method.'); // Added this line
        Schema::table('plans', function ($table) {
            if (!Schema::hasColumn('plans', 'rate_type')) {
                $table->enum('rate_type', ['minute', 'kwh'])->default('minute');
                Log::info('Added rate_type column to plans table.'); // Added this line
            }
            if (!Schema::hasColumn('plans', 'price_per_minute')) {
                $table->decimal('price_per_minute', 8, 2)->default(0);
                Log::info('Added price_per_minute column to plans table.'); // Added this line
            }
            if (!Schema::hasColumn('plans', 'price_per_kwh')) {
                $table->decimal('price_per_kwh', 8, 2)->default(0);
                Log::info('Added price_per_kwh column to plans table.'); // Added this line
            }
            if (!Schema::hasColumn('plans', 'base_rate')) {
                $table->decimal('base_rate', 8, 2)->nullable();
                Log::info('Added base_rate column to plans table.'); // Added this line
            }
            if (!Schema::hasColumn('plans', 'tva_rate')) {
                $table->decimal('tva_rate', 5, 2)->default(20);
                Log::info('Added tva_rate column to plans table.'); // Added this line
            }
            if (!Schema::hasColumn('plans', 'priority')) {
                $table->integer('priority')->default(0);
                Log::info('Added priority column to plans table.'); // Added this line
            }
            if (!Schema::hasColumn('plans', 'is_active')) {
                $table->boolean('is_active')->default(false);
                Log::info('Added is_active column to plans table.'); // Added this line
            }
        });
        Log::info('Finished updatePlansTable method.'); // Added this line
    }

    /**
     * Seed default VAT rates
     */
    private function seedVatRates()
    {
        Log::info('Executing seedVatRates method.'); // Added this line
        $defaultRates = [
            ['name' => 'Sans TVA', 'rate' => 0, 'is_default' => false, 'is_active' => true],
            ['name' => 'TVA 7%', 'rate' => 7, 'is_default' => false, 'is_active' => true],
            ['name' => 'TVA 14%', 'rate' => 14, 'is_default' => false, 'is_active' => true],
            ['name' => 'TVA 20%', 'rate' => 20, 'is_default' => true, 'is_active' => true],
        ];

        foreach ($defaultRates as $rate) {
            VatRate::firstOrCreate(['rate' => $rate['rate']], $rate);
            Log::info("Seeded or found VAT rate: {$rate['name']}"); // Added this line
        }
        Log::info('Finished seedVatRates method.'); // Added this line
    }

    /**
     * Set a default VAT rate
     */
    private function setDefaultVatRate()
    {
        Log::info('Executing setDefaultVatRate method.'); // Added this line
        $firstVatRate = VatRate::first();
        if ($firstVatRate) {
            $firstVatRate->update(['is_default' => true]);
            Log::info("Set VAT rate ID {$firstVatRate->id} as default."); // Added this line
        } else {
            Log::warning('No VAT rates found to set as default.'); // Added this line
        }
        Log::info('Finished setDefaultVatRate method.'); // Added this line
    }

    /**
     * Fix missing base rates in plans
     */
    private function fixMissingBaseRates()
    {
        Log::info('Executing fixMissingBaseRates method.'); // Added this line
        $updatedMinute = DB::table('plans')
            ->whereNull('base_rate')
            ->where('rate_type', 'minute')
            ->whereNotNull('price_per_minute')
            ->update(['base_rate' => DB::raw('price_per_minute')]);
        Log::info("Fixed {$updatedMinute} plans with missing base_rate for minute rate_type."); // Added this line

        $updatedKwh = DB::table('plans')
            ->whereNull('base_rate')
            ->where('rate_type', 'kwh')
            ->whereNotNull('price_per_kwh')
            ->update(['base_rate' => DB::raw('price_per_kwh')]);
        Log::info("Fixed {$updatedKwh} plans with missing base_rate for kwh rate_type."); // Added this line
        Log::info('Finished fixMissingBaseRates method.'); // Added this line
    }
}