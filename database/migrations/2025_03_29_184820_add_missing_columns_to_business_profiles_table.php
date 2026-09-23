<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('business_profiles', 'name')) {
                $table->string('name', 100)->after('id');
            }
            if (!Schema::hasColumn('business_profiles', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('business_profiles', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('description');
            }
            if (!Schema::hasColumn('business_profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_public');
            }
            if (!Schema::hasColumn('business_profiles', 'target_type')) {
                if (DB::getDriverName() === 'sqlite') {
                    $table->string('target_type')->default('integrator')->after('is_active');
                } else {
                    $table->enum('target_type', ['integrator', 'partner'])->after('is_active');
                }
            }
            if (!Schema::hasColumn('business_profiles', 'maintenance_fee_type')) {
                if (DB::getDriverName() === 'sqlite') {
                    $table->string('maintenance_fee_type')->nullable()->after('target_type');
                } else {
                    $table->enum('maintenance_fee_type', ['monthly', 'quarterly', 'yearly'])->nullable()->after('target_type');
                }
            }
            if (!Schema::hasColumn('business_profiles', 'maintenance_fee_amount')) {
                $table->decimal('maintenance_fee_amount', 10, 2)->nullable()->after('maintenance_fee_type');
            }
            if (!Schema::hasColumn('business_profiles', 'transaction_fee_type')) {
                if (DB::getDriverName() === 'sqlite') {
                    $table->string('transaction_fee_type')->nullable()->after('maintenance_fee_amount');
                } else {
                    $table->enum('transaction_fee_type', ['fixed', 'percentage'])->nullable()->after('maintenance_fee_amount');
                }
            }
            if (!Schema::hasColumn('business_profiles', 'transaction_fee_amount')) {
                $table->decimal('transaction_fee_amount', 10, 2)->nullable()->after('transaction_fee_type');
            }
            if (!Schema::hasColumn('business_profiles', 'charge_fee_amount')) {
                $table->decimal('charge_fee_amount', 10, 2)->nullable()->after('transaction_fee_amount');
            }
            if (!Schema::hasColumn('business_profiles', 'terminal_fee_amount')) {
                $table->decimal('terminal_fee_amount', 10, 2)->nullable()->after('charge_fee_amount');
            }
            if (!Schema::hasColumn('business_profiles', 'terminal_fee_period')) {
                if (DB::getDriverName() === 'sqlite') {
                    $table->string('terminal_fee_period')->nullable()->after('terminal_fee_amount');
                } else {
                    $table->enum('terminal_fee_period', ['monthly', 'quarterly', 'yearly'])->nullable()->after('terminal_fee_amount');
                }
            }
            
            if (!Schema::hasColumn('business_profiles', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            
            if (!Schema::hasColumn('business_profiles', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('business_profiles', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('business_profiles', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('business_profiles', 'is_public')) {
                $table->dropColumn('is_public');
            }
            if (Schema::hasColumn('business_profiles', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('business_profiles', 'target_type')) {
                $table->dropColumn('target_type');
            }
            if (Schema::hasColumn('business_profiles', 'maintenance_fee_type')) {
                $table->dropColumn('maintenance_fee_type');
            }
            if (Schema::hasColumn('business_profiles', 'maintenance_fee_amount')) {
                $table->dropColumn('maintenance_fee_amount');
            }
            if (Schema::hasColumn('business_profiles', 'transaction_fee_type')) {
                $table->dropColumn('transaction_fee_type');
            }
            if (Schema::hasColumn('business_profiles', 'transaction_fee_amount')) {
                $table->dropColumn('transaction_fee_amount');
            }
            if (Schema::hasColumn('business_profiles', 'charge_fee_amount')) {
                $table->dropColumn('charge_fee_amount');
            }
            if (Schema::hasColumn('business_profiles', 'terminal_fee_amount')) {
                $table->dropColumn('terminal_fee_amount');
            }
            if (Schema::hasColumn('business_profiles', 'terminal_fee_period')) {
                $table->dropColumn('terminal_fee_period');
            }
        });
    }
};
