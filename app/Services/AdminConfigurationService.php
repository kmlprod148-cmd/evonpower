<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service de gestion des configurations admin
 * 
 * Ce service gère :
 * - Les paramètres admin complets
 * - L'import/export des configurations
 * - La validation des paramètres
 * - La mise en cache des configurations
 */
class AdminConfigurationService
{
    protected array $configCategories = [
        'system', 'business', 'financial', 'charging_points', 
        'notifications', 'security', 'api', 'external_apis', 'backup'
    ];

    /**
     * Obtient toutes les configurations admin
     * 
     * @return array
     */
    public function getAllAdminSettings(): array
    {
        $cacheKey = 'admin_settings_all';
        
        return Cache::remember($cacheKey, 3600, function () {
            $settings = [];
            
            foreach ($this->configCategories as $category) {
                $settings[$category] = $this->getCategorySettings($category);
            }
            
            return $settings;
        });
    }

    /**
     * Obtient les paramètres d'une catégorie
     * 
     * @param string $category
     * @return array
     */
    public function getCategorySettings(string $category): array
    {
        $cacheKey = "admin_settings_{$category}";
        
        return Cache::remember($cacheKey, 3600, function () use ($category) {
            $defaultConfig = config("admin_settings.{$category}", []);

            if (!$this->adminSettingsTableExists()) {
                return $defaultConfig;
            }

            $dbSettings = AdminSetting::where('category', $category)
                ->where('is_active', true)
                ->get()
                ->keyBy('key')
                ->toArray();

            $settings = [];
            foreach ($defaultConfig as $key => $config) {
                $settings[$key] = $config;
                if (isset($dbSettings[$key])) {
                    $settings[$key]['value'] = $dbSettings[$key]['value'];
                }
            }

            return $settings;
        });
    }

