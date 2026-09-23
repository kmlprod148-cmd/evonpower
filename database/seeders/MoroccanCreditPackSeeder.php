<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CreditPack;

/**
 * Seeder pour créer des packs de crédit entre 50 et 5000 EUR
 * Optimisé pour les paiements par cartes marocaines via CMI International
 */
class MoroccanCreditPackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packs = [
            [
                'name' => 'Pack Essentiel',
                'description' => 'Parfait pour commencer avec un crédit de base',
                'amount' => 50.00,
                'price' => 50.00,
                'currency' => 'EUR',
                'order' => 1,
                'is_active' => true,
                'is_featured' => false,
                'color' => '#10b981',
                'icon' => 'fas fa-bolt',
            ],
            [
                'name' => 'Pack Standard',
                'description' => 'Le choix populaire pour une utilisation régulière',
                'amount' => 100.00,
                'price' => 100.00,
                'currency' => 'EUR',
                'order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#3b82f6',
                'icon' => 'fas fa-star',
                'bonus' => ['type' => 'percentage', 'value' => 5], // 5% de bonus = 105 EUR
            ],
            [
                'name' => 'Pack Plus',
                'description' => 'Plus de crédit pour plus de liberté',
                'amount' => 200.00,
                'price' => 200.00,
                'currency' => 'EUR',
                'order' => 3,
                'is_active' => true,
                'is_featured' => false,
                'color' => '#8b5cf6',
                'icon' => 'fas fa-gem',
            ],
            [
                'name' => 'Pack Premium',
                'description' => 'Pour les utilisateurs fréquents avec bonus',
                'amount' => 300.00,
                'price' => 300.00,
                'currency' => 'EUR',
                'order' => 4,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#f59e0b',
                'icon' => 'fas fa-crown',
                'bonus' => ['type' => 'percentage', 'value' => 10], // 10% de bonus = 330 EUR
            ],
            [
                'name' => 'Pack Pro',
                'description' => 'Pour les professionnels et entreprises',
                'amount' => 500.00,
                'price' => 500.00,
                'currency' => 'EUR',
                'order' => 5,
                'is_active' => true,
                'is_featured' => false,
                'color' => '#ef4444',
                'icon' => 'fas fa-briefcase',
            ],
            [
                'name' => 'Pack Business',
                'description' => 'Solution complète pour les entreprises',
                'amount' => 750.00,
                'price' => 750.00,
                'currency' => 'EUR',
                'order' => 6,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#6366f1',
                'icon' => 'fas fa-building',
                'bonus' => ['type' => 'percentage', 'value' => 12], // 12% de bonus = 840 EUR
            ],
            [
                'name' => 'Pack Enterprise',
                'description' => 'Maximum de crédit avec bonus exceptionnel',
                'amount' => 1000.00,
                'price' => 1000.00,
                'currency' => 'EUR',
                'order' => 7,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#ec4899',
                'icon' => 'fas fa-rocket',
                'bonus' => ['type' => 'percentage', 'value' => 15], // 15% de bonus = 1150 EUR
            ],
            [
                'name' => 'Pack VIP',
                'description' => 'Exclusif avec bonus maximum',
                'amount' => 2000.00,
                'price' => 2000.00,
                'currency' => 'EUR',
                'order' => 8,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#14b8a6',
                'icon' => 'fas fa-trophy',
                'bonus' => ['type' => 'percentage', 'value' => 20], // 20% de bonus = 2400 EUR
            ],
            [
                'name' => 'Pack Ultimate',
                'description' => 'Le pack ultime avec bonus exceptionnel',
                'amount' => 3000.00,
                'price' => 3000.00,
                'currency' => 'EUR',
                'order' => 9,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#f97316',
                'icon' => 'fas fa-fire',
                'bonus' => ['type' => 'percentage', 'value' => 25], // 25% de bonus = 3750 EUR
            ],
            [
                'name' => 'Pack Maximum',
                'description' => 'Le pack maximum avec bonus premium',
                'amount' => 5000.00,
                'price' => 5000.00,
                'currency' => 'EUR',
                'order' => 10,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#dc2626',
                'icon' => 'fas fa-diamond',
                'bonus' => ['type' => 'percentage', 'value' => 30], // 30% de bonus = 6500 EUR
            ],
        ];

        foreach ($packs as $pack) {
            CreditPack::updateOrCreate(
                ['name' => $pack['name']],
                $pack
            );
        }

        $this->command->info('✅ Packs de crédit marocains créés avec succès (50-5000 EUR)');
    }
}

