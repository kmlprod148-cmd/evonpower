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
        if (DB::getDriverName() === 'mysql') {
            Schema::table('charging_points', function (Blueprint $table) {
                // Check if the column exists before adding the foreign key
                if (Schema::hasColumn('charging_points', 'integrator_id')) {
                    // Drop existing foreign key if it exists to avoid duplicates
                    // Using conventional naming to avoid Doctrine dependency issues
                    try {
                        $table->dropForeign('charging_points_integrator_id_foreign');
                    } catch (\Exception $e) {
                        // Ignore error if foreign key doesn't exist
                    }
                    // Add the foreign key
                    $table->foreign('integrator_id')->references('id')->on('integrators')->onDelete('cascade');
                }
            });
        } else {
            // Pour SQLite, on ne peut pas facilement ajouter des clés étrangères après création
            echo "SQLite detected - skipping foreign key addition\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            if (Schema::hasColumn('charging_points', 'integrator_id')) {
                $table->dropForeign(['integrator_id']);
            }
        });
    }
};
