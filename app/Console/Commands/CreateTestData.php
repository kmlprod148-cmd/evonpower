<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculator;
use App\Enums\TransactionStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateTestData extends Command
{
    protected $signature = 'test:create-data';
    protected $description = 'Créer des données de test pour diagnostiquer les répartitions';

    public function handle()
    {
        $this->info('🧪 Création de données de test...');
        
        try {
            DB::beginTransaction();
            
            // Utiliser un utilisateur existant ou en créer un
            $user = User::first();
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                    'balance' => 1000.00,
                    'currency' => 'EUR'
                ]);
                $this->info("✅ Utilisateur créé: {$user->name}");
            } else {
                $this->info("✅ Utilisateur existant: {$user->name}");
            }
            
            // Utiliser un profil business existant ou en créer un
            $businessProfile = BusinessProfile::first();
            if (!$businessProfile) {
                $businessProfile = BusinessProfile::create([
                    'transaction_fee' => 2.50,
                    'recharge_fee' => 1.00,
                    'other_fees' => 0.50,
                    'admin_percentage' => 5.0,
                    'integrator_percentage' => 15.0
                ]);
                $this->info("✅ Profil business créé");
            } else {
                $this->info("✅ Profil business existant");
            }
            
            // Créer un point de charge
            $chargingPoint = ChargingPoint::create([
                'name' => 'Test Charging Point',
                'location' => 'Test Location',
                'user_id' => $user->id,
                'business_profile_id' => $businessProfile->id,
                'status' => 'active'
            ]);
            $this->info("✅ Point de charge créé: {$chargingPoint->name}");
            
            // Créer une transaction
            $transaction = Transaction::create([
                'amount' => 145.00,
                'currency' => 'EUR',
                'status' => TransactionStatus::COMPLETED,
                'charging_point_id' => $chargingPoint->id,
                'user_id' => $user->id,
                'price_energy' => 100.00,
                'price_time' => 30.00,
                'price_tax' => 15.00,
                'price_total' => 145.00
            ]);
            $this->info("✅ Transaction créée: #{$transaction->id} - {$transaction->amount} EUR");
            
            // Tester le calculateur
            $this->info("🧮 Test du calculateur de répartition...");
            $calculator = new TransactionCalculator();
            $calculation = $calculator->calculate($transaction);
            
            $this->line("   - Admin: {$calculation['admin']} EUR");
            $this->line("   - Intégrateur: {$calculation['integrator']} EUR");
            $this->line("   - Opérateur: {$calculation['operator']} EUR");
            $this->line("   - Total: " . ($calculation['admin'] + $calculation['integrator'] + $calculation['operator']) . " EUR");
            
            // Créer la répartition
            $repartition = TransactionRepartition::create([
                'transaction_id' => $transaction->id,
                'admin_amount' => $calculation['admin'],
                'integrator_amount' => $calculation['integrator'],
                'operator_amount' => $calculation['operator'],
            ]);
            
            $this->info("✅ Répartition créée:");
            $this->line("   - Admin: {$repartition->admin_amount} EUR");
            $this->line("   - Intégrateur: {$repartition->integrator_amount} EUR");
            $this->line("   - Opérateur: {$repartition->operator_amount} EUR");
            $this->line("   - Total: {$repartition->getTotalAmount()} EUR");
            
            DB::commit();
            
            $this->info("🎯 Données de test créées avec succès !");
            $this->line("   - Transaction ID: {$transaction->id}");
            $this->line("   - Montant: {$transaction->amount} EUR");
            $this->line("   - Répartition: {$repartition->getTotalAmount()} EUR");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erreur: " . $e->getMessage());
            $this->line("Trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}