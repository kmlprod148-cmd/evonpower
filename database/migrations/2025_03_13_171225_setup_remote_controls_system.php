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
        Schema::create('remote_controls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['rfid', 'mobile', 'card', 'other']);
            $table->enum('status', ['active', 'inactive', 'pending'])->default('inactive');
            $table->foreignId('station_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_controls');
    }
};