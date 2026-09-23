<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeRequiredFieldsNullableInBusinessProfilesTableV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // $table->string('name')->nullable()->change();
            // $table->string('phone')->nullable()->change();
            // $table->string('address')->nullable()->default('Not provided')->change();
            // $table->string('city')->nullable()->change();
            // $table->string('state')->nullable()->change();
            // $table->string('postal_code')->nullable()->change();
            // $table->string('country')->nullable()->change();
            // $table->string('contact_name')->nullable()->change();
            // $table->string('contact_title')->nullable()->change();
            // $table->string('contact_email')->nullable()->change();
            // $table->string('contact_phone')->nullable()->change();
            // $table->string('website')->nullable()->change();
            // $table->string('logo')->nullable()->change();
            // $table->text('description')->nullable()->change();
            // $table->string('registration_number')->nullable()->change();
            // $table->string('vat_number')->nullable()->change();
            // $table->json('business_hours')->nullable()->change();
            // $table->integer('station_count')->nullable()->change();
            // $table->date('installation_date')->nullable()->change();
            // $table->text('installation_notes')->nullable()->change();
            // $table->string('status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('country')->nullable(false)->change();
            $table->string('contact_name')->nullable(false)->change();
            $table->string('contact_email')->nullable(false)->change();
            $table->string('contact_phone')->nullable(false)->change();
            $table->string('type')->nullable(false)->change();
            $table->enum('target_type', ['integrator', 'partner'])->nullable(false)->change();
        });
    }
}
