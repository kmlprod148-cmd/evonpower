<?php

namespace App\Console\Commands;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSimpleHierarchicalLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:simple-hierarchical-logic {--amount=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test simple de la logique hiérarchique des wallets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amount = (float) $this->option('amount');
        
        $this->info("🧪 Test simple de la logique hiérarchique pour {$amount}€");
        $this->newLine();

        try {
            DB::beginTransaction();

            // 1. Créer les utilisateurs
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

            // 2. Créer les wallets
            $adminWallet = Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $admin->id,
                'balance' => 1000.00,
                'currency' => 'EUR',
                'is_active' => true,
                'name' => 'Admin Wallet'
            ]);

            $integratorWallet = Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $integrator->id,
                'balance' => 500.00,
                'currency' => 'EUR',
                'is_active' => true,
                'name' => 'Integrator Wallet'
            ]);

            $operatorWallet = Wallet::create([
                'owner_type' => User::class,
                'owner_id' => $operator->id,
                'balance' => 200.00,
                'currency' => 'EUR',
                'is_active' => true,
                'name' => 'Operator Wallet'
            ]);

            // 3. Calculer les parts (simulation)
            $adminShare = 15.5; // Frais admin calculés
            $integratorShare = 9.5; // Frais intégrateur calculés
            $operatorShare = $amount - $adminShare - $integratorShare; // 75€

            $this->info("💰 Calculs des parts:");
            $this->line("   - Montant total: {$amount}€");
            $this->line("   - Part Admin: {$adminShare}€");
            $this->line("   - Part Intégrateur: {$integratorShare}€");
            $this->line("   - Part Opérateur: {$operatorShare}€");
            $this->newLine();

            // 4. Appliquer la logique hiérarchique
            $this->info("🔄 Application de la logique hiérarchique:");
            
            // Étape 1: Débiter l'opérateur du montant total
            $operatorWallet->debit($amount, "Paiement réservation test");
            $this->line("   1. Opérateur débité de {$amount}€");
            
            // Étape 2: Admin → Intégrateur
            // L'Admin prend sa part, déduite de la balance de l'intégrateur
            $integratorWallet->debit($adminShare, "Déduction part Admin");
            $adminWallet->credit($adminShare, "Part Admin");
            $this->line("   2. Admin prend {$adminShare}€ (déduit de l'intégrateur)");
            
            // Étape 3: Intégrateur → Opérateur
            // L'intégrateur prend sa part, le reste va à l'opérateur
            $integratorWallet->credit($integratorShare, "Part Intégrateur");
            $operatorWallet->credit($operatorShare, "Part Opérateur");
            $this->line("   3. Intégrateur prend {$integratorShare}€, Opérateur reçoit {$operatorShare}€");

            // 5. Afficher les résultats
            $this->newLine();
            $this->info("📊 RÉSULTATS FINAUX:");
            $this->line("   - Admin: {$adminWallet->balance}€");
            $this->line("   - Intégrateur: {$integratorWallet->balance}€");
            $this->line("   - Opérateur: {$operatorWallet->balance}€");
            $this->newLine();

            // 6. Calculer les balances nettes
            $integratorNet = $integratorShare - $adminShare; // 9.5 - 15.5 = -6€
            $operatorNet = $operatorShare - $amount; // 75 - 100 = -25€
            
            $this->info("💡 Analyse des balances nettes:");
            $this->line("   - Intégrateur net: {$integratorNet}€ (reçoit {$integratorShare}€, paye {$adminShare}€)");
            $this->line("   - Opérateur net: {$operatorNet}€ (reçoit {$operatorShare}€, paye {$amount}€)");
            $this->line("   - Admin net: +{$adminShare}€ (reçoit sa part)");

            DB::rollBack();
            
            $this->newLine();
            $this->info("✅ Test terminé avec succès!");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
