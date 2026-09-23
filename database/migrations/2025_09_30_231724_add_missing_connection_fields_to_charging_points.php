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
        // Vérifier et ajouter les colonnes manquantes
        $columns = [
            'last_connection_attempt' => 'timestamp',
            'steve_connection_status' => 'string',
            'charge_box_id' => 'string',
            'firmware_version' => 'string'
        ];
        
        foreach ($columns as $column => $type) {
            if (!$this->columnExists('charging_points', $column)) {
                Schema::table('charging_points', function (Blueprint $table) use ($column, $type) {
                    if ($type === 'timestamp') {
                        $table->timestamp($column)->nullable();
                    } else {
                        $table->string($column)->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            $table->dropColumn([
                'last_connection_attempt',
                'steve_connection_status', 
                'charge_box_id',
                'firmware_version'
            ]);
        });
    }
    
    /**
     * Vérifier si une colonne existe
     */
    private function columnExists($table, $column)
    {
        try {
            $columns = DB::select("PRAGMA table_info({$table})");
            foreach ($columns as $col) {
                if ($col->name === $column) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            // Si SQLite n'est pas disponible, essayer MySQL
            try {
                $columns = DB::select("SHOW COLUMNS FROM {$table}");
                foreach ($columns as $col) {
                    if ($col->Field === $column) {
                        return true;
                    }
                }
                return false;
            } catch (\Exception $e2) {
                return false;
            }
        }
    }
};