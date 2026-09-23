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
        // Index pour les points de charge
        if (Schema::hasTable('charging_points')) {
            Schema::table('charging_points', function (Blueprint $table) {
                // Vérifier si les colonnes existent avant de créer les index
                if (Schema::hasColumn('charging_points', 'status') && Schema::hasColumn('charging_points', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['status', 'created_at'], 'idx_charging_points_status_created');
                }
                if (Schema::hasColumn('charging_points', 'integrator_id') && Schema::hasColumn('charging_points', 'status')) {
                    $this->createIndexIfNotExists($table, ['integrator_id', 'status'], 'idx_charging_points_integrator_status');
                }
                if (Schema::hasColumn('charging_points', 'partner_id') && Schema::hasColumn('charging_points', 'status')) {
                    $this->createIndexIfNotExists($table, ['partner_id', 'status'], 'idx_charging_points_partner_status');
                }
                if (Schema::hasColumn('charging_points', 'group_id') && Schema::hasColumn('charging_points', 'status')) {
                    $this->createIndexIfNotExists($table, ['group_id', 'status'], 'idx_charging_points_group_status');
                }
                if (Schema::hasColumn('charging_points', 'latitude') && Schema::hasColumn('charging_points', 'longitude')) {
                    $this->createIndexIfNotExists($table, ['latitude', 'longitude'], 'idx_charging_points_location');
                }
            });
        }

        // Index pour les transactions
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'user_id') && Schema::hasColumn('transactions', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['user_id', 'created_at'], 'idx_transactions_user_created');
                }
                if (Schema::hasColumn('transactions', 'charging_point_id') && Schema::hasColumn('transactions', 'status')) {
                    $this->createIndexIfNotExists($table, ['charging_point_id', 'status'], 'idx_transactions_charging_point_status');
                }
                if (Schema::hasColumn('transactions', 'status') && Schema::hasColumn('transactions', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['status', 'created_at'], 'idx_transactions_status_created');
                }
                if (Schema::hasColumn('transactions', 'payment_status') && Schema::hasColumn('transactions', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['payment_status', 'created_at'], 'idx_transactions_payment_status_created');
                }
            });
        }

        // Index pour les utilisateurs
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'integrator_id') && Schema::hasColumn('users', 'is_active')) {
                    $this->createIndexIfNotExists($table, ['integrator_id', 'is_active'], 'idx_users_integrator_active');
                }
                if (Schema::hasColumn('users', 'partner_id') && Schema::hasColumn('users', 'is_active')) {
                    $this->createIndexIfNotExists($table, ['partner_id', 'is_active'], 'idx_users_partner_active');
                }
                if (Schema::hasColumn('users', 'is_active') && Schema::hasColumn('users', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['is_active', 'created_at'], 'idx_users_active_created');
                }
            });
        }

        // Index pour les réservations
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'user_id') && Schema::hasColumn('reservations', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['user_id', 'created_at'], 'idx_reservations_user_created');
                }
                if (Schema::hasColumn('reservations', 'charging_point_id') && Schema::hasColumn('reservations', 'status')) {
                    $this->createIndexIfNotExists($table, ['charging_point_id', 'status'], 'idx_reservations_charging_point_status');
                }
                if (Schema::hasColumn('reservations', 'status') && Schema::hasColumn('reservations', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['status', 'created_at'], 'idx_reservations_status_created');
                }
            });
        }

        // Index pour les commandes (seulement les colonnes qui existent)
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'user_id') && Schema::hasColumn('orders', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['user_id', 'created_at'], 'idx_orders_user_created');
                }
                if (Schema::hasColumn('orders', 'status') && Schema::hasColumn('orders', 'created_at')) {
                    $this->createIndexIfNotExists($table, ['status', 'created_at'], 'idx_orders_status_created');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les index de manière sécurisée
        if (Schema::hasTable('charging_points')) {
            Schema::table('charging_points', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_charging_points_status_created');
                $this->dropIndexIfExists($table, 'idx_charging_points_integrator_status');
                $this->dropIndexIfExists($table, 'idx_charging_points_partner_status');
                $this->dropIndexIfExists($table, 'idx_charging_points_group_status');
                $this->dropIndexIfExists($table, 'idx_charging_points_location');
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_transactions_user_created');
                $this->dropIndexIfExists($table, 'idx_transactions_charging_point_status');
                $this->dropIndexIfExists($table, 'idx_transactions_status_created');
                $this->dropIndexIfExists($table, 'idx_transactions_payment_status_created');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_users_integrator_active');
                $this->dropIndexIfExists($table, 'idx_users_partner_active');
                $this->dropIndexIfExists($table, 'idx_users_active_created');
            });
        }

        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_reservations_user_created');
                $this->dropIndexIfExists($table, 'idx_reservations_charging_point_status');
                $this->dropIndexIfExists($table, 'idx_reservations_status_created');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_orders_user_created');
                $this->dropIndexIfExists($table, 'idx_orders_status_created');
            });
        }
    }

    /**
     * Créer un index seulement s'il n'existe pas déjà
     */
    private function createIndexIfNotExists(Blueprint $table, array $columns, string $indexName): void
    {
        try {
            // Vérifier si l'index existe déjà
            $existingIndexes = $this->getExistingIndexes($table->getTable());
            if (!in_array($indexName, $existingIndexes)) {
                $table->index($columns, $indexName);
            }
        } catch (\Exception $e) {
            // En cas d'erreur, on continue sans créer l'index
        }
    }

    /**
     * Obtenir la liste des index existants pour une table
     */
    private function getExistingIndexes(string $tableName): array
    {
        try {
            $driver = DB::getDriverName();
            
            if ($driver === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list({$tableName})");
                return array_map(function($index) {
                    return $index->name;
                }, $indexes);
            } elseif ($driver === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM {$tableName}");
                return array_unique(array_map(function($index) {
                    return $index->Key_name;
                }, $indexes));
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Supprimer un index seulement s'il existe
     */
    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        try {
            $table->dropIndex($indexName);
        } catch (\Exception $e) {
            // L'index n'existe pas, on continue
        }
    }
};