<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToIntegrators extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('integrators', function (Blueprint $table) {
            if (!Schema::hasColumn('integrators', 'email')) {
                $table->string('email')->nullable();
            }
            
            if (!Schema::hasColumn('integrators', 'phone')) {
                $table->string('phone')->nullable();
            }
            
            if (!Schema::hasColumn('integrators', 'admin_name')) {
                $table->string('admin_name')->nullable();
            }
            
            // Add any other missing columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('integrators', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'phone',
                'admin_name'
            ]);
        });
    }
}