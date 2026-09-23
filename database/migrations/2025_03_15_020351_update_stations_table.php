<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stations', function (Blueprint $table) {
            // Check and add columns safely
            if (!Schema::hasColumn('stations', 'label')) {
                $table->string('label', 100)
                    ->nullable()
                    ->after('id')
                    ->comment('Display name or label for the station');
            }

            if (!Schema::hasColumn('stations', 'code')) {
                $table->string('code', 50)
                    ->nullable()
                    ->unique()
                    ->after('label')
                    ->comment('Unique identifier or code for the station');
            }

            // Add geographical coordinates if not exists
            if (!Schema::hasColumn('stations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }

            if (!Schema::hasColumn('stations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }

            // Add station type and status
            if (!Schema::hasColumn('stations', 'type')) {
                $table->enum('type', [
                    'public', 
                    'private', 
                    'semi-public', 
                    'workplace', 
                    'highway', 
                    'residential'
                ])->default('public');
            }

            if (!Schema::hasColumn('stations', 'status')) {
                $table->enum('status', [
                    'active', 
                    'inactive', 
                    'maintenance', 
                    'planned'
                ])->default('active');
            }

            // Add additional metadata columns
            if (!Schema::hasColumn('stations', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('stations', 'amenities')) {
                $table->json('amenities')->nullable();
            }

            // Safe index creation - only create if columns exist
            if (Schema::hasColumn('stations', 'label')) {
                if (!Schema::hasIndex('stations', 'stations_label_index')) {
                    $table->index('label');
                }
            }

            // Type index
            if (Schema::hasColumn('stations', 'type')) {
                if (!Schema::hasIndex('stations', 'stations_type_index')) {
                    $table->index('type');
                }
            }

            // Status index
            if (Schema::hasColumn('stations', 'status')) {
                if (!Schema::hasIndex('stations', 'stations_status_index')) {
                    $table->index('status');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stations', function (Blueprint $table) {
            // Attempt to drop columns and indexes safely
            $dropColumns = [];
            $dropIndexes = [];

            if (Schema::hasColumn('stations', 'label')) {
                $dropColumns[] = 'label';
                $dropIndexes[] = 'stations_label_index';
            }

            if (Schema::hasColumn('stations', 'code')) {
                $dropColumns[] = 'code';
            }

            if (Schema::hasColumn('stations', 'latitude')) {
                $dropColumns[] = 'latitude';
            }

            if (Schema::hasColumn('stations', 'longitude')) {
                $dropColumns[] = 'longitude';
            }

            if (Schema::hasColumn('stations', 'type')) {
                $dropColumns[] = 'type';
                $dropIndexes[] = 'stations_type_index';
            }

            if (Schema::hasColumn('stations', 'status')) {
                $dropColumns[] = 'status';
                $dropIndexes[] = 'stations_status_index';
            }

            if (Schema::hasColumn('stations', 'description')) {
                $dropColumns[] = 'description';
            }

            if (Schema::hasColumn('stations', 'amenities')) {
                $dropColumns[] = 'amenities';
            }

            // Drop columns if they exist
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }

            // Drop indexes if they exist
            foreach ($dropIndexes as $indexName) {
                $table->dropIndex($indexName);
            }
        });
    }
}