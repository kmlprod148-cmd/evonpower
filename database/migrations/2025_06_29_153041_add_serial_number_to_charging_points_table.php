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
            if (!Schema::hasColumn('charging_points', 'serial_number')) {
                $table->string('serial_number')->nullable()->unique()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            if (Schema::hasColumn('charging_points', 'serial_number')) {
                $table->dropColumn('serial_number');
            }
        });
    }
};
