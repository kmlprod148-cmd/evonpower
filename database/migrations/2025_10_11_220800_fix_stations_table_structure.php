<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            // Ajouter les colonnes manquantes si elles n'existent pas
            if (!Schema::hasColumn('stations', 'name')) {
                $table->string('name');
            }
            if (!Schema::hasColumn('stations', 'address')) {
                $table->string('address');
            }
            if (!Schema::hasColumn('stations', 'city')) {
                $table->string('city');
            }
            if (!Schema::hasColumn('stations', 'postal_code')) {
                $table->string('postal_code')->nullable();
            }
            if (!Schema::hasColumn('stations', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('stations', 'type')) {
                $table->enum('type', ['private', 'public', 'commercial']);
            }
            if (!Schema::hasColumn('stations', 'status')) {
                $table->enum('status', ['active', 'inactive', 'maintenance', 'planned'])->default('active');
            }
            if (!Schema::hasColumn('stations', 'group_id')) {
                $table->unsignedBigInteger('group_id')->nullable();
            }
            if (!Schema::hasColumn('stations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('stations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('stations', 'opening_hours')) {
                $table->json('opening_hours')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $columns = ['name', 'address', 'city', 'postal_code', 'country', 'type', 'status', 'group_id', 'latitude', 'longitude', 'opening_hours'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('stations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
