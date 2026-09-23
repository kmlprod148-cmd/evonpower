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
        if (Schema::hasTable('charge_point_commands')) {
            return;
        }

        Schema::create('charge_point_commands', function (Blueprint $table) {
            $table->id();
            $table->string('charge_box_id')->index();
            $table->string('command');
            $table->json('params')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'failed'])->default('pending');
            $table->json('response')->nullable();
            $table->foreignId('triggered_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration duplicates an earlier table-creation migration.
        // Leave teardown to the original migration to avoid dropping the table twice.
    }
};
