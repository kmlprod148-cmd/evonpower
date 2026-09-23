<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckLogs extends Command
{
    protected $signature = 'check:logs';
    protected $description = 'Vérifier les logs Laravel pour les erreurs récentes';

    public function handle()
    {
        $this->info('🔍 Vérification des Logs Laravel');
        $this->info('================================');

        try {
            $logFile = storage_path('logs/laravel.log');
            
            if (!file_exists($logFile)) {
                $this->info("📁 Fichier de log n'existe pas: {$logFile}");
                return;
            }

            $this->info("📁 Fichier de log trouvé: {$logFile}");
            
            // Lire les dernières lignes du fichier
            $lines = file($logFile, FILE_IGNORE_NEW_LINES);
            $recentLines = array_slice($lines, -50); // 50 dernières lignes
            
            $this->info("📊 Nombre total de lignes: " . count($lines));
            $this->info("📊 Dernières 50 lignes:");
            
            $errorCount = 0;
            $exceptionCount = 0;
            
            foreach ($recentLines as $line) {
                if (strpos($line, 'ERROR') !== false) {
                    $this->error("❌ ERROR: " . $line);
                    $errorCount++;
                } elseif (strpos($line, 'Exception') !== false) {
                    $this->error("💥 EXCEPTION: " . $line);
                    $exceptionCount++;
                } elseif (strpos($line, 'WARNING') !== false) {
                    $this->warn("⚠️  WARNING: " . $line);
                } elseif (strpos($line, 'INFO') !== false) {
                    $this->info("ℹ️  INFO: " . $line);
                }
            }
            
            $this->info("\n📊 Résumé:");
            $this->info("   - Erreurs: {$errorCount}");
            $this->info("   - Exceptions: {$exceptionCount}");
            
            if ($errorCount === 0 && $exceptionCount === 0) {
                $this->info("✅ Aucune erreur récente trouvée dans les logs");
            }

        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
