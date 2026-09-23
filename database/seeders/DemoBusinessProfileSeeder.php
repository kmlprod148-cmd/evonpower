<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\Station;

class DemoBusinessProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Création des données de démonstration Business Profile...');

        // 1. Vérifier les utilisateurs existants
        $this->command->info('1. Vérification des utilisateurs existants...');
        
        $admin = User::find(1);
        $integrator = User::find(12);
        $operator = User::find(12); // Même ID que l'intégrateur selon votre spécification
        
        if (!$admin) {
            $this->command->error('❌ Admin ID 1 non trouvé');
            return;
        }
        
        if (!$integrator) {
            $this->command->error('❌ Intégrateur ID 12 non trouvé');
            return;
        }
        
        if (!$operator) {
            $this->command->error('❌ Opérateur ID 12 non trouvé');
            return;
        }
        
        $this->command->info("✅ Admin: {$admin->name} ({$admin->email})");
        $this->command->info("✅ Intégrateur: {$integrator->name} ({$integrator->email})");
        $this->command->info("✅ Opérateur: {$operator->name} ({$operator->email})");
        
        // Créer un client de test
        $client = User::firstOrCreate(
            ['email' => 'client.demo@test.com'],
            [
                'name' => 'Client Test Démo',
                'password' => Hash::make('password'),
                'role' => 'client'
            ]
        );
        $this->command->info("✅ Client: {$client->name} ({$client->email})");
        
        // 2. Créer un Business Profile
        $this->command->info("\n2. Création du Business Profile...");
        
        $businessProfile = BusinessProfile::firstOrCreate(
            ['name' => 'Business Profile Démo Seeder'],
            [
                'description' => 'Business Profile pour la démonstration avec seeder',
                'is_active' => true,
                'is_public' => true,
                'target_audience' => 'all',
                'maintenance_fee_type' => 'percentage',
                'maintenance_fee_amount' => 2.5,
                'transaction_fee_config' => json_encode([
                    'fixed_amount' => 0.50,
                    'percentage' => 1.5
                ]),
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 1.00,
                    'percentage' => 2.0,
                    'per_kwh_fee' => 0.10,
                    'per_minute_fee' => 0.05
                ]),
                'base_fee_amount' => 2.00,
                'admin_fee_fixed' => 0.50,
                'admin_fee_percentage' => 3.0,
                'integrator_fee_fixed' => 1.00,
                'integrator_fee_percentage' => 5.0,
                'partner_fee_fixed' => 0.50,
                'partner_fee_percentage' => 2.0,
                'min_transaction_amount' => 1.00,
                'max_transaction_amount' => 1000.00,
                'daily_limit' => 5000.00,
                'monthly_limit' => 50000.00
            ]
        );
        
        $this->command->info("✅ Business Profile créé: {$businessProfile->name}");
        $this->command->info("   - Frais Admin: {$businessProfile->admin_fee_fixed}€ + {$businessProfile->admin_fee_percentage}%");
        $this->command->info("   - Frais Intégrateur: {$businessProfile->integrator_fee_fixed}€ + {$businessProfile->integrator_fee_percentage}%");
        $this->command->info("   - Frais Partenaire: {$businessProfile->partner_fee_fixed}€ + {$businessProfile->partner_fee_percentage}%");
        
        // 3. Créer une station si elle n'existe pas
        $station = Station::firstOrCreate(
            ['name' => 'Station Démo'],
            [
                'location' => 'Test Location',
                'status' => 'active',
                'description' => 'Station de démonstration'
            ]
        );
        
        // 4. Créer un point de charge
        $this->command->info("\n3. Création du point de charge...");
        
        $chargingPoint = ChargingPoint::firstOrCreate(
            ['name' => 'Point de Charge Démo Seeder'],
            [
                'station_id' => $station->id,
                'user_id' => $operator->id,
                'integrator_id' => $integrator->id,
                'partner_id' => 1,
                'business_profile_id' => $businessProfile->id,
                'status' => 'active',
                'power_output' => 22.0,
                'connector_type' => 'Type 2',
                'location' => 'Test Location - Seeder'
            ]
        );
        
        $this->command->info("✅ Point de charge créé: {$chargingPoint->name}");
        $this->command->info("   - Puissance: {$chargingPoint->power_output} kW");
        $this->command->info("   - Opérateur: {$operator->name} (ID: {$operator->id})");
        $this->command->info("   - Intégrateur: {$integrator->name} (ID: {$integrator->id})");
        
        // 5. Créer plusieurs transactions de démonstration
        $this->command->info("\n4. Création des transactions de démonstration...");
        
        $demoTransactions = [
            [
                'amount' => 25.50,
                'type' => 'kwh',
                'value' => 15.0,
                'description' => 'Recharge matinale - 15 kWh'
            ],
            [
                'amount' => 35.00,
                'type' => 'kwh',
                'value' => 20.0,
                'description' => 'Recharge après-midi - 20 kWh'
            ],
            [
                'amount' => 18.75,
                'type' => 'minute',
                'value' => 30.0,
                'description' => 'Recharge rapide - 30 minutes'
            ]
        ];
        
        $createdTransactions = [];
        
        foreach ($demoTransactions as $index => $demoData) {
            $this->command->info("   📋 Transaction " . ($index + 1) . ": {$demoData['description']}");
            
            // Calculer les frais
            $reservationAmount = $demoData['amount'];
            $reservationType = $demoData['type'];
            $reservationValue = $demoData['value'];
            
            // Frais d'activation
            $activationFee = (float) $businessProfile->base_fee_amount;
            
            // Frais de recharge
            $chargeFeeConfig = json_decode($businessProfile->charge_fee_config, true);
            $chargeFeeFixed = (float) ($chargeFeeConfig['fixed_amount'] ?? 0);
            $chargeFeePercentage = (float) ($chargeFeeConfig['percentage'] ?? 0);
            $chargeFeePerKwh = (float) ($chargeFeeConfig['per_kwh_fee'] ?? 0);
            $chargeFeePerMinute = (float) ($chargeFeeConfig['per_minute_fee'] ?? 0);
            
            $chargeFeeAmount = $chargeFeeFixed + ($reservationAmount * $chargeFeePercentage / 100);
            if ($reservationType === 'kwh') {
                $chargeFeeAmount += ($reservationValue * $chargeFeePerKwh);
            } elseif ($reservationType === 'minute') {
                $chargeFeeAmount += ($reservationValue * $chargeFeePerMinute);
            }
            
            // Frais de transaction
            $transactionFeeConfig = json_decode($businessProfile->transaction_fee_config, true);
            $transactionFeeFixed = (float) ($transactionFeeConfig['fixed_amount'] ?? 0);
            $transactionFeePercentage = (float) ($transactionFeeConfig['percentage'] ?? 0);
            $transactionFeeAmount = $transactionFeeFixed + ($reservationAmount * $transactionFeePercentage / 100);
            
            // Total des frais
            $totalFees = $activationFee + $chargeFeeAmount + $transactionFeeAmount;
            $totalAmount = $reservationAmount + $totalFees;
            
            // Calculer les commissions
            $adminCommission = $businessProfile->admin_fee_fixed + ($totalAmount * $businessProfile->admin_fee_percentage / 100);
            $integratorCommission = $businessProfile->integrator_fee_fixed + ($totalAmount * $businessProfile->integrator_fee_percentage / 100);
            $partnerCommission = $businessProfile->partner_fee_fixed + ($totalAmount * $businessProfile->partner_fee_percentage / 100);
            
            $totalCommissions = $adminCommission + $integratorCommission + $partnerCommission;
            $netAmount = $totalAmount - $totalCommissions;
            
            $now = now();
            
            // Transaction principale (Client → Système)
            $mainTransaction = Transaction::create([
                'user_id' => $client->id,
                'charging_point_id' => $chargingPoint->id,
                'business_profile_id' => $businessProfile->id,
                'transaction_type' => 'client',
                'transaction_category' => 'charging',
                'status' => 'completed',
                'amount' => $reservationAmount,
                'price_total' => $totalAmount,
                'currency' => 'EUR',
                'admin_commission' => $adminCommission,
                'integrator_commission' => $integratorCommission,
                'partner_commission' => $partnerCommission,
                'description' => $demoData['description'],
                'start_timestamp' => $now,
                'stop_timestamp' => $now->copy()->addMinutes(30),
                'energy_delivered' => $reservationType === 'kwh' ? $reservationValue : 0,
                'duration' => $reservationType === 'minute' ? $reservationValue : 30
            ]);
            
            // Transaction Admin
            $adminTransaction = Transaction::create([
                'user_id' => $admin->id,
                'charging_point_id' => $chargingPoint->id,
                'business_profile_id' => $businessProfile->id,
                'transaction_type' => 'admin',
                'transaction_category' => 'commission',
                'status' => 'completed',
                'amount' => $adminCommission,
                'price_total' => $adminCommission,
                'currency' => 'EUR',
                'admin_commission' => 0,
                'integrator_commission' => 0,
                'partner_commission' => 0,
                'description' => "Commission Admin - {$demoData['description']}",
                'start_timestamp' => $now,
                'stop_timestamp' => $now,
                'energy_delivered' => 0,
                'duration' => 0
            ]);
            
            // Transaction Intégrateur
            $integratorTransaction = Transaction::create([
                'user_id' => $integrator->id,
                'charging_point_id' => $chargingPoint->id,
                'business_profile_id' => $businessProfile->id,
                'transaction_type' => 'admin',
                'transaction_category' => 'integrator_commission',
                'status' => 'completed',
                'amount' => $integratorCommission,
                'price_total' => $integratorCommission,
                'currency' => 'EUR',
                'admin_commission' => 0,
                'integrator_commission' => 0,
                'partner_commission' => 0,
                'description' => "Commission Intégrateur - {$demoData['description']}",
                'start_timestamp' => $now,
                'stop_timestamp' => $now,
                'energy_delivered' => 0,
                'duration' => 0
            ]);
            
            // Transaction Opérateur
            $operatorTransaction = Transaction::create([
                'user_id' => $operator->id,
                'charging_point_id' => $chargingPoint->id,
                'business_profile_id' => $businessProfile->id,
                'transaction_type' => 'admin',
                'transaction_category' => 'operator_commission',
                'status' => 'completed',
                'amount' => $netAmount,
                'price_total' => $netAmount,
                'currency' => 'EUR',
                'admin_commission' => 0,
                'integrator_commission' => 0,
                'partner_commission' => 0,
                'description' => "Part Opérateur - {$demoData['description']}",
                'start_timestamp' => $now,
                'stop_timestamp' => $now,
                'energy_delivered' => 0,
                'duration' => 0
            ]);
            
            $createdTransactions[] = [
                'main' => $mainTransaction,
                'admin' => $adminTransaction,
                'integrator' => $integratorTransaction,
                'operator' => $operatorTransaction,
                'total_amount' => $totalAmount,
                'admin_commission' => $adminCommission,
                'integrator_commission' => $integratorCommission,
                'net_amount' => $netAmount
            ];
            
            $this->command->info("   ✅ Transaction créée: #{$mainTransaction->id} - {$totalAmount}€");
        }
        
        // 6. Résumé des transactions créées
        $this->command->info("\n5. Résumé des transactions créées...");
        
        $totalAmount = 0;
        $totalAdminCommission = 0;
        $totalIntegratorCommission = 0;
        $totalNetAmount = 0;
        
        foreach ($createdTransactions as $transactionGroup) {
            $totalAmount += $transactionGroup['total_amount'];
            $totalAdminCommission += $transactionGroup['admin_commission'];
            $totalIntegratorCommission += $transactionGroup['integrator_commission'];
            $totalNetAmount += $transactionGroup['net_amount'];
        }
        
        $this->command->info("📊 Résumé financier:");
        $this->command->info("   - Montant total: " . number_format($totalAmount, 2) . "€");
        $this->command->info("   - Commission Admin: " . number_format($totalAdminCommission, 2) . "€");
        $this->command->info("   - Commission Intégrateur: " . number_format($totalIntegratorCommission, 2) . "€");
        $this->command->info("   - Part Opérateur: " . number_format($totalNetAmount, 2) . "€");
        $this->command->info("   - Total transactions: " . (count($createdTransactions) * 4));
        
        $this->command->info("\n🎉 Données de démonstration créées avec succès !");
        $this->command->info("Vous pouvez maintenant voir ces transactions dans l'interface d'administration.");
        $this->command->info("Utilisateurs de test:");
        $this->command->info("   - Admin: {$admin->email}");
        $this->command->info("   - Intégrateur: {$integrator->email}");
        $this->command->info("   - Opérateur: {$operator->email}");
        $this->command->info("   - Client: {$client->email}");
    }
}
