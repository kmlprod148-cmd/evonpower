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
        Schema::table('business_profiles', function (Blueprint $table) {
            // Champs pour la gestion des transactions et virements
            $table->json('transaction_management_config')->nullable()->after('charge_fee_config');
            $table->json('payment_processing_config')->nullable()->after('transaction_management_config');
            $table->json('wire_transfer_config')->nullable()->after('payment_processing_config');
            
            // Champs pour les informations de contact et comptes bancaires
            $table->string('admin_contact_name')->nullable()->after('wire_transfer_config');
            $table->string('admin_contact_email')->nullable()->after('admin_contact_name');
            $table->string('admin_contact_phone')->nullable()->after('admin_contact_email');
            $table->string('integrator_contact_name')->nullable()->after('admin_contact_phone');
            $table->string('integrator_contact_email')->nullable()->after('integrator_contact_name');
            $table->string('integrator_contact_phone')->nullable()->after('integrator_contact_email');
            
            // Champs pour les comptes bancaires
            $table->string('admin_bank_account')->nullable()->after('integrator_contact_phone');
            $table->string('integrator_bank_account')->nullable()->after('admin_bank_account');
            $table->string('operator_bank_account')->nullable()->after('integrator_bank_account');
            
            // Champs pour les responsabilités de traitement
            $table->boolean('handles_admin_debits')->default(false)->after('operator_bank_account');
            $table->boolean('handles_integrator_debits')->default(false)->after('handles_admin_debits');
            $table->boolean('handles_client_payments')->default(false)->after('handles_integrator_debits');
            $table->boolean('handles_wire_transfers')->default(false)->after('handles_client_payments');
            
            // Champs pour les seuils et limites
            $table->decimal('min_transaction_amount', 10, 2)->nullable()->after('handles_wire_transfers');
            $table->decimal('max_transaction_amount', 10, 2)->nullable()->after('min_transaction_amount');
            $table->decimal('daily_limit', 10, 2)->nullable()->after('max_transaction_amount');
            $table->decimal('monthly_limit', 10, 2)->nullable()->after('daily_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_management_config',
                'payment_processing_config',
                'wire_transfer_config',
                'admin_contact_name',
                'admin_contact_email',
                'admin_contact_phone',
                'integrator_contact_name',
                'integrator_contact_email',
                'integrator_contact_phone',
                'admin_bank_account',
                'integrator_bank_account',
                'operator_bank_account',
                'handles_admin_debits',
                'handles_integrator_debits',
                'handles_client_payments',
                'handles_wire_transfers',
                'min_transaction_amount',
                'max_transaction_amount',
                'daily_limit',
                'monthly_limit'
            ]);
        });
    }
};