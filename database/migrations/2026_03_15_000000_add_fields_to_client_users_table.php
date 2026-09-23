<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute les champs nécessaires pour les clients finaux avec véhicule
     */
    public function up(): void
    {
        Schema::table('client_users', function (Blueprint $table) {
            // Informations personnelles supplémentaires
            $table->string('first_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('postal_code')->nullable()->after('city');
            $table->string('country')->nullable()->after('postal_code');
            
            // Relation avec User (compte admin/système)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->after('country');
            
            // Statut du compte
            $table->boolean('is_active')->default(true)->after('user_id');
            $table->timestamp('email_verified_at')->nullable()->after('is_active');
            $table->timestamp('activated_at')->nullable()->after('email_verified_at');
            
            // Verification token for email confirmation
            $table->string('verification_token', 100)->nullable()->unique()->after('activated_at');
            
            // Preferences
            $table->string('language', 10)->default('fr')->after('verification_token');
            $table->string('currency', 3)->default('EUR')->after('language');
            
            // Dates importantes
            $table->timestamp('last_login_at')->nullable()->after('currency');
            
            // Index pour optimiser les requêtes
            $table->index('user_id');
            $table->index('is_active');
            $table->index(['email', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_users', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['email', 'is_active']);
            $table->dropColumn([
                'first_name',
                'phone',
                'address',
                'city',
                'postal_code',
                'country',
                'user_id',
                'is_active',
                'email_verified_at',
                'activated_at',
                'verification_token',
                'language',
                'currency',
                'last_login_at',
            ]);
        });
    }
};
