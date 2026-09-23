<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   
public function up()
{
    // First check if the contact_phone column exists
    if (!Schema::hasColumn('integrators', 'contact_phone')) {
        // ADD the column since it doesn't exist
        Schema::table('integrators', function (Blueprint $table) {
            $table->string('contact_phone')->nullable();
        });
    } else {
        // This part won't run if the column doesn't exist
        Schema::table('integrators', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->change();
        });
    }
}

public function down()
{
    // Only drop the column if it was added by this migration
    if (Schema::hasColumn('integrators', 'contact_phone')) {
        Schema::table('integrators', function (Blueprint $table) {
            $table->dropColumn('contact_phone');
        });
    }
}
};
