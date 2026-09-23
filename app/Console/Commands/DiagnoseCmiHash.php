<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentKeysService;
use App\Models\AdminSetting;
use Illuminate\Support\Facades\Log;

class DiagnoseCmiHash extends Command
{
    protected $signature = 'cmi:diagnose-hash';
    protected $description = 'Diagnostiquer le problème de hash CMI invalide';

    public function handle()
    {
        $this->info('=== Diagnostic du Hash CMI ===');
        $this->newLine();

        // Récupérer la configuration CMI
        $paymentKeysService = app(PaymentKeysService::class);
        $activeKeys = $paymentKeysService->getActiveCmiKeys();

        $this->info('1. Vérification de la Configuration CMI');
        $this->line('─────────────────────────────────────');
        
        // Vérifier l'environnement
        $environment = $activeKeys['environment'] ?? 'unknown';
        $this->info("Environnement actif : {$environment}");
        
        // Vérifier la Store Key
        $storeKey = $activeKeys['storekey'] ?? '';
        if (empty($storeKey)) {
            $this->error('❌ Clé API (Store Key) : NON CONFIGURÉE');
            $this->warn('   → Aller dans Paramètres > API CMI pour configurer');
        } else {
            $this->info('✅ Clé API (Store Key) : Configurée');
            $this->line("   Longueur : " . strlen($storeKey) . " caractères");
            $this->line("   Preview : " . substr($storeKey, 0, 10) . "...");
            
            // Vérifier les espaces
            if (trim($storeKey) !== $storeKey) {
                $this->error('   ⚠️ ATTENTION : La clé contient des espaces avant/après !');
            }
        }
        
        // Vérifier le Client ID
        $clientId = $activeKeys['clientid'] ?? '';
        if (empty($clientId)) {
            $this->error('❌ ID Marchand (Client ID) : NON CONFIGURÉ');
            $this->warn('   → Aller dans Paramètres > API CMI pour configurer');
        } else {
            $this->info('✅ ID Marchand (Client ID) : Configuré');
            $this->line("   Valeur : {$clientId}");
            
            // Vérifier les espaces
            if (trim($clientId) !== $clientId) {
                $this->error('   ⚠️ ATTENTION : L\'ID contient des espaces avant/après !');
            }
        }
        
        // Vérifier l'URL de l'API
        $apiUrl = $activeKeys['api_url'] ?? '';
        if (empty($apiUrl)) {
            $this->warn('⚠️ URL API : NON CONFIGURÉE');
        } else {
            $this->info('✅ URL API : Configurée');
            $this->line("   URL : {$apiUrl}");
        }
        
        $this->newLine();
        
        // Vérifier la cohérence environnement/clés
        $this->info('2. Vérification de la Cohérence Environnement/Clés');
        $this->line('─────────────────────────────────────');
        
        // Récupérer directement les clés depuis la base de données
        $settings = AdminSetting::where('category', 'external_apis')
            ->whereIn('key', [
                'cmi_test_api_key',
                'cmi_test_merchant_id',
                'cmi_prod_api_key',
                'cmi_prod_merchant_id',
                'cmi_environment',
            ])
            ->get()
            ->keyBy('key');
        
        $activeEnvironment = $settings->get('cmi_environment')?->value ?? 'test';
        
        $this->line("Environnement configuré dans AdminSettings : {$activeEnvironment}");
        
        if ($environment !== $activeEnvironment) {
            $this->error("⚠️ INCOHÉRENCE : L'environnement actif ({$environment}) ne correspond pas à celui configuré ({$activeEnvironment})");
        }
        
        if ($activeEnvironment === 'test') {
            $testApiKey = $this->getDecryptedValue($settings->get('cmi_test_api_key')?->value);
            $testMerchantId = $settings->get('cmi_test_merchant_id')?->value;
            
            $this->newLine();
            $this->line('Clés TEST dans la base de données :');
            if (empty($testApiKey)) {
                $this->error('   ❌ Clé API TEST : NON CONFIGURÉE');
            } else {
                $this->info('   ✅ Clé API TEST : Configurée (' . strlen($testApiKey) . ' caractères)');
            }
            
            if (empty($testMerchantId)) {
                $this->error('   ❌ ID Marchand TEST : NON CONFIGURÉ');
            } else {
                $this->info('   ✅ ID Marchand TEST : Configuré (' . $testMerchantId . ')');
            }
            
            if (empty($testApiKey) || empty($testMerchantId)) {
                $this->newLine();
                $this->error('❌ Les clés de TEST ne sont pas configurées');
                $this->warn('   → Aller dans Paramètres > API CMI');
                $this->warn('   → Sélectionner l\'environnement "Test"');
                $this->warn('   → Configurer la Clé API (Store Key) TEST');
                $this->warn('   → Configurer l\'ID Marchand (Client ID) TEST');
                $this->warn('   → Sauvegarder');
            }
        } elseif ($activeEnvironment === 'prod') {
            $prodApiKey = $this->getDecryptedValue($settings->get('cmi_prod_api_key')?->value);
            $prodMerchantId = $settings->get('cmi_prod_merchant_id')?->value;
            
            $this->newLine();
            $this->line('Clés PRODUCTION dans la base de données :');
            if (empty($prodApiKey)) {
                $this->error('   ❌ Clé API PRODUCTION : NON CONFIGURÉE');
            } else {
                $this->info('   ✅ Clé API PRODUCTION : Configurée (' . strlen($prodApiKey) . ' caractères)');
            }
            
            if (empty($prodMerchantId)) {
                $this->error('   ❌ ID Marchand PRODUCTION : NON CONFIGURÉ');
            } else {
                $this->info('   ✅ ID Marchand PRODUCTION : Configuré (' . $prodMerchantId . ')');
            }
            
            if (empty($prodApiKey) || empty($prodMerchantId)) {
                $this->newLine();
                $this->error('❌ Les clés de PRODUCTION ne sont pas configurées');
                $this->warn('   → Aller dans Paramètres > API CMI');
                $this->warn('   → Sélectionner l\'environnement "Production"');
                $this->warn('   → Configurer la Clé API (Store Key) PRODUCTION');
                $this->warn('   → Configurer l\'ID Marchand (Client ID) PRODUCTION');
                $this->warn('   → Sauvegarder');
            }
        }
        
        $this->newLine();
        
        // Test de calcul de hash
        $this->info('3. Test de Calcul du Hash');
        $this->line('─────────────────────────────────────');
        
        if (empty($storeKey) || empty($clientId)) {
            $this->error('❌ Impossible de tester le hash : Clés manquantes');
            $this->newLine();
            $this->error('═══════════════════════════════════════════════════════');
            $this->error('⚠️  CONFIGURATION REQUISE AVANT DE CONTINUER');
            $this->error('═══════════════════════════════════════════════════════');
            $this->newLine();
            $this->info('📋 Étapes pour Configurer les Clés CMI :');
            $this->newLine();
            $this->line('1. Obtenir les clés CMI :');
            $this->line('   - Contacter CMI pour obtenir vos clés API');
            $this->line('   - Demander la Clé API (Store Key) pour l\'environnement ' . $environment);
            $this->line('   - Demander l\'ID Marchand (Client ID) pour l\'environnement ' . $environment);
            $this->newLine();
            $this->line('2. Configurer dans l\'interface admin :');
            $this->line('   - Aller dans : Paramètres > API CMI');
            $this->line('   - Ou directement : /admin/cmi-api-settings');
            $this->newLine();
            $this->line('3. Dans l\'interface de configuration :');
            $this->line('   - Sélectionner l\'Environnement actif : ' . $environment);
            if ($environment === 'test') {
                $this->line('   - Remplir "Clé API Test" avec la clé fournie par CMI');
                $this->line('   - Remplir "ID Marchand Test" avec l\'ID fourni par CMI');
            } else {
                $this->line('   - Remplir "Clé API Production" avec la clé fournie par CMI');
                $this->line('   - Remplir "ID Marchand Production" avec l\'ID fourni par CMI');
            }
            $this->line('   - Configurer l\'URL de Callback (ex: https://votre-domaine.com)');
            $this->line('   - Sauvegarder');
            $this->newLine();
            $this->line('4. Vider le cache et relancer le diagnostic :');
            $this->line('   php artisan cache:clear');
            $this->line('   php artisan cmi:diagnose-hash');
            $this->newLine();
            $this->warn('⚠️  IMPORTANT :');
            $this->line('   - Les clés TEST et PRODUCTION sont différentes');
            $this->line('   - Assurez-vous d\'utiliser les bonnes clés selon l\'environnement');
            $this->line('   - Ne pas mettre d\'espaces avant/après les clés');
            $this->line('   - Copier les clés exactement comme fournies par CMI');
            return;
        }
        
        // Données de test
        $testData = [
            'clientid' => $clientId,
            'amount' => '10.25',
            'okUrl' => 'https://example.com/ok',
            'failUrl' => 'https://example.com/fail',
            'TranType' => 'PreAuth',
            'callbackUrl' => 'https://example.com/callback',
            'shopurl' => 'https://example.com',
            'currency' => '504',
            'rnd' => trim(microtime()),
            'storetype' => '3D_PAY_HOSTING',
            'hashAlgorithm' => 'ver3',
            'lang' => 'fr',
            'refreshtime' => '5',
            'BillToName' => 'Test User',
            'BillToCompany' => '',
            'BillToStreet1' => 'Test Street',
            'BillToCity' => 'Test City',
            'BillToStateProv' => 'Test State',
            'BillToPostalCode' => '12345',
            'BillToCountry' => '504',
            'email' => 'test@example.com',
            'tel' => '0021200000000',
            'encoding' => 'UTF-8',
            'oid' => 'TEST123',
        ];
        
        // Calculer le hash
        $hash = $this->calculateHash($testData, $storeKey);
        $hashString = $this->buildHashString($testData, $storeKey);
        
        $this->info('Hash calculé : ' . substr($hash, 0, 30) . '...');
        $this->line('Longueur du hash : ' . strlen($hash) . ' caractères');
        $this->newLine();
        
        // Afficher les clés triées
        $this->info('4. Ordre des Paramètres dans le Hash');
        $this->line('─────────────────────────────────────');
        $sortedKeys = $this->getSortedKeys($testData);
        $this->table(['Ordre', 'Clé'], array_map(fn($i, $k) => [$i + 1, $k], array_keys($sortedKeys), $sortedKeys));
        
        $this->newLine();
        
        // Afficher un aperçu de la chaîne de hash
        $this->info('5. Aperçu de la Chaîne de Hash');
        $this->line('─────────────────────────────────────');
        $this->line('Début : ' . substr($hashString, 0, 150) . '...');
        $this->line('Fin : ...' . substr($hashString, -100));
        $this->line('Longueur totale : ' . strlen($hashString) . ' caractères');
        
        $this->newLine();
        
        // Recommandations
        $this->info('6. Recommandations');
        $this->line('─────────────────────────────────────');
        
        if (empty($storeKey) || empty($clientId)) {
            $this->error('❌ Les clés CMI ne sont pas configurées');
            $this->newLine();
            $this->error('═══════════════════════════════════════════════════════');
            $this->error('⚠️  ACTION REQUISE : Configurer les Clés CMI');
            $this->error('═══════════════════════════════════════════════════════');
            $this->newLine();
            $this->info('📍 Accès à la configuration :');
            $this->line('   URL : /admin/cmi-api-settings');
            $this->line('   Menu : Paramètres > API CMI');
            $this->newLine();
            $this->info('🔑 Clés à configurer pour l\'environnement "' . $environment . '" :');
            if ($environment === 'test') {
                $this->line('   - Clé API Test (Store Key)');
                $this->line('   - ID Marchand Test (Client ID)');
            } else {
                $this->line('   - Clé API Production (Store Key)');
                $this->line('   - ID Marchand Production (Client ID)');
            }
            $this->newLine();
            $this->info('📞 Pour obtenir les clés :');
            $this->line('   Contacter CMI pour activer votre compte marchand');
            $this->line('   Demander les clés pour l\'environnement : ' . $environment);
        } else {
            $this->info('✅ Configuration de base OK');
            $this->newLine();
            $this->line('Si le hash est toujours rejeté par CMI :');
            $this->line('   1. Vérifier que les clés correspondent à l\'environnement (' . $environment . ')');
            $this->line('   2. Vérifier qu\'il n\'y a pas d\'espaces dans les clés');
            $this->line('   3. Vérifier que les clés sont exactement celles fournies par CMI');
            $this->line('   4. Contacter CMI pour vérifier les clés');
            $this->line('   5. Vérifier les logs : storage/logs/laravel.log');
            $this->line('      Rechercher : "CMI Hash calculation details"');
        }
        
        $this->newLine();
        $this->info('📖 Documentation : Voir DIAGNOSTIC_HASH_INVALIDE.md');
    }
    
