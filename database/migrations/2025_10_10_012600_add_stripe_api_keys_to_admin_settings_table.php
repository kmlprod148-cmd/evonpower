<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter les paramètres Stripe API par défaut
        DB::table('admin_settings')->insert([
            [
                'category' => 'external_apis',
                'key' => 'stripe_publishable_key',
                'value' => '',
                'type' => 'text',
                'description' => 'Clé publique Stripe pour le frontend',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|string|min:20',
                    'encrypted' => false
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'external_apis',
                'key' => 'stripe_secret_key',
                'value' => '',
                'type' => 'password',
                'description' => 'Clé secrète Stripe pour le backend',
                'is_active' => true,
                'metadata' => json_encode([
                    'encrypted' => true,
                    'required' => true,
                    'validation' => 'required|string|min:20'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'external_apis',
                'key' => 'stripe_webhook_secret',
                'value' => '',
                'type' => 'password',
                'description' => 'Secret webhook Stripe pour la validation',
                'is_active' => true,
                'metadata' => json_encode([
                    'encrypted' => true,
                    'required' => false,
                    'validation' => 'nullable|string|min:20'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'external_apis',
                'key' => 'stripe_environment',
                'value' => 'test',
                'type' => 'select',
                'description' => 'Environnement Stripe (test/live)',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|in:test,live',
                    'options' => [
                        'test' => 'Test',
                        'live' => 'Production'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'external_apis',
                'key' => 'stripe_currency',
                'value' => 'EUR',
                'type' => 'select',
                'description' => 'Devise pour les paiements Stripe',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|in:EUR,USD',
                    'options' => [
                        'EUR' => 'Euro',
                        'USD' => 'Dollar américain'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'external_apis',
                'key' => 'stripe_webhook_url',
                'value' => '',
                'type' => 'url',
                'description' => 'URL du webhook Stripe',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => false,
                    'validation' => 'nullable|url',
                    'default' => 'https://yourdomain.com/webhooks/stripe'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Ajouter les paramètres de devise de l'application
        DB::table('admin_settings')->insert([
            [
                'category' => 'system',
                'key' => 'app_default_currency',
                'value' => 'EUR',
                'type' => 'select',
                'description' => 'Devise par défaut de l\'application',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|in:EUR,USD',
                    'options' => [
                        'EUR' => 'Euro (EUR)',
                        'USD' => 'Dollar américain (USD)'
                    ],
                    'affects' => ['transactions', 'pricing_plans', 'users', 'balance']
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'system',
                'key' => 'app_currency_symbol',
                'value' => 'EUR',
                'type' => 'text',
                'description' => 'Symbole de la devise de l\'application',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|string|max:5',
                    'auto_generated' => true
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'system',
                'key' => 'app_currency_format',
                'value' => '2',
                'type' => 'number',
                'description' => 'Nombre de décimales pour la devise',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|integer|min:0|max:4',
                    'default' => 2
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les paramètres Stripe API
        DB::table('admin_settings')
            ->where('category', 'external_apis')
            ->whereIn('key', [
                'stripe_publishable_key',
                'stripe_secret_key',
                'stripe_webhook_secret',
                'stripe_environment',
                'stripe_currency',
                'stripe_webhook_url'
            ])
            ->delete();

        // Supprimer les paramètres de devise
        DB::table('admin_settings')
            ->where('category', 'system')
            ->whereIn('key', [
                'app_default_currency',
                'app_currency_symbol',
                'app_currency_format'
            ])
            ->delete();
    }
};