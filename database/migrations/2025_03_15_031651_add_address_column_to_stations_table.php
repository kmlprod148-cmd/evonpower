<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressColumnToStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stations', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('stations', 'address')) {
                $table->string('address')->nullable()->after('code');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stations', function (Blueprint $table) {
            // Drop the column if it exists
            if (Schema::hasColumn('stations', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
}