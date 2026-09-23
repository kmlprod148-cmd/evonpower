<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = $this->getColumns('users');

        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (!in_array('position', $columns)) {
                $table->string('position')->nullable()->after('raison_social');
            }

            if (!in_array('notes', $columns)) {
                $table->text('notes')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = $this->getColumns('users');

        Schema::table('users', function (Blueprint $table) use ($columns) {
            if (in_array('notes', $columns)) {
                $table->dropColumn('notes');
            }

            if (in_array('position', $columns)) {
                $table->dropColumn('position');
            }
        });
    }

    /**
     * Get column names for a table. Uses PRAGMA table_info for SQLite
     * compatibility with versions older than 3.35 (which lack pragma_table_xinfo).
     */
    private function getColumns(string $table): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return array_column(DB::select("PRAGMA table_info({$table})"), 'name');
        }

        return Schema::getColumnListing($table);
    }
};
