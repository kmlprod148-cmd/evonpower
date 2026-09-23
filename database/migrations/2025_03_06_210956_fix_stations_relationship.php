<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('stations', function (Blueprint $table) {
        // Temporarily remove foreign key constraint until integrators table exists
        if (!Schema::hasColumn('stations', 'integrator_id')) {
            $table->unsignedBigInteger('integrator_id')->nullable();
        }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            //
        });
    }
};
