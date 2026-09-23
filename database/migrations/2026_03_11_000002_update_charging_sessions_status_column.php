<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('charging_sessions') || !Schema::hasColumn('charging_sessions', 'status')) {
            return;
        }

        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });

        $normalization = [
            'RUNNING' => 'active',
            'ACTIVE' => 'active',
            'PENDING' => 'pending',
            'INITIATING' => 'initiating',
            'IN_PROGRESS' => 'in_progress',
            'COMPLETED' => 'completed',
            'STOPPED' => 'stopped',
            'FAILED' => 'failed',
            'TIMEOUT' => 'timeout',
            'CANCELLED' => 'cancelled',
            'ERROR' => 'error',
        ];

        foreach ($normalization as $from => $to) {
            DB::table('charging_sessions')
                ->where('status', $from)
                ->update(['status' => $to]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('charging_sessions') || !Schema::hasColumn('charging_sessions', 'status')) {
            return;
        }

        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->enum('status', ['active', 'in_progress', 'completed', 'stopped', 'error'])
                ->default('active')
                ->change();
        });
    }
};
