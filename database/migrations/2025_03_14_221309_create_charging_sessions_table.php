<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargingSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charging_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->unique()->comment('Unique session identifier used in communication with charging point');
            
            $table->foreignId('charging_point_id')
                ->constrained()
                ->cascadeOnDelete();
                
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
                
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            
            $table->decimal('energy_consumed', 10, 2)
                ->default(0)
                ->comment('Energy consumed in kWh');
                
            $table->decimal('duration', 10, 2)
                ->default(0)
                ->comment('Duration in minutes');
                
            $table->decimal('cost', 10, 2)
                ->default(0)
                ->comment('Cost in default currency');
                
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])
                ->default('pending');
                
            $table->enum('status', ['in_progress', 'completed', 'stopped', 'error'])
                ->default('in_progress');
                
            $table->json('meter_values')->nullable()->comment('JSON containing meter values during charging');
            $table->json('metadata')->nullable()->comment('Additional session metadata');
            
            $table->string('stop_reason')->nullable()->comment('Reason for stopping the session');
            $table->string('error_code')->nullable()->comment('Error code if session ended with error');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charging_sessions');
    }
}