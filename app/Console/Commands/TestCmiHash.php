<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CMICreditRechargeService;
use Illuminate\Support\Facades\Log;

class TestCmiHash extends Command
{
    protected $signature = 'cmi:test-hash';
    protected $description = 'Tester le calcul du hash CMI avec les données de l\'exemple';

    public function handle()
    {
        $this->info('=== Test du Calcul du Hash CMI ===');
        $this->newLine();

        // Données de l'exemple fourni
        $exampleData = [
            'clientid' => '100200127',
            'amount' => '10.25',
            'okUrl' => 'http://www.nom_de_domaine.com/Ok-Fail.php',
            'failUrl' => 'http://www.nom_de_domaine.com/Ok-Fail.php',
            'TranType' => 'PreAuth',
            'callbackUrl' => 'http://www.nom_de_domaine.com/callback.php',
            'shopurl' => 'http://www.nom_de_domaine.com',
            'currency' => '504',
            'rnd' => '0.84012800 1763741863',
            'storetype' => '3D_PAY_HOSTING',
            'hashAlgorithm' => 'ver3',
            'lang' => 'fr',
            'refreshtime' => '5',
            'BillToName' => 'name',
            'BillToCompany' => 'billToCompany',
            'BillToStreet1' => '100 rue adress',
            'BillToCity' => 'casablanca',
            'BillToStateProv' => 'Maarif Casablanca',
            'BillToPostalCode' => '20230',
            'BillToCountry' => '504',
            'email' => 'email@domaine.com',
            'tel' => '0021201020304',
            'encoding' => 'UTF-8',
            'oid' => '123ABC',
        ];

        // StoreKey de test (doit être configuré)
        $storeKey = env('CMI_STOREKEY', 'TEST1234');

        $this->info('Données de test (exemple CMI):');
        $this->table(
            ['Paramètre', 'Valeur'],
            array_map(fn($k, $v) => [$k, $v], array_keys($exampleData), array_values($exampleData))
        );

        $this->newLine();
        $this->info("StoreKey: {$storeKey}");
        $this->newLine();

        // Calculer le hash selon notre méthode
        $hash = $this->calculateHash($exampleData, $storeKey);

        $this->info("Hash calculé: {$hash}");
        $this->newLine();

        // Afficher la chaîne de hash pour debug
        $hashString = $this->buildHashString($exampleData, $storeKey);
        $this->info("Chaîne de hash (preview): " . substr($hashString, 0, 200) . "...");
        $this->info("Longueur de la chaîne: " . strlen($hashString));
        $this->newLine();

        // Vérifier avec le service CMI
        $this->info('=== Test avec le Service CMI ===');
        try {
            $service = app(CMICreditRechargeService::class);
            // Utiliser la réflexion pour accéder à la méthode protégée
            $reflection = new \ReflectionClass($service);
            $method = $reflection->getMethod('generateHash');
            $method->setAccessible(true);
            
            $serviceHash = $method->invoke($service, $exampleData);
            $this->info("Hash du service: {$serviceHash}");
            
            if ($hash === $serviceHash) {
                $this->info('✅ Les deux méthodes produisent le même hash');
            } else {
                $this->error('❌ Les hashs sont différents !');
            }
        } catch (\Exception $e) {
            $this->error('Erreur lors du test avec le service: ' . $e->getMessage());
        }
    }

    protected function calculateHash(array $params, string $storeKey): string
    {
        // Exactement comme dans 2.SendData.php
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
            // Échapper exactement comme dans 2.SendData.php ligne 33
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
}

