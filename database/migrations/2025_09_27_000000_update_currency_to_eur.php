<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update all currency references from EUR to EUR
     */
    public function up(): void
    {
        // Update users table currency
        DB::table('users')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        
        // Update pricing_plans table currency
        if (Schema::hasTable('pricing_plans')) {
            DB::table('pricing_plans')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        }
        
        // Update tariff_plans table currency
        if (Schema::hasTable('tariff_plans')) {
            DB::table('tariff_plans')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        }
        
        // Update transactions table currency
        if (Schema::hasTable('transactions')) {
            DB::table('transactions')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        }
        
        // Update wire_transfers table currency
        if (Schema::hasTable('wire_transfers')) {
            DB::table('wire_transfers')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        }
        
        // The 'reservations' table does not have a 'currency' column.
        // The 'orders' table does not have a 'currency' column.
        
        // Update any other tables that might have currency fields
        $tables = ['business_profiles', 'commission_plans', 'balance_movements'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'currency')) {
                DB::table($table)->where('currency', 'EUR')->update(['currency' => 'EUR']);
            }
        }
        
        // Convert EUR amounts to EUR (approximate conversion rate: 1 EUR = 10.7 EUR)
        // This is a rough conversion - adjust the rate as needed
        $conversionRate = 10.7;
        
        // Convert user balances
        DB::statement("UPDATE users SET balance = ROUND(balance / {$conversionRate}, 2) WHERE currency = 'EUR' AND balance > 0");
        
        // Convert pricing plan amounts
        if (Schema::hasTable('pricing_plans')) {
            DB::statement("UPDATE pricing_plans SET 
                price_per_kwh = ROUND(price_per_kwh / {$conversionRate}, 4),
                price_per_minute = ROUND(price_per_minute / {$conversionRate}, 4),
                activation_fee = ROUND(activation_fee / {$conversionRate}, 2),
                base_rate = ROUND(base_rate / {$conversionRate}, 4)
                WHERE currency = 'EUR'");
        }
        
        // Convert transaction amounts
        if (Schema::hasTable('transactions')) {
            DB::statement("UPDATE transactions SET 
                amount = ROUND(amount / {$conversionRate}, 2),
                price_total = ROUND(price_total / {$conversionRate}, 4),
                price_energy = ROUND(price_energy / {$conversionRate}, 4),
                price_time = ROUND(price_time / {$conversionRate}, 4),
                price_service = ROUND(price_service / {$conversionRate}, 4),
                price_tax = ROUND(price_tax / {$conversionRate}, 4),
                admin_commission = ROUND(admin_commission / {$conversionRate}, 4),
                integrator_commission = ROUND(integrator_commission / {$conversionRate}, 4),
                partner_commission = ROUND(partner_commission / {$conversionRate}, 4)
                WHERE currency = 'EUR'");
        }
        
        // Convert business profile fees
        if (Schema::hasTable('business_profiles')) {
            DB::statement("UPDATE business_profiles SET 
                admin_fee_fixed = ROUND(admin_fee_fixed / {$conversionRate}, 2),
                integrator_fee_fixed = ROUND(integrator_fee_fixed / {$conversionRate}, 2),
                partner_fee_fixed = ROUND(partner_fee_fixed / {$conversionRate}, 2),
                maintenance_fee_amount = ROUND(maintenance_fee_amount / {$conversionRate}, 2),
                terminal_fee_amount = ROUND(terminal_fee_amount / {$conversionRate}, 2),
                base_fee_amount = ROUND(base_fee_amount / {$conversionRate}, 2)
                WHERE 1=1"); // Update all business profiles
        }
        
        // Convert wire transfer amounts
        if (Schema::hasTable('wire_transfers')) {
            DB::statement("UPDATE wire_transfers SET 
                amount = ROUND(amount / {$conversionRate}, 2)
                WHERE currency = 'EUR'");
        }
        
        // Log the currency conversion
        \Log::info('Currency conversion completed: EUR to EUR', [
            'conversion_rate' => $conversionRate,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse conversion (EUR back to EUR)
        $conversionRate = 10.7;
        
        // Convert back to EUR
        DB::table('users')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        
        if (Schema::hasTable('pricing_plans')) {
            DB::table('pricing_plans')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        }
        
        if (Schema::hasTable('tariff_plans')) {
            DB::table('tariff_plans')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        }
        
        if (Schema::hasTable('transactions')) {
            DB::table('transactions')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        }
        
        if (Schema::hasTable('wire_transfers')) {
            DB::table('wire_transfers')->where('currency', 'EUR')->update(['currency' => 'EUR']);
        }
        
        // Convert amounts back (multiply by conversion rate)
        DB::statement("UPDATE users SET balance = ROUND(balance * {$conversionRate}, 2) WHERE currency = 'EUR'");
        
        if (Schema::hasTable('pricing_plans')) {
            DB::statement("UPDATE pricing_plans SET 
                price_per_kwh = ROUND(price_per_kwh * {$conversionRate}, 4),
                price_per_minute = ROUND(price_per_minute * {$conversionRate}, 4),
                activation_fee = ROUND(activation_fee * {$conversionRate}, 2),
                base_rate = ROUND(base_rate * {$conversionRate}, 4)
                WHERE currency = 'EUR'");
        }
        
        if (Schema::hasTable('transactions')) {
            DB::statement("UPDATE transactions SET 
                amount = ROUND(amount * {$conversionRate}, 2),
                price_total = ROUND(price_total * {$conversionRate}, 4),
                price_energy = ROUND(price_energy * {$conversionRate}, 4),
                price_time = ROUND(price_time * {$conversionRate}, 4),
                price_service = ROUND(price_service * {$conversionRate}, 4),
                price_tax = ROUND(price_tax * {$conversionRate}, 4),
                admin_commission = ROUND(admin_commission * {$conversionRate}, 4),
                integrator_commission = ROUND(integrator_commission * {$conversionRate}, 4),
                partner_commission = ROUND(partner_commission * {$conversionRate}, 4)
                WHERE currency = 'EUR'");
        }
    }
};
