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
        Schema::table('integrators', function (Blueprint $table) {
            // Ajouter la colonne address seulement si elle n'existe pas déjà
            if (!Schema::hasColumn('integrators', 'address')) {
                $table->string('address')->nullable()->after('city');
            }
            
            // Ajouter d'autres colonnes de contact si nécessaire
            if (!Schema::hasColumn('integrators', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('integrators', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            
            if (!Schema::hasColumn('integrators', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrators', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'email', 'website']);
        });
    }
};
