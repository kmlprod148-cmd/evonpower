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
        Schema::table('additional_rates', function (Blueprint $table) {
            // Drop old price columns if they exist
            if (Schema::hasColumn('additional_rates', 'price_per_kwh')) {
                $table->dropColumn('price_per_kwh');
            }
            if (Schema::hasColumn('additional_rates', 'price_per_minute')) {
                $table->dropColumn('price_per_minute');
            }

            // Add new 'price' column if it doesn't exist
            if (!Schema::hasColumn('additional_rates', 'price')) {
                $table->decimal('price', 8, 2)->default(0.00)->after('rate_type');
            }

            // Add pricing_plan_id if it doesn't exist
            if (!Schema::hasColumn('additional_rates', 'pricing_plan_id')) {
                $table->unsignedBigInteger('pricing_plan_id')->nullable()->after('id');
            }
            // Add vat_rate_id if it doesn't exist
            if (!Schema::hasColumn('additional_rates', 'vat_rate_id')) {
                $table->unsignedBigInteger('vat_rate_id')->nullable()->after('price');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('additional_rates', function (Blueprint $table) {
                if (Schema::hasColumn('additional_rates', 'pricing_plan_id')) {
                    $foreignKeys = DB::select(
                        'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                        [DB::getDatabaseName(), 'additional_rates', 'pricing_plan_id']
                    );

                    if (empty($foreignKeys)) {
                        $table->foreign('pricing_plan_id')->references('id')->on('pricing_plans')->onDelete('cascade');
                    }
                }
                if (Schema::hasColumn('additional_rates', 'vat_rate_id')) {
                    $foreignKeys = DB::select(
                        'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                        [DB::getDatabaseName(), 'additional_rates', 'vat_rate_id']
                    );

                    if (empty($foreignKeys)) {
                        $table->foreign('vat_rate_id')->references('id')->on('vat_rates')->onDelete('set null');
                    }
                }
            });
        } else {
            // Pour SQLite, on ne peut pas facilement ajouter des clés étrangères après création
            echo "SQLite detected - skipping foreign key additions\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('additional_rates', function (Blueprint $table) {
            // Drop foreign keys and columns in reverse order of creation
            if (Schema::hasColumn('additional_rates', 'vat_rate_id')) {
                $table->dropForeign(['vat_rate_id']);
                $table->dropColumn('vat_rate_id');
            }
            if (Schema::hasColumn('additional_rates', 'pricing_plan_id')) {
                $table->dropForeign(['pricing_plan_id']);
                $table->dropColumn('pricing_plan_id');
            }
            
            // Re-add old price columns if they were dropped
            if (!Schema::hasColumn('additional_rates', 'price_per_kwh')) {
                $table->decimal('price_per_kwh', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('additional_rates', 'price_per_minute')) {
                $table->decimal('price_per_minute', 8, 2)->nullable();
            }

            // Drop new 'price' column if it was added
            if (Schema::hasColumn('additional_rates', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
