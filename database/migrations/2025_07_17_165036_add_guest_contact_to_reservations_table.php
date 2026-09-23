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
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('reservations', 'guest_phone')) {
                $table->string('guest_phone')->nullable()->after('guest_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'guest_email')) {
                $table->dropColumn('guest_email');
            }
            if (Schema::hasColumn('reservations', 'guest_phone')) {
                $table->dropColumn('guest_phone');
            }
        });
    }
};
