<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mettre à jour toutes les devises MAD vers EUR dans la base de données
        
        // Table users
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'currency')) {
            DB::table('users')->where('currency', 'MAD')->update(['currency' => 'EUR']);
        }
        
        // Table transactions
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'currency')) {
            DB::table('transactions')->where('currency', 'MAD')->update(['currency' => 'EUR']);
        }
        
        // Table transaction_repartitions (si elle existe)
        if (Schema::hasTable('transaction_repartitions')) {
            // Pas de colonne currency dans cette table
            // SQLite ne supporte pas les commentaires de table, on ignore cette partie
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE transaction_repartitions COMMENT = 'Montants en EUR'");
            }
        }
        
        // Table plans
        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'currency')) {
            DB::table('plans')->where('currency', 'MAD')->update(['currency' => 'EUR']);
        }
        
        // Table additional_rates
        if (Schema::hasTable('additional_rates') && Schema::hasColumn('additional_rates', 'currency')) {
            DB::table('additional_rates')->where('currency', 'MAD')->update(['currency' => 'EUR']);
        }
        
        // Table wire_transfers
        if (Schema::hasTable('wire_transfers') && Schema::hasColumn('wire_transfers', 'currency')) {
            DB::table('wire_transfers')->where('currency', 'MAD')->update(['currency' => 'EUR']);
        }
        
        // Table balance_histories
        if (Schema::hasTable('balance_histories') && Schema::hasColumn('balance_histories', 'currency')) {
            DB::table('balance_histories')->where('currency', 'MAD')->update(['currency' => 'EUR']);
        }
        
        // Table balance_snapshots
        if (Schema::hasTable('balance_snapshots') && Schema::hasColumn('balance_snapshots', 'currency')) {
            DB::table('balance_snapshots')->where('currency', 'MAD')->update(['currency' => 'EUR']);
        }
        
        // Table transaction_histories
        if (Schema::hasTable('transaction_histories') && Schema::hasColumn('transaction_histories', 'currency')) {
            DB::table('transaction_histories')->where('currency', 'MAD')->update(['currency' => 'EUR']);
        }
        
        // Mettre à jour les configurations par défaut
        if (Schema::hasTable('admin_settings')) {
            // Vérifier d'abord si l'enregistrement existe
            $existingCurrency = DB::table('admin_settings')->where('key', 'default_currency')->first();
            
            if ($existingCurrency) {
                // Mettre à jour l'enregistrement existant
                DB::table('admin_settings')->where('key', 'default_currency')->update(['value' => 'EUR']);
            } else {
                // Créer un nouvel enregistrement
                DB::table('admin_settings')->insert([
                    'category' => 'system',
                    'key' => 'default_currency',
                    'value' => 'EUR',
                    'type' => 'select',
                    'description' => 'Devise par défaut de l\'application',
                    'is_active' => true,
                    'metadata' => json_encode([
                        'required' => true,
                        'validation' => 'required|in:MAD,EUR,USD',
                        'options' => [
                            'MAD' => 'Dirham marocain',
                            'EUR' => 'Euro',
                            'USD' => 'Dollar américain'
                        ]
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            // Mettre à jour currency si elle existe
            DB::table('admin_settings')->where('key', 'currency')->update(['value' => 'EUR']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir à MAD (si nécessaire)
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'currency')) {
            DB::table('users')->where('currency', 'EUR')->update(['currency' => 'MAD']);
        }
        
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'currency')) {
            DB::table('transactions')->where('currency', 'EUR')->update(['currency' => 'MAD']);
        }
        
        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'currency')) {
            DB::table('plans')->where('currency', 'EUR')->update(['currency' => 'MAD']);
        }
        
        if (Schema::hasTable('additional_rates') && Schema::hasColumn('additional_rates', 'currency')) {
            DB::table('additional_rates')->where('currency', 'EUR')->update(['currency' => 'MAD']);
        }
        
        if (Schema::hasTable('wire_transfers') && Schema::hasColumn('wire_transfers', 'currency')) {
            DB::table('wire_transfers')->where('currency', 'EUR')->update(['currency' => 'MAD']);
        }
        
        if (Schema::hasTable('balance_histories') && Schema::hasColumn('balance_histories', 'currency')) {
            DB::table('balance_histories')->where('currency', 'EUR')->update(['currency' => 'MAD']);
        }
        
        if (Schema::hasTable('balance_snapshots') && Schema::hasColumn('balance_snapshots', 'currency')) {
            DB::table('balance_snapshots')->where('currency', 'EUR')->update(['currency' => 'MAD']);
        }
        
        if (Schema::hasTable('transaction_histories') && Schema::hasColumn('transaction_histories', 'currency')) {
            DB::table('transaction_histories')->where('currency', 'EUR')->update(['currency' => 'MAD']);
        }
        
        if (Schema::hasTable('admin_settings')) {
            DB::table('admin_settings')->where('key', 'default_currency')->update(['value' => 'MAD']);
            DB::table('admin_settings')->where('key', 'currency')->update(['value' => 'MAD']);
        }
    }
};