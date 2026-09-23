<?php

namespace App\Console\Commands;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ReservationTransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestHierarchicalWalletLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:hierarchical-wallet-logic {--amount=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester la logique hiérarchique des wallets (Admin → Intégrateur → Opérateur)';

    protected ReservationTransactionService $reservationTransactionService;

    public function __construct(ReservationTransactionService $reservationTransactionService)
    {
        parent::__construct();
        $this->reservationTransactionService = $reservationTransactionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amount = (float) $this->option('amount');
        
        $this->info("🧪 Test de la logique hiérarchique des wallets pour un montant de {$amount}€");
        $this->newLine();

        try {
            DB::beginTransaction();

            // 1. Créer les utilisateurs de test
            $users = $this->createTestUsers();
            
            // 2. Créer les wallets avec soldes initiaux
            $wallets = $this->createTestWallets($users);
            
            // 3. Créer un Business Profile de test
            $businessProfile = $this->createTestBusinessProfile();
            
            // 4. Simuler la logique de calcul des parts
            $calculation = $this->simulateHierarchicalCalculation($amount, $businessProfile);
            
            // 5. Appliquer la logique hiérarchique des wallets
            $this->applyHierarchicalWalletLogic($wallets, $calculation, $amount);
            
            // 6. Afficher les résultats
            $this->displayResults($wallets, $calculation, $amount);
            
            DB::rollBack(); // Annuler les changements pour ne pas polluer la DB
            
            $this->newLine();
            $this->info("✅ Test terminé avec succès!");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur lors du test: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function createTestUsers(): array
    {
        $this->info("👥 Création des utilisateurs de test...");
        
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'balance' => 1000.00
        ]);
        $admin->assignRole('admin');

        $integrator = User::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'password' => bcrypt('password'),
            'balance' => 500.00
        ]);
        $integrator->assignRole('integrator');

        $operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'balance' => 200.00
        ]);
        $operator->assignRole('operator');

        return compact('admin', 'integrator', 'operator');
    }

    protected function createTestWallets(array $users): array
    {
        $this->info("💳 Création des wallets de test...");
        
        $wallets = [];
        
        foreach ($users as $role => $user) {
            $wallet = Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $user->id,
                'balance' => $user->balance,
                'currency' => 'EUR',
                'is_active' => true,
                'name' => "Wallet {$role}"
            ]);
            $wallets[$role] = $wallet;
        }

        return $wallets;
    }

    protected function createTestBusinessProfile(): BusinessProfile
    {
        $this->info("📋 Création du Business Profile de test...");
        
        return BusinessProfile::create([
            'name' => 'Test Hierarchical Profile',
            'description' => 'Profil de test pour logique hiérarchique',
            'is_active' => true,
            'created_by_id' => 1,
            'created_by_type' => 'admin',
            'admin_fee_percentage' => 10.0,
            'admin_fee_fixed' => 2.0,
            'integrator_fee_percentage' => 5.0,
            'integrator_fee_fixed' => 1.0,
            'integrator_commission' => 5.0,
            'partner_commission' => 3.0,
            'owner_commission' => 2.0,
            'integrator_id' => 1,
            'partner_id' => 1,
            'base_fee_amount' => 1.0,
            'transaction_fee_config' => json_encode([
                'fixed_amount' => 0.5,
                'percentage' => 1.0
            ]),
            'charge_fee_config' => json_encode([
                'fixed_amount' => 0.5,
                'percentage' => 0.5
            ])
        ]);
    }

    protected function simulateHierarchicalCalculation(float $amount, BusinessProfile $profile): array
    {
        $this->info("💰 Simulation du calcul hiérarchique...");
        
        // Utiliser la méthode privée via reflection
        $reflection = new \ReflectionClass($this->reservationTransactionService);
        $method = $reflection->getMethod('calculateBusinessProfileFees');
        $method->setAccessible(true);

        // Calculer les frais pour Admin et Intégrateur
        $adminFees = $method->invoke($this->reservationTransactionService, $profile, $amount, 'admin');
        $integratorFees = $method->invoke($this->reservationTransactionService, $profile, $amount, 'integrator');

        return [
            'total_amount' => $amount,
            'admin_share' => $adminFees['total_fees'],
            'integrator_share' => $integratorFees['total_fees'],
            'operator_share' => $amount - $adminFees['total_fees'] - $integratorFees['total_fees']
        ];
    }

    protected function applyHierarchicalWalletLogic(array $wallets, array $calculation, float $amount): void
    {
        $this->info("🔄 Application de la logique hiérarchique des wallets...");
        
        // 1. DÉBITER L'OPÉRATEUR DU MONTANT TOTAL
        $wallets['operator']->debit($calculation['total_amount'], "Paiement réservation test");
        
        // 2. LOGIQUE Admin → Intégrateur
        // L'Admin prend sa part, déduite de la balance de l'intégrateur
        $wallets['integrator']->debit($calculation['admin_share'], "Déduction part Admin");
        $wallets['admin']->credit($calculation['admin_share'], "Part Admin");
        
        // 3. LOGIQUE Intégrateur → Opérateur
        // L'intégrateur prend sa part, le reste va à l'opérateur
        $wallets['integrator']->credit($calculation['integrator_share'], "Part Intégrateur");
        $wallets['operator']->credit($calculation['operator_share'], "Part Opérateur");
    }

    protected function displayResults(array $wallets, array $calculation, float $amount): void
    {
        $this->newLine();
        $this->info("📊 RÉSULTATS DE LA LOGIQUE HIÉRARCHIQUE:");
        $this->newLine();

        // Afficher les calculs
        $this->line("💰 Calculs des parts:");
        $this->line("   - Montant total: {$calculation['total_amount']}€");
        $this->line("   - Part Admin: {$calculation['admin_share']}€");
        $this->line("   - Part Intégrateur: {$calculation['integrator_share']}€");
        $this->line("   - Part Opérateur: {$calculation['operator_share']}€");
        $this->newLine();

        // Afficher les soldes finaux
        $this->line("💳 Soldes des wallets après transaction:");
        foreach ($wallets as $role => $wallet) {
            $this->line("   - {$role}: {$wallet->balance}€");
        }
        $this->newLine();

        // Afficher la logique hiérarchique
        $this->line("🔄 Logique hiérarchique appliquée:");
        $this->line("   1. Admin → Intégrateur:");
        $this->line("      - Admin prend: {$calculation['admin_share']}€");
        $this->line("      - Intégrateur paye: {$calculation['admin_share']}€");
        $this->line("      - Intégrateur net: " . ($calculation['integrator_share'] - $calculation['admin_share']) . "€");
        $this->line("   2. Intégrateur → Opérateur:");
        $this->line("      - Intégrateur prend: {$calculation['integrator_share']}€");
        $this->line("      - Opérateur reçoit: {$calculation['operator_share']}€");
        $this->line("      - Opérateur net: " . ($calculation['operator_share'] - $calculation['total_amount']) . "€");
        $this->newLine();

        // Vérifier la cohérence
        $totalBalance = array_sum(array_column($wallets, 'balance'));
        $this->line("✅ Vérification de cohérence:");
        $this->line("   - Total des soldes: {$totalBalance}€");
        $this->line("   - Montant original: " . (1000 + 500 + 200) . "€");
        $this->line("   - Différence: " . ($totalBalance - (1000 + 500 + 200)) . "€");
    }
}
