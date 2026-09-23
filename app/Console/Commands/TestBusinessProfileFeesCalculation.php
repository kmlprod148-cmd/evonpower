<?php

namespace App\Console\Commands;

use App\Models\BusinessProfile;
use App\Services\ReservationTransactionService;
use Illuminate\Console\Command;

class TestBusinessProfileFeesCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:business-profile-fees {--amount=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester le calcul des frais des Business Profiles';

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
        
        $this->info("🧪 Test du calcul des frais Business Profiles pour un montant de {$amount}€");
        $this->newLine();

        try {
            // Récupérer ou créer un Business Profile
            $businessProfile = BusinessProfile::where('is_active', true)->first();
            
            if (!$businessProfile) {
                $this->info('📝 Création d\'un Business Profile de test...');
                $businessProfile = BusinessProfile::create([
                    'name' => 'Test Business Profile',
                    'description' => 'Profil de test pour validation des frais',
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
                $this->info('✅ Business Profile de test créé');
            }

            $this->info("📋 Business Profile utilisé: {$businessProfile->name}");
            $this->line("   - ID: {$businessProfile->id}");
            $this->line("   - Créé par: {$businessProfile->created_by_id} ({$businessProfile->created_by_type})");
            $this->newLine();

            // Tester le calcul des frais Admin
            $this->info("💰 Test des frais Admin:");
            $adminFees = $this->testFeesCalculation($businessProfile, $amount, 'admin');
            $this->displayFeesBreakdown('Admin', $adminFees);

            $this->newLine();

            // Tester le calcul des frais Intégrateur
            $this->info("💰 Test des frais Intégrateur:");
            $integratorFees = $this->testFeesCalculation($businessProfile, $amount, 'integrator');
            $this->displayFeesBreakdown('Intégrateur', $integratorFees);

            $this->newLine();

            // Résumé
            $this->info("📊 Résumé des calculs:");
            $this->line("   - Montant total: {$amount}€");
            $this->line("   - Frais Admin: {$adminFees['total_fees']}€");
            $this->line("   - Frais Intégrateur: {$integratorFees['total_fees']}€");
            $this->line("   - Total des frais: " . ($adminFees['total_fees'] + $integratorFees['total_fees']) . "€");
            $this->line("   - Montant restant: " . ($amount - $adminFees['total_fees'] - $integratorFees['total_fees']) . "€");

            $this->newLine();
            $this->info("✅ Test terminé avec succès!");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du test: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function testFeesCalculation(BusinessProfile $profile, float $amount, string $role): array
    {
        // Utiliser la méthode privée via reflection
        $reflection = new \ReflectionClass($this->reservationTransactionService);
        $method = $reflection->getMethod('calculateBusinessProfileFees');
        $method->setAccessible(true);

        return $method->invoke($this->reservationTransactionService, $profile, $amount, $role);
    }

    protected function displayFeesBreakdown(string $role, array $fees): void
    {
        $this->line("   - Frais d'activation: {$fees['activation_fee']}€");
        $this->line("   - Frais de transaction: {$fees['transaction_fee']}€");
        $this->line("   - Frais de recharge: {$fees['charge_fee']}€");
        $this->line("   - Frais spécifiques {$role}: {$fees['role_fee']}€");
        $this->line("   - TOTAL: {$fees['total_fees']}€");
        
        if (!empty($fees['breakdown'])) {
            $this->line("   - Détail des calculs:");
            foreach ($fees['breakdown'] as $type => $breakdown) {
                if (isset($breakdown['formula'])) {
                    $this->line("     * {$type}: {$breakdown['formula']} = {$breakdown['calculated']}€");
                }
            }
        }
    }
}
