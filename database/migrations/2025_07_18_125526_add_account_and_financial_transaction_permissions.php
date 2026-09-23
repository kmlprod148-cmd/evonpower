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
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view_accounts']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view_financial_transactions']);

        // Assign these permissions to the 'admin' role
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(['view_accounts', 'view_financial_transactions']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        \Spatie\Permission\Models\Permission::where('name', 'view_accounts')->delete();
        \Spatie\Permission\Models\Permission::where('name', 'view_financial_transactions')->delete();
    }
};
