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
        Schema::create('remote_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charging_point_id')->constrained('charging_points')->onDelete('cascade');
            $table->string('action'); // e.g., 'start_charging', 'stop_charging', etc.
            $table->string('status'); // e.g., 'Accepted', 'Rejected', 'Pending', 'Failed'
            $table->json('response_json')->nullable(); // Full response from Steve API
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for better query performance
            $table->index('charging_point_id');
            $table->index('action');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_actions');
    }
};
