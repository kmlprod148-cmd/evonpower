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
        // Ajouter une contrainte unique partielle pour éviter les doubles démarrages
        // Une réservation ne peut avoir qu'une seule transaction PENDING à la fois
        Schema::table('ocpp_transactions', function (Blueprint $table) {
            // Note: MySQL ne supporte pas les index uniques partiels directement
            // On utilisera un index composite et une logique applicative
            $table->index(['reservation_id', 'status'], 'idx_reservation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ocpp_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_reservation_status');
        });
    }
};

