<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\App;

class VerifyTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:verify {--locale=} {--missing} {--incomplete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier la couverture des traductions pour toutes les langues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Vérification des traductions...');
        
        $locales = $this->option('locale') ? [$this->option('locale')] : ['fr', 'en', 'ar', 'es'];
        $showMissing = $this->option('missing');
        $showIncomplete = $this->option('incomplete');
        
        $results = [];
        
        foreach ($locales as $locale) {
            $this->info("\n📋 Vérification de la langue: {$locale}");
            $results[$locale] = $this->verifyLocale($locale, $showMissing, $showIncomplete);
        }
        
        $this->displaySummary($results);
        
        return Command::SUCCESS;
    }
    
    /**
     * Vérifier une locale spécifique
     */
    private function verifyLocale($locale, $showMissing = false, $showIncomplete = false)
    {
        $langPath = resource_path("lang/{$locale}");
        
        if (!File::exists($langPath)) {
            $this->error("❌ Dossier de langue manquant: {$langPath}");
            return ['status' => 'missing', 'files' => [], 'keys' => 0];
        }
        
        $files = File::allFiles($langPath);
        $fileCount = count($files);
        $totalKeys = 0;
        $missingFiles = [];
        $incompleteFiles = [];
        
        // Vérifier les fichiers de traduction
        $expectedFiles = [
            'messages.php',
            'auth.php',
            'dashboard.php',
            'validation.php',
            'switch.php',
            'charging_points.php',
            'transactions.php',
            'reservations.php',
            'users.php',
            'reports.php',
            'error.php'
        ];
        
        $existingFiles = array_map(function($file) {
            return basename($file->getPathname());
        }, $files);
        
        foreach ($expectedFiles as $expectedFile) {
            if (!in_array($expectedFile, $existingFiles)) {
                $missingFiles[] = $expectedFile;
            }
        }
        
        // Compter les clés de traduction
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = include $file->getPathname();
                if (is_array($content)) {
                    $totalKeys += $this->countArrayKeys($content);
                }
            }
        }
        
        // Vérifier les fichiers incomplets
        if ($showIncomplete) {
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $content = include $file->getPathname();
                    if (is_array($content)) {
                        $keyCount = $this->countArrayKeys($content);
                        if ($keyCount < 10) { // Seuil arbitraire
                            $incompleteFiles[] = [
                                'file' => basename($file->getPathname()),
                                'keys' => $keyCount
                            ];
                        }
                    }
                }
            }
        }
        
        // Afficher les résultats
        $this->info("✅ Fichiers trouvés: {$fileCount}");
        $this->info("🔑 Clés de traduction: {$totalKeys}");
        
        if (!empty($missingFiles)) {
            $this->warn("⚠️  Fichiers manquants: " . implode(', ', $missingFiles));
        }
        
        if ($showMissing && !empty($missingFiles)) {
            $this->table(['Fichiers manquants'], array_map(function($file) {
                return [$file];
            }, $missingFiles));
        }
        
        if ($showIncomplete && !empty($incompleteFiles)) {
            $this->table(['Fichier', 'Clés'], array_map(function($file) {
                return [$file['file'], $file['keys']];
            }, $incompleteFiles));
        }
        
        return [
            'status' => empty($missingFiles) ? 'complete' : 'incomplete',
            'files' => $fileCount,
            'keys' => $totalKeys,
            'missing' => $missingFiles,
            'incomplete' => $incompleteFiles
        ];
    }
    
    /**
     * Compter les clés dans un tableau
     */
    private function countArrayKeys($array, $prefix = '')
    {
        $count = 0;
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $count += $this->countArrayKeys($value, $fullKey);
            } else {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Afficher le résumé
     */
    private function displaySummary($results)
    {
        $this->info("\n📊 Résumé de la vérification:");
        
        $table = [];
        foreach ($results as $locale => $result) {
            $status = $result['status'] === 'complete' ? '✅' : '⚠️';
            $table[] = [
                $locale,
                $status,
                $result['files'],
                $result['keys'],
                count($result['missing'])
            ];
        }
        
        $this->table([
            'Langue',
            'Statut',
            'Fichiers',
            'Clés',
            'Manquants'
        ], $table);
        
        // Recommandations
        $this->info("\n💡 Recommandations:");
        
        $totalKeys = array_sum(array_column($results, 'keys'));
        $avgKeys = $totalKeys / count($results);
        
        $this->info("- Total des clés de traduction: {$totalKeys}");
        $this->info("- Moyenne par langue: " . round($avgKeys));
        
        $incompleteLocales = array_filter($results, function($result) {
            return $result['status'] !== 'complete';
        });
        
        if (!empty($incompleteLocales)) {
            $this->warn("⚠️  Langues incomplètes: " . implode(', ', array_keys($incompleteLocales)));
        } else {
            $this->info("🎉 Toutes les langues sont complètes!");
        }
    }
}
