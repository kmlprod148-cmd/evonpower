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
        Schema::table('charging_points', function (Blueprint $table) {
            $table->integer('max_duration')->nullable()->after('pricing_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            $table->dropColumn('max_duration');
        });
    }
};
