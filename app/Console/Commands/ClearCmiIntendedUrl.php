<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ClearCmiIntendedUrl extends Command
{
    protected $signature = 'session:clear-cmi-intended';
    protected $description = 'Nettoyer toutes les sessions contenant des URLs CMI dans url.intended';

    public function handle()
    {
        $this->info('=== Nettoyage des Sessions avec URLs CMI ===');
        $this->newLine();
        
        $driver = config('session.driver');
        $this->info("Driver de session détecté : {$driver}");
        $this->newLine();
        
        $cleanedCount = 0;
        
        // Nettoyer selon le driver
        switch ($driver) {
            case 'database':
                $cleanedCount = $this->cleanDatabaseSessions();
                break;
            case 'file':
                $cleanedCount = $this->cleanFileSessions();
                break;
            case 'cache':
            case 'redis':
                $this->info('⚠️  Pour le driver "cache" ou "redis", les sessions expirent automatiquement.');
                $this->info('   Exécutez : php artisan cache:clear');
                Cache::flush();
                $this->info('   ✅ Cache vidé');
                break;
            default:
                $this->warn("⚠️  Driver non supporté : {$driver}");
                $this->info('   Veuillez nettoyer manuellement les sessions.');
        }
        
        $this->newLine();
        $this->info("✅ Nettoyage terminé. {$cleanedCount} session(s) nettoyée(s).");
        $this->newLine();
        $this->warn('⚠️  IMPORTANT :');
        $this->line('   Après avoir nettoyé les sessions, testez la connexion :');
        $this->line('   1. Accéder à /login');
        $this->line('   2. Se connecter');
        $this->line('   3. Vérifier que la redirection se fait vers /dashboard et non vers CMI');
        
        return 0;
    }
    
    /**
     * Nettoyer les sessions dans la base de données
     */
    private function cleanDatabaseSessions(): int
    {
        $this->info('Nettoyage des sessions dans la base de données...');
        
        try {
            $sessions = DB::table('sessions')->get();
            $cleanedCount = 0;
            
            foreach ($sessions as $session) {
                try {
                    $payload = unserialize(base64_decode($session->payload));
                    
                    // Vérifier si url.intended contient une URL CMI
                    if (isset($payload['url.intended'])) {
                        $intendedUrl = $payload['url.intended'];
                        if (strpos($intendedUrl, 'testpayment.cmi.co.ma') !== false || 
                            strpos($intendedUrl, 'payment.cmi.co.ma') !== false ||
                            strpos($intendedUrl, 'est3Dgate') !== false) {
                            
                            // Supprimer url.intended de la session
                            unset($payload['url.intended']);
                            
                            // Mettre à jour la session
                            $newPayload = base64_encode(serialize($payload));
                            DB::table('sessions')
                                ->where('id', $session->id)
                                ->update(['payload' => $newPayload]);
                            
                            $cleanedCount++;
                        }
                    }
                } catch (\Exception $e) {
                    // Ignorer les sessions corrompues
                    continue;
                }
            }
            
            $this->info("   ✅ {$cleanedCount} session(s) nettoyée(s)");
            return $cleanedCount;
            
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur lors du nettoyage : " . $e->getMessage());
            $this->info('   💡 Alternative : Exécutez manuellement en SQL :');
            $this->line('      DELETE FROM sessions WHERE payload LIKE "%testpayment.cmi.co.ma%"');
            return 0;
        }
    }
    
    /**
     * Nettoyer les sessions dans les fichiers
     */
    private function cleanFileSessions(): int
    {
        $this->info('Nettoyage des sessions dans les fichiers...');
        
        $sessionPath = storage_path('framework/sessions');
        
        if (!File::exists($sessionPath)) {
            $this->warn("   ⚠️  Le répertoire {$sessionPath} n'existe pas");
            return 0;
        }
        
        $files = File::files($sessionPath);
        $cleanedCount = 0;
        
        foreach ($files as $file) {
            try {
                $content = File::get($file);
                
                // Vérifier si le fichier contient une URL CMI
                if (strpos($content, 'testpayment.cmi.co.ma') !== false || 
                    strpos($content, 'payment.cmi.co.ma') !== false ||
                    strpos($content, 'est3Dgate') !== false) {
                    
                    // Supprimer le fichier de session
                    File::delete($file);
                    $cleanedCount++;
                }
            } catch (\Exception $e) {
                // Ignorer les fichiers corrompus
                continue;
            }
        }
        
        $this->info("   ✅ {$cleanedCount} fichier(s) de session supprimé(s)");
        return $cleanedCount;
    }
}

