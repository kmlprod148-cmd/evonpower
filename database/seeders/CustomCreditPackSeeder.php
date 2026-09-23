<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CreditPack;

class CustomCreditPackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crée les packs de crédit personnalisés réservés aux clients
     * qui ont fait des réservations (100, 200, 500, 1000, 2500, 5000, 10000)
     */
    public function run(): void
    {
        $customPacks = [
            [
                'name' => 'Pack Client 100',
                'description' => 'Pack réservé aux clients actifs - 100 EUR',
                'amount' => 100.00,
                'price' => 100.00,
                'currency' => 'EUR',
                'order' => 100,
                'is_active' => true,
                'is_featured' => false,
                'is_client_only' => true,
                'color' => '#10b981',
            ],
            [
                'name' => 'Pack Client 200',
                'description' => 'Pack réservé aux clients actifs - 200 EUR',
                'amount' => 200.00,
                'price' => 200.00,
                'currency' => 'EUR',
                'order' => 101,
                'is_active' => true,
                'is_featured' => false,
                'is_client_only' => true,
                'color' => '#3b82f6',
            ],
            [
                'name' => 'Pack Client 500',
                'description' => 'Pack réservé aux clients actifs - 500 EUR',
                'amount' => 500.00,
                'price' => 500.00,
                'currency' => 'EUR',
                'order' => 102,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => true,
                'color' => '#8b5cf6',
            ],
            [
                'name' => 'Pack Client 1000',
                'description' => 'Pack réservé aux clients actifs - 1000 EUR',
                'amount' => 1000.00,
                'price' => 1000.00,
                'currency' => 'EUR',
                'order' => 103,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => true,
                'color' => '#f59e0b',
            ],
            [
                'name' => 'Pack Client 2500',
                'description' => 'Pack réservé aux clients actifs - 2500 EUR',
                'amount' => 2500.00,
                'price' => 2500.00,
                'currency' => 'EUR',
                'order' => 104,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => true,
                'color' => '#ef4444',
            ],
            [
                'name' => 'Pack Client 5000',
                'description' => 'Pack réservé aux clients actifs - 5000 EUR',
                'amount' => 5000.00,
                'price' => 5000.00,
                'currency' => 'EUR',
                'order' => 105,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => true,
                'color' => '#6366f1',
            ],
            [
                'name' => 'Pack Client 10000',
                'description' => 'Pack réservé aux clients actifs - 10000 EUR',
                'amount' => 10000.00,
                'price' => 10000.00,
                'currency' => 'EUR',
                'order' => 106,
                'is_active' => true,
                'is_featured' => true,
                'is_client_only' => true,
                'color' => '#ec4899',
            ],
        ];

        foreach ($customPacks as $pack) {
            CreditPack::updateOrCreate(
                ['name' => $pack['name']],
                $pack
            );
        }

        $this->command->info('Packs de crédit personnalisés créés avec succès !');
    }
}

