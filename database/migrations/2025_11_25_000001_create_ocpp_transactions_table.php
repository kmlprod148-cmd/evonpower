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
        Schema::create('ocpp_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('charge_box_id');
            $table->integer('connector_id');
            $table->string('ocpp_id_tag');
            $table->string('status')->default('PENDING');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->decimal('start_meter', 12, 3)->nullable();
            $table->decimal('stop_meter', 12, 3)->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('charge_box_id');
            $table->index('connector_id');
            $table->index('ocpp_id_tag');
            $table->index('status');
            $table->index('reservation_id');
            $table->index(['charge_box_id', 'connector_id', 'status']);

            // Foreign keys
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocpp_transactions');
    }
};

