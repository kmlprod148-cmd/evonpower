<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Integrator Payment Credentials
 * 
 * This table stores encrypted payment gateway credentials for each integrator.
 * Supports both Stripe and CMI credentials with secure encryption.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('integrator_payment_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('integrator_id');
            $table->string('gateway_type'); // 'stripe' or 'cmi'
            $table->string('environment'); // 'test' or 'production'
            
            // Encrypted credentials storage
            $table->text('encrypted_credentials'); // JSON encrypted
            
            // Public/non-sensitive information
            $table->string('public_key')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('merchant_id')->nullable(); // For CMI
            
            // Status and validation
            $table->boolean('is_active')->default(true);
            $table->boolean('is_validated')->default(false);
            $table->timestamp('last_validated_at')->nullable();
            $table->string('validation_error')->nullable();
            
            // Metadata
            $table->json('settings')->nullable(); // Non-sensitive settings
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('integrator_id')
                ->references('id')
                ->on('integrators')
                ->onDelete('cascade');
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Unique constraint per integrator + gateway + environment
            $table->unique(['integrator_id', 'gateway_type', 'environment'], 'unique_integrator_gateway_env');
            
            // Indexes
            $table->index('gateway_type');
            $table->index('is_active');
        });
        
        // Add payment credentials columns to integrators table as fallback
        Schema::table('integrators', function (Blueprint $table) {
            // Stripe credentials (encrypted JSON)
            $table->text('stripe_credentials')->nullable()->after('collection_mode');
            
            // CMI credentials (encrypted JSON)
            $table->text('cmi_credentials')->nullable()->after('stripe_credentials');
            
            // Payment settings (metadata JSON, non-sensitive)
            $table->json('payment_settings')->nullable()->after('cmi_credentials');
            
            // Preferred payment gateway
            $table->enum('preferred_payment_gateway', ['stripe', 'cmi', 'platform'])->default('platform')->after('payment_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrators', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_credentials',
                'cmi_credentials',
                'payment_settings',
                'preferred_payment_gateway',
            ]);
        });
        
        Schema::dropIfExists('integrator_payment_credentials');
    }
};
