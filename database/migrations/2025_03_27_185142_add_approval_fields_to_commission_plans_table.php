<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalFieldsToCommissionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('commission_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_plans', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('transaction_manager');
            }
            
            // Vérifiez également si ces autres colonnes existent déjà
            if (!Schema::hasColumn('commission_plans', 'approval_workflow')) {
                $table->json('approval_workflow')->nullable()->after('valid_until');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('commission_plans', function (Blueprint $table) {
            if (Schema::hasColumn('commission_plans', 'requires_approval')) {
                $table->dropColumn('requires_approval');
            }
            if (Schema::hasColumn('commission_plans', 'approval_workflow')) {
                $table->dropColumn('approval_workflow');
            }
        });
    }
}