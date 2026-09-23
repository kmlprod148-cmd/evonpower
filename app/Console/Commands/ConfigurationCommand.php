<?php

namespace App\Console\Commands;

use App\Services\AdminConfigurationService;
use App\Services\ConfigurationService;
use Illuminate\Console\Command;

class ConfigurationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:manage 
                            {action : Action à effectuer (list|export|import|reset|seed)}
                            {--file= : Fichier pour import/export}
                            {--category= : Catégorie spécifique}
                            {--overwrite : Écraser les paramètres existants lors de l\'import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestion des configurations admin';

    protected AdminConfigurationService $adminConfigService;
    protected ConfigurationService $configService;

    public function __construct(
        AdminConfigurationService $adminConfigService,
        ConfigurationService $configService
    ) {
        parent::__construct();
        $this->adminConfigService = $adminConfigService;
        $this->configService = $configService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listConfigurations();
                break;
            case 'export':
                $this->exportConfigurations();
                break;
            case 'import':
                $this->importConfigurations();
                break;
            case 'reset':
                $this->resetConfigurations();
                break;
            case 'seed':
                $this->seedConfigurations();
                break;
            default:
                $this->error("Action inconnue: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Liste toutes les configurations
     */
    protected function listConfigurations(): void
    {
        $this->info('=== CONFIGURATIONS ADMIN ===');
        
        $configurations = $this->adminConfigService->getAllAdminSettings();
        $stats = $this->adminConfigService->getConfigurationStats();

        foreach ($configurations as $category => $settings) {
            $this->line("\n<fg=cyan>📁 {$category}</fg=cyan>");
            
            foreach ($settings as $key => $config) {
                $value = $config['value'] ?? 'Non défini';
                $type = $config['type'] ?? 'text';
                $required = $config['required'] ?? false;
                
                $status = $required ? '<fg=red>*</fg=red>' : '<fg=green>✓</fg=green>';
                $this->line("  {$status} <fg=yellow>{$key}</fg=yellow> ({$type}): {$value}");
            }
        }

        $this->line("\n<fg=magenta>=== STATISTIQUES ===</fg=magenta>");
        foreach ($stats as $category => $stat) {
            $percentage = $stat['percentage'];
            $color = $percentage >= 80 ? 'green' : ($percentage >= 50 ? 'yellow' : 'red');
            $this->line("<fg={$color}>{$category}: {$stat['configured']}/{$stat['total']} ({$percentage}%)</fg={$color}>");
        }
    }

    /**
     * Exporte les configurations
     */
    protected function exportConfigurations(): void
    {
        $file = $this->option('file') ?: 'configurations_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        try {
            $configurations = $this->adminConfigService->exportConfigurations();
            $content = json_encode($configurations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            file_put_contents($file, $content);
            
            $this->info("✅ Configurations exportées vers: {$file}");
            $this->line("📊 Total: " . count($configurations['configurations']) . " catégories");
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'export: " . $e->getMessage());
        }
    }

    /**
     * Importe les configurations
     */
    protected function importConfigurations(): void
    {
        $file = $this->option('file');
        
        if (!$file || !file_exists($file)) {
            $this->error("❌ Fichier non trouvé: {$file}");
            return;
        }

        try {
            $content = file_get_contents($file);
            $configurations = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Fichier JSON invalide');
            }

            $overwrite = $this->option('overwrite') ?: $this->confirm('Écraser les paramètres existants ?', false);
            
            $success = $this->adminConfigService->importConfigurations($configurations, $overwrite);
            
            if ($success) {
                $this->info("✅ Configurations importées avec succès");
            } else {
                $this->error("❌ Erreur lors de l'import");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'import: " . $e->getMessage());
        }
    }

    /**
     * Réinitialise les configurations
     */
    protected function resetConfigurations(): void
    {
        $category = $this->option('category');
        
        if ($category) {
            $confirmed = $this->confirm("Réinitialiser la catégorie '{$category}' ?");
        } else {
            $confirmed = $this->confirm("Réinitialiser TOUTES les configurations ?", false);
        }

        if (!$confirmed) {
            $this->info("❌ Opération annulée");
            return;
        }

        try {
            $success = $this->adminConfigService->resetToDefaults($category);
            
            if ($success) {
                $target = $category ? "catégorie '{$category}'" : 'toutes les catégories';
                $this->info("✅ Configurations réinitialisées pour: {$target}");
            } else {
                $this->error("❌ Erreur lors de la réinitialisation");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la réinitialisation: " . $e->getMessage());
        }
    }

    /**
     * Initialise les configurations par défaut
     */
    protected function seedConfigurations(): void
    {
        try {
            $this->call('db:seed', ['--class' => 'AdminSettingsSeeder']);
            $this->info("✅ Configurations par défaut initialisées");
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'initialisation: " . $e->getMessage());
        }
    }
}
