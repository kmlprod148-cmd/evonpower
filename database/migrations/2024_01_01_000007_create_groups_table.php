<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('type', ['public', 'private'])->default('private');
                $table->string('city')->nullable();
                $table->string('address')->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('country', 100)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('business_profile_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('business_profile_id')->references('id')->on('business_profiles')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
}