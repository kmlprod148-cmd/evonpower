<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BusinessProfile;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Créer un Business Profile par défaut pour éviter les erreurs
            BusinessProfile::create([
                'name' => 'Business Profile Standard',
                'description' => 'Business Profile par défaut pour les transactions',
                'is_public' => true,
                'is_active' => true,
                'target_audience' => json_encode(['admin', 'integrator', 'operator']),
                'maintenance_fee_type' => 'monthly',
                'maintenance_fee_amount' => 0,
                'transaction_fee_config' => json_encode([
                    'fixed_amount' => 0,
                    'percentage' => 0
                ]),
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 0,
                    'percentage' => 0
                ]),
                'terminal_fee_amount' => 0,
                'terminal_fee_period' => 'monthly',
                'base_fee_amount' => 0,
                'terminal_count' => 0,
                'operator_commission' => 0,
                'integrator_commission' => 0,
                'owner_commission' => 0,
                'partner_commission' => 0,
                'admin_fee_fixed' => 0,
                'admin_fee_percentage' => 5.0, // 5% pour l'admin (valeur réduite pour éviter l'erreur de plage)
                'integrator_fee_fixed' => 0.24, // 0.24 EUR fixe pour l'intégrateur
                'integrator_fee_percentage' => 0,
                'partner_fee_fixed' => 0,
                'partner_fee_percentage' => 0,
                'partner_id' => null,
                'integrator_id' => null,
                'created_by_type' => 'App\\Models\\User',
                'created_by_id' => 1, // Admin par défaut
                'pricing_plan_id' => null,
                'transaction_management_config' => json_encode([
                    'auto_process' => true,
                    'notification_enabled' => true
                ]),
                'payment_processing_config' => json_encode([
                    'auto_approve' => true,
                    'require_confirmation' => false
                ]),
                'wire_transfer_config' => json_encode([
                    'enabled' => false,
                    'min_amount' => 100
                ]),
                'admin_contact_name' => 'Admin EVON',
                'admin_contact_email' => 'admin@evonpower.com',
                'admin_contact_phone' => '+212600000000',
                'integrator_contact_name' => null,
                'integrator_contact_email' => null,
                'integrator_contact_phone' => null,
                'admin_bank_account' => null,
                'integrator_bank_account' => null,
                'operator_bank_account' => null,
                'handles_admin_debits' => true,
                'handles_integrator_debits' => true,
                'handles_client_payments' => true,
                'handles_wire_transfers' => false,
                'min_transaction_amount' => 0.01,
                'max_transaction_amount' => 10000.00,
                'daily_limit' => 1000.00,
                'monthly_limit' => 30000.00,
            ]);
        } else {
            // Pour SQLite, on ne peut pas facilement créer des enregistrements avec des contraintes NOT NULL
            echo "SQLite detected - skipping default business profile creation\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Supprimer le Business Profile par défaut
            BusinessProfile::where('name', 'Business Profile Standard')->delete();
        } else {
            // Pour SQLite, on ne peut pas facilement supprimer des enregistrements
            echo "SQLite detected - skipping default business profile deletion\n";
        }
    }
};