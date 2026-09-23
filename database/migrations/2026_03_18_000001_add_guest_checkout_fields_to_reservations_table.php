<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Guest Checkout Fields to Reservations Table
 * 
 * Adds fields required for the guest checkout flow:
 * - is_guest: Boolean to track if checkout was made as guest
 * - guest_info: JSON field for storing guest information
 * - integrator_id: Link to integrator for the checkout
 * - partner_id: Link to partner for the checkout
 * - metadata: JSON field for additional checkout data
 * 
 * @package Database\Migrations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Guest checkout fields
            if (!Schema::hasColumn('reservations', 'is_guest')) {
                $table->boolean('is_guest')->default(false)->after('guest_phone');
            }
            
            if (!Schema::hasColumn('reservations', 'guest_info')) {
                $table->json('guest_info')->nullable()->after('is_guest');
            }
            
            // Relationship fields for hierarchical data
            if (!Schema::hasColumn('reservations', 'integrator_id')) {
                $table->unsignedBigInteger('integrator_id')->nullable()->after('guest_info');
            }
            
            if (!Schema::hasColumn('reservations', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('integrator_id');
            }
            
            // Transaction reference
            if (!Schema::hasColumn('reservations', 'transaction_id')) {
                $table->unsignedBigInteger('transaction_id')->nullable()->after('partner_id');
            }
            
            // Metadata for additional checkout data (GDPR consent, etc.)
            if (!Schema::hasColumn('reservations', 'metadata')) {
                $table->json('metadata')->nullable()->after('transaction_id');
            }
        });
        
        // Add foreign key constraints
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'integrator_id')) {
                $table->foreign('integrator_id')
                    ->references('id')
                    ->on('integrators')
                    ->onDelete('set null');
            }
            
            if (Schema::hasColumn('reservations', 'partner_id')) {
                $table->foreign('partner_id')
                    ->references('id')
                    ->on('partners')
                    ->onDelete('set null');
            }
            
            if (Schema::hasColumn('reservations', 'transaction_id')) {
                $table->foreign('transaction_id')
                    ->references('id')
                    ->on('transactions')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('reservations', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
            }
            
            if (Schema::hasColumn('reservations', 'partner_id')) {
                $table->dropForeign(['partner_id']);
            }
            
            if (Schema::hasColumn('reservations', 'integrator_id')) {
                $table->dropForeign(['integrator_id']);
            }
            
            // Drop columns
            if (Schema::hasColumn('reservations', 'metadata')) {
                $table->dropColumn('metadata');
            }
            
            if (Schema::hasColumn('reservations', 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }
            
            if (Schema::hasColumn('reservations', 'partner_id')) {
                $table->dropColumn('partner_id');
            }
            
            if (Schema::hasColumn('reservations', 'integrator_id')) {
                $table->dropColumn('integrator_id');
            }
            
            if (Schema::hasColumn('reservations', 'guest_info')) {
                $table->dropColumn('guest_info');
            }
            
            if (Schema::hasColumn('reservations', 'is_guest')) {
                $table->dropColumn('is_guest');
            }
        });
    }
};
