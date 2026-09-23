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
            $table->decimal('min_transaction_value', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_plans', function (Blueprint $table) {
            $table->decimal('min_transaction_value', 10, 2)->nullable()->change();
        });
    }
};
