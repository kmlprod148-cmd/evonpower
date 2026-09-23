<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocpp_command_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->string('charge_box_id');
            $table->unsignedInteger('connector_id')->nullable();
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->unsignedBigInteger('charging_session_id')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->json('response')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['charge_box_id', 'status']);
            $table->index(['reservation_id', 'command']);
            $table->index(['correlation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocpp_command_outbox');
    }
};