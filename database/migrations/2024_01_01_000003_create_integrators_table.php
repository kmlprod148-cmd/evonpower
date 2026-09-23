<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIntegratorsTable extends Migration
{
    protected $constraints = [];
    protected $backupData = [];
    
    public function up()
    {
        // Simple table creation for SQLite compatibility
        Schema::create('integrators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('admin_name')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // This is complex to reverse properly
        // You might want to implement a specific rollback strategy if needed
    }
}