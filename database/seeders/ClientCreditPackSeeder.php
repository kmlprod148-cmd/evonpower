<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CreditPack;

class ClientCreditPackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crée 10 packs de crédit pour les clients entre 50 et 5000 EUR
     */
    public function run(): void
    {
        $packs = [
            [
                'name' => 'Pack Essentiel',
                'description' => 'Pack de base pour commencer vos recharges',
                'amount' => 50.00,
                'price' => 50.00,
                'currency' => 'EUR',
                'order' => 1,
                'is_active' => true,
                'is_featured' => false,
                'is_client_only' => false,
                'color' => '#10b981',
            ],
            [
                'name' => 'Pack Confort',
                'description' => 'Idéal pour une utilisation régulière',
                'amount' => 100.00,
                'price' => 100.00,
                'currency' => 'EUR',
                'order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => false,
                'color' => '#3b82f6',
                'bonus' => ['type' => 'percentage', 'value' => 3], // 3% de bonus
            ],
            [
                'name' => 'Pack Avantage',
                'description' => 'Plus de crédit avec bonus',
                'amount' => 200.00,
                'price' => 200.00,
                'currency' => 'EUR',
                'order' => 3,
                'is_active' => true,
                'is_featured' => false,
                'is_client_only' => false,
                'color' => '#8b5cf6',
                'bonus' => ['type' => 'percentage', 'value' => 5], // 5% de bonus
            ],
            [
                'name' => 'Pack Premium',
                'description' => 'Pour les utilisateurs fréquents',
                'amount' => 300.00,
                'price' => 300.00,
                'currency' => 'EUR',
                'order' => 4,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => false,
                'color' => '#f59e0b',
                'bonus' => ['type' => 'percentage', 'value' => 7], // 7% de bonus
            ],
            [
                'name' => 'Pack Pro',
                'description' => 'Solution professionnelle avec bonus attractif',
                'amount' => 500.00,
                'price' => 500.00,
                'currency' => 'EUR',
                'order' => 5,
                'is_active' => true,
                'is_featured' => false,
                'is_client_only' => false,
                'color' => '#ef4444',
                'bonus' => ['type' => 'percentage', 'value' => 10], // 10% de bonus
            ],
            [
                'name' => 'Pack Business',
                'description' => 'Pour les entreprises et flottes',
                'amount' => 750.00,
                'price' => 750.00,
                'currency' => 'EUR',
                'order' => 6,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => false,
                'color' => '#6366f1',
                'bonus' => ['type' => 'percentage', 'value' => 12], // 12% de bonus
            ],
            [
                'name' => 'Pack Enterprise',
                'description' => 'Solution complète pour grandes entreprises',
                'amount' => 1000.00,
                'price' => 1000.00,
                'currency' => 'EUR',
                'order' => 7,
                'is_active' => true,
                'is_featured' => false,
                'is_client_only' => false,
                'color' => '#ec4899',
                'bonus' => ['type' => 'percentage', 'value' => 15], // 15% de bonus
            ],
            [
                'name' => 'Pack Excellence',
                'description' => 'Maximum de crédit avec bonus exceptionnel',
                'amount' => 2000.00,
                'price' => 2000.00,
                'currency' => 'EUR',
                'order' => 8,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => false,
                'color' => '#14b8a6',
                'bonus' => ['type' => 'percentage', 'value' => 18], // 18% de bonus
            ],
            [
                'name' => 'Pack VIP',
                'description' => 'Exclusif avec bonus maximum',
                'amount' => 3000.00,
                'price' => 3000.00,
                'currency' => 'EUR',
                'order' => 9,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => false,
                'color' => '#f97316',
                'bonus' => ['type' => 'percentage', 'value' => 20], // 20% de bonus
            ],
            [
                'name' => 'Pack Ultimate',
                'description' => 'Le pack ultime avec bonus exceptionnel',
                'amount' => 5000.00,
                'price' => 5000.00,
                'currency' => 'EUR',
                'order' => 10,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => false,
                'color' => '#dc2626',
                'bonus' => ['type' => 'percentage', 'value' => 25], // 25% de bonus
            ],
        ];

        foreach ($packs as $pack) {
            CreditPack::updateOrCreate(
                ['name' => $pack['name']],
                $pack
            );
        }

        // Afficher le message seulement si la commande est disponible
        if ($this->command) {
            $this->command->info('10 packs de crédit clients créés avec succès (50-5000 EUR)');
        }
    }
}

