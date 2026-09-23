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
        Schema::dropIfExists('reports');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('custom'); // Added type column
            $table->text('description')->nullable();
            $table->date('start_date')->nullable(); // Renamed and made nullable
            $table->date('end_date')->nullable(); // Renamed and made nullable
            $table->foreignId('partner_id')->nullable()->constrained()->onDelete('set null'); // Added partner_id
            $table->string('status')->default('draft'); // Added default status
            $table->text('recipients')->nullable(); // Added recipients
            $table->boolean('include_charts')->default(false); // Added include_charts
            $table->boolean('include_summary')->default(false); // Added include_summary
            $table->boolean('auto_send')->default(false); // Added auto_send
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
};
