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
        Schema::table('users', function (Blueprint $table) {
            // Email notification preferences
            $table->boolean('email_security_alerts')->default(true);
            $table->boolean('email_account_updates')->default(true);
            $table->boolean('email_transactions')->default(true);
            $table->boolean('email_stations')->default(true);
            $table->boolean('email_marketing')->default(false);
            
            // In-app notification preferences
            $table->boolean('app_security_alerts')->default(true);
            $table->boolean('app_account_updates')->default(true);
            $table->boolean('app_transactions')->default(true);
            $table->boolean('app_stations')->default(true);
            
            // Email frequency preference
            $table->enum('email_frequency', ['realtime', 'daily', 'weekly', 'never'])->default('daily');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_security_alerts',
                'email_account_updates',
                'email_transactions',
                'email_stations',
                'email_marketing',
                'app_security_alerts',
                'app_account_updates',
                'app_transactions',
                'app_stations',
                'email_frequency'
            ]);
        });
    }
};
