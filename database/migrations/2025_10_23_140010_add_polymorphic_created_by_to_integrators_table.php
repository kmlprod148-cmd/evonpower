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
        Schema::table('integrators', function (Blueprint $table) {
            if (!Schema::hasColumn('integrators', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('integrators', 'created_by_type')) {
                $table->string('created_by_type')->nullable();
            }
            if (!Schema::hasColumn('integrators', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable();
            }
        });

        // Add composite index on the polymorphic pair if it doesn't already exist (MySQL-safe check)
        try {
            $indexes = DB::select("SHOW INDEX FROM integrators WHERE Key_name = 'integrators_created_by_type_created_by_id_index'");
            if (empty($indexes)) {
                Schema::table('integrators', function (Blueprint $table) {
                    $table->index(['created_by_type', 'created_by_id']);
                });
            }
        } catch (\Throwable $e) {
            // On non-MySQL drivers, attempt to create the index and ignore if it exists
            try {
                Schema::table('integrators', function (Blueprint $table) {
                    $table->index(['created_by_type', 'created_by_id']);
                });
            } catch (\Throwable $ignored) {
                // ignore
            }
        }

        // Backfill polymorphic creator columns from created_by (when present)
        try {
            DB::statement('UPDATE integrators SET created_by_type = "App\\\\Models\\\\User", created_by_id = created_by WHERE created_by IS NOT NULL AND (created_by_type IS NULL OR created_by_id IS NULL)');
        } catch (\Throwable $e) {
            // Ignore if the database driver does not support this exact syntax
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrators', function (Blueprint $table) {
            // Drop index if present
            try {
                $table->dropIndex('integrators_created_by_type_created_by_id_index');
            } catch (\Throwable $e) {
                // ignore if index doesn't exist
            }

            if (Schema::hasColumn('integrators', 'created_by')) {
                try { $table->dropForeign(['created_by']); } catch (\Throwable $e) { /* ignore */ }
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('integrators', 'created_by_type')) {
                $table->dropColumn('created_by_type');
            }
            if (Schema::hasColumn('integrators', 'created_by_id')) {
                $table->dropColumn('created_by_id');
            }
        });
    }
};

