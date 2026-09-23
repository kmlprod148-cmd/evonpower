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
        if (!Schema::hasTable('api_monitoring')) {
            Schema::create('api_monitoring', function (Blueprint $table) {
            $table->id();
            $table->string('api_name'); // 'evon' ou 'steve'
            $table->string('endpoint');
            $table->string('method')->default('GET');
            $table->integer('status_code');
            $table->text('response_body')->nullable();
            $table->integer('response_time_ms');
            $table->boolean('success');
            $table->text('error_message')->nullable();
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_id')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->useCurrent();
            $table->timestamps();
            
            // Indexes pour les performances
            $table->index(['api_name', 'success']);
            $table->index(['api_name', 'requested_at']);
            $table->index(['status_code']);
            $table->index(['requested_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_monitoring');
    }
};
