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
        Schema::table('admin_notifications', function (Blueprint $table) {
            // Rename columns with existence checks
            if (Schema::hasColumn('admin_notifications', 'content')) {
                $table->renameColumn('content', 'message');
            }
            if (Schema::hasColumn('admin_notifications', 'link')) {
                $table->renameColumn('link', 'action_url');
            }
            if (Schema::hasColumn('admin_notifications', 'user_id')) {
                if (DB::getDriverName() === 'mysql') {
                    // Drop existing foreign key if it exists before renaming
                     $foreignKeys = DB::select(
                        'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                        [DB::getDatabaseName(), 'admin_notifications', 'user_id']
                    );
                    foreach ($foreignKeys as $key) {
                        $table->dropForeign($key->CONSTRAINT_NAME);
                    }
                }
                $table->renameColumn('user_id', 'created_by');
            }
             if (Schema::hasColumn('admin_notifications', 'user_roles')) {
                if (!Schema::hasColumn('admin_notifications', 'target_roles')) {
                    $table->renameColumn('user_roles', 'target_roles');
                } else {
                    // If target_roles already exists, and user_roles still exists, drop user_roles
                    $table->dropColumn('user_roles');
                }
            }


            // Add missing columns with existence checks
            if (!Schema::hasColumn('admin_notifications', 'priority')) {
                $table->string('priority')->nullable()->after('type'); // Assuming 'type' exists
            }
            if (!Schema::hasColumn('admin_notifications', 'action_text')) {
                $table->string('action_text')->nullable()->after('action_url'); // Assuming 'action_url' exists
            }
            if (!Schema::hasColumn('admin_notifications', 'color')) {
                $table->string('color')->nullable()->after('icon'); // Assuming 'icon' exists
            }
            if (!Schema::hasColumn('admin_notifications', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('expires_at'); // Assuming 'expires_at' exists
            }
             if (!Schema::hasColumn('admin_notifications', 'read_by')) {
                $table->json('read_by')->nullable()->after('is_read'); // Assuming 'is_read' exists
            }

            // Drop read_at if it exists
            if (Schema::hasColumn('admin_notifications', 'read_at')) {
                 $table->dropColumn('read_at');
            }


            // Update 'type' column to string (if it's an enum)
            if (Schema::hasColumn('admin_notifications', 'type')) {
                if (DB::getDriverName() === 'mysql') {
                    // Check if it's an enum before attempting to change
                     $columnType = DB::select("SHOW COLUMNS FROM admin_notifications WHERE Field = 'type'")[0]->Type;
                     if (str_contains($columnType, 'enum')) {
                         $table->string('type')->default('info')->change();
                     }
                } else {
                    // Pour SQLite, on ne peut pas facilement modifier le type de colonne
                    echo "SQLite detected - skipping type column modification\n";
                }
            }


            // Remove extra columns if they exist (MySQL only)
            if (DB::getDriverName() === 'mysql') {
                $extraColumns = ['notification_group'];
                foreach ($extraColumns as $column) {
                    if (Schema::hasColumn('admin_notifications', $column)) {
                        $table->dropColumn($column);
                    }
                }

                // Remove softDeletes if it exists
                if (Schema::hasColumn('admin_notifications', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            } else {
                echo "SQLite detected - skipping column removal to avoid index conflicts\n";
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            // Revert column names with existence checks
            if (Schema::hasColumn('admin_notifications', 'message')) {
                $table->renameColumn('message', 'content');
            }
            if (Schema::hasColumn('admin_notifications', 'action_url')) {
                $table->renameColumn('action_url', 'link');
            }
            if (Schema::hasColumn('admin_notifications', 'created_by')) {
                 // Drop existing foreign key if it exists before renaming
                 $foreignKeys = DB::select(
                    'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                    [DB::getDatabaseName(), 'admin_notifications', 'created_by']
                );
                foreach ($foreignKeys as $key) {
                    $table->dropForeign($key->CONSTRAINT_NAME);
                }
                $table->renameColumn('created_by', 'user_id');
            }
             if (Schema::hasColumn('admin_notifications', 'target_roles')) {
                $table->renameColumn('target_roles', 'user_roles');
            }


            // Drop added columns with existence checks
            if (Schema::hasColumn('admin_notifications', 'read_by')) {
                $table->dropColumn('read_by');
            }
            if (Schema::hasColumn('admin_notifications', 'is_read')) {
                $table->dropColumn('is_read');
            }
            if (Schema::hasColumn('admin_notifications', 'color')) {
                $table->dropColumn('color');
            }
            if (Schema::hasColumn('admin_notifications', 'action_text')) {
                $table->dropColumn('action_text');
            }
            if (Schema::hasColumn('admin_notifications', 'priority')) {
                $table->dropColumn('priority');
            }

            // Add back read_at if it was dropped
            if (!Schema::hasColumn('admin_notifications', 'read_at')) {
                 $table->timestamp('read_at')->nullable()->after('expires_at'); // Assuming original position
            }


            // Revert 'type' column to enum (assuming original enum values)
            if (Schema::hasColumn('admin_notifications', 'type')) {
                 $table->dropColumn('type');
            }
            if (!Schema::hasColumn('admin_notifications', 'type')) {
                 $table->enum('type', ['info', 'warning', 'success', 'error'])->default('info')->after('content'); // Assuming original enum and position
            }


            // Add back removed columns if needed (based on previous schema)
            $removedColumns = ['notification_group'];
            foreach ($removedColumns as $column) {
                if (!Schema::hasColumn('admin_notifications', $column)) {
                    // This is a simplified approach; actual column types and positions would be needed
                    // For now, just add them back as nullable strings.
                    $table->string($column)->nullable();
                }
            }

            // Add back softDeletes if it was dropped
            if (!Schema::hasColumn('admin_notifications', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }
};
