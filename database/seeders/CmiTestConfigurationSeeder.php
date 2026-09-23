<?php

namespace Database\Seeders;

use App\Models\AdminSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Seeder pour configurer les paramètres CMI de test
 * 
 * Environnement de test CMI:
 * - ClientId: 600002823
 * - URL Back Office: https://testpayment.cmi.co.ma/cmi/report
 * - Utilisateur: bd_a / Bd_a2021
 * 
 * IMPORTANT: La clé de hachage (storekey) doit être configurée manuellement
 * via le back office CMI: Administration -> Changer les clés du magasin
 * 
 * Cartes de test:
 * - Visa (non-authentifiable): 4000000000000010, Exp: 12/XX, CVS: 000
 * - MasterCard (authentifiable): 5191630100004896, Exp: 12/XX, CVS: 000, Code: 123
 * - MasterCard (non participante): 5453010000066100, Exp: 12/XX, CVS: 000
 */
class CmiTestConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Configuration des paramètres CMI de test...');

        // Paramètres CMI Test
        $testSettings = [
            'cmi_test_merchant_id' => [
                'value' => '600002823',
                'description' => 'Identifiant marchand CMI (test)',
                'type' => 'text',
            ],
            'cmi_test_api_key' => [
                'value' => '', // La clé doit être configurée via le back office CMI
                'description' => 'Clé de hachage CMI (test) - À configurer via le back office',
                'type' => 'password',
                'encrypt' => true,
            ],
            'cmi_test_api_url' => [
                'value' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                'description' => 'URL API de paiement CMI (test)',
                'type' => 'url',
            ],
            'cmi_test_callback_url' => [
                'value' => env('APP_URL', 'https://devcharge.evonpower.com'),
                'description' => 'URL de callback pour les notifications CMI (test)',
                'type' => 'url',
            ],
        ];

        // Paramètres CMI Production (vides par défaut)
        $prodSettings = [
            'cmi_prod_merchant_id' => [
                'value' => '',
                'description' => 'Identifiant marchand CMI (production)',
                'type' => 'text',
            ],
            'cmi_prod_api_key' => [
                'value' => '',
                'description' => 'Clé de hachage CMI (production)',
                'type' => 'password',
                'encrypt' => true,
            ],
            'cmi_prod_api_url' => [
                'value' => 'https://payment.cmi.co.ma/fim/est3Dgate',
                'description' => 'URL API de paiement CMI (production)',
                'type' => 'url',
            ],
            'cmi_prod_callback_url' => [
                'value' => env('APP_URL', 'https://devcharge.evonpower.com'),
                'description' => 'URL de callback pour les notifications CMI (production)',
                'type' => 'url',
            ],
        ];

        // Paramètres généraux CMI
        $generalSettings = [
            'cmi_environment' => [
                'value' => 'test',
                'description' => 'Environnement CMI actif (test ou prod)',
                'type' => 'select',
            ],
            'cmi_default_currency' => [
                'value' => 'MAD',
                'description' => 'Devise par défaut pour les paiements CMI',
                'type' => 'select',
            ],
            'cmi_default_language' => [
                'value' => 'fr',
                'description' => 'Langue par défaut pour l\'interface CMI',
                'type' => 'select',
            ],
            'cmi_enabled' => [
                'value' => 'true',
                'description' => 'Activer les paiements CMI',
                'type' => 'boolean',
            ],
        ];

        // Insérer les paramètres de test
        foreach ($testSettings as $key => $setting) {
            $value = $setting['value'];
            
            // Crypter les valeurs sensibles si nécessaire
            if (!empty($value) && ($setting['encrypt'] ?? false)) {
                $value = Crypt::encryptString($value);
            }

            AdminSetting::updateOrCreate(
                [
                    'category' => 'external_apis',
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'description' => $setting['description'],
                    'type' => $setting['type'],
                    'is_active' => true,
                ]
            );
            
            $this->command->info("  ✓ {$key} configuré");
        }

        // Insérer les paramètres de production
        foreach ($prodSettings as $key => $setting) {
            $value = $setting['value'];
            
            if (!empty($value) && ($setting['encrypt'] ?? false)) {
                $value = Crypt::encryptString($value);
            }

            AdminSetting::updateOrCreate(
                [
                    'category' => 'external_apis',
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'description' => $setting['description'],
                    'type' => $setting['type'],
                    'is_active' => true,
                ]
            );
            
            $this->command->info("  ✓ {$key} configuré");
        }

        // Insérer les paramètres généraux
        foreach ($generalSettings as $key => $setting) {
            AdminSetting::updateOrCreate(
                [
                    'category' => 'external_apis',
                    'key' => $key,
                ],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'type' => $setting['type'],
                    'is_active' => true,
                ]
            );
            
            $this->command->info("  ✓ {$key} configuré");
        }

        $this->command->newLine();
        $this->command->warn('╔══════════════════════════════════════════════════════════════════════╗');
        $this->command->warn('║                    CONFIGURATION CMI IMPORTANTE                      ║');
        $this->command->warn('╠══════════════════════════════════════════════════════════════════════╣');
        $this->command->warn('║ La clé de hachage (storekey) doit être configurée manuellement:      ║');
        $this->command->warn('║                                                                      ║');
        $this->command->warn('║ 1. Connectez-vous au back office CMI:                               ║');
        $this->command->warn('║    https://testpayment.cmi.co.ma/cmi/report                          ║');
        $this->command->warn('║    Utilisateur: bd_a                                                 ║');
        $this->command->warn('║    Mot de passe: Bd_a2021 (à changer à la 1ère connexion)           ║');
        $this->command->warn('║                                                                      ║');
        $this->command->warn('║ 2. Allez dans: Administration -> Changer les clés du magasin        ║');
        $this->command->warn('║                                                                      ║');
        $this->command->warn('║ 3. Générez ou définissez votre clé de hachage                       ║');
        $this->command->warn('║                                                                      ║');
        $this->command->warn('║ 4. Configurez cette clé dans:                                        ║');
        $this->command->warn('║    - Le fichier .env (CMI_STOREKEY=votre_cle)                       ║');
        $this->command->warn('║    - OU via le panneau admin (Paramètres > API CMI)                 ║');
        $this->command->warn('╚══════════════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        $this->command->info('Cartes de test CMI disponibles:');
        $this->command->table(
            ['Marque', 'Numéro', 'Expiration', 'CVS', 'Code Auth', 'Type'],
            [
                ['Visa', '4000000000000010', '12/XX', '000', 'N/A', 'Non-authentifiable'],
                ['MasterCard', '5191630100004896', '12/XX', '000', '123', 'Authentifiable'],
                ['MasterCard', '5453010000066100', '12/XX', '000', 'N/A', 'Non participante'],
            ]
        );

        Log::info('Seeder CmiTestConfigurationSeeder exécuté avec succès');
    }
}
