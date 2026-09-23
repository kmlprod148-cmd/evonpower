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
            // Vérifier si les colonnes n'existent pas déjà avant de les ajouter
            if (!Schema::hasColumn('charging_points', 'last_connection_attempt')) {
                $table->timestamp('last_connection_attempt')->nullable();
            }
            if (!Schema::hasColumn('charging_points', 'steve_connection_status')) {
                $table->string('steve_connection_status')->nullable();
            }
            if (!Schema::hasColumn('charging_points', 'charge_box_id')) {
                $table->string('charge_box_id')->nullable();
            }
            if (!Schema::hasColumn('charging_points', 'firmware_version')) {
                $table->string('firmware_version')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            $table->dropColumn([
                'last_connection_attempt',
                'steve_connection_status', 
                'charge_box_id',
                'firmware_version'
            ]);
        });
    }
};