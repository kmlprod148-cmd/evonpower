<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('connectors')) {
            Schema::create('connectors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('charging_point_id')->constrained()->onDelete('cascade');
                $table->integer('connector_id');
                $table->string('type');
                $table->string('status')->default('Available');
                $table->decimal('power', 8, 2);
                $table->string('format');
                $table->string('tariff_id')->nullable();
                $table->timestamps();

                $table->unique(['charging_point_id', 'connector_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('connectors');
    }
};
