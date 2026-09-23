<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // Supprimer les anciennes contraintes MySQL si elles existent
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'role_has_permissions' 
                AND CONSTRAINT_NAME LIKE '%foreign'
                AND COLUMN_NAME IN ('client_id', 'permission_id', 'role_id')
            ");
            
            foreach ($constraints as $constraint) {
                try {
                    DB::statement("ALTER TABLE `role_has_permissions` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            }
        } else {
            // Pour les autres SGBD, utiliser une approche générique
            Schema::table('role_has_permissions', function (Blueprint $table) {
                try {
                    $table->dropForeign(['client_id']);
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
                try {
                    $table->dropForeign(['permission_id']);
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
                try {
                    $table->dropForeign(['role_id']);
                } catch (\Exception $e) {
                    // Ignorer si la contrainte n'existe pas
                }
            });
        }

        // Nettoyer les données orphelines avant d'ajouter les contraintes
        if ($driver === 'sqlite') {
            // SQLite-compatible syntax
            // Supprimer les enregistrements avec des permission_id qui n'existent pas
            DB::statement("
                DELETE FROM role_has_permissions 
                WHERE permission_id NOT IN (SELECT id FROM permissions)
            ");

            // Supprimer les enregistrements avec des role_id qui n'existent pas
            DB::statement("
                DELETE FROM role_has_permissions 
                WHERE role_id NOT IN (SELECT id FROM roles)
            ");

            // Supprimer les enregistrements avec des client_id qui n'existent pas (si la colonne existe)
            if (Schema::hasColumn('role_has_permissions', 'client_id')) {
                DB::statement("
                    DELETE FROM role_has_permissions 
                    WHERE client_id IS NOT NULL 
                    AND client_id NOT IN (SELECT id FROM clients)
                ");
            }
        } else {
            // MySQL/MariaDB syntax
            // Supprimer les enregistrements avec des permission_id qui n'existent pas
            DB::statement("
                DELETE rhp FROM role_has_permissions rhp
                LEFT JOIN permissions p ON rhp.permission_id = p.id
                WHERE p.id IS NULL
            ");

            // Supprimer les enregistrements avec des role_id qui n'existent pas
            DB::statement("
                DELETE rhp FROM role_has_permissions rhp
                LEFT JOIN roles r ON rhp.role_id = r.id
                WHERE r.id IS NULL
            ");

            // Supprimer les enregistrements avec des client_id qui n'existent pas (si la colonne existe)
            if (Schema::hasColumn('role_has_permissions', 'client_id')) {
                DB::statement("
                    DELETE rhp FROM role_has_permissions rhp
                    LEFT JOIN clients c ON rhp.client_id = c.id
                    WHERE rhp.client_id IS NOT NULL AND c.id IS NULL
                ");
            }
        }

        Schema::table('role_has_permissions', function (Blueprint $table) {
            // Ajouter les contraintes avec les noms spécifiques
            if (Schema::hasColumn('role_has_permissions', 'client_id')) {
                $table->foreign('client_id', 'role_has_permissions_client_id_foreign')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');
            }

            $table->foreign('permission_id', 'role_has_permissions_permission_id_foreign')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->foreign('role_id', 'role_has_permissions_role_id_foreign')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_has_permissions', function (Blueprint $table) {
            // Supprimer les contraintes avec les noms spécifiques
            $table->dropForeign('role_has_permissions_client_id_foreign');
            $table->dropForeign('role_has_permissions_permission_id_foreign');
            $table->dropForeign('role_has_permissions_role_id_foreign');
        });
    }
};
