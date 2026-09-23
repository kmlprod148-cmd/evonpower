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
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('transactions', 'admin_current_balance')) {
                    $table->decimal('admin_current_balance', 15, 2)->nullable()->after('current_balance')->comment('Solde du wallet admin au moment de la transaction');
                }
                if (!Schema::hasColumn('transactions', 'integrator_current_balance')) {
                    $table->decimal('integrator_current_balance', 15, 2)->nullable()->after('admin_current_balance')->comment('Solde du wallet intégrateur au moment de la transaction');
                }
                if (!Schema::hasColumn('transactions', 'operator_current_balance')) {
                    $table->decimal('operator_current_balance', 15, 2)->nullable()->after('integrator_current_balance')->comment('Solde du wallet opérateur/partenaire au moment de la transaction');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'operator_current_balance')) {
                    $table->dropColumn('operator_current_balance');
                }
                if (Schema::hasColumn('transactions', 'integrator_current_balance')) {
                    $table->dropColumn('integrator_current_balance');
                }
                if (Schema::hasColumn('transactions', 'admin_current_balance')) {
                    $table->dropColumn('admin_current_balance');
                }
            });
        }
    }
};

