<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get foreign key constraints for a table
     */
    private function getForeignKeyConstraints($tableName)
    {
        $foreignKeys = [];
        $constraints = \DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$tableName]);
        
        foreach ($constraints as $constraint) {
            $foreignKeys[] = $constraint->CONSTRAINT_NAME;
        }
        
        return $foreignKeys;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Add missing columns with existence checks
            if (!Schema::hasColumn('business_profiles', 'base_fee_amount')) {
                $table->decimal('base_fee_amount', 10, 2)->nullable()->after('terminal_fee_period');
            }
            if (!Schema::hasColumn('business_profiles', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }

            // Ensure columns are JSON type and remove old related columns
            if (Schema::hasColumn('business_profiles', 'target_type')) {
                $table->dropColumn('target_type');
            }
            if (!Schema::hasColumn('business_profiles', 'target_audience')) {
                $table->json('target_audience')->nullable()->after('is_active');
            }

            $transactionFeeColumns = ['transaction_fee_type', 'transaction_fee_amount'];
            foreach ($transactionFeeColumns as $column) {
                if (Schema::hasColumn('business_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (!Schema::hasColumn('business_profiles', 'transaction_fee_config')) {
                $table->json('transaction_fee_config')->nullable()->after('maintenance_fee_amount');
            }

            if (Schema::hasColumn('business_profiles', 'charge_fee_amount')) {
                $table->dropColumn('charge_fee_amount');
            }
            if (!Schema::hasColumn('business_profiles', 'charge_fee_config')) {
                $table->json('charge_fee_config')->nullable()->after('transaction_fee_config');
            }

            // Ensure address is nullable if it exists and is not
            if (Schema::hasColumn('business_profiles', 'address')) {
                 $table->string('address')->nullable()->change();
            }


            // Remove extra columns if they exist (based on previous migrations)
            $extraColumns = [
                'email', 'phone', 'website', 'logo', 'city', 'state', 'postal_code',
                'country', 'contact_name', 'contact_title', 'contact_email', 'contact_phone',
                'registration_number', 'experience', 'certifications', 'specialization',
                'type', 'code', 'api_key', 'api_secret', 'webhook_url',
                'vat_number', 'business_hours', 'station_count', 'installation_date',
                'installation_notes'
            ];
            foreach ($extraColumns as $column) {
                if (Schema::hasColumn('business_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Handle integrator_id and partner_id columns séparément due to foreign key constraints
            /*
            if (Schema::hasColumn('business_profiles', 'integrator_id')) {
                // Check if foreign key constraint exists before dropping it
                $foreignKeys = $this->getForeignKeyConstraints('business_profiles');
                if (in_array('business_profiles_integrator_id_foreign', $foreignKeys)) {
                    $table->dropForeign('business_profiles_integrator_id_foreign');
                }
                $table->dropColumn('integrator_id');
            }
            if (Schema::hasColumn('business_profiles', 'partner_id')) {
                // Check if foreign key constraint exists before dropping it
                $foreignKeys = $this->getForeignKeyConstraints('business_profiles');
                if (in_array('business_profiles_partner_id_foreign', $foreignKeys)) {
                    $table->dropForeign('business_profiles_partner_id_foreign');
                }
                $table->dropColumn('partner_id');
            }
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Drop added columns with existence checks
            if (Schema::hasColumn('business_profiles', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('business_profiles', 'base_fee_amount')) {
                $table->dropColumn('base_fee_amount');
            }
            if (Schema::hasColumn('business_profiles', 'charge_fee_config')) {
                $table->dropColumn('charge_fee_config');
            }
            if (Schema::hasColumn('business_profiles', 'transaction_fee_config')) {
                $table->dropColumn('transaction_fee_config');
            }
            if (Schema::hasColumn('business_profiles', 'target_audience')) {
                $table->dropColumn('target_audience');
            }

            // Revert address nullability if it was changed (assuming it was not nullable originally)
             if (Schema::hasColumn('business_profiles', 'address')) {
                 // Keep address nullable during rollback to avoid data truncation errors
                 $table->string('address')->nullable()->change();
             }


            // Add back removed columns if needed (based on previous schema)
             $removedColumns = [
                'email', 'phone', 'website', 'logo', 'city', 'state', 'postal_code',
                'country', 'contact_name', 'contact_title', 'contact_email', 'contact_phone',
                'registration_number', 'experience', 'certifications', 'specialization',
                'type', 'code', 'api_key', 'api_secret', 'webhook_url',
                'vat_number', 'business_hours', 'station_count', 'installation_date',
                'installation_notes', 'target_type', 'transaction_fee_type', 'transaction_fee_amount',
                'charge_fee_amount'
            ];
            foreach ($removedColumns as $column) {
                if (!Schema::hasColumn('business_profiles', $column)) {
                    // This is a simplified approach; actual column types and positions would be needed
                    // For now, just add them back as nullable strings.
                    $table->string($column)->nullable();
                }
            }

            // Add back integrator_id and partner_id columns with foreign key constraints
            if (!Schema::hasColumn('business_profiles', 'integrator_id')) {
                $table->unsignedBigInteger('integrator_id')->nullable();
                $table->foreign('integrator_id')->references('id')->on('integrators')->onDelete('set null');
            }
            if (!Schema::hasColumn('business_profiles', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable();
                $table->foreign('partner_id')->references('id')->on('partners')->onDelete('set null');
            }
        });
    }
};
