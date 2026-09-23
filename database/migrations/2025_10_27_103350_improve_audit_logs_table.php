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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Make user_id nullable for system events
            $table->foreignId('user_id')->nullable()->change();
            
            // Add description field
            $table->text('description')->nullable()->after('action');
            
            // Rename and improve metadata fields
            $table->json('metadata')->nullable()->after('description');
            
            // Add severity level for security events
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low')->after('metadata');
            
            // Add session ID for tracking
            $table->string('session_id')->nullable()->after('severity');
            
            // Add request method
            $table->string('method', 10)->nullable()->after('session_id');
            
            // Add response status
            $table->integer('status_code')->nullable()->after('method');
            
            // Add execution time
            $table->integer('execution_time_ms')->nullable()->after('status_code');
            
            // Add indexes for better performance
            $table->index(['severity', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'description',
                'metadata',
                'severity',
                'session_id',
                'method',
                'status_code',
                'execution_time_ms'
            ]);
            
            // Drop new indexes
            $table->dropIndex(['severity', 'created_at']);
            $table->dropIndex(['action', 'created_at']);
            $table->dropIndex(['session_id']);
            
            // Revert user_id to not nullable
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};