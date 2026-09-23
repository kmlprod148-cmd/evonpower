<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Service de gestion du thème (Dark/Light Mode)
 */
class ThemeService
{
    /**
     * Clé de cache pour le thème système par défaut
     */
    const CACHE_KEY_SYSTEM_THEME = 'theme:system:default';

    /**
     * Obtenir le thème effectif pour un utilisateur
     * 
     * @param User|null $user
     * @return string 'dark' ou 'light'
     */
    public function getEffectiveTheme(?User $user = null): string
    {
        $theme = $this->getThemePreference($user);
        
        if ($theme === 'system') {
            return $this->getSystemTheme();
        }
        
        return $theme;
    }

    /**
     * Obtenir la préférence de thème de l'utilisateur
     */
    public function getThemePreference(?User $user = null): string
    {
        if (!$user) {
            return $this->getSystemTheme();
        }

        // Vérifier le cache utilisateur
        $cacheKey = "theme:user:{$user->id}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($user) {
            return $user->theme ?? 'system';
        });
    }

    /**
     * Obtenir le thème système par défaut
     */
    public function getSystemTheme(): string
    {
        return Cache::remember(self::CACHE_KEY_SYSTEM_THEME, now()->addDays(7), function () {
            // Détecter le thème système à partir de la requête
            if (request()->hasHeader('sec-ch-prefers-color-scheme')) {
                return request()->header('sec-ch-prefers-color-scheme') === 'dark' ? 'dark' : 'light';
            }
            
            // Valeur par défaut
            return 'light';
        });
    }

    /**
     * Mettre à jour le thème d'un utilisateur
     */
    public function setTheme(User $user, string $theme): bool
    {
        // Valider le thème
        if (!in_array($theme, ['light', 'dark', 'system'])) {
            return false;
        }

        // Mettre à jour l'utilisateur
        $user->update(['theme' => $theme]);

        // Effacer le cache
        Cache::forget("theme:user:{$user->id}");

        return true;
    }

    /**
     * Basculer le thème pour un utilisateur
     */
    public function toggleTheme(User $user): string
    {
        $currentTheme = $this->getEffectiveTheme($user);
        $newTheme = $currentTheme === 'dark' ? 'light' : 'dark';

        $this->setTheme($user, $newTheme);

        return $newTheme;
    }

    /**
     * Obtenir la classe CSS pour le thème
     */
    public function getThemeClass(?User $user = null): string
    {
        $theme = $this->getEffectiveTheme($user);
        
        return $theme === 'dark' ? 'dark' : '';
    }

    /**
     * Vérifier si le thème sombre est actif
     */
    public function isDarkMode(?User $user = null): bool
    {
        return $this->getEffectiveTheme($user) === 'dark';
    }

    /**
     * Préparer les données du thème pour les vues
     */
    public function prepareThemeData(?User $user = null): array
    {
        return [
            'theme' => $this->getEffectiveTheme($user),
            'theme_preference' => $this->getThemePreference($user),
            'theme_class' => $this->getThemeClass($user),
            'is_dark' => $this->isDarkMode($user),
        ];
    }

    /**
     * Mettre à jour le thème système (pour les tests ou admin)
     */
    public function setSystemTheme(string $theme): void
    {
        if (in_array($theme, ['dark', 'light'])) {
            Cache::put(self::CACHE_KEY_SYSTEM_THEME, $theme, now()->addDays(7));
        }
    }

    /**
     * Obtenir les options de thème disponibles
     */
    public function getThemeOptions(): array
    {
        return [
            [
                'value' => 'light',
                'label' => 'Clair',
                'icon' => 'sun',
                'description' => 'Mode clair pour la journée',
            ],
            [
                'value' => 'dark',
                'label' => 'Sombre',
                'icon' => 'moon',
                'description' => 'Mode sombre pour réduire la fatigue visuelle',
            ],
            [
                'value' => 'system',
                'label' => 'Système',
                'icon' => 'computer',
                'description' => 'Suivre les paramètres du système',
            ],
        ];
    }
}
