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
        Schema::table('commission_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_plans', 'type')) {
                $table->string('type')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_plans', function (Blueprint $table) {
            if (Schema::hasColumn('commission_plans', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
