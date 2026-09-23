<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocpp_incoming_events', function (Blueprint $table) {
            $table->id();
            $table->string('message_type');
            $table->string('charge_box_id');
            $table->unsignedInteger('connector_id')->nullable();
            $table->string('action')->nullable();
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestamps();

            $table->index(['charge_box_id', 'message_type']);
            $table->index(['received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocpp_incoming_events');
    }
};