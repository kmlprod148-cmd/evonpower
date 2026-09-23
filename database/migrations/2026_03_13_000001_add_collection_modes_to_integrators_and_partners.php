<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('integrators')) {
            Schema::table('integrators', function (Blueprint $table) {
                if (!Schema::hasColumn('integrators', 'collection_mode')) {
                    $table->string('collection_mode', 50)
                        ->default('admin')
                        ->after('city');
                }
            });
        }

        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                if (!Schema::hasColumn('partners', 'collection_mode')) {
                    $table->string('collection_mode', 50)
                        ->default('admin')
                        ->after('is_active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('integrators')) {
            Schema::table('integrators', function (Blueprint $table) {
                if (Schema::hasColumn('integrators', 'collection_mode')) {
                    $table->dropColumn('collection_mode');
                }
            });
        }

        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                if (Schema::hasColumn('partners', 'collection_mode')) {
                    $table->dropColumn('collection_mode');
                }
            });
        }
    }
};

