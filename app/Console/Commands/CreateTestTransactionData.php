<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Integrator;

class CreateTestTransactionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-transaction-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée des données de test pour tester les calculs de répartition';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📝 Création des données de test pour les transactions...');
        $this->newLine();

        // Créer un business profile avec des frais configurés
        $businessProfile = BusinessProfile::create([
            'name' => 'Business Profile Test',
            'description' => 'Business profile pour tester les calculs de répartition',
            'is_active' => true,
            'transaction_fee' => 2.50,
            'recharge_fee' => 1.00,
            'active_terminal_fee' => 0.50,
            'admin_fee_fixed' => 5.00,
            'admin_fee_percentage' => 10.0,
            'integrator_fee_fixed' => 3.00,
            'integrator_fee_percentage' => 15.0,
        ]);

        $this->info("✅ Business Profile créé avec ID: {$businessProfile->id}");

        // Créer un business profile pour l'intégrateur
        $integratorBusinessProfile = BusinessProfile::create([
            'name' => 'Business Profile Intégrateur Test',
            'description' => 'Business profile pour l\'intégrateur',
            'is_active' => true,
            'integrator_fee_fixed' => 2.00,
            'integrator_fee_percentage' => 20.0,
        ]);

        $this->info("✅ Business Profile Intégrateur créé avec ID: {$integratorBusinessProfile->id}");

        // Créer un utilisateur intégrateur
        $integratorUser = User::create([
            'name' => 'Intégrateur Test',
            'email' => 'integrator@test.com',
            'password' => bcrypt('password'),
        ]);
        $integratorUser->assignRole('integrator');

        // Créer un intégrateur
        $integrator = Integrator::create([
            'user_id' => $integratorUser->id,
            'business_profile_id' => $integratorBusinessProfile->id,
            'name' => 'Intégrateur Test',
            'email' => 'integrator@test.com',
        ]);

        $this->info("✅ Intégrateur créé avec ID: {$integrator->id}");

        // Créer un point de charge
        $chargingPoint = ChargingPoint::create([
            'name' => 'Point de charge test',
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => $integrator->id,
            'status' => 'active',
            'location' => 'Test Location',
        ]);

        $this->info("✅ Point de charge créé avec ID: {$chargingPoint->id}");

        // Créer quelques transactions de test
        $transactions = [
            ['amount' => 10.00, 'status' => 'completed'],
            ['amount' => 25.50, 'status' => 'completed'],
            ['amount' => 5.00, 'status' => 'completed'],
            ['amount' => 50.00, 'status' => 'completed'],
        ];

        foreach ($transactions as $index => $transactionData) {
            $transaction = Transaction::create([
                'charging_point_id' => $chargingPoint->id,
                'amount' => $transactionData['amount'],
                'status' => $transactionData['status'],
                'start_timestamp' => now()->subHours($index + 1),
                'stop_timestamp' => now()->subHours($index),
                'price_total' => $transactionData['amount'],
                'currency' => 'EUR',
            ]);

            $this->info("✅ Transaction créée avec ID: {$transaction->id}, Montant: {$transaction->amount} €");
        }

        $this->newLine();
        $this->info('🎉 Données de test créées avec succès !');
        $this->info('Vous pouvez maintenant tester le calcul de répartition avec ces données.');
        $this->newLine();
        $this->info('Commandes utiles:');
        $this->info('  - php artisan test:transaction-calculator (tester le service)');
        $this->info('  - php artisan transactions:recalculate-repartitions (recalculer les répartitions)');

        return 0;
    }
}
