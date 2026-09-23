<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si la colonne existe déjà
        if (!Schema::hasColumn('charging_points', 'integrator_id')) {
            Schema::table('charging_points', function (Blueprint $table) {
                $table->unsignedBigInteger('integrator_id')->nullable()->after('user_id');
            });
        }
        
        // Vérifier si la clé étrangère existe déjà (compatible SQLite et MySQL)
        try {
            // Pour SQLite, on essaie simplement d'ajouter la clé étrangère
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('charging_points', function (Blueprint $table) {
                    $table->foreign('integrator_id')->references('id')->on('users')->onDelete('cascade');
                });
            } else {
                // Pour MySQL, vérifier l'existence de la clé étrangère
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'charging_points' 
                    AND COLUMN_NAME = 'integrator_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (empty($foreignKeys)) {
                    Schema::table('charging_points', function (Blueprint $table) {
                        $table->foreign('integrator_id')->references('id')->on('users')->onDelete('cascade');
                    });
                }
            }
        } catch (\Exception $e) {
            // Si la clé étrangère existe déjà, ignorer l'erreur
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'duplicate') === false) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            $table->dropForeign(['integrator_id']);
            $table->dropColumn('integrator_id');
        });
    }
};