<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('stations')) {
            Schema::create('stations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('address');
                $table->string('city');
                $table->string('postal_code');
                $table->string('country');
                $table->enum('type', ['private', 'public', 'commercial']);
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->json('opening_hours')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('group_id')->nullable();

                $table->foreign('group_id')->references('id')->on('groups');
                $table->timestamps();

                $table->index(['city', 'type']);
            });
        } else {
            Schema::table('stations', function (Blueprint $table) {
                if (!Schema::hasColumn('stations', 'name')) {
                    $table->string('name');
                }
                if (!Schema::hasColumn('stations', 'address')) {
                    $table->string('address');
                }
                if (!Schema::hasColumn('stations', 'city')) {
                    $table->string('city');
                }
                if (!Schema::hasColumn('stations', 'postal_code')) {
                    $table->string('postal_code');
                }
                if (!Schema::hasColumn('stations', 'country')) {
                    $table->string('country');
                }
                if (!Schema::hasColumn('stations', 'type')) {
                    $table->enum('type', ['private', 'public', 'commercial']);
                }
                if (!Schema::hasColumn('stations', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable();
                }
                if (!Schema::hasColumn('stations', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable();
                }
                if (!Schema::hasColumn('stations', 'opening_hours')) {
                    $table->json('opening_hours')->nullable();
                }
                if (!Schema::hasColumn('stations', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('stations', 'group_id')) {
                    $table->unsignedBigInteger('group_id')->nullable();
                }

                // Add foreign key only if column exists and foreign key doesn't exist
                if (Schema::hasColumn('stations', 'group_id') && !Schema::hasColumn('stations', 'stations_group_id_foreign')) {
                     $table->foreign('group_id')->references('id')->on('groups');
                }

                // Add index only if columns exist
                if (Schema::hasColumn('stations', 'city') && Schema::hasColumn('stations', 'type')) {
                     $table->index(['city', 'type']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('stations');
        Schema::enableForeignKeyConstraints();
    }
};
