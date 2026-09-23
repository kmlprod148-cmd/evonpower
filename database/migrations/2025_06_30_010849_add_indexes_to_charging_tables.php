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
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Check if the index already exists before adding it
            if (!Schema::hasIndex('charging_sessions', 'charging_sessions_charging_point_id_index')) {
                $table->index('charging_point_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Drop the foreign key constraint before dropping the index
            $table->dropForeign(['charging_point_id']);

            // Check if the index exists before dropping it
            if (Schema::hasIndex('charging_sessions', 'charging_sessions_charging_point_id_index')) {
                $table->dropIndex(['charging_point_id']);
            }
        });
    }
};
