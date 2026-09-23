<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute les champs nécessaires pour le mode postpayé:
     * - postpaid_status: statut de l'autorisation postpayé
     * - postpaid_credit_limit: limite de crédit autorisée
     * - postpaid_approved_at: date d'approbation
     * - postpaid_approved_by: utilisateur qui a approuvé
     * - postpaid_billing_day: jour de facturation
     * - postpaid_suspended_at: date de suspension
     * - postpaid_suspension_reason: raison de la suspension
     * - postpaid_revoked_at: date de révocation
     * - postpaid_revoked_by: utilisateur qui a révoqué
     * - postpaid_revocation_reason: raison de la révocation
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Statut de l'autorisation postpayé
            $table->enum('postpaid_status', [
                'not_authorized',
                'pending',
                'approved',
                'suspended',
                'revoked'
            ])->default('not_authorized')->after('default_payment_method');
            
            // Limite de crédit
            $table->decimal('postpaid_credit_limit', 12, 2)->nullable()->after('postpaid_status');
            
            // Date et utilisateur d'approbation
            $table->timestamp('postpaid_approved_at')->nullable()->after('postpaid_credit_limit');
            $table->unsignedBigInteger('postpaid_approved_by')->nullable()->after('postpaid_approved_at');
            $table->foreign('postpaid_approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Jour de facturation (1-31)
            $table->tinyInteger('postpaid_billing_day')->nullable()->after('postpaid_approved_by');
            
            // Suspension
            $table->timestamp('postpaid_suspended_at')->nullable()->after('postpaid_billing_day');
            $table->string('postpaid_suspension_reason', 500)->nullable()->after('postpaid_suspended_at');
            
            // Révocation
            $table->timestamp('postpaid_revoked_at')->nullable()->after('postpaid_suspension_reason');
            $table->unsignedBigInteger('postpaid_revoked_by')->nullable()->after('postpaid_revoked_at');
            $table->foreign('postpaid_revoked_by')->references('id')->on('users')->onDelete('set null');
            $table->string('postpaid_revocation_reason', 500)->nullable()->after('postpaid_revoked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer les colonnes dans l'ordre inverse
            $table->dropColumn([
                'postpaid_revocation_reason',
                'postpaid_revoked_by',
                'postpaid_revoked_at',
                'postpaid_suspension_reason',
                'postpaid_suspended_at',
                'postpaid_billing_day',
                'postpaid_approved_by',
                'postpaid_approved_at',
                'postpaid_credit_limit',
                'postpaid_status',
            ]);
        });
    }
};
