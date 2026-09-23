<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profile_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('integrator_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('owner_type', ['admin', 'integrator'])->default('integrator');
            $table->enum('profile_type', ['integrator', 'partner'])->default('partner');
            $table->json('settings')->nullable(); // Paramètres du profil
            $table->json('features')->nullable(); // Fonctionnalités incluses
            $table->json('limitations')->nullable(); // Limitations (nombre de bornes, etc.)
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profile_plans');
    }
}