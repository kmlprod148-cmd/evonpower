<?php

namespace Database\Seeders;

use App\Models\BillingCycle;
use App\Models\BillingPlan;
use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les cycles de facturation de base
        $cycles = [
            [
                'name' => 'Facturation Quotidienne',
                'description' => 'Facturation automatique quotidienne',
                'frequency' => 'daily',
                'interval' => 1,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1
            ],
            [
                'name' => 'Facturation Hebdomadaire',
                'description' => 'Facturation automatique hebdomadaire',
                'frequency' => 'weekly',
                'interval' => 1,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1
            ],
            [
                'name' => 'Facturation Mensuelle',
                'description' => 'Facturation automatique mensuelle',
                'frequency' => 'monthly',
                'interval' => 1,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1
            ],
            [
                'name' => 'Facturation Trimestrielle',
                'description' => 'Facturation automatique trimestrielle',
                'frequency' => 'monthly',
                'interval' => 3,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1
            ],
            [
                'name' => 'Facturation Annuelle',
                'description' => 'Facturation automatique annuelle',
                'frequency' => 'yearly',
                'interval' => 1,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1
            ]
        ];

        foreach ($cycles as $cycleData) {
            BillingCycle::create($cycleData);
        }

        $this->command->info('✅ Cycles de facturation créés');

        // Créer des plans de facturation d'exemple
        $dailyCycle = BillingCycle::where('frequency', 'daily')->first();
        $monthlyCycle = BillingCycle::where('frequency', 'monthly')->where('interval', 1)->first();

        // Récupérer un utilisateur et un profil d'entreprise pour les exemples
        $user = User::first();
        $businessProfile = BusinessProfile::first();

        if ($user && $dailyCycle) {
            BillingPlan::create([
                'name' => 'Abonnement Utilisateur Quotidien',
                'description' => 'Abonnement quotidien pour utilisateur',
                'billing_cycle_id' => $dailyCycle->id,
                'billable_type' => 'App\Models\User',
                'billable_id' => $user->id,
                'amount' => 5.00,
                'currency' => 'EUR',
                'tax_rate' => 20.0,
                'is_active' => true,
                'start_date' => now(),
                'end_date' => null,
                'auto_renew' => true,
                'created_by' => 1,
                'updated_by' => 1
            ]);
        }

        if ($businessProfile && $monthlyCycle) {
            BillingPlan::create([
                'name' => 'Abonnement Profil d\'Entreprise Mensuel',
                'description' => 'Abonnement mensuel pour profil d\'entreprise',
                'billing_cycle_id' => $monthlyCycle->id,
                'billable_type' => 'App\Models\BusinessProfile',
                'billable_id' => $businessProfile->id,
                'amount' => 100.00,
                'currency' => 'EUR',
                'tax_rate' => 20.0,
                'is_active' => true,
                'start_date' => now(),
                'end_date' => null,
                'auto_renew' => true,
                'created_by' => 1,
                'updated_by' => 1
            ]);
        }

        $this->command->info('✅ Plans de facturation créés');
        $this->command->info('🎉 Système de facturation initialisé avec succès !');
    }
}
