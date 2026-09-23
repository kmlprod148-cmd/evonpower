<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixMigrationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrations:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix migration issues and foreign key constraints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Début de la réparation des migrations...');

        try {
            // Désactiver les contraintes de clé étrangère
            $this->info('📋 Désactivation des contraintes de clé étrangère...');
            Schema::disableForeignKeyConstraints();

            // Vérifier et corriger la table transactions
            if (Schema::hasTable('transactions')) {
                $this->info('🔍 Vérification de la table transactions...');
                
                // Supprimer la colonne order_id si elle existe
                if (Schema::hasColumn('transactions', 'order_id')) {
                    $this->info('🗑️  Suppression de la colonne order_id de transactions...');
                    Schema::table('transactions', function ($table) {
                        $table->dropForeign(['order_id']);
                        $table->dropColumn('order_id');
                    });
                }
            }

            // Vérifier et corriger la table business_profiles
            if (Schema::hasTable('business_profiles')) {
                $this->info('🔍 Vérification de la table business_profiles...');
                
                // Ajouter les colonnes manquantes si nécessaire
                $columns = [
                    'name' => 'string',
                    'description' => 'text',
                    'is_public' => 'boolean',
                    'is_active' => 'boolean',
                    'target_type' => 'enum',
                    'maintenance_fee_type' => 'enum',
                    'maintenance_fee_amount' => 'decimal',
                    'transaction_fee_type' => 'enum',
                    'transaction_fee_amount' => 'decimal',
                    'charge_fee_amount' => 'decimal',
                    'terminal_fee_amount' => 'decimal',
                    'terminal_fee_period' => 'enum'
                ];

                foreach ($columns as $column => $type) {
                    if (!Schema::hasColumn('business_profiles', $column)) {
                        $this->info("➕ Ajout de la colonne {$column} à business_profiles...");
                        Schema::table('business_profiles', function ($table) use ($column, $type) {
                            switch ($type) {
                                case 'string':
                                    $table->string($column, 100)->after('id');
                                    break;
                                case 'text':
                                    $table->text($column)->nullable()->after('name');
                                    break;
                                case 'boolean':
                                    $default = $column === 'is_public' ? false : true;
                                    $table->boolean($column)->default($default)->after('description');
                                    break;
                                case 'enum':
                                    switch ($column) {
                                        case 'target_type':
                                            $table->enum($column, ['integrator', 'partner'])->after('is_active');
                                            break;
                                        case 'maintenance_fee_type':
                                        case 'terminal_fee_period':
                                            $table->enum($column, ['monthly', 'quarterly', 'yearly'])->nullable()->after('target_type');
                                            break;
                                        case 'transaction_fee_type':
                                            $table->enum($column, ['fixed', 'percentage'])->nullable()->after('maintenance_fee_amount');
                                            break;
                                    }
                                    break;
                                case 'decimal':
                                    $table->decimal($column, 10, 2)->nullable()->after('transaction_fee_type');
                                    break;
                            }
                        });
                    }
                }

                // Ajouter les timestamps si manquants
                if (!Schema::hasColumn('business_profiles', 'created_at')) {
                    $this->info('➕ Ajout de created_at à business_profiles...');
                    Schema::table('business_profiles', function ($table) {
                        $table->timestamp('created_at')->nullable();
                    });
                }

                if (!Schema::hasColumn('business_profiles', 'updated_at')) {
                    $this->info('➕ Ajout de updated_at à business_profiles...');
                    Schema::table('business_profiles', function ($table) {
                        $table->timestamp('updated_at')->nullable();
                    });
                }
            }

            // Réactiver les contraintes de clé étrangère
            $this->info('📋 Réactivation des contraintes de clé étrangère...');
            Schema::enableForeignKeyConstraints();

            // Ajouter order_id à transactions si la table orders existe
            if (Schema::hasTable('orders') && Schema::hasTable('transactions')) {
                $this->info('➕ Ajout de order_id à transactions...');
                Schema::table('transactions', function ($table) {
                    $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                });
            }

            $this->info('✅ Réparation des migrations terminée avec succès !');
            $this->info('💡 Vous pouvez maintenant exécuter: php artisan migrate');

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la réparation: ' . $e->getMessage());
            $this->error('📍 Fichier: ' . $e->getFile() . ':' . $e->getLine());
            
            // Réactiver les contraintes en cas d'erreur
            Schema::enableForeignKeyConstraints();
            
            return 1;
        }

        return 0;
    }
}
