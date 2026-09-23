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
        Schema::table('stations', function (Blueprint $table) {
            // Add missing columns with existence checks
            if (!Schema::hasColumn('stations', 'identifier')) {
                $table->string('identifier')->unique()->nullable()->after('name');
            }
            if (!Schema::hasColumn('stations', 'status')) {
                $table->string('status')->nullable()->after('longitude');
            }

            // Adjust latitude and longitude precision
            if (Schema::hasColumn('stations', 'latitude')) {
                $table->decimal('latitude', 11, 8)->change();
            }
            if (Schema::hasColumn('stations', 'longitude')) {
                $table->decimal('longitude', 11, 8)->change();
            }

            // Remove extra columns if they exist
            $extraColumns = ['opening_hours', 'description'];
            foreach ($extraColumns as $column) {
                if (Schema::hasColumn('stations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            // Drop added columns with existence checks
            if (Schema::hasColumn('stations', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('stations', 'identifier')) {
                $table->dropColumn('identifier');
            }

            // Revert latitude and longitude precision (assuming original was 10, 7)
            if (Schema::hasColumn('stations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->change();
            }
            if (Schema::hasColumn('stations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->change();
            }

            // Add back removed columns if needed (based on previous schema)
            $removedColumns = ['opening_hours', 'description'];
            foreach ($removedColumns as $column) {
                if (!Schema::hasColumn('stations', $column)) {
                    // This is a simplified approach; actual column types and positions would be needed
                    // For now, just add them back as nullable strings/text.
                    if ($column === 'description') {
                        $table->text($column)->nullable();
                    } else {
                        $table->json($column)->nullable();
                    }
                }
            }
        });
    }
};
