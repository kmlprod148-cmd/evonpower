<?php

namespace App\Services\Authorization;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class PermissionService
{
    // Define role hierarchy
    protected const ROLE_HIERARCHY = [
        'super_admin' => 5, // Highest level
        'admin' => 4,
        'integrator' => 3,
        'partner' => 2,
        'user' => 1,
    ];

    /**
     * Check if the authenticated user has a specific role or a higher role in the hierarchy.
     *
     * @param string $role The role to check against.
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        $user = Auth::user();

        if (!$user) {
            Log::debug('PermissionService@hasRole: No user authenticated, returning false.');
            return false;
        }

        // Normalize the requested role to lowercase for consistent comparison
        $requestedRoleLower = strtolower($role);
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();

        // Admin and Super Admin always have the highest privilege, check using normalized roles
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            Log::debug("PermissionService@hasRole: User {$user->id} is admin/super-admin (normalized check), granting role check for '{$role}'.");
            return true;
        }

        // Check if the user has the exact role (using normalized roles)
        if (in_array($requestedRoleLower, $userRolesLower)) {
            Log::debug("PermissionService@hasRole: User {$user->id} has exact role '{$role}' (normalized).");
            return true;
        }

        // Check role hierarchy (using normalized roles)
        $requiredLevel = self::ROLE_HIERARCHY[$requestedRoleLower] ?? 0;
        foreach ($userRolesLower as $userRoleLower) {
            $userLevel = self::ROLE_HIERARCHY[$userRoleLower] ?? 0;
            if ($userLevel >= $requiredLevel) {
                Log::debug("PermissionService@hasRole: User {$user->id} has role '{$userRoleLower}' (level {$userLevel}), which is sufficient for '{$role}' (level {$requiredLevel}).");
                return true;
            }
        }

        Log::debug("PermissionService@hasRole: User {$user->id} does not have role '{$role}' or a higher role.");
        return false;
    }

    /**
     * Check if the authenticated user has a specific permission.
     * Includes caching for performance.
     *
     * @param string $permission The permission to check.
     * @param mixed $model Optional: The model instance for policy checks.
     * @return bool
     */
    public function hasPermission(string $permission, $model = null): bool
    {
        $user = Auth::user();

        if (!$user) {
            Log::debug('PermissionService@hasPermission: No user authenticated, returning false.');
            return false;
        }

        // Admin and Super Admin bypass all permission checks (using normalized roles)
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            Log::debug("PermissionService@hasPermission: User {$user->id} is admin/super-admin (normalized bypass), granting permission '{$permission}'.");
            return true;
        }

        $cacheKey = 'user_permissions:' . $user->id . ':' . md5($permission . serialize($model));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $permission, $model) {
            Log::debug("PermissionService@hasPermission: Checking permission '{$permission}' for user {$user->id} (cache miss).");

            // Check if the permission is a Gate policy check (e.g., 'viewAny,App\Models\BusinessProfile')
            if (str_contains($permission, ',')) {
                [$action, $modelClass] = explode(',', $permission);
                if (Gate::allows($action, $modelClass)) {
                    Log::info("PermissionService@hasPermission: User {$user->id} allowed by policy: {$permission}.");
                    return true;
                }
            } else {
                // Assume it's a simple permission string
                if ($user->can($permission, $model)) {
                    Log::info("PermissionService@hasPermission: User {$user->id} allowed by permission: {$permission}.");
                    return true;
                }
            }

            Log::warning("PermissionService@hasPermission: User {$user->id} denied permission: {$permission}.");
            return false;
        });
    }

    /**
     * Check if the authenticated user has any of the given permissions.
     *
     * @param array $permissions An array of permissions to check.
     * @param mixed $model Optional: The model instance for policy checks.
     * @return bool
     */
    public function hasAnyPermission(array $permissions, $model = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission, $model)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the authenticated user has all of the given permissions.
     *
     * @param array $permissions An array of permissions to check.
     * @param mixed $model Optional: The model instance for policy checks.
     * @return bool
     */
    public function hasAllPermissions(array $permissions, $model = null): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission, $model)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Invalidate permission cache for a specific user.
     *
     * @param int $userId
     * @return void
     */
    public function invalidateUserPermissionsCache(int $userId): void
    {
        try {
            // Essayer de vider les clés spécifiques à l'utilisateur
            $userCacheKeys = [
                'user_permissions:' . $userId,
                'user_roles:' . $userId,
                'user_permissions:' . $userId . ':*',
                'spatie.permission.cache.user.' . $userId
            ];
            
            foreach ($userCacheKeys as $key) {
                Cache::forget($key);
            }
            
            Log::info("PermissionService: Invalidated permission cache for user ID: {$userId}.");
        } catch (\Exception $e) {
            Log::warning("PermissionService: Could not invalidate user permission cache for user {$userId}. Error: " . $e->getMessage());
        }
    }

    /**
     * Invalidate all permission caches.
     * Use with caution as it affects all users.
     *
     * @return void
     */
    public function invalidateAllPermissionsCache(): void
    {
        try {
            // Méthode 1: Essayer de vider tout le cache (compatible avec tous les drivers)
            Cache::flush();
            Log::info("PermissionService: Invalidated all caches (full flush).");
        } catch (\Exception $e) {
            Log::warning("PermissionService: Could not flush all cache, trying alternative method. Error: " . $e->getMessage());
            
            // Méthode 2: Alternative pour les caches qui ne supportent pas flush()
            try {
                // Vider seulement les clés connues liées aux permissions
                $cacheKeys = [
                    'spatie.permission.cache',
                    'permissions',
                    'roles',
                    'role_permissions',
                    'user_permissions'
                ];
                
                foreach ($cacheKeys as $key) {
                    Cache::forget($key);
                }
                
                Log::info("PermissionService: Invalidated permission-related cache keys.");
            } catch (\Exception $e2) {
                Log::error("PermissionService: Failed to invalidate permission caches. Error: " . $e2->getMessage());
            }
        }
    }
}