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
        // Vérifier si la colonne 'mode' existe déjà
        if (Schema::hasColumn('charging_sessions', 'mode')) {
            // La colonne existe déjà, on vérifie juste sa structure
            $this->ensureModeColumnStructure();
        } else {
            // La colonne n'existe pas, on l'ajoute
            Schema::table('charging_sessions', function (Blueprint $table) {
                $table->enum('mode', ['prepaid', 'postpaid'])->default('postpaid')->after('user_id');
            });
        }
        
        // Vérifier et ajouter les autres colonnes si elles n'existent pas
        $this->ensureOtherColumnsExist();
    }

    /**
     * S'assurer que la colonne mode a la bonne structure
     */
    private function ensureModeColumnStructure(): void
    {
        try {
            // Vérifier si la colonne a la bonne structure en essayant de la modifier
            DB::statement("ALTER TABLE charging_sessions MODIFY COLUMN mode ENUM('prepaid', 'postpaid') NOT NULL DEFAULT 'postpaid'");
        } catch (\Exception $e) {
            // Si la modification échoue, c'est probablement que la structure est déjà correcte
            // ou que nous sommes sur SQLite qui ne supporte pas MODIFY COLUMN
            if (DB::getDriverName() === 'sqlite') {
                // Sur SQLite, on ignore cette vérification
                return;
            }
            
            // Pour MySQL, on peut ignorer l'erreur si la colonne existe déjà avec la bonne structure
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }
    }

    /**
     * S'assurer que les autres colonnes existent
     */
    private function ensureOtherColumnsExist(): void
    {
        $columns = [
            'estimated_cost' => 'decimal(10,2)',
            'prepaid_amount' => 'decimal(10,2)', 
            'refund_amount' => 'decimal(10,2)',
            'min_threshold' => 'decimal(10,2)',
            'wallet_validation_passed' => 'boolean',
            'wallet_validated_at' => 'timestamp'
        ];

        foreach ($columns as $column => $type) {
            if (!Schema::hasColumn('charging_sessions', $column)) {
                Schema::table('charging_sessions', function (Blueprint $table) use ($column, $type) {
                    switch ($column) {
                        case 'estimated_cost':
                            $table->decimal('estimated_cost', 10, 2)->nullable()->after('cost')->comment('Estimated cost for prepaid sessions');
                            break;
                        case 'prepaid_amount':
                            $table->decimal('prepaid_amount', 10, 2)->nullable()->after('estimated_cost')->comment('Amount charged for prepaid sessions');
                            break;
                        case 'refund_amount':
                            $table->decimal('refund_amount', 10, 2)->nullable()->after('prepaid_amount')->comment('Amount refunded for prepaid sessions');
                            break;
                        case 'min_threshold':
                            $table->decimal('min_threshold', 10, 2)->nullable()->after('refund_amount')->comment('Minimum threshold for postpaid sessions');
                            break;
                        case 'wallet_validation_passed':
                            $table->boolean('wallet_validation_passed')->default(false)->after('min_threshold')->comment('Whether wallet validation passed');
                            break;
                        case 'wallet_validated_at':
                            $table->timestamp('wallet_validated_at')->nullable()->after('wallet_validation_passed')->comment('When wallet validation was performed');
                            break;
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
        // Ne pas supprimer les colonnes car elles pourraient être utilisées
        // Cette migration est uniquement pour corriger les problèmes de duplication
    }
};