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
                // Drop existing foreign keys if they exist

                // Make columns nullable first if they are not already
                $table->bigInteger('integrator_id')->unsigned()->nullable()->change();
                $table->bigInteger('partner_id')->unsigned()->nullable()->change();
                $table->bigInteger('group_id')->unsigned()->nullable()->change();
            });

            // Update inconsistent data to NULL
            DB::table('charging_points')
                ->whereNotNull('integrator_id')
                ->whereNotIn('integrator_id', DB::table('integrators')->select('id'))
                ->update(['integrator_id' => NULL]);

            DB::table('charging_points')
                ->whereNotNull('partner_id')
                ->whereNotIn('partner_id', DB::table('partners')->select('id'))
                ->update(['partner_id' => NULL]);

            DB::table('charging_points')
                ->whereNotNull('group_id')
                ->whereNotIn('group_id', DB::table('groups')->select('id'))
                ->update(['group_id' => NULL]);

            Schema::table('charging_points', function (Blueprint $table) {
                // Add foreign key constraints
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les contraintes de clé étrangère
            echo "SQLite detected - skipping foreign key constraint modifications\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily
        Schema::disableForeignKeyConstraints();

        Schema::table('charging_points', function (Blueprint $table) {
            // Drop foreign key constraints
            // Drop foreign key constraints by column name if the column exists
            // Revert columns to non-nullable if necessary (assuming they were nullable based on plan)
            // $table->bigInteger('integrator_id')->unsigned()->nullable(false)->change();
            // $table->bigInteger('partner_id')->unsigned()->nullable(false)->change();
            // $table->bigInteger('group_id')->unsigned()->nullable(false)->change();
        });

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }
};
