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
        // Supprimer d'abord les contraintes de clés étrangères qui référencent la table transactions
        $this->dropForeignKeysReferencingTransactions();
        
        // Attendre un peu pour que les contraintes soient bien supprimées
        sleep(1);
        
        // Essayer de supprimer la table
        try {
            Schema::dropIfExists('transactions');
        } catch (Exception $e) {
            // Si la suppression échoue encore, essayer une approche alternative
            echo "Erreur lors de la suppression de la table transactions: " . $e->getMessage() . "\n";
            echo "Tentative d'approche alternative...\n";
            
            // Essayer de modifier la colonne directement si la table existe
            if (Schema::hasTable('transactions')) {
                try {
                    Schema::table('transactions', function (Blueprint $table) {
                        $table->unsignedBigInteger('user_id')->nullable()->change();
                    });
                    echo "Colonne user_id modifiée avec succès en nullable\n";
                    return; // Sortir de la méthode si la modification directe réussit
                } catch (Exception $e2) {
                    echo "Erreur lors de la modification directe: " . $e2->getMessage() . "\n";
                    throw $e; // Relancer l'erreur originale
                }
            }
        }
        
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Rendu nullable
            $table->unsignedBigInteger('charging_point_id');
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('pricing_plan_id')->nullable();
            $table->unsignedBigInteger('business_profile_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('session_id')->nullable();
            $table->decimal('meter_start', 10, 4)->nullable();
            $table->decimal('meter_stop', 10, 4)->nullable();
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('stop_timestamp')->nullable();
            $table->string('reason')->nullable();
            $table->decimal('energy_delivered', 10, 4)->nullable();
            $table->integer('duration')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('price_total', 10, 4)->nullable();
            $table->decimal('price_energy', 10, 4)->nullable();
            $table->decimal('price_time', 10, 4)->nullable();
            $table->decimal('price_service', 10, 4)->nullable();
            $table->decimal('price_tax', 10, 4)->nullable();
            $table->text('price_details')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->nullable();
            $table->string('auth_method')->nullable();
            $table->string('auth_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_id')->nullable();
            $table->decimal('admin_commission', 10, 4)->nullable();
            $table->decimal('integrator_commission', 10, 4)->nullable();
            $table->decimal('partner_commission', 10, 4)->nullable();
            $table->boolean('admin_commission_paid')->default(false);
            $table->boolean('integrator_commission_paid')->default(false);
            $table->boolean('partner_commission_paid')->default(false);
            $table->timestamp('admin_commission_paid_at')->nullable();
            $table->timestamp('integrator_commission_paid_at')->nullable();
            $table->timestamp('partner_commission_paid_at')->nullable();
            $table->text('commission_notes')->nullable();
            $table->text('repartition_breakdown')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Colonnes supplémentaires
            $table->string('transaction_type', 50)->nullable();
            $table->string('transaction_category', 50)->nullable();
            $table->decimal('activation_fee', 10, 2)->nullable();
            $table->string('activation_fee_type', 50)->nullable();
            $table->decimal('activation_fee_amount', 10, 2)->nullable();
            $table->decimal('activation_fee_percentage', 5, 4)->nullable();
            $table->unsignedBigInteger('activation_fee_business_profile_id')->nullable();
            $table->string('business_profile_owner_type', 50)->nullable();
            $table->unsignedBigInteger('business_profile_owner_id')->nullable();
            $table->json('business_profile_fee_breakdown')->nullable();
            $table->decimal('creator_charging_fees', 10, 4)->nullable();
            $table->decimal('creator_transaction_fees', 10, 4)->nullable();
            $table->decimal('creator_activation_fees', 10, 4)->nullable();
            $table->decimal('creator_admin_fees', 10, 4)->nullable();
            $table->decimal('creator_fees_total', 10, 4)->nullable();
            $table->timestamp('creator_fees_applied_at')->nullable();
            $table->string('creator_fees_source', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('tariff_plan_id')->nullable();
            
            // Index
            $table->index(['user_id', 'status']);
            $table->index(['charging_point_id', 'status']);
            $table->index(['reservation_id']);
            $table->index(['order_id']);
            $table->index(['pricing_plan_id']);
            $table->index(['business_profile_id']);
            $table->index(['transaction_type', 'transaction_category']);
        });
        
        // Recréer les clés étrangères si nécessaire
        $this->recreateForeignKeys();
    }
    
    /**
     * Recréer les clés étrangères vers la table transactions
     */
    private function recreateForeignKeys(): void
    {
        $tables = [
            'transaction_details' => ['transaction_id'],
            'transaction_hierarchies' => ['original_transaction_id'],
            'revenue_shares' => ['transaction_id'],
            'financial_transactions' => ['transaction_id'],
            'gain_distributions' => ['transaction_id']
        ];
        
        foreach ($tables as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                foreach ($columns as $column) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) use ($column) {
                            $table->foreign($column)->references('id')->on('transactions')->onDelete('cascade');
                        });
                        echo "Clé étrangère recréée pour $tableName.$column\n";
                    } catch (Exception $e) {
                        echo "Erreur lors de la recréation de la clé étrangère pour $tableName.$column: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }

    /**
     * Supprimer les contraintes de clés étrangères qui référencent la table transactions
     */
    private function dropForeignKeysReferencingTransactions(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // Pour MySQL, utiliser une requête plus robuste pour trouver toutes les contraintes
            try {
                // Trouver toutes les contraintes de clés étrangères qui référencent la table transactions
                $constraints = DB::select("
                    SELECT 
                        TABLE_NAME,
                        CONSTRAINT_NAME,
                        COLUMN_NAME
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND REFERENCED_TABLE_NAME = 'transactions'
                    AND REFERENCED_COLUMN_NAME = 'id'
                ");
                
                foreach ($constraints as $constraint) {
                    try {
                        DB::statement("ALTER TABLE `{$constraint->TABLE_NAME}` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
                        echo "Supprimé la contrainte {$constraint->CONSTRAINT_NAME} de la table {$constraint->TABLE_NAME}\n";
                    } catch (Exception $e) {
                        echo "Erreur lors de la suppression de la contrainte {$constraint->CONSTRAINT_NAME}: " . $e->getMessage() . "\n";
                    }
                }
            } catch (Exception $e) {
                echo "Erreur lors de la recherche des contraintes: " . $e->getMessage() . "\n";
            }
        } else {
            // Pour SQLite, utiliser Schema::table avec dropForeign
            $tables = [
                'transaction_details' => ['transaction_id'],
                'transaction_hierarchies' => ['original_transaction_id'],
                'commission_transactions' => ['transaction_id'],
                'revenue_shares' => ['transaction_id'],
                'financial_transactions' => ['transaction_id'],
                'gain_distributions' => ['transaction_id']
            ];
            
            foreach ($tables as $tableName => $columns) {
                if (Schema::hasTable($tableName)) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) use ($columns) {
                            foreach ($columns as $column) {
                                try {
                                    $table->dropForeign([$column]);
                                } catch (Exception $e) {
                                    // Ignorer si la clé étrangère n'existe pas
                                }
                            }
                        });
                    } catch (Exception $e) {
                        // Ignorer les erreurs si la table n'a pas de clés étrangères
                    }
                }
            }
        }
    }
};
