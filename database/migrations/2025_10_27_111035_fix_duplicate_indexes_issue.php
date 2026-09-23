<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if index exists on table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            // Try SQLite first
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            // Fallback for MySQL
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");
                return !empty($indexes);
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    /**
     * Check if column exists in table
     */
    private function columnExists(string $table, string $columnName): bool
    {
        try {
            // Try SQLite first
            $columns = DB::select("PRAGMA table_info({$table})");
            foreach ($columns as $column) {
                if ($column->name === $columnName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            // Fallback for MySQL
            try {
                $columns = DB::select("SHOW COLUMNS FROM {$table} LIKE '{$columnName}'");
                return !empty($columns);
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add only missing indexes for critical performance
        $this->addMissingIndexes();
    }

    /**
     * Add missing indexes for performance
     */
    private function addMissingIndexes(): void
    {
        // Users table - add missing indexes
        if ($this->columnExists('users', 'created_at') && !$this->indexExists('users', 'users_created_at_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('created_at');
            });
        }

        if ($this->columnExists('users', 'last_activity_at') && !$this->indexExists('users', 'users_last_activity_at_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('last_activity_at');
            });
        }

        if ($this->columnExists('users', 'is_active') && !$this->indexExists('users', 'users_is_active_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('is_active');
            });
        }

        // Transactions table - add missing indexes
        if ($this->columnExists('transactions', 'user_id') && !$this->indexExists('transactions', 'transactions_user_id_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('user_id');
            });
        }

        if ($this->columnExists('transactions', 'status') && !$this->indexExists('transactions', 'transactions_status_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('status');
            });
        }

        if ($this->columnExists('transactions', 'created_at') && !$this->indexExists('transactions', 'transactions_created_at_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('created_at');
            });
        }

        if ($this->columnExists('transactions', 'amount') && !$this->indexExists('transactions', 'transactions_amount_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('amount');
            });
        }

        // Charging points table - add missing indexes
        if ($this->columnExists('charging_points', 'integrator_id') && !$this->indexExists('charging_points', 'charging_points_integrator_id_index')) {
            Schema::table('charging_points', function (Blueprint $table) {
                $table->index('integrator_id');
            });
        }

        if ($this->columnExists('charging_points', 'partner_id') && !$this->indexExists('charging_points', 'charging_points_partner_id_index')) {
            Schema::table('charging_points', function (Blueprint $table) {
                $table->index('partner_id');
            });
        }

        if ($this->columnExists('charging_points', 'status') && !$this->indexExists('charging_points', 'charging_points_status_index')) {
            Schema::table('charging_points', function (Blueprint $table) {
                $table->index('status');
            });
        }

        // Audit logs table - add missing indexes
        if ($this->columnExists('audit_logs', 'user_id') && !$this->indexExists('audit_logs', 'audit_logs_user_id_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('user_id');
            });
        }

        if ($this->columnExists('audit_logs', 'action') && !$this->indexExists('audit_logs', 'audit_logs_action_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('action');
            });
        }

        if ($this->columnExists('audit_logs', 'created_at') && !$this->indexExists('audit_logs', 'audit_logs_created_at_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('created_at');
            });
        }

        // Notifications table - add missing indexes
        if ($this->columnExists('notifications', 'notifiable_id') && !$this->indexExists('notifications', 'notifications_notifiable_id_index')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index('notifiable_id');
            });
        }

        if ($this->columnExists('notifications', 'type') && !$this->indexExists('notifications', 'notifications_type_index')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index('type');
            });
        }

        if ($this->columnExists('notifications', 'read_at') && !$this->indexExists('notifications', 'notifications_read_at_index')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index('read_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the indexes we created (only if they exist)
        try {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'users_created_at_index')) {
                    $table->dropIndex(['created_at']);
                }
                if ($this->columnExists('users', 'last_activity_at') && $this->indexExists('users', 'users_last_activity_at_index')) {
                    $table->dropIndex(['last_activity_at']);
                }
                if ($this->columnExists('users', 'is_active') && $this->indexExists('users', 'users_is_active_index')) {
                    $table->dropIndex(['is_active']);
                }
            });

            Schema::table('transactions', function (Blueprint $table) {
                if ($this->columnExists('transactions', 'user_id') && $this->indexExists('transactions', 'transactions_user_id_index')) {
                    $table->dropIndex(['user_id']);
                }
                if ($this->columnExists('transactions', 'status') && $this->indexExists('transactions', 'transactions_status_index')) {
                    $table->dropIndex(['status']);
                }
                if ($this->columnExists('transactions', 'created_at') && $this->indexExists('transactions', 'transactions_created_at_index')) {
                    $table->dropIndex(['created_at']);
                }
                if ($this->columnExists('transactions', 'amount') && $this->indexExists('transactions', 'transactions_amount_index')) {
                    $table->dropIndex(['amount']);
                }
            });

            Schema::table('charging_points', function (Blueprint $table) {
                if ($this->columnExists('charging_points', 'integrator_id') && $this->indexExists('charging_points', 'charging_points_integrator_id_index')) {
                    $table->dropIndex(['integrator_id']);
                }
                if ($this->columnExists('charging_points', 'partner_id') && $this->indexExists('charging_points', 'charging_points_partner_id_index')) {
                    $table->dropIndex(['partner_id']);
                }
                if ($this->columnExists('charging_points', 'status') && $this->indexExists('charging_points', 'charging_points_status_index')) {
                    $table->dropIndex(['status']);
                }
            });

            Schema::table('audit_logs', function (Blueprint $table) {
                if ($this->columnExists('audit_logs', 'user_id') && $this->indexExists('audit_logs', 'audit_logs_user_id_index')) {
                    $table->dropIndex(['user_id']);
                }
                if ($this->columnExists('audit_logs', 'action') && $this->indexExists('audit_logs', 'audit_logs_action_index')) {
                    $table->dropIndex(['action']);
                }
                if ($this->columnExists('audit_logs', 'created_at') && $this->indexExists('audit_logs', 'audit_logs_created_at_index')) {
                    $table->dropIndex(['created_at']);
                }
            });

            Schema::table('notifications', function (Blueprint $table) {
                if ($this->columnExists('notifications', 'notifiable_id') && $this->indexExists('notifications', 'notifications_notifiable_id_index')) {
                    $table->dropIndex(['notifiable_id']);
                }
                if ($this->columnExists('notifications', 'type') && $this->indexExists('notifications', 'notifications_type_index')) {
                    $table->dropIndex(['type']);
                }
                if ($this->columnExists('notifications', 'read_at') && $this->indexExists('notifications', 'notifications_read_at_index')) {
                    $table->dropIndex(['read_at']);
                }
            });
        } catch (\Exception $e) {
            // Ignore errors during rollback
        }
    }
};