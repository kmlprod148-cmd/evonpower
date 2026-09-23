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
        Schema::table('users', function (Blueprint $table) {
            // Add the foreign key column for the integrator relationship
            // Make it nullable as users might not always belong to an integrator (e.g., admins)
            // Ensure it comes after the 'id' column or another logical place
            $table->foreignId('integrator_id')
                  ->nullable()
                  ->after('id') // Or choose a different column to place it after
                  ->constrained('integrators')
                  ->onUpdate('cascade')
                  ->onDelete('set null'); // Or 'cascade' if users should be deleted with integrator
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['integrator_id']);
            // Then drop the column
            $table->dropColumn('integrator_id');
        });
    }
};