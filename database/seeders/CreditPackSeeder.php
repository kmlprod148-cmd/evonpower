<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CreditPack;

class CreditPackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packs = [
            [
                'name' => 'Pack Starter',
                'description' => 'Parfait pour débuter avec un petit crédit',
                'amount' => 10.00,
                'price' => 10.00,
                'currency' => 'EUR',
                'order' => 1,
                'is_active' => true,
                'is_featured' => false,
                'color' => '#10b981',
            ],
            [
                'name' => 'Pack Basic',
                'description' => 'Idéal pour une utilisation occasionnelle',
                'amount' => 25.00,
                'price' => 25.00,
                'currency' => 'EUR',
                'order' => 2,
                'is_active' => true,
                'is_featured' => false,
                'color' => '#3b82f6',
            ],
            [
                'name' => 'Pack Standard',
                'description' => 'Le choix populaire pour une utilisation régulière',
                'amount' => 50.00,
                'price' => 50.00,
                'currency' => 'EUR',
                'order' => 3,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#8b5cf6',
                'bonus' => ['type' => 'percentage', 'value' => 5], // 5% de bonus
            ],
            [
                'name' => 'Pack Plus',
                'description' => 'Plus de crédit pour plus de liberté',
                'amount' => 100.00,
                'price' => 100.00,
                'currency' => 'EUR',
                'order' => 4,
                'is_active' => true,
                'is_featured' => false,
                'color' => '#f59e0b',
            ],
            [
                'name' => 'Pack Premium',
                'description' => 'Pour les utilisateurs fréquents avec bonus',
                'amount' => 200.00,
                'price' => 200.00,
                'currency' => 'EUR',
                'order' => 5,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#ef4444',
                'bonus' => ['type' => 'percentage', 'value' => 10], // 10% de bonus
            ],
            [
                'name' => 'Pack Pro',
                'description' => 'Pour les professionnels et entreprises',
                'amount' => 500.00,
                'price' => 500.00,
                'currency' => 'EUR',
                'order' => 6,
                'is_active' => true,
                'is_featured' => false,
                'color' => '#6366f1',
            ],
            [
                'name' => 'Pack Business',
                'description' => 'Solution complète pour les entreprises',
                'amount' => 1000.00,
                'price' => 1000.00,
                'currency' => 'EUR',
                'order' => 7,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#ec4899',
                'bonus' => ['type' => 'percentage', 'value' => 15], // 15% de bonus
            ],
            [
                'name' => 'Pack Enterprise',
                'description' => 'Maximum de crédit avec bonus exceptionnel',
                'amount' => 2000.00,
                'price' => 2000.00,
                'currency' => 'EUR',
                'order' => 8,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#14b8a6',
                'bonus' => ['type' => 'percentage', 'value' => 20], // 20% de bonus
            ],
            [
                'name' => 'Pack VIP',
                'description' => 'Exclusif avec bonus maximum',
                'amount' => 5000.00,
                'price' => 5000.00,
                'currency' => 'EUR',
                'order' => 9,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#f97316',
                'bonus' => ['type' => 'percentage', 'value' => 25], // 25% de bonus
            ],
            [
                'name' => 'Pack Ultimate',
                'description' => 'Le pack ultime avec bonus exceptionnel',
                'amount' => 10000.00,
                'price' => 10000.00,
                'currency' => 'EUR',
                'order' => 10,
                'is_active' => true,
                'is_featured' => true,
                'color' => '#dc2626',
                'bonus' => ['type' => 'percentage', 'value' => 30], // 30% de bonus
            ],
        ];

        foreach ($packs as $pack) {
            CreditPack::updateOrCreate(
                ['name' => $pack['name']],
                $pack
            );
        }
    }
}

