<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentKeysService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DiagnoseCmiCallbackUrl extends Command
{
    protected $signature = 'cmi:diagnose-callback-url';
    protected $description = 'Diagnostiquer la configuration de l\'URL de callback CMI pour résoudre l\'erreur "Impossible de localiser l\'URL du retour marchand"';

    public function handle()
    {
        $this->info('=== Diagnostic de l\'URL de Callback CMI ===');
        $this->newLine();

        $paymentKeysService = app(PaymentKeysService::class);
        $activeKeys = $paymentKeysService->getActiveCmiKeys();

        // 1. Vérifier l'environnement
        $this->info('1. Vérification de l\'Environnement');
        $this->line('─────────────────────────────────────');
        $environment = $activeKeys['environment'] ?? 'unknown';
        $this->line("Environnement actif : <fg=cyan>{$environment}</>");
        $this->newLine();

        // 2. Vérifier l'URL de callback
        $this->info('2. Vérification de l\'URL de Callback');
        $this->line('─────────────────────────────────────');
        
        $callbackUrl = $activeKeys['callback_url'] ?? null;
        $appUrl = config('app.url');

        if (empty($callbackUrl)) {
            $this->error('❌ URL de Callback : NON CONFIGURÉE dans les paramètres admin');
            $this->warn('   → Utilisation de APP_URL comme fallback');
            
            if (empty($appUrl)) {
                $this->error('   ❌ APP_URL est également vide !');
                $this->warn('   → Configurer APP_URL dans .env ou dans Paramètres > API CMI > URL de Callback');
                return 1;
            } else {
                $callbackUrl = $appUrl;
                $this->line("   URL utilisée (APP_URL) : <fg=yellow>{$callbackUrl}</>");
            }
        } else {
            $this->info("✅ URL de Callback : Configurée dans les paramètres admin");
            $this->line("   URL : <fg=cyan>{$callbackUrl}</>");
        }

        $this->newLine();

        // 3. Valider l'URL
        $this->info('3. Validation de l\'URL');
        $this->line('─────────────────────────────────────');
        
        $issues = [];
        
        // Vérifier le format
        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            $issues[] = 'Format invalide';
            $this->error('   ❌ Format de l\'URL invalide');
        } else {
            $this->info('   ✅ Format de l\'URL valide');
        }

        // Vérifier HTTPS
        if (!preg_match('/^https:\/\//i', $callbackUrl)) {
            $issues[] = 'HTTP au lieu de HTTPS';
            $this->error('   ❌ L\'URL doit utiliser HTTPS (pas HTTP)');
            $this->warn('   → CMI exige HTTPS pour les callbacks');
        } else {
            $this->info('   ✅ URL utilise HTTPS');
        }

        // Vérifier localhost/IP privée
        if (preg_match('/localhost|127\.0\.0\.1|::1|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[01])\./i', $callbackUrl)) {
            $issues[] = 'URL locale/IP privée';
            $this->error('   ❌ L\'URL contient localhost ou une IP privée');
            $this->warn('   → CMI ne peut pas accéder aux URLs locales depuis Internet');
            $this->warn('   → Utiliser une URL publique accessible (ex: https://votre-domaine.com)');
        } else {
            $this->info('   ✅ URL est publique (pas localhost/IP privée)');
        }

        // Vérifier le trailing slash
        if (rtrim($callbackUrl, '/') !== $callbackUrl) {
            $this->warn('   ⚠️ L\'URL contient un trailing slash (sera automatiquement supprimé)');
        }

        $this->newLine();

        // 4. Tester l'accessibilité
        if (empty($issues)) {
            $this->info('4. Test d\'Accessibilité');
            $this->line('─────────────────────────────────────');
            
            $baseUrl = rtrim($callbackUrl, '/');
            
            // Test 1: Route de test générale
            $testUrl = $baseUrl . '/payment/cmi/test-callback';
            $this->line("Test 1 - Route de test : <fg=cyan>{$testUrl}</>");
            
            try {
                $response = Http::timeout(10)->get($testUrl);
                
                if ($response->successful()) {
                    $this->info('   ✅ Route de test accessible');
                    $this->line("   Code HTTP : <fg=green>{$response->status()}</>");
                } else {
                    $this->error("   ❌ Route de test retourne un code d'erreur : {$response->status()}");
                }
            } catch (\Exception $e) {
                $this->error('   ❌ Impossible d\'accéder à la route de test');
                $this->line("   Erreur : <fg=red>{$e->getMessage()}</>");
            }
            
            $this->newLine();
            
            // Test 2: Route de callback réelle (simulation)
            $testReservationId = 1;
            $callbackTestUrl = $baseUrl . '/payment/cmi/reservation/' . $testReservationId . '/callback';
            $this->line("Test 2 - Route de callback (simulation) : <fg=cyan>{$callbackTestUrl}</>");
            $this->warn('   ⚠️ Cette route nécessite une requête POST avec des données CMI');
            $this->warn('   → Tester manuellement avec curl ou depuis CMI');
            
            $this->newLine();
            
            // Test 3: Vérifier que les routes sont bien définies
            $this->line('Test 3 - Vérification des routes définies :');
            try {
                $routes = \Illuminate\Support\Facades\Route::getRoutes();
                $callbackRoutes = [];
                
                foreach ($routes as $route) {
                    $uri = $route->uri();
                    if (strpos($uri, 'payment/cmi/reservation') !== false || 
                        strpos($uri, 'credit-recharge/cmi') !== false) {
                        $callbackRoutes[] = [
                            'method' => implode('|', $route->methods()),
                            'uri' => $uri,
                            'name' => $route->getName(),
                        ];
                    }
                }
                
                if (!empty($callbackRoutes)) {
                    $this->info('   ✅ Routes de callback trouvées :');
                    foreach ($callbackRoutes as $route) {
                        $this->line("      {$route['method']} /{$route['uri']} ({$route['name']})");
                    }
                } else {
                    $this->error('   ❌ Aucune route de callback trouvée !');
                }
            } catch (\Exception $e) {
                $this->warn("   ⚠️ Impossible de vérifier les routes : {$e->getMessage()}");
            }
        } else {
            $this->warn('4. Test d\'Accessibilité : SKIPPÉ (problèmes détectés)');
        }

        $this->newLine();

        // 5. Exemple d'URLs générées
        $this->info('5. Exemple d\'URLs Générées');
        $this->line('─────────────────────────────────────');
        
        $baseUrl = rtrim($callbackUrl, '/');
        $testId = 123;
        
        $exampleUrls = [
            'okUrl' => $baseUrl . '/payment/cmi/reservation/' . $testId . '/success',
            'failUrl' => $baseUrl . '/payment/cmi/reservation/' . $testId . '/failure',
            'callbackUrl' => $baseUrl . '/payment/cmi/reservation/' . $testId . '/callback',
        ];
        
        foreach ($exampleUrls as $name => $url) {
            $this->line("   <fg=cyan>{$name}</> : {$url}");
        }

        $this->newLine();

        // 6. Vérification des URLs envoyées à CMI
        $this->info('6. Vérification des URLs Envoyées à CMI');
        $this->line('─────────────────────────────────────');
        
        $baseUrl = rtrim($callbackUrl, '/');
        $testId = 123;
        
        $urlsToCheck = [
            'okUrl' => $baseUrl . '/payment/cmi/reservation/' . $testId . '/success',
            'failUrl' => $baseUrl . '/payment/cmi/reservation/' . $testId . '/failure',
            'callbackUrl' => $baseUrl . '/payment/cmi/reservation/' . $testId . '/callback',
        ];
        
        foreach ($urlsToCheck as $name => $url) {
            $this->line("   <fg=cyan>{$name}</> : {$url}");
            
            // Vérifier que l'URL est valide
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $this->error("      ❌ URL invalide");
            } else {
                $this->info("      ✅ URL valide");
            }
            
            // Vérifier HTTPS
            if (!preg_match('/^https:\/\//i', $url)) {
                $this->error("      ❌ N'utilise pas HTTPS");
            } else {
                $this->info("      ✅ Utilise HTTPS");
            }
        }

        $this->newLine();

        // 7. Recommandations
        $this->info('7. Recommandations');
        $this->line('─────────────────────────────────────');
        
        if (!empty($issues)) {
            $this->error('❌ Problèmes détectés :');
            foreach ($issues as $issue) {
                $this->line("   - {$issue}");
            }
            $this->newLine();
            $this->warn('Actions à effectuer :');
            $this->line('   1. Aller dans Paramètres > API CMI');
            $this->line('   2. Configurer "URL Callback Test" ou "URL Callback Production" selon l\'environnement');
            $this->line('   3. Utiliser une URL publique en HTTPS (ex: https://devcharge.evonpower.com)');
            $this->line('   4. Ne PAS utiliser localhost, 127.0.0.1, ou IPs privées');
            $this->line('   5. Vider le cache : php artisan config:clear && php artisan cache:clear');
        } else {
            $this->info('✅ Configuration semble correcte');
            $this->newLine();
            $this->warn('Si l\'erreur "Impossible de localiser l\'URL du retour marchand" persiste :');
            $this->line('   1. Vérifier que les routes de callback sont bien définies dans routes/web.php');
            $this->line('   2. Vérifier que le serveur est accessible depuis Internet');
            $this->line('   3. Vérifier que le firewall n\'bloque pas les requêtes POST de CMI');
            $this->line('   4. Vérifier les logs Laravel après une tentative de paiement :');
            $this->line('      tail -f storage/logs/laravel.log | grep "CMI Payment data prepared"');
            $this->line('   5. Vérifier que callbackUrl est bien présent dans les paramètres envoyés à CMI');
            $this->line('   6. Tester manuellement la route de callback avec curl :');
            $this->line("      curl -X POST {$baseUrl}/payment/cmi/reservation/1/callback -d 'test=1'");
        }

        $this->newLine();
        return 0;
    }
}