    protected function calculateHash(array $params, string $storeKey): string
    {
        $postParams = [];
        foreach ($params as $key => $value) {
            $lowerParam = strtolower($key);
            if ($lowerParam !== 'hash' && $lowerParam !== 'encoding') {
                $postParams[] = $key;
            }
        }
        
        natcasesort($postParams);
        
        $hashval = '';
        foreach ($postParams as $param) {
            $paramValue = trim((string) ($params[$param] ?? ''));
            $escapedParamValue = str_replace("\\", "\\\\", $paramValue);
            $escapedParamValue = str_replace("|", "\\|", $escapedParamValue);
            $hashval .= $escapedParamValue . "|";
        }
        
        $escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));
        $hashval .= $escapedStoreKey;
        
        $calculatedHashValue = hash('sha512', $hashval);
        $hash = base64_encode(pack('H*', $calculatedHashValue));
        
        return $hash;
    }
    
    protected function buildHashString(array $params, string $storeKey): string
    {
        $postParams = [];
        foreach ($params as $key => $value) {
            $lowerParam = strtolower($key);
            if ($lowerParam !== 'hash' && $lowerParam !== 'encoding') {
                $postParams[] = $key;
            }
        }
        
        natcasesort($postParams);
        
        $hashval = '';
        foreach ($postParams as $param) {
            $paramValue = trim((string) ($params[$param] ?? ''));
            $escapedParamValue = str_replace("\\", "\\\\", $paramValue);
            $escapedParamValue = str_replace("|", "\\|", $escapedParamValue);
            $hashval .= $escapedParamValue . "|";
        }
        
        $escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));
        $hashval .= $escapedStoreKey;
        
        return $hashval;
    }
    
    protected function getSortedKeys(array $params): array
    {
        $postParams = [];
        foreach ($params as $key => $value) {
            $lowerParam = strtolower($key);
            if ($lowerParam !== 'hash' && $lowerParam !== 'encoding') {
                $postParams[] = $key;
            }
        }
        
        natcasesort($postParams);
        
        return $postParams;
    }
    
    /**
     * Récupère une valeur décryptée si nécessaire
     * 
     * @param string|null $value
     * @return string|null
     */
    private function getDecryptedValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Si le décryptage échoue, retourner la valeur telle quelle (peut-être déjà en clair)
            return $value;
        }
    }
}

