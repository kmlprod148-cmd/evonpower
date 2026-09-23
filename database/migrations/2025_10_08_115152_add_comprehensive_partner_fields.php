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
        Schema::table('partners', function (Blueprint $table) {
            // Champs de contact étendus
            $table->string('technical_contact_name')->nullable();
            $table->string('technical_contact_email')->nullable();
            $table->string('technical_contact_phone')->nullable();
            $table->string('technical_contact_title')->nullable();
            
            $table->string('commercial_contact_name')->nullable();
            $table->string('commercial_contact_email')->nullable();
            $table->string('commercial_contact_phone')->nullable();
            $table->string('commercial_contact_title')->nullable();
            
            $table->text('internal_notes')->nullable();
            
            // Champs business étendus
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();
            
            $table->string('sector')->nullable();
            $table->string('company_size')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->integer('employee_count')->nullable();
            
            $table->text('billing_address')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('currency', 3)->default('EUR');
            
            // Champs de paramètres
            $table->string('timezone')->default('Europe/Paris');
            $table->string('date_format')->default('d/m/Y');
            
            $table->boolean('receive_marketing')->default(false);
            $table->boolean('receive_sms')->default(false);
            
            $table->string('access_level')->default('basic');
            $table->boolean('api_access')->default(false);
            $table->integer('max_users')->default(5);
            $table->integer('max_charging_points')->default(10);
            
            $table->boolean('two_factor_auth')->default(false);
            $table->boolean('password_expiry')->default(false);
            $table->boolean('ip_restriction')->default(false);
            $table->text('allowed_ips')->nullable();
            
            $table->string('billing_frequency')->default('monthly');
            $table->boolean('auto_renewal')->default(false);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'technical_contact_name',
                'technical_contact_email',
                'technical_contact_phone',
                'technical_contact_title',
                'commercial_contact_name',
                'commercial_contact_email',
                'commercial_contact_phone',
                'commercial_contact_title',
                'internal_notes',
                'bank_name',
                'bank_account',
                'iban',
                'bic',
                'sector',
                'company_size',
                'annual_revenue',
                'employee_count',
                'billing_address',
                'billing_email',
                'payment_terms',
                'currency',
                'timezone',
                'date_format',
                'receive_marketing',
                'receive_sms',
                'access_level',
                'api_access',
                'max_users',
                'max_charging_points',
                'two_factor_auth',
                'password_expiry',
                'ip_restriction',
                'allowed_ips',
                'billing_frequency',
                'auto_renewal',
                'discount_percentage',
                'credit_limit'
            ]);
        });
    }
};
