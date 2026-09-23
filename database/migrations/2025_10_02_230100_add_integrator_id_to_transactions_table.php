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
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'integrator_id')) {
                $table->unsignedBigInteger('integrator_id')->nullable()->after('charging_point_id');
            }
            if (!Schema::hasColumn('transactions', 'transaction_type')) {
                $table->string('transaction_type')->nullable()->after('integrator_id');
            }
            if (!Schema::hasColumn('transactions', 'transaction_category')) {
                $table->string('transaction_category')->nullable()->after('transaction_type');
            }
            if (!Schema::hasColumn('transactions', 'description')) {
                $table->text('description')->nullable()->after('transaction_category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'integrator_id')) {
                $table->dropColumn('integrator_id');
            }
            if (Schema::hasColumn('transactions', 'transaction_type')) {
                $table->dropColumn('transaction_type');
            }
            if (Schema::hasColumn('transactions', 'transaction_category')) {
                $table->dropColumn('transaction_category');
            }
            if (Schema::hasColumn('transactions', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
