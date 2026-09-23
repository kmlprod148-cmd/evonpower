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
        // Vérifier et ajouter les contraintes de suppression en cascade
        $this->addCascadeConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les contraintes ajoutées
        $this->removeCascadeConstraints();
    }

    /**
     * Add cascade delete constraints for partner relations.
     */
    private function addCascadeConstraints(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            echo "ℹ️ SQLite détecté - Les contraintes CASCADE sont gérées au niveau application\n";
            echo "✅ La suppression en cascade sera gérée par le PartnerService\n";
            return;
        }
        
        // Pour MySQL/PostgreSQL
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'partner_id')) {
            try {
                DB::statement('ALTER TABLE users DROP FOREIGN KEY IF EXISTS users_partner_id_foreign');
                DB::statement('ALTER TABLE users ADD CONSTRAINT users_partner_id_foreign 
                    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE');
                echo "✅ Contrainte CASCADE ajoutée pour users.partner_id\n";
            } catch (Exception $e) {
                echo "⚠️ Impossible d'ajouter la contrainte CASCADE pour users.partner_id: " . $e->getMessage() . "\n";
            }
        }

        if (Schema::hasTable('charging_points') && Schema::hasColumn('charging_points', 'partner_id')) {
            try {
                DB::statement('ALTER TABLE charging_points DROP FOREIGN KEY IF EXISTS charging_points_partner_id_foreign');
                DB::statement('ALTER TABLE charging_points ADD CONSTRAINT charging_points_partner_id_foreign 
                    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE');
                echo "✅ Contrainte CASCADE ajoutée pour charging_points.partner_id\n";
            } catch (Exception $e) {
                echo "⚠️ Impossible d'ajouter la contrainte CASCADE pour charging_points.partner_id: " . $e->getMessage() . "\n";
            }
        }

        if (Schema::hasTable('groups') && Schema::hasColumn('groups', 'partner_id')) {
            try {
                DB::statement('ALTER TABLE groups DROP FOREIGN KEY IF EXISTS groups_partner_id_foreign');
                DB::statement('ALTER TABLE groups ADD CONSTRAINT groups_partner_id_foreign 
                    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE');
                echo "✅ Contrainte CASCADE ajoutée pour groups.partner_id\n";
            } catch (Exception $e) {
                echo "⚠️ Impossible d'ajouter la contrainte CASCADE pour groups.partner_id: " . $e->getMessage() . "\n";
            }
        }

        if (Schema::hasTable('connectors') && Schema::hasColumn('connectors', 'charging_point_id')) {
            try {
                DB::statement('ALTER TABLE connectors DROP FOREIGN KEY IF EXISTS connectors_charging_point_id_foreign');
                DB::statement('ALTER TABLE connectors ADD CONSTRAINT connectors_charging_point_id_foreign 
                    FOREIGN KEY (charging_point_id) REFERENCES charging_points(id) ON DELETE CASCADE');
                echo "✅ Contrainte CASCADE ajoutée pour connectors.charging_point_id\n";
            } catch (Exception $e) {
                echo "⚠️ Impossible d'ajouter la contrainte CASCADE pour connectors.charging_point_id: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Remove cascade delete constraints.
     */
    private function removeCascadeConstraints(): void
    {
        try {
            // Supprimer les contraintes CASCADE
            DB::statement('ALTER TABLE users DROP FOREIGN KEY IF EXISTS users_partner_id_foreign');
            DB::statement('ALTER TABLE charging_points DROP FOREIGN KEY IF EXISTS charging_points_partner_id_foreign');
            DB::statement('ALTER TABLE groups DROP FOREIGN KEY IF EXISTS groups_partner_id_foreign');
            DB::statement('ALTER TABLE connectors DROP FOREIGN KEY IF EXISTS connectors_charging_point_id_foreign');
            
            echo "✅ Contraintes CASCADE supprimées\n";
        } catch (Exception $e) {
            echo "⚠️ Erreur lors de la suppression des contraintes: " . $e->getMessage() . "\n";
        }
    }
};
