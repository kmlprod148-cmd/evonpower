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
        Schema::table('settings', function (Blueprint $table) {
            // Add missing columns with existence checks
            if (!Schema::hasColumn('settings', 'category')) {
                $table->string('category')->nullable()->after('type');
            }
            if (!Schema::hasColumn('settings', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('category');
            }
            if (!Schema::hasColumn('settings', 'encrypted')) {
                $table->boolean('encrypted')->default(false)->after('is_public');
            }
            if (!Schema::hasColumn('settings', 'validation_rules')) {
                $table->json('validation_rules')->nullable()->after('encrypted');
            }

            // Remove description column if it exists
            if (Schema::hasColumn('settings', 'description')) {
                $table->dropColumn('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Drop added columns with existence checks
            if (Schema::hasColumn('settings', 'validation_rules')) {
                $table->dropColumn('validation_rules');
            }
            if (Schema::hasColumn('settings', 'encrypted')) {
                $table->dropColumn('encrypted');
            }
            if (Schema::hasColumn('settings', 'is_public')) {
                $table->dropColumn('is_public');
            }
            if (Schema::hasColumn('settings', 'category')) {
                $table->dropColumn('category');
            }

            // Add back description column if it was dropped
            if (!Schema::hasColumn('settings', 'description')) {
                $table->text('description')->nullable()->after('type'); // Assuming original position
            }
        });
    }
};
