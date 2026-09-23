<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // doctrine/dbal is required for change() on existing columns
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }
        });

        // Replace the existing global unique index on `email` with a partial-style
        // dedupe enforced at the app layer: keep the unique index but allow many NULLs.
        // MySQL already permits multiple NULLs in a UNIQUE index, so no change needed there.
        // For Postgres the default UNIQUE also allows multiple NULLs.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone_verified_at')) {
                $table->dropColumn('phone_verified_at');
            }
        });

        // Reverting NOT NULL is unsafe if NULL rows exist; backfill placeholders first.
        DB::table('users')->whereNull('email')->update([
            'email' => DB::raw("CONCAT('placeholder+', id, '@evon.local')"),
        ]);
        DB::table('users')->whereNull('password')->update([
            'password' => DB::raw("''"),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
