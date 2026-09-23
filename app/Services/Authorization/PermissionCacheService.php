<?php

namespace App\Services\Authorization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\User;

class PermissionCacheService
{
    protected const CACHE_PREFIX = 'permissions';
    protected const DEFAULT_TTL = 900; // 15 minutes
    protected const USER_PERMISSIONS_TTL = 600; // 10 minutes
    protected const ROLE_PERMISSIONS_TTL = 1800; // 30 minutes

    /**
     * Get user permissions with intelligent caching
     */
    public function getUserPermissions(User $user): array
    {
        $cacheKey = $this->getUserPermissionsKey($user->id);
        
        return Cache::remember($cacheKey, now()->addSeconds(self::USER_PERMISSIONS_TTL), function () use ($user) {
            Log::debug("Cache miss for user permissions", ['user_id' => $user->id]);
            
            $permissions = [];
            
            // Get direct permissions
            $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
            $permissions = array_merge($permissions, $directPermissions);
            
            // Get role permissions
            foreach ($user->getRoles() as $role) {
                $rolePermissions = $this->getRolePermissions($role->name);
                $permissions = array_merge($permissions, $rolePermissions);
            }
            
            return array_unique($permissions);
        });
    }

    /**
     * Get role permissions with caching
     */
    public function getRolePermissions(string $roleName): array
    {
        $cacheKey = $this->getRolePermissionsKey($roleName);
        
        return Cache::remember($cacheKey, now()->addSeconds(self::ROLE_PERMISSIONS_TTL), function () use ($roleName) {
            Log::debug("Cache miss for role permissions", ['role' => $roleName]);
            
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
            
            if (!$role) {
                return [];
            }
            
            return $role->permissions->pluck('name')->toArray();
        });
    }

    /**
     * Check if user has specific permission with caching
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        $userPermissions = $this->getUserPermissions($user);
        
        // Check for wildcard permission
        if (in_array('*', $userPermissions)) {
            return true;
        }
        
        return in_array($permission, $userPermissions);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function userHasAnyPermission(User $user, array $permissions): bool
    {
        $userPermissions = $this->getUserPermissions($user);
        
        // Check for wildcard permission
        if (in_array('*', $userPermissions)) {
            return true;
        }
        
        return !empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * Check if user has all of the given permissions
     */
    public function userHasAllPermissions(User $user, array $permissions): bool
    {
        $userPermissions = $this->getUserPermissions($user);
        
        // Check for wildcard permission
        if (in_array('*', $userPermissions)) {
            return true;
        }
        
        return empty(array_diff($permissions, $userPermissions));
    }

    /**
     * Invalidate user permission cache
     */
    public function invalidateUserPermissions(int $userId): void
    {
        $cacheKey = $this->getUserPermissionsKey($userId);
        Cache::forget($cacheKey);
        
        // Also clear related caches
        $this->clearUserRelatedCaches($userId);
        
        Log::info("User permission cache invalidated", ['user_id' => $userId]);
    }

    /**
     * Invalidate role permission cache
     */
    public function invalidateRolePermissions(string $roleName): void
    {
        $cacheKey = $this->getRolePermissionsKey($roleName);
        Cache::forget($cacheKey);
        
        // Clear all user caches that might be affected by this role
        $this->clearRoleRelatedCaches($roleName);
        
        Log::info("Role permission cache invalidated", ['role' => $roleName]);
    }

    /**
     * Invalidate all permission caches
     */
    public function invalidateAllPermissions(): void
    {
        try {
            // Get all cache keys with our prefix
            $pattern = self::CACHE_PREFIX . ':*';
            
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Redis::keys($pattern);
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            } else {
                // For other cache drivers, we need to flush everything
                Cache::flush();
            }
            
            Log::info("All permission caches invalidated");
            
        } catch (\Exception $e) {
            Log::error("Failed to invalidate all permission caches", [
                'error' => $e->getMessage()
            ]);
            
            // Fallback to flush all cache
            Cache::flush();
        }
    }

    /**
     * Warm up permission caches for active users
     */
    public function warmUpPermissionCaches(int $limit = 100): void
    {
        try {
            $activeUsers = User::where('last_activity_at', '>=', now()->subDays(7))
                ->limit($limit)
                ->get();
            
            foreach ($activeUsers as $user) {
                $this->getUserPermissions($user);
            }
            
            Log::info("Permission caches warmed up", [
                'users_processed' => $activeUsers->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to warm up permission caches", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        try {
            $stats = [
                'total_keys' => 0,
                'memory_usage' => 0,
                'hit_rate' => 0,
                'keys_by_type' => []
            ];
            
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $info = Redis::info();
                $stats['memory_usage'] = $info['used_memory_human'] ?? 'N/A';
                
                // Count keys by pattern
                $patterns = [
                    'user_permissions' => self::CACHE_PREFIX . ':user:*',
                    'role_permissions' => self::CACHE_PREFIX . ':role:*',
                    'permission_checks' => self::CACHE_PREFIX . ':check:*'
                ];
                
                foreach ($patterns as $type => $pattern) {
                    $keys = Redis::keys($pattern);
                    $stats['keys_by_type'][$type] = count($keys);
                    $stats['total_keys'] += count($keys);
                }
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            Log::error("Failed to get cache statistics", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Unable to retrieve cache statistics',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpiredEntries(): int
    {
        try {
            $cleared = 0;
            
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $pattern = self::CACHE_PREFIX . ':*';
                $keys = Redis::keys($pattern);
                
                foreach ($keys as $key) {
                    $ttl = Redis::ttl($key);
                    if ($ttl === -1) {
                        // Key has no expiration, skip
                        continue;
                    }
                    
                    if ($ttl === -2) {
                        // Key has expired
                        Redis::del($key);
                        $cleared++;
                    }
                }
            }
            
            Log::info("Expired cache entries cleared", ['count' => $cleared]);
            return $cleared;
            
        } catch (\Exception $e) {
            Log::error("Failed to clear expired cache entries", [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Generate cache key for user permissions
     */
    private function getUserPermissionsKey(int $userId): string
    {
        return self::CACHE_PREFIX . ':user:' . $userId;
    }

    /**
     * Generate cache key for role permissions
     */
    private function getRolePermissionsKey(string $roleName): string
    {
        return self::CACHE_PREFIX . ':role:' . $roleName;
    }

    /**
     * Clear caches related to a specific user
     */
    private function clearUserRelatedCaches(int $userId): void
    {
        $patterns = [
            self::CACHE_PREFIX . ':user:' . $userId,
            self::CACHE_PREFIX . ':check:user:' . $userId . ':*'
        ];
        
        foreach ($patterns as $pattern) {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Redis::keys($pattern);
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            }
        }
    }

    /**
     * Clear caches related to a specific role
     */
    private function clearRoleRelatedCaches(string $roleName): void
    {
        $patterns = [
            self::CACHE_PREFIX . ':role:' . $roleName,
            self::CACHE_PREFIX . ':user:*' // All user caches might be affected
        ];
        
        foreach ($patterns as $pattern) {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Redis::keys($pattern);
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            }
        }
    }
}
