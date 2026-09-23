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
        // Ajouter les paramètres CMI API par défaut
        DB::table('admin_settings')->insert([
            [
                'category' => 'external_apis',
                'key' => 'cmi_api_key',
                'value' => '',
                'type' => 'password',
                'description' => 'Clé API CMI pour les paiements',
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
                'category' => 'external_apis',
                'key' => 'cmi_merchant_id',
                'value' => '',
                'type' => 'text',
                'description' => 'ID du marchand CMI',
                'is_active' => true,
                'metadata' => json_encode([
                    'required' => true,
                    'validation' => 'required|string|min:3'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category' => 'external_apis',
                'key' => 'cmi_api_url',
                'value' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                'type' => 'url',
                'description' => 'URL de l\'API CMI',
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
                'category' => 'external_apis',
                'key' => 'cmi_environment',
                'value' => 'test',
                'type' => 'select',
                'description' => 'Environnement CMI (test/production)',
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
                'category' => 'external_apis',
                'key' => 'cmi_currency',
                'value' => 'EUR',
                'type' => 'select',
                'description' => 'Devise pour les paiements CMI',
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
                'key' => 'cmi_language',
                'value' => 'fr',
                'type' => 'select',
                'description' => 'Langue de l\'interface CMI',
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
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les paramètres CMI API
        DB::table('admin_settings')
            ->where('category', 'external_apis')
            ->whereIn('key', [
                'cmi_api_key',
                'cmi_merchant_id', 
                'cmi_api_url',
                'cmi_environment',
                'cmi_currency',
                'cmi_language'
            ])
            ->delete();
    }
};
