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
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Vérifier si la colonne 'mode' existe déjà avant de l'ajouter
            if (!Schema::hasColumn('charging_sessions', 'mode')) {
                $table->enum('mode', ['prepaid', 'postpaid'])->default('postpaid')->after('user_id');
            }
            
            // Vérifier et ajouter les autres colonnes si elles n'existent pas
            if (!Schema::hasColumn('charging_sessions', 'estimated_cost')) {
                $table->decimal('estimated_cost', 10, 2)->nullable()->after('cost')->comment('Estimated cost for prepaid sessions');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'prepaid_amount')) {
                $table->decimal('prepaid_amount', 10, 2)->nullable()->after('estimated_cost')->comment('Amount charged for prepaid sessions');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->nullable()->after('prepaid_amount')->comment('Amount refunded for prepaid sessions');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'min_threshold')) {
                $table->decimal('min_threshold', 10, 2)->nullable()->after('refund_amount')->comment('Minimum threshold for postpaid sessions');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'wallet_validation_passed')) {
                $table->boolean('wallet_validation_passed')->default(false)->after('min_threshold')->comment('Whether wallet validation passed');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'wallet_validated_at')) {
                $table->timestamp('wallet_validated_at')->nullable()->after('wallet_validation_passed')->comment('When wallet validation was performed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'mode',
                'estimated_cost',
                'prepaid_amount',
                'refund_amount',
                'min_threshold',
                'wallet_validation_passed',
                'wallet_validated_at',
            ]);
        });
    }
};
