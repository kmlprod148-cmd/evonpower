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
        // Mettre à jour les utilisateurs existants qui n'ont pas de user_type défini
        // Par défaut, les utilisateurs sans rôle système sont des clients
        DB::table('users')
            ->whereNull('user_type')
            ->update(['user_type' => 'customer']);

        // Mettre à jour les utilisateurs qui ont un rôle système pour qu'ils aient user_type = 'system'
        // Note: Cette logique peut être ajustée selon vos besoins
        $systemUserIds = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['admin', 'integrator', 'operator', 'partner'])
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->pluck('model_has_roles.model_id')
            ->toArray();

        if (!empty($systemUserIds)) {
            DB::table('users')
                ->whereIn('id', $systemUserIds)
                ->where('user_type', '!=', 'system')
                ->update(['user_type' => 'system']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cette migration ne peut pas être inversée de manière sûre
        // car nous ne savons pas quels utilisateurs avaient user_type = NULL
    }
};
