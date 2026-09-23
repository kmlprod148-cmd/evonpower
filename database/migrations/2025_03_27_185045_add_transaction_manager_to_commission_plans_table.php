<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('commission_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_plans', 'transaction_manager')) {
                $table->string('transaction_manager')->nullable()->after('applies_to_id');
            }
        });
    }

    public function down()
    {
        Schema::table('commission_plans', function (Blueprint $table) {
            if (Schema::hasColumn('commission_plans', 'transaction_manager')) {
                $table->dropColumn('transaction_manager');
            }
        });
    }
};