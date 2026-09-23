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
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Vérifier si les colonnes existent déjà avant de les ajouter
            if (!Schema::hasColumn('charging_sessions', 'hierarchical_transaction_processed')) {
                $table->boolean('hierarchical_transaction_processed')->default(false)->after('status');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'hierarchical_transaction_processed_at')) {
                $table->timestamp('hierarchical_transaction_processed_at')->nullable()->after('hierarchical_transaction_processed');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'hierarchical_transaction_data')) {
                $table->json('hierarchical_transaction_data')->nullable()->after('hierarchical_transaction_processed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'hierarchical_transaction_processed',
                'hierarchical_transaction_processed_at',
                'hierarchical_transaction_data'
            ]);
        });
    }
};
