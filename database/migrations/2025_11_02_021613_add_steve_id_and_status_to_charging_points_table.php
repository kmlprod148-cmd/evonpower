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
            if (!Schema::hasColumn('charging_points', 'steve_charging_point_id')) {
                $table->string('steve_charging_point_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('charging_points', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status');
            }
            // Note: Le champ 'status' existe déjà dans la table (enum avec online, offline, etc.)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            if (Schema::hasColumn('charging_points', 'steve_charging_point_id')) {
                $table->dropIndex(['steve_charging_point_id']);
                $table->dropColumn('steve_charging_point_id');
            }
            if (Schema::hasColumn('charging_points', 'status_updated_at')) {
                $table->dropColumn('status_updated_at');
            }
        });
    }
};
