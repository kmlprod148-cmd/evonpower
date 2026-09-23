<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // IDs des acteurs (Admin, Intégrateur, Opérateur) - Vérifier existence avant d'ajouter
            if (!Schema::hasColumn('transactions', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('transactions', 'integrator_id')) {
                $table->unsignedBigInteger('integrator_id')->nullable()->after('admin_id');
            }
            if (!Schema::hasColumn('transactions', 'operator_id')) {
                $table->unsignedBigInteger('operator_id')->nullable()->after('integrator_id');
            }
            
            // IDs des wallets (Admin, Intégrateur, Opérateur) - Vérifier existence avant d'ajouter
            if (!Schema::hasColumn('transactions', 'admin_wallet_id')) {
                $table->unsignedBigInteger('admin_wallet_id')->nullable()->after('operator_id');
            }
            if (!Schema::hasColumn('transactions', 'integrator_wallet_id')) {
                $table->unsignedBigInteger('integrator_wallet_id')->nullable()->after('admin_wallet_id');
            }
            if (!Schema::hasColumn('transactions', 'operator_wallet_id')) {
                $table->unsignedBigInteger('operator_wallet_id')->nullable()->after('integrator_wallet_id');
            }
            
            // Parts détaillées (montants des parts) - Vérifier existence avant d'ajouter
            if (!Schema::hasColumn('transactions', 'admin_share_amount')) {
                $table->decimal('admin_share_amount', 12, 4)->nullable()->after('admin_commission');
            }
            if (!Schema::hasColumn('transactions', 'integrator_share_amount')) {
                $table->decimal('integrator_share_amount', 12, 4)->nullable()->after('integrator_commission');
            }
            if (!Schema::hasColumn('transactions', 'operator_share_amount')) {
                $table->decimal('operator_share_amount', 12, 4)->nullable()->after('partner_commission');
            }
        });
        
        // Ajouter les index seulement si les colonnes existent
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        Schema::table('transactions', function (Blueprint $table) use ($connection, $driver) {
            // Helper function to check if index exists
            $indexExists = function($indexName) use ($connection, $driver) {
                if ($driver === 'sqlite') {
                    $indexes = $connection->select("PRAGMA index_list('transactions')");
                    foreach ($indexes as $index) {
                        if ($index->name === $indexName) {
                            return true;
                        }
                    }
                    return false;
                } else {
                    // MySQL/MariaDB
                    $database = $connection->getDatabaseName();
                    $result = $connection->select(
                        "SELECT COUNT(*) as count FROM information_schema.statistics 
                         WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                        [$database, 'transactions', $indexName]
                    );
                    return $result[0]->count > 0;
                }
            };
            
            // Index pour améliorer les performances - Vérifier existence avant d'ajouter
            if (Schema::hasColumn('transactions', 'admin_id') && !$indexExists('idx_transactions_admin_id')) {
                $table->index('admin_id', 'idx_transactions_admin_id');
            }
            
            if (Schema::hasColumn('transactions', 'integrator_id') && !$indexExists('idx_transactions_integrator_id')) {
                $table->index('integrator_id', 'idx_transactions_integrator_id');
            }
            
            if (Schema::hasColumn('transactions', 'operator_id') && !$indexExists('idx_transactions_operator_id')) {
                $table->index('operator_id', 'idx_transactions_operator_id');
            }
            
            if (Schema::hasColumn('transactions', 'admin_wallet_id') && !$indexExists('idx_transactions_admin_wallet_id')) {
                $table->index('admin_wallet_id', 'idx_transactions_admin_wallet_id');
            }
            
            if (Schema::hasColumn('transactions', 'integrator_wallet_id') && !$indexExists('idx_transactions_integrator_wallet_id')) {
                $table->index('integrator_wallet_id', 'idx_transactions_integrator_wallet_id');
            }
            
            if (Schema::hasColumn('transactions', 'operator_wallet_id') && !$indexExists('idx_transactions_operator_wallet_id')) {
                $table->index('operator_wallet_id', 'idx_transactions_operator_wallet_id');
            }
        });
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        Schema::table('transactions', function (Blueprint $table) use ($connection, $driver) {
            // Helper function to check if index exists
            $indexExists = function($indexName) use ($connection, $driver) {
                if ($driver === 'sqlite') {
                    $indexes = $connection->select("PRAGMA index_list('transactions')");
                    foreach ($indexes as $index) {
                        if ($index->name === $indexName) {
                            return true;
                        }
                    }
                    return false;
                } else {
                    // MySQL/MariaDB
                    $database = $connection->getDatabaseName();
                    $result = $connection->select(
                        "SELECT COUNT(*) as count FROM information_schema.statistics 
                         WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                        [$database, 'transactions', $indexName]
                    );
                    return $result[0]->count > 0;
                }
            };
            
            // Supprimer les index seulement s'ils existent
            $indexes = [
                'idx_transactions_admin_id',
                'idx_transactions_integrator_id',
                'idx_transactions_operator_id',
                'idx_transactions_admin_wallet_id',
                'idx_transactions_integrator_wallet_id',
                'idx_transactions_operator_wallet_id'
            ];
            
            foreach ($indexes as $indexName) {
                if ($indexExists($indexName)) {
                    try {
                        $table->dropIndex($indexName);
                    } catch (\Exception $e) {
                        // Ignore errors if index doesn't exist
                    }
                }
            }
            
            // Supprimer les colonnes seulement si elles existent
            $columnsToDrop = [];
            if (Schema::hasColumn('transactions', 'admin_id')) {
                $columnsToDrop[] = 'admin_id';
            }
            if (Schema::hasColumn('transactions', 'integrator_id')) {
                $columnsToDrop[] = 'integrator_id';
            }
            if (Schema::hasColumn('transactions', 'operator_id')) {
                $columnsToDrop[] = 'operator_id';
            }
            if (Schema::hasColumn('transactions', 'admin_wallet_id')) {
                $columnsToDrop[] = 'admin_wallet_id';
            }
            if (Schema::hasColumn('transactions', 'integrator_wallet_id')) {
                $columnsToDrop[] = 'integrator_wallet_id';
            }
            if (Schema::hasColumn('transactions', 'operator_wallet_id')) {
                $columnsToDrop[] = 'operator_wallet_id';
            }
            if (Schema::hasColumn('transactions', 'admin_share_amount')) {
                $columnsToDrop[] = 'admin_share_amount';
            }
            if (Schema::hasColumn('transactions', 'integrator_share_amount')) {
                $columnsToDrop[] = 'integrator_share_amount';
            }
            if (Schema::hasColumn('transactions', 'operator_share_amount')) {
                $columnsToDrop[] = 'operator_share_amount';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

