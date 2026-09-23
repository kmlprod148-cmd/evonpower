<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class UserRoleHelper
{
    /**
     * Get role-based styling configuration for a user
     * 
     * @param \App\Models\User|null $user
     * @return array
     */
    public static function getRoleStyles($user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return self::getDefaultStyles();
        }
        
        // If user doesn't support roles (e.g. ClientUser) return default/client styles
        if (!method_exists($user, 'hasRole')) {
            return array_merge(self::getDefaultStyles(), [
                'role_name' => 'Client',
                'role_key' => 'client'
            ]);
        }

        // Admin & Super Admin - Red Theme
        if ($user->hasRole(['admin', 'super_admin'])) {
            return [
                'gradient' => 'from-red-50 via-red-100 to-red-200 dark:from-red-950/60 dark:via-red-900/50 dark:to-red-800/40',
                'bg' => 'bg-red-100 dark:bg-red-900/30',
                'bg_hover' => 'hover:bg-red-50 dark:hover:bg-red-900/40',
                'text' => 'text-red-700 dark:text-red-300',
                'border' => 'ring-red-400 dark:ring-red-500',
                'icon_bg' => 'bg-red-100 dark:bg-red-900/40',
                'badge' => 'bg-gradient-to-r from-red-500 to-red-600',
                'role_name' => 'Administrateur',
                'role_emoji' => '👑',
                'role_key' => 'admin'
            ];
        }

        // Integrator - Blue Theme
        if ($user->hasRole('integrator')) {
            return [
                'gradient' => 'from-blue-50 via-blue-100 to-blue-200 dark:from-blue-950/60 dark:via-blue-900/50 dark:to-blue-800/40',
                'bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'bg_hover' => 'hover:bg-blue-50 dark:hover:bg-blue-900/40',
                'text' => 'text-blue-700 dark:text-blue-300',
                'border' => 'ring-blue-400 dark:ring-blue-500',
                'icon_bg' => 'bg-blue-100 dark:bg-blue-900/40',
                'badge' => 'bg-gradient-to-r from-blue-500 to-blue-600',
                'role_name' => 'Intégrateur',
                'role_emoji' => '🔧',
                'role_key' => 'integrator'
            ];
        }

        // Operator & Partner - Green Theme
        if ($user->hasRole(['operator', 'partner'])) {
            return [
                'gradient' => 'from-green-50 via-green-100 to-green-200 dark:from-green-950/60 dark:via-green-900/50 dark:to-green-800/40',
                'bg' => 'bg-green-100 dark:bg-green-900/30',
                'bg_hover' => 'hover:bg-green-50 dark:hover:bg-green-900/40',
                'text' => 'text-green-700 dark:text-green-300',
                'border' => 'ring-green-400 dark:ring-green-500',
                'icon_bg' => 'bg-green-100 dark:bg-green-900/40',
                'badge' => 'bg-gradient-to-r from-green-500 to-green-600',
                'role_name' => 'Partenaire',
                'role_emoji' => '🤝',
                'role_key' => 'partner'
            ];
        }

        // Default User - Gray Theme
        return self::getDefaultStyles();
    }

    /**
     * Get default styling for regular users
     * 
     * @return array
     */
    protected static function getDefaultStyles(): array
    {
        return [
            'gradient' => 'from-gray-50 via-gray-100 to-gray-200 dark:from-gray-900/60 dark:via-gray-800/50 dark:to-gray-700/40',
            'bg' => 'bg-gray-100 dark:bg-gray-800/50',
            'bg_hover' => 'hover:bg-gray-50 dark:hover:bg-gray-800/60',
            'text' => 'text-gray-700 dark:text-gray-300',
            'border' => 'ring-gray-300 dark:ring-gray-600',
            'icon_bg' => 'bg-gray-100 dark:bg-gray-800',
            'badge' => 'bg-gradient-to-r from-gray-500 to-gray-600',
            'role_name' => 'Utilisateur',
            'role_emoji' => '👤',
            'role_key' => 'user'
        ];
    }

    /**
     * Get simplified role info
     * 
     * @param \App\Models\User|null $user
     * @return array
     */
    public static function getRoleInfo($user = null): array
    {
        $styles = self::getRoleStyles($user);
        
        return [
            'name' => $styles['role_name'],
            'emoji' => $styles['role_emoji'],
            'key' => $styles['role_key']
        ];
    }
}

