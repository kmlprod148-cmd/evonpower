<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Add missing columns with existence checks
            if (!Schema::hasColumn('reports', 'filters')) {
                $table->json('filters')->nullable()->after('status');
            }
            if (!Schema::hasColumn('reports', 'data')) {
                $table->json('data')->nullable()->after('filters');
            }

            // Rename user_id to generated_by and update foreign key
            if (Schema::hasColumn('reports', 'user_id')) {
                if (DB::getDriverName() === 'mysql') {
                    // Drop existing foreign key if it exists
                    $foreignKeys = DB::select(
                        'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                        [DB::getDatabaseName(), 'reports', 'user_id']
                    );
                    foreach ($foreignKeys as $key) {
                        $table->dropForeign($key->CONSTRAINT_NAME);
                    }
                }
                $table->renameColumn('user_id', 'generated_by');
            }
            // Add foreign key for generated_by
            if (Schema::hasColumn('reports', 'generated_by')) {
                if (DB::getDriverName() === 'mysql') {
                    // Drop existing foreign key reports_generated_by_foreign if it exists
                     $foreignKeys = DB::select(
                        'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
                        [DB::getDatabaseName(), 'reports', 'reports_generated_by_foreign']
                    );
                    foreach ($foreignKeys as $key) {
                        $table->dropForeign($key->CONSTRAINT_NAME);
                    }

                    // Log column types for debugging (optional, can be removed later)
                    $reportsGeneratedByType = DB::getSchemaBuilder()->getColumnType('reports', 'generated_by');
                    $usersIdType = DB::getSchemaBuilder()->getColumnType('users', 'id');
                    Log::info("Column type for reports.generated_by: {$reportsGeneratedByType}");
                    Log::info("Column type for users.id: {$usersIdType}");

                    // Check for orphaned records before adding foreign key (optional, can be removed later if not needed)
                    $orphanedReports = DB::table('reports')
                        ->whereNotNull('generated_by')
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('users')
                                ->whereColumn('users.id', 'reports.generated_by');
                        })
                        ->get();

                    if ($orphanedReports->isNotEmpty()) {
                        Log::error('Orphaned records found in reports table before adding foreign key:', ['orphaned_reports' => $orphanedReports->toArray()]);
                        // Handle orphaned records: set generated_by to null
                        DB::table('reports')
                            ->whereIn('id', $orphanedReports->pluck('id'))
                            ->update(['generated_by' => null]);
                        Log::info('Orphaned records in reports table have been set to NULL.');
                    }

                    // Drop existing index if it exists to prevent duplicate key errors
                    $indexes = DB::select(
                        'SHOW INDEX FROM reports WHERE Key_name = ?',
                        ['reports_generated_by_foreign']
                    );
                    if (!empty($indexes)) {
                        $table->dropIndex('reports_generated_by_foreign');
                        Log::info('Dropped existing index reports_generated_by_foreign.');
                    }
                }
 
                $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
            }


            // Update 'type' column to enum if it exists
            if (Schema::hasColumn('reports', 'type')) {
                if (DB::getDriverName() === 'sqlite') {
                    // Pour SQLite, on ne peut pas facilement modifier le type de colonne
                    echo "SQLite detected - skipping type column modification\n";
                } else {
                    $table->enum('type', ['usage', 'revenue', 'commission', 'maintenance', 'user'])->nullable()->change();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Drop added columns with existence checks
            if (Schema::hasColumn('reports', 'data')) {
                $table->dropColumn('data');
            }
            if (Schema::hasColumn('reports', 'filters')) {
                $table->dropColumn('filters');
            }

            // Revert generated_by to user_id and update foreign key
            if (Schema::hasColumn('reports', 'generated_by')) {
                // Drop existing foreign key if it exists
                 $foreignKeys = DB::select(
                    'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                    [DB::getDatabaseName(), 'reports', 'generated_by']
                );
                foreach ($foreignKeys as $key) {
                    $table->dropForeign($key->CONSTRAINT_NAME);
                }
                $table->renameColumn('generated_by', 'user_id');
            }
            // Add foreign key for user_id (assuming original onDelete behavior was cascade)
            if (Schema::hasColumn('reports', 'user_id')) {
                 $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }


            // Revert 'type' column to string
            if (Schema::hasColumn('reports', 'type')) {
                $table->string('type')->nullable()->change();
            }
        });
    }
};
