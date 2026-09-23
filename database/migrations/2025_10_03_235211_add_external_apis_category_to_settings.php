<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier et ajouter la colonne category si elle n'existe pas
        if (!Schema::hasColumn('settings', 'category')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('category')->default('general')->after('key');
            });
        }

        // Vérifier et ajouter la colonne is_active si elle n'existe pas
        if (!Schema::hasColumn('settings', 'is_active')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('value');
            });
        }

        // Vérifier et ajouter la colonne description si elle n'existe pas
        if (!Schema::hasColumn('settings', 'description')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->text('description')->nullable()->after('type');
            });
        }

        // Insérer les clés API par défaut si elles n'existent pas
        $this->insertDefaultApiKeys();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les clés API externes
        DB::table('settings')
            ->where('category', 'external_apis')
            ->delete();

        // Optionnel: supprimer les colonnes ajoutées
        // Schema::table('settings', function (Blueprint $table) {
        //     $table->dropColumn(['category', 'is_active']);
        // });
    }

    /**
     * Insère les clés API par défaut
     */
    private function insertDefaultApiKeys(): void
    {
        $defaultApiKeys = [
            'currency_api_key' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Clé API pour récupérer les taux de change'
            ],
            'currency_api_provider' => [
                'value' => 'fixer',
                'type' => 'select',
                'description' => 'Fournisseur de taux de change'
            ],
            'currency_api_base_url' => [
                'value' => 'https://api.fixer.io',
                'type' => 'url',
                'description' => 'URL de base de l\'API des devises'
            ],
            'stripe_public_key' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Clé publique Stripe'
            ],
            'stripe_secret_key' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Clé secrète Stripe'
            ],
            'stripe_webhook_secret' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Secret webhook Stripe'
            ],
            'cmi_store_key' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Clé de magasin CMI'
            ],
            'cmi_client_id' => [
                'value' => '',
                'type' => 'password',
                'description' => 'ID client CMI'
            ],
            'cmi_username' => [
                'value' => '',
                'type' => 'text',
                'description' => 'Nom d\'utilisateur CMI'
            ],
            'cmi_password' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Mot de passe CMI'
            ],
            'paypal_client_id' => [
                'value' => '',
                'type' => 'password',
                'description' => 'ID client PayPal'
            ],
            'paypal_secret' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Secret PayPal'
            ],
            'aws_access_key_id' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Clé d\'accès AWS'
            ],
            'aws_secret_access_key' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Clé secrète AWS'
            ],
            'aws_region' => [
                'value' => 'eu-west-1',
                'type' => 'text',
                'description' => 'Région AWS'
            ],
            'sentry_dsn' => [
                'value' => '',
                'type' => 'password',
                'description' => 'DSN Sentry'
            ],
            'google_maps_api_key' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Clé API Google Maps'
            ],
            'twilio_account_sid' => [
                'value' => '',
                'type' => 'password',
                'description' => 'SID compte Twilio'
            ],
            'twilio_auth_token' => [
                'value' => '',
                'type' => 'password',
                'description' => 'Token d\'authentification Twilio'
            ]
        ];

        foreach ($defaultApiKeys as $key => $config) {
            DB::table('settings')->updateOrInsert(
                [
                    'key' => $key,
                    'category' => 'external_apis'
                ],
                [
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description' => $config['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
};