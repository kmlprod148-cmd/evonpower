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
        Schema::table('integrators', function (Blueprint $table) {
            if (!Schema::hasColumn('integrators', 'address')) {
                $table->string('address')->nullable()->after('city');
            }
            if (!Schema::hasColumn('integrators', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('address');
            }
            if (!Schema::hasColumn('integrators', 'country')) {
                $table->string('country')->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('integrators', 'website')) {
                $table->string('website')->nullable()->after('country');
            }
            if (!Schema::hasColumn('integrators', 'description')) {
                $table->text('description')->nullable()->after('website');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrators', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('integrators', 'address')) {
                $columnsToDrop[] = 'address';
            }
            if (Schema::hasColumn('integrators', 'postal_code')) {
                $columnsToDrop[] = 'postal_code';
            }
            if (Schema::hasColumn('integrators', 'country')) {
                $columnsToDrop[] = 'country';
            }
            if (Schema::hasColumn('integrators', 'website')) {
                $columnsToDrop[] = 'website';
            }
            if (Schema::hasColumn('integrators', 'description')) {
                $columnsToDrop[] = 'description';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
