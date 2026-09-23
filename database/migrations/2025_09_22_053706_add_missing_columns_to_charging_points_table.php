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
            // Ajouter les colonnes manquantes si elles n'existent pas
            if (!Schema::hasColumn('charging_points', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('name');
            }
            if (!Schema::hasColumn('charging_points', 'manufacturer')) {
                $table->string('manufacturer')->nullable()->after('serial_number');
            }
            if (!Schema::hasColumn('charging_points', 'model')) {
                $table->string('model')->nullable()->after('manufacturer');
            }
            if (!Schema::hasColumn('charging_points', 'location')) {
                $table->string('location')->nullable()->after('model');
            }
            if (!Schema::hasColumn('charging_points', 'status')) {
                $table->enum('status', ['online', 'offline', 'maintenance', 'error'])->default('online')->after('location');
            }
            if (!Schema::hasColumn('charging_points', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('status');
            }
            if (!Schema::hasColumn('charging_points', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('charging_points', 'power_output')) {
                $table->decimal('power_output', 8, 2)->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('charging_points', 'connector_type')) {
                $table->string('connector_type')->nullable()->after('power_output');
            }
            if (!Schema::hasColumn('charging_points', 'connection_type')) {
                $table->string('connection_type')->nullable()->after('connector_type');
            }
            if (!Schema::hasColumn('charging_points', 'communication_protocol')) {
                $table->string('communication_protocol')->nullable()->after('connection_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            //
        });
    }
};
