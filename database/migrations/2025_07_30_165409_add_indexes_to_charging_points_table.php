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
            // Vérifier si les index existent avant de les créer
            if (!Schema::hasIndex('charging_points', 'charging_points_status_index')) {
                $table->index('status');
            }
            if (!Schema::hasIndex('charging_points', 'charging_points_group_id_index')) {
                $table->index('group_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['group_id']);
        });
    }
};
