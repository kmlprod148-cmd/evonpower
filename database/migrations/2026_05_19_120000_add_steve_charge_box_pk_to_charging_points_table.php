<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Idempotent: a prior partial run on this DB created `steve_charge_box_pk`
        // before failing on the second column, and the migration never recorded.
        // Re-running it must finish the work without re-adding columns that
        // already exist — otherwise SQLite throws "duplicate column".
        Schema::table('charging_points', function (Blueprint $table) {
            if (!Schema::hasColumn('charging_points', 'steve_charge_box_pk')) {
                $table->unsignedBigInteger('steve_charge_box_pk')->nullable()->after('steve_charging_point_id');
            }
            if (!Schema::hasColumn('charging_points', 'steve_provisioned_at')) {
                $table->timestamp('steve_provisioned_at')->nullable()->after('steve_charge_box_pk');
            }
        });

        // The unique index lives in a separate statement so the column-add and
        // index-add can be skipped independently; older SQLite versions also
        // reject combining ADD COLUMN with UNIQUE in one ALTER. The doctrine/dbal
        // dependency that powers Schema::hasIndex isn't required in this repo,
        // so probe the index via raw introspection instead.
        if (!self::hasIndex('charging_points', 'charging_points_steve_charge_box_pk_unique')) {
            Schema::table('charging_points', function (Blueprint $table) {
                $table->unique('steve_charge_box_pk', 'charging_points_steve_charge_box_pk_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            if (self::hasIndex('charging_points', 'charging_points_steve_charge_box_pk_unique')) {
                $table->dropUnique('charging_points_steve_charge_box_pk_unique');
            }
            $cols = array_values(array_filter(
                ['steve_charge_box_pk', 'steve_provisioned_at'],
                fn ($c) => Schema::hasColumn('charging_points', $c),
            ));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }

    /**
     * Driver-agnostic index probe. Schema::hasIndex() needs doctrine/dbal which
     * this project doesn't pull in; query the underlying catalog directly.
     */
    private static function hasIndex(string $table, string $indexName): bool
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => \Illuminate\Support\Facades\DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName],
            ) !== null,
            'mysql', 'mariadb' => \Illuminate\Support\Facades\DB::selectOne(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
                [$table, $indexName],
            ) !== null,
            'pgsql' => \Illuminate\Support\Facades\DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $indexName],
            ) !== null,
            default => false,
        };
    }
};
