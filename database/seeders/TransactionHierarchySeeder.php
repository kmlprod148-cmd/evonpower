<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionHierarchy;
use App\Models\ChargingPoint;
use App\Services\HierarchicalTransactionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class TransactionHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les rôles s'ils n'existent pas
        $this->createRoles();

        // Créer les utilisateurs de test
        $users = $this->createTestUsers();

        // Créer un business profile de test
        $businessProfile = $this->createTestBusinessProfile();

        // Créer une borne de recharge de test
        $chargingPoint = $this->createTestChargingPoint($users['operator'], $businessProfile);

        // Créer une transaction de test
        $transaction = $this->createTestTransaction($users['operator'], $chargingPoint);

        // Traiter la transaction avec le service hiérarchique
        $this->processTestTransaction($transaction);

        $this->command->info('Transaction hiérarchique de test créée avec succès !');
        $this->command->info('Admin: ' . $users['admin']->name . ' - Solde: ' . $users['admin']->getFormattedBalance());
        $this->command->info('Intégrateur: ' . $users['integrator']->name . ' - Solde: ' . $users['integrator']->getFormattedBalance());
        $this->command->info('Opérateur: ' . $users['operator']->name . ' - Solde: ' . $users['operator']->getFormattedBalance());
    }

    private function createRoles(): void
    {
        $roles = ['admin', 'integrator', 'partner'];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createTestUsers(): array
    {
        // Créer l'admin ALAA
        $admin = User::firstOrCreate(
            ['email' => 'alaa@admin.com'],
            [
                'name' => 'ALAA',
                'password' => Hash::make('password'),
                'balance' => 0.00,
                'currency' => 'EUR',
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // Créer l'intégrateur MEHDI (créé par ALAA)
        $integrator = User::firstOrCreate(
            ['email' => 'mehdi@integrator.com'],
            [
                'name' => 'MEHDI',
                'password' => Hash::make('password'),
                'balance' => 0.00,
                'currency' => 'EUR',
                'created_by' => $admin->id,
                'is_active' => true,
            ]
        );
        $integrator->assignRole('integrator');

        // Créer l'opérateur ANAS (créé par MEHDI)
        $operator = User::firstOrCreate(
            ['email' => 'anas@operator.com'],
            [
                'name' => 'ANAS',
                'password' => Hash::make('password'),
                'balance' => 200.00, // Solde initial pour payer la transaction
                'currency' => 'EUR',
                'created_by' => $integrator->id,
                'is_active' => true,
            ]
        );
        $operator->assignRole('partner');

        return [
            'admin' => $admin,
            'integrator' => $integrator,
            'operator' => $operator,
        ];
    }

    private function createTestBusinessProfile(): \App\Models\BusinessProfile
    {
        return \App\Models\BusinessProfile::firstOrCreate(
            ['name' => 'Business Profile Test'],
            [
                'description' => 'Business profile pour les tests de transaction hiérarchique',
                'is_active' => true,
                'is_public' => false,
                'transaction_fee_amount' => 1.00, // 1 DH fixe
                'transaction_fee_type' => 'fixed',
                'charge_fee_amount' => 2.00, // 2 DH de frais de recharge
                'base_fee_amount' => 1.00, // 1 DH de frais de base
                'admin_fee_percentage' => 10.0, // 10%
                'integrator_fee_percentage' => 5.0, // 5%
                'operator_commission' => 85.0, // 85% pour l'opérateur
                'transaction_fee_config' => json_encode([
                    'fixed_amount' => 1.00,
                    'percentage' => 0,
                    'charge_fee' => 2.00,
                    'base_fee' => 1.00
                ]),
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 2.00,
                    'percentage' => 0
                ]),
            ]
        );
    }

    private function createTestChargingPoint(User $operator, \App\Models\BusinessProfile $businessProfile): ChargingPoint
    {
        return ChargingPoint::firstOrCreate(
            ['name' => 'Borne Test ANAS'],
            [
                'user_id' => $operator->id,
                'business_profile_id' => $businessProfile->id,
                'status' => 'online',
                'max_power' => 22.0,
                'serial_number' => 'TEST-001',
                'latitude' => 33.5731,
                'longitude' => -7.5898,
            ]
        );
    }

    private function createTestTransaction(User $operator, ChargingPoint $chargingPoint): Transaction
    {
        return Transaction::create([
            'user_id' => $operator->id,
            'charging_point_id' => $chargingPoint->id,
            'amount' => 200.00,
            'price_total' => 200.00,
            'price_energy' => 180.00,
            'price_time' => 20.00,
            'price_service' => 0.00,
            'price_tax' => 0.00,
            'currency' => 'EUR',
            'status' => 'completed',
            'payment_status' => 'paid',
            'energy_delivered' => 10.0,
            'duration' => 30,
            'start_timestamp' => now()->subMinutes(30),
            'stop_timestamp' => now(),
        ]);
    }

    private function processTestTransaction(Transaction $transaction): void
    {
        $service = new HierarchicalTransactionService();
        $result = $service->processTransaction($transaction);

        if ($result['success']) {
            $this->command->info('Transaction traitée avec succès !');
            $this->command->info('Frais de transaction: ' . $result['transaction_detail']->transaction_fee_total . ' EUR');
            $this->command->info('Part Admin: ' . $result['transaction_detail']->admin_share_amount . ' EUR');
            $this->command->info('Part Intégrateur: ' . $result['transaction_detail']->integrator_share_amount . ' EUR');
            $this->command->info('Part Opérateur: ' . $result['transaction_detail']->operator_share_amount . ' EUR');
        } else {
            $this->command->error('Erreur lors du traitement: ' . $result['error']);
        }
    }
}