    /**
     * Obtient un paramètre spécifique
     * 
     * @param string $category
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $category, string $key, $default = null)
    {
        $categorySettings = $this->getCategorySettings($category);
        
        if (isset($categorySettings[$key])) {
            // Si la valeur est définie en base de données, la retourner
            if (isset($categorySettings[$key]['value'])) {
                return $categorySettings[$key]['value'];
            }
            // Sinon, retourner la valeur par défaut de la config
            if (isset($categorySettings[$key]['default'])) {
                return $categorySettings[$key]['default'];
            }
        }
        
        // Retourner la valeur par défaut fournie ou depuis la config
        $configValue = config("admin_settings.{$category}.{$key}.default", $default);

        if (!$this->adminSettingsTableExists()) {
            return $configValue;
        }
        
        // Vérifier aussi directement en base de données
        $dbSetting = AdminSetting::where('category', $category)
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
        
        if ($dbSetting) {
            return $dbSetting->value;
        }
        
        return $configValue;
    }

    /**
     * Met à jour un paramètre admin
     * 
     * @param string $category
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function updateSetting(string $category, string $key, $value): bool
    {
        try {
            if (!$this->adminSettingsTableExists()) {
                Log::warning("Table admin_settings indisponible, impossible de mettre à jour {$category}.{$key}");
                return false;
            }

            // Valider la valeur
            if (!$this->validateSettingValue($category, $key, $value)) {
                throw new \InvalidArgumentException("Valeur invalide pour {$category}.{$key}");
            }

            // Mettre à jour en base de données
            AdminSetting::updateOrCreate(
                [
                    'category' => $category,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );

            // Nettoyer le cache
            $this->clearCache($category);

            Log::info("Paramètre admin mis à jour: {$category}.{$key} = {$value}");
            return true;

        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour du paramètre {$category}.{$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour plusieurs paramètres
     * 
     * @param array $settings
     * @return bool
     */
    public function updateMultipleSettings(array $settings): bool
    {
        DB::beginTransaction();
        
        try {
            foreach ($settings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $value) {
                    $this->updateSetting($category, $key, $value);
                }
            }
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la mise à jour multiple des paramètres: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valide une valeur de paramètre
     * 
     * @param string $category
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    protected function validateSettingValue(string $category, string $key, $value): bool
    {
        $config = config("admin_settings.{$category}.{$key}");
        
        if (!$config) {
            return false;
        }

        $type = $config['type'] ?? 'text';
        $required = $config['required'] ?? false;

        // Vérifier si requis
        if ($required && (is_null($value) || $value === '')) {
            return false;
        }

        // Validation selon le type
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'number':
                $min = $config['min'] ?? null;
                $max = $config['max'] ?? null;
                if (!is_numeric($value)) return false;
                if ($min !== null && $value < $min) return false;
                if ($max !== null && $value > $max) return false;
                return true;
            case 'boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', true, false]);
            case 'select':
                $options = $config['options'] ?? [];
                return array_key_exists($value, $options);
            default:
                return true;
        }
    }

    /**
     * Exporte toutes les configurations
     * 
     * @return array
     */
    public function exportConfigurations(): array
    {
        $configurations = $this->getAllAdminSettings();
        
        return [
            'export_date' => now()->toISOString(),
            'version' => '1.0',
            'configurations' => $configurations,
            'metadata' => [
                'total_categories' => count($this->configCategories),
                'total_settings' => $this->countTotalSettings($configurations),
            ]
        ];
    }

    /**
     * Importe des configurations
     * 
     * @param array $configurations
     * @param bool $overwrite
     * @return bool
     */
    public function importConfigurations(array $configurations, bool $overwrite = false): bool
    {
        if (!$this->adminSettingsTableExists()) {
            Log::warning('Table admin_settings indisponible, import annulé');
            return false;
        }

        DB::beginTransaction();
        
        try {
            if (!isset($configurations['configurations'])) {
                throw new \InvalidArgumentException('Format de configuration invalide');
            }

            $imported = 0;
            $skipped = 0;

            foreach ($configurations['configurations'] as $category => $settings) {
                foreach ($settings as $key => $config) {
                    if (!isset($config['value'])) {
                        $skipped++;
                        continue;
                    }

                    // Vérifier si le paramètre existe déjà
                    $existing = AdminSetting::where('category', $category)
                        ->where('key', $key)
                        ->first();

                    if ($existing && !$overwrite) {
                        $skipped++;
                        continue;
                    }

                    if ($this->updateSetting($category, $key, $config['value'])) {
                        $imported++;
                    } else {
                        $skipped++;
                    }
                }
            }

            DB::commit();
            
            Log::info("Import terminé: {$imported} paramètres importés, {$skipped} ignorés");
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de l'import des configurations: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Réinitialise les paramètres par défaut
     * 
     * @param string|null $category
     * @return bool
     */
    public function resetToDefaults(string $category = null): bool
    {
        try {
            if (!$this->adminSettingsTableExists()) {
                Log::warning('Table admin_settings indisponible, réinitialisation ignorée');
                return false;
            }

            if ($category) {
                // Réinitialiser une catégorie spécifique
                AdminSetting::where('category', $category)->delete();
                $this->clearCache($category);
            } else {
                // Réinitialiser toutes les catégories
                AdminSetting::whereIn('category', $this->configCategories)->delete();
                $this->clearAllCache();
            }

            Log::info("Paramètres réinitialisés: " . ($category ?: 'toutes les catégories'));
            return true;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la réinitialisation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compte le nombre total de paramètres
     * 
     * @param array $configurations
     * @return int
     */
    protected function countTotalSettings(array $configurations): int
    {
        $total = 0;
        foreach ($configurations as $category => $settings) {
            $total += count($settings);
        }
        return $total;
    }

    /**
     * Nettoie le cache pour une catégorie
     * 
     * @param string $category
     */
    protected function clearCache(string $category): void
    {
        Cache::forget("admin_settings_{$category}");
        Cache::forget('admin_settings_all');
    }

    /**
     * Nettoie tout le cache
     */
    protected function clearAllCache(): void
    {
        foreach ($this->configCategories as $category) {
            Cache::forget("admin_settings_{$category}");
        }
        Cache::forget('admin_settings_all');
    }

    /**
     * Obtient les statistiques des configurations
     * 
     * @return array
     */
    public function getConfigurationStats(): array
    {
        $stats = [];
        $tableExists = $this->adminSettingsTableExists();
        
        foreach ($this->configCategories as $category) {
            $total = count(config("admin_settings.{$category}", []));
            $configured = $tableExists
                ? AdminSetting::where('category', $category)
                    ->where('is_active', true)
                    ->count()
                : 0;
            
            $stats[$category] = [
                'total' => $total,
                'configured' => $configured,
                'percentage' => $total > 0 ? round(($configured / $total) * 100, 2) : 0
            ];
        }
        
        return $stats;
    }

    /**
     * Vérifie si la table des paramètres admin est disponible.
     */
    protected function adminSettingsTableExists(): bool
    {
        try {
            return Schema::hasTable((new AdminSetting())->getTable());
        } catch (\Throwable $e) {
            Log::warning('Impossible de vérifier la table admin_settings', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
