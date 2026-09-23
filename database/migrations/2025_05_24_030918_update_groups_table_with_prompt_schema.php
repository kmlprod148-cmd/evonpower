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
        if (Schema::hasTable('groups')) {
            Schema::table('groups', function (Blueprint $table) {
                // Add missing partner_id column
                if (!Schema::hasColumn('groups', 'partner_id')) {
                    $table->unsignedBigInteger('partner_id')->nullable()->after('id');
                    $table->foreign('partner_id')->references('id')->on('partners')->onDelete('set null');
                }

                // Remove external_id column if it exists
                if (Schema::hasColumn('groups', 'external_id')) {
                    $table->dropColumn('external_id');
                }

                // Remove softDeletes if it exists
                if (Schema::hasColumn('groups', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('groups')) {
            Schema::table('groups', function (Blueprint $table) {
                // Drop added partner_id column
                // Drop added partner_id column and foreign key if they exist
                if (Schema::hasColumn('groups', 'partner_id')) {
                    Schema::table('groups', function (Blueprint $table) {
                        // Drop the foreign key by convention name if it exists
                        $foreignKeyName = 'groups_partner_id_foreign';
                        $sm = Schema::getConnection()->getDoctrineSchemaManager();
                        $tableExists = $sm->tablesExist('groups');
                        if ($tableExists) {
                            $foreignKeys = $sm->listTableForeignKeys('groups');
                            foreach ($foreignKeys as $foreignKey) {
                                if ($foreignKey->getName() === $foreignKeyName) {
                                    $table->dropForeign($foreignKeyName);
                                    break;
                                }
                            }
                        }
                    });
                    $table->dropColumn('partner_id');
                }

                // Add back external_id column if needed (based on previous schema)
                // This assumes external_id was a string, adjust if necessary
                if (!Schema::hasColumn('groups', 'external_id')) {
                     $table->string('external_id')->nullable()->after('id');
                }

                // Add back softDeletes if it was dropped
                if (!Schema::hasColumn('groups', 'deleted_at')) {
                     $table->softDeletes();
                }
            });
        }
    }
};
