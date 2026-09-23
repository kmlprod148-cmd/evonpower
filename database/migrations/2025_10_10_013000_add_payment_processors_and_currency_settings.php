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
        // Ajouter les paramètres des processeurs de paiement
        DB::table('admin_settings')->insert([
            // CMI API Settings
            [
                'category' => 'payment_processors',
                'key' => 'cmi_api_key',
                'value' => '',
                'type' => 'password',
                'description' => 'Clé secrète fournie par CMI pour les paiements',
                'is_active' => true,
                'metadata' => json_encode([
                    'encrypted' => true,
                    'required' => true,
                    'validation' => 'required|string|min:10'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'payment_processors',
                'key' => 'cmi_merchant_id',
                'value' => '',
                'type' => 'text',
                'description' => 'Identifiant unique de votre compte marchand CMI',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|string|min:3'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'payment_processors',
                'key' => 'cmi_api_url',
                'value' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                'type' => 'url',
                'description' => 'URL de l\'endpoint de l\'API CMI',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|url',
                    'options' => [
                        'test' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                        'production' => 'https://payment.cmi.co.ma/fim/est3Dgate'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'payment_processors',
                'key' => 'cmi_environment',
                'value' => 'test',
                'type' => 'select',
                'description' => 'Choisissez l\'environnement CMI',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|in:test,production',
                    'options' => [
                        'test' => 'Test',
                        'production' => 'Production'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'payment_processors',
                'key' => 'cmi_currency',
                'value' => 'EUR',
                'type' => 'select',
                'description' => 'Devise utilisée pour les transactions CMI',
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
                'category' => 'payment_processors',
                'key' => 'cmi_language',
                'value' => 'fr',
                'type' => 'select',
                'description' => 'Langue par défaut pour l\'interface de paiement CMI',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|in:fr,en,ar',
                    'options' => [
                        'fr' => 'Français',
                        'en' => 'English',
                        'ar' => 'العربية'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Stripe API Settings
            [
                'category' => 'payment_processors',
                'key' => 'stripe_secret_key',
                'value' => '',
                'type' => 'password',
                'description' => 'Votre clé secrète Stripe (commence par sk_live_ ou sk_test_)',
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
                'category' => 'payment_processors',
                'key' => 'stripe_public_key',
                'value' => '',
                'type' => 'text',
                'description' => 'Votre clé publique Stripe (commence par pk_live_ ou pk_test_)',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|string|min:20'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'payment_processors',
                'key' => 'stripe_webhook_secret',
                'value' => '',
                'type' => 'password',
                'description' => 'La clé secrète de signature de votre webhook Stripe',
                'is_active' => true,
                'metadata' => json_encode([
                    'encrypted' => true,
                    'required' => false,
                    'validation' => 'nullable|string|min:10'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'payment_processors',
                'key' => 'stripe_environment',
                'value' => 'test',
                'type' => 'select',
                'description' => 'Choisissez l\'environnement Stripe',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|in:test,production',
                    'options' => [
                        'test' => 'Test',
                        'production' => 'Production'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'payment_processors',
                'key' => 'stripe_currency',
                'value' => 'EUR',
                'type' => 'select',
                'description' => 'Devise utilisée pour les transactions Stripe',
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
        ]);

        // Ajouter les paramètres de devise
        DB::table('admin_settings')->insert([
            [
                'category' => 'currency',
                'key' => 'app_default_currency',
                'value' => 'EUR',
                'type' => 'select',
                'description' => 'Devise par défaut pour toutes les transactions et affichages',
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
                'category' => 'currency',
                'key' => 'app_currency_symbol',
                'value' => '€',
                'type' => 'text',
                'description' => 'Symbole affiché pour la devise par défaut',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|string|max:5'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'currency',
                'key' => 'app_currency_format',
                'value' => '2',
                'type' => 'select',
                'description' => 'Nombre de décimales à afficher pour les montants',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|in:0,1,2',
                    'options' => [
                        '0' => 'Pas de décimales (ex: 100)',
                        '1' => '1 décimale (ex: 100.0)',
                        '2' => '2 décimales (ex: 100.00)'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les paramètres des processeurs de paiement
        DB::table('admin_settings')
            ->where('category', 'payment_processors')
            ->delete();

        // Supprimer les paramètres de devise
        DB::table('admin_settings')
            ->where('category', 'currency')
            ->delete();
    }
};
