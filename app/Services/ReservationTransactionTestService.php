<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionHierarchy;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationTransactionTestService
{
    protected ReservationTransactionService $reservationTransactionService;

    public function __construct(ReservationTransactionService $reservationTransactionService)
    {
        $this->reservationTransactionService = $reservationTransactionService;
    }

    /**
     * Créer un scénario de test complet avec hiérarchie et Business Profiles
     */
    public function createTestScenario(): array
    {
        try {
            DB::beginTransaction();

            // 1. Créer l'Admin
            $admin = User::create([
                'name' => 'Test Admin',
                'email' => 'admin@test.com',
                'password' => bcrypt('password'),
                'balance' => 1000.00
            ]);
            $admin->assignRole('admin');

            // 2. Créer l'Intégrateur
            $integrator = Integrator::create([
                'name' => 'Test Integrator',
                'email' => 'integrator@test.com',
                'created_by' => $admin->id
            ]);

            // 3. Créer le Partenaire
            $partner = Partner::create([
                'name' => 'Test Partner',
                'email' => 'partner@test.com',
                'integrator_id' => $integrator->id,
                'created_by' => $admin->id
            ]);

            // 4. Créer le Groupe
            $group = Group::create([
                'name' => 'Test Group',
                'description' => 'Test Description',
                'type' => 'charging',
                'partner_id' => $partner->id,
                'user_id' => $admin->id
            ]);

            // 5. Créer l'Opérateur
            $operator = User::create([
                'name' => 'Test Operator',
                'email' => 'operator@test.com',
                'password' => bcrypt('password'),
                'balance' => 500.00
            ]);
            $operator->assignRole('operator');

            // 6. Créer la Borne
            $chargingPoint = ChargingPoint::create([
                'name' => 'Test Charging Point',
                'serial_number' => 'TEST123',
                'group_id' => $group->id,
                'partner_id' => $partner->id,
                'integrator_id' => $integrator->id,
                'operator_id' => $operator->id,
                'created_by' => $admin->id
            ]);

            // 7. Créer les Business Profiles avec frais variables
            $adminIntegratorProfile = BusinessProfile::create([
                'name' => 'Admin → Intégrateur Profile',
                'description' => 'Profil pour transactions Admin vers Intégrateur',
                'is_active' => true,
                'created_by_id' => $admin->id,
                'created_by_type' => 'admin',
                'integrator_id' => $integrator->id,
                'admin_fee_percentage' => 10.0,
                'admin_fee_fixed' => 2.0,
                'integrator_fee_percentage' => 0.0,
                'integrator_fee_fixed' => 0.0,
                'base_fee_amount' => 1.0, // Frais de base
                'transaction_fee_config' => json_encode([
                    'fixed_amount' => 0.5,
                    'percentage' => 1.0
                ]),
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 0.5,
                    'percentage' => 0.5
                ])
            ]);

            $integratorOperatorProfile = BusinessProfile::create([
                'name' => 'Intégrateur → Opérateur Profile',
                'description' => 'Profil pour transactions Intégrateur vers Opérateur',
                'is_active' => true,
                'created_by_id' => $integrator->id,
                'created_by_type' => 'integrator',
                'partner_id' => $partner->id,
                'admin_fee_percentage' => 0.0,
                'admin_fee_fixed' => 0.0,
                'integrator_fee_percentage' => 5.0,
                'integrator_fee_fixed' => 1.0,
                'base_fee_amount' => 0.5, // Frais de base
                'transaction_fee_config' => json_encode([
                    'fixed_amount' => 0.3,
                    'percentage' => 0.5
                ]),
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 0.2,
                    'percentage' => 0.3
                ])
            ]);

            // 8. Créer une réservation de test
            $reservation = Reservation::create([
                'user_id' => $operator->id,
                'charging_point_id' => $chargingPoint->id,
                'start_time' => now(),
                'end_time' => now()->addHours(2),
                'total_amount' => 100.00,
                'status' => 'confirmed'
            ]);

            DB::commit();

            return [
                'success' => true,
                'scenario' => [
                    'admin' => $admin,
                    'integrator' => $integrator,
                    'partner' => $partner,
                    'group' => $group,
                    'operator' => $operator,
                    'charging_point' => $chargingPoint,
                    'reservation' => $reservation,
                    'business_profiles' => [
                        'admin_integrator' => $adminIntegratorProfile,
                        'integrator_operator' => $integratorOperatorProfile
                    ]
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tester la logique de répartition des parts
     */
    public function testShareCalculation(): array
    {
        $scenario = $this->createTestScenario();
        
        if (!$scenario['success']) {
            return $scenario;
        }

        try {
            // Traiter la transaction de réservation
            $result = $this->reservationTransactionService->processReservationTransaction(
                $scenario['scenario']['reservation']
            );

            if (!$result['success']) {
                return $result;
            }

            // Vérifier les calculs avec frais variables
            $calculation = $result['calculation'];
            
            // Calculs attendus avec frais variables EXACTS :
            // Admin: activation_fee(1.0) + transaction_fee(0.5 + 1%) + charge_fee(0.5 + 0.5%) + role_fee(2.0 + 10%)
            // = 1.0 + (0.5 + 1.0) + (0.5 + 0.5) + (2.0 + 10.0) = 15.5€
            $expectedAdminShare = 1.0 + (0.5 + 1.0) + (0.5 + 0.5) + (2.0 + 10.0); // 15.5€
            
            // Intégrateur: activation_fee(0.5) + transaction_fee(0.3 + 0.5%) + charge_fee(0.2 + 0.3%) + role_fee(1.0 + 5%)
            // = 0.5 + (0.3 + 0.5) + (0.2 + 0.3) + (1.0 + 5.0) = 7.8€
            $expectedIntegratorShare = 0.5 + (0.3 + 0.5) + (0.2 + 0.3) + (1.0 + 5.0); // 7.8€
            
            $expectedOperatorShare = 100.00 - $expectedAdminShare - $expectedIntegratorShare; // 76.7€

            // Vérifier le breakdown des frais
            $adminFeesBreakdown = $calculation['admin_fees_breakdown'] ?? [];
            $integratorFeesBreakdown = $calculation['integrator_fees_breakdown'] ?? [];

            $testResults = [
                'total_amount' => $calculation['total_amount'],
                'admin_share' => $calculation['admin_share'],
                'integrator_share' => $calculation['integrator_share'],
                'operator_share' => $calculation['operator_share'],
                'expected_admin_share' => $expectedAdminShare,
                'expected_integrator_share' => $expectedIntegratorShare,
                'expected_operator_share' => $expectedOperatorShare,
                'admin_calculation_correct' => abs($calculation['admin_share'] - $expectedAdminShare) < 0.01,
                'integrator_calculation_correct' => abs($calculation['integrator_share'] - $expectedIntegratorShare) < 0.01,
                'operator_calculation_correct' => abs($calculation['operator_share'] - $expectedOperatorShare) < 0.01,
                'total_calculation_correct' => abs(($calculation['admin_share'] + $calculation['integrator_share'] + $calculation['operator_share']) - 100.00) < 0.01,
                'admin_fees_breakdown' => $adminFeesBreakdown,
                'integrator_fees_breakdown' => $integratorFeesBreakdown,
                'fees_validation' => [
                    'admin_activation_fee' => $adminFeesBreakdown['activation_fee'] ?? 0,
                    'admin_transaction_fee' => $adminFeesBreakdown['transaction_fee'] ?? 0,
                    'admin_charge_fee' => $adminFeesBreakdown['charge_fee'] ?? 0,
                    'admin_role_fee' => $adminFeesBreakdown['role_fee'] ?? 0,
                    'integrator_activation_fee' => $integratorFeesBreakdown['activation_fee'] ?? 0,
                    'integrator_transaction_fee' => $integratorFeesBreakdown['transaction_fee'] ?? 0,
                    'integrator_charge_fee' => $integratorFeesBreakdown['charge_fee'] ?? 0,
                    'integrator_role_fee' => $integratorFeesBreakdown['role_fee'] ?? 0,
                ]
            ];

            // Vérifier les wallets
            $adminWallet = Wallet::where('owner_type', User::class)
                ->where('owner_id', $scenario['scenario']['admin']->id)
                ->first();
            
            $integratorWallet = Wallet::where('owner_type', User::class)
                ->where('owner_id', $scenario['scenario']['integrator']->user->id)
                ->first();
            
            $operatorWallet = Wallet::where('owner_type', User::class)
                ->where('owner_id', $scenario['scenario']['operator']->id)
                ->first();

            $walletResults = [
                'admin_wallet_balance' => $adminWallet?->balance ?? 0,
                'integrator_wallet_balance' => $integratorWallet?->balance ?? 0,
                'operator_wallet_balance' => $operatorWallet?->balance ?? 0,
                'admin_credit_correct' => $adminWallet && $adminWallet->balance >= $expectedAdminShare,
                'integrator_credit_correct' => $integratorWallet && $integratorWallet->balance >= ($expectedIntegratorShare - $expectedAdminShare),
                'operator_debit_correct' => $operatorWallet && $operatorWallet->balance <= (500.00 - 100.00 + $expectedOperatorShare)
            ];

            return [
                'success' => true,
                'test_results' => $testResults,
                'wallet_results' => $walletResults,
                'transaction_data' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Nettoyer les données de test
     */
    public function cleanupTestData(): array
    {
        try {
            DB::beginTransaction();

            // Supprimer dans l'ordre inverse des dépendances
            TransactionHierarchy::where('description', 'like', '%Test%')->delete();
            TransactionDetail::whereHas('transaction', function($q) {
                $q->where('description', 'like', '%Test%');
            })->delete();
            Transaction::where('description', 'like', '%Test%')->delete();
            Reservation::where('total_amount', 100.00)->delete();
            ChargingPoint::where('serial_number', 'TEST123')->delete();
            Group::where('name', 'Test Group')->delete();
            Partner::where('name', 'Test Partner')->delete();
            Integrator::where('name', 'Test Integrator')->delete();
            User::where('email', 'like', '%@test.com')->delete();
            BusinessProfile::where('name', 'like', '%Test%')->delete();
            Wallet::where('name', 'like', '%Test%')->delete();

            DB::commit();

            return ['success' => true, 'message' => 'Données de test nettoyées'];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Exécuter une suite de tests complets
     */
    public function runFullTestSuite(): array
    {
        $results = [];

        // Test 1: Création du scénario
        $scenario = $this->createTestScenario();
        $results['scenario_creation'] = $scenario;

        if (!$scenario['success']) {
            return $results;
        }

        // Test 2: Calcul des parts
        $calculation = $this->testShareCalculation();
        $results['share_calculation'] = $calculation;

        // Test 3: Nettoyage
        $cleanup = $this->cleanupTestData();
        $results['cleanup'] = $cleanup;

        return $results;
    }
}
