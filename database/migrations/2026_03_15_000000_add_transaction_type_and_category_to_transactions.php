<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute les colonnes transaction_type et transaction_category à la table transactions
     * si elles n'existent pas déjà.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter la colonne transaction_type si elle n'existe pas
            if (!Schema::hasColumn('transactions', 'transaction_type')) {
                $table->string('transaction_type', 50)
                    ->nullable()
                    ->default('client')
                    ->after('id')
                    ->comment('Type de transaction normalisé');
            }

            // Ajouter la colonne transaction_category si elle n'existe pas
            if (!Schema::hasColumn('transactions', 'transaction_category')) {
                $table->string('transaction_category', 50)
                    ->nullable()
                    ->default('charging')
                    ->after('transaction_type')
                    ->comment('Catégorie de transaction (recharge, recharge_wallet, abonnement, commission)');
            }

            // Ajouter les index pour les nouvelles colonnes
            if (!Schema::hasIndex('transactions', 'idx_transactions_type_category')) {
                $table->index(['transaction_type', 'transaction_category'], 'idx_transactions_type_category');
            }
        });

        // Mettre à jour les transactions existantes avec les types par défaut
        // berdasarkan kategori yang ada
        try {
            \Illuminate\Support\Facades\DB::table('transactions')
                ->whereNull('transaction_type')
                ->whereNotNull('price_total')
                ->where('price_total', '>', 0)
                ->update([
                    'transaction_type' => 'charging_session',
                    'transaction_category' => 'recharge',
                ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Impossible de mettre à jour les types de transactions existants', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasIndex('transactions', 'idx_transactions_type_category')) {
                $table->dropIndex('idx_transactions_type_category');
            }

            if (Schema::hasColumn('transactions', 'transaction_category')) {
                $table->dropColumn('transaction_category');
            }

            if (Schema::hasColumn('transactions', 'transaction_type')) {
                $table->dropColumn('transaction_type');
            }
        });
    }
};
