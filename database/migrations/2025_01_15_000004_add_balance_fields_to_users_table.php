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
        Schema::table('users', function (Blueprint $table) {
            // Champs de balance existants (si pas déjà présents)
            if (!Schema::hasColumn('users', 'balance')) {
                $table->decimal('balance', 15, 2)->default(0)->after('email');
            }
            
            if (!Schema::hasColumn('users', 'last_balance_calculation')) {
                $table->timestamp('last_balance_calculation')->nullable()->after('balance');
            }

            // Nouveaux champs pour le système hiérarchique
            $table->decimal('total_earnings', 15, 2)->default(0)->after('balance');
            $table->decimal('total_commissions', 15, 2)->default(0)->after('total_earnings');
            $table->decimal('pending_amount', 15, 2)->default(0)->after('total_commissions');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('pending_amount');
            $table->integer('transaction_count')->default(0)->after('paid_amount');
            $table->timestamp('last_transaction_date')->nullable()->after('transaction_count');
            $table->json('balance_metadata')->nullable()->after('last_transaction_date')->comment('Métadonnées de balance');
            $table->boolean('balance_locked')->default(false)->after('balance_metadata')->comment('Balance verrouillée pour calcul');
            $table->timestamp('balance_lock_expires_at')->nullable()->after('balance_locked');

            // Index pour les performances
            $table->index(['balance', 'last_balance_calculation']);
            $table->index('balance_locked');
            $table->index('last_transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'total_earnings',
                'total_commissions', 
                'pending_amount',
                'paid_amount',
                'transaction_count',
                'last_transaction_date',
                'balance_metadata',
                'balance_locked',
                'balance_lock_expires_at'
            ]);
        });
    }
};
